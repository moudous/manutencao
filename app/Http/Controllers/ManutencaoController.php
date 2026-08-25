<?php

namespace App\Http\Controllers;

use App\Models\AgendaPreventiva;
use App\Models\Compra;
use App\Models\Despesa;
use App\Models\Lancamento;
use App\Models\SituacaoLancamento;
use App\Services\GiPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManutencaoController extends Controller
{
    public function index(Request $request): View
    {
        $filtro = in_array($request->query('filtro'), ['hoje', '7dias', '30dias', 'todas'], true)
            ? $request->query('filtro') : 'hoje';
        $usuarioId = (int) $request->session()->get('gi_context.usuario.id');
        $query = Lancamento::query()
            ->where('agenda_id', '>', 0)
            ->whereNotNull('data_agendamento')
            ->with(['agenda', 'equipamento.local.unidade', 'local.unidade', 'tecnico', 'situacao', 'despesas.compra'])
            ->withSum('despesas as total_despesas', DB::raw('quantidade * custo'))
            ->orderByRaw(
                'CASE WHEN data_inicio IS NOT NULL AND data_arquivamento IS NULL AND tecnicos_id = ? THEN 0 '
                .'WHEN data_inicio IS NOT NULL AND data_arquivamento IS NULL THEN 1 '
                .'WHEN data_arquivamento IS NULL THEN 2 ELSE 3 END',
                [$usuarioId],
            )
            ->orderByRaw('CASE WHEN data_arquivamento IS NULL THEN data_agendamento END ASC')
            ->orderByRaw('CASE WHEN data_arquivamento IS NOT NULL THEN data_arquivamento END DESC');

        $hoje = today();
        if ($filtro === 'hoje') $query->whereDate('data_agendamento', $hoje);
        if ($filtro === '7dias') $query->whereBetween('data_agendamento', [$hoje, $hoje->copy()->addDays(7)->endOfDay()]);
        if ($filtro === '30dias') $query->whereBetween('data_agendamento', [$hoje, $hoje->copy()->addDays(30)->endOfDay()]);

        $lancamentos = in_array($filtro, ['30dias', 'todas'], true)
            ? $query->paginate(20)->withQueryString()
            : $query->get();

        $permissoes = app(GiPermissionService::class);
        $podeEditar = $permissoes->permite('manutencao.editar', $request);
        $podeVisualizar = $podeEditar || $permissoes->permite('manutencao.visualizar', $request);
        $ativa = Lancamento::query()
            ->where('tecnicos_id', $usuarioId)
            ->whereNotNull('data_inicio')
            ->whereNull('data_arquivamento')
            ->oldest('data_inicio')->first();
        if ($ativa && ! $lancamentos->contains('id', $ativa->id)) {
            $ativa->load(['agenda', 'equipamento.local.unidade', 'local.unidade', 'tecnico', 'situacao', 'despesas.compra']);
            $ativa->setAttribute('total_despesas', $ativa->despesas->sum(fn($despesa)=>(float)$despesa->quantidade*(float)$despesa->custo));
            if (method_exists($lancamentos, 'getCollection')) $lancamentos->getCollection()->prepend($ativa);
            else $lancamentos->prepend($ativa);
        }
        $abrirId = $request->has('filtro')
            ? null
            : ($ativa?->id ?: ($request->integer('abrir') ?: null));

        $compras = $podeEditar ? Compra::query()
            ->where('disponivel', true)
            ->whereRaw('(COALESCE(quantidade, 0) * COALESCE(quantidade_unitaria, 0)) - COALESCE(qtde_utilizada, 0) > 0')
            ->orderBy('titulo')->get() : collect();
        $situacoes = SituacaoLancamento::query()->where('ativo', true)->orderBy('titulo')->get(['id', 'titulo']);

        return view('manutencao.index', compact('filtro', 'lancamentos', 'podeEditar', 'podeVisualizar', 'abrirId', 'compras', 'situacoes'));
    }

    public function iniciar(Request $request, Lancamento $lancamento): JsonResponse
    {
        $this->ensurePreventiva($lancamento);
        abort_if($lancamento->data_arquivamento, 422, 'Esta manutenção já foi arquivada.');
        abort_if($lancamento->data_inicio, 422, 'Esta manutenção já foi iniciada.');
        $usuarioId = (int) $request->session()->get('gi_context.usuario.id');
        abort_if(Lancamento::query()->where('tecnicos_id', $usuarioId)->whereNotNull('data_inicio')->whereNull('data_arquivamento')->exists(), 422, 'Conclua sua manutenção ativa antes de iniciar outra.');

        $lancamento->update(['data_inicio'=>now(), 'tecnicos_id'=>$usuarioId, 'etapa'=>max(2, (int)$lancamento->etapa)]);
        return response()->json(['message'=>'Manutenção iniciada.', 'redirect'=>route('manutencao.index', ['filtro'=>$request->input('filtro', 'hoje'), 'abrir'=>$lancamento->id])]);
    }

    public function visualizarDespesas(Lancamento $lancamento): JsonResponse
    {
        $this->ensurePreventiva($lancamento);
        abort_unless($lancamento->data_inicio, 422, 'Inicie a manutenção antes de acessar as despesas.');
        if (! $lancamento->data_arquivamento && (int)$lancamento->etapa < 3) $lancamento->update(['etapa'=>3]);
        return response()->json(['message'=>'Etapa de despesas acessada.', 'conclusao_disponivel'=>true]);
    }

    public function adicionarDespesa(Request $request, Lancamento $lancamento): JsonResponse
    {
        $this->ensureEditable($lancamento);
        abort_unless((int)$lancamento->etapa >= 3, 422, 'Acesse a etapa de despesas antes de adicionar produtos.');
        $data = $request->validate(['compra_id'=>['required','integer','exists:manut_compras,id'], 'quantidade'=>['required','numeric','gt:0']]);
        $resultado = DB::transaction(function () use ($data, $lancamento): array {
            $compra = Compra::query()->lockForUpdate()->findOrFail($data['compra_id']);
            $quantidade = (float)$data['quantidade'];
            $estoque = max(0, ((float)$compra->quantidade * (float)$compra->quantidade_unitaria) - (float)$compra->qtde_utilizada);
            if (! $compra->disponivel || $quantidade > $estoque + 0.000001) throw ValidationException::withMessages(['quantidade'=>'A quantidade informada é maior que o estoque disponível.']);
            if ($compra->unidade === 'un' && floor($quantidade) !== $quantidade) throw ValidationException::withMessages(['quantidade'=>'Itens em unidade devem utilizar uma quantidade inteira.']);
            $despesa = $lancamento->despesas()->create(['compra_id'=>$compra->id, 'quantidade'=>$quantidade, 'custo'=>$compra->preco_unitario, 'unidade'=>$compra->unidade]);
            $compra->update(['qtde_utilizada'=>(float)$compra->qtde_utilizada + $quantidade]);
            return ['compra_id'=>$compra->id, 'estoque'=>$estoque-$quantidade, 'despesa'=>['id'=>$despesa->id, 'produto'=>$compra->titulo, 'quantidade'=>$quantidade, 'unidade'=>$compra->unidade, 'custo'=>(float)$despesa->custo, 'subtotal'=>$quantidade*(float)$despesa->custo]];
        });
        $total=(float)$lancamento->despesas()->selectRaw('COALESCE(SUM(quantidade * custo), 0) as total')->value('total');
        return response()->json(['message'=>'Despesa adicionada com sucesso.', 'total'=>$total] + $resultado);
    }

    public function excluirDespesa(Lancamento $lancamento, Despesa $despesa): JsonResponse
    {
        $this->ensureEditable($lancamento);
        abort_unless((int)$despesa->lancamentos_id === (int)$lancamento->id, 404);
        $resultado = DB::transaction(function () use ($despesa): array {
            $compra = Compra::withTrashed()->lockForUpdate()->find($despesa->compra_id);
            $estoque=null;
            if ($compra) {
                $compra->update(['qtde_utilizada'=>max(0, (float)$compra->qtde_utilizada - (float)$despesa->quantidade)]);
                $estoque=max(0,(float)$compra->quantidade*(float)$compra->quantidade_unitaria-(float)$compra->qtde_utilizada);
            }
            $despesa->delete();
            return ['compra_id'=>$despesa->compra_id, 'estoque'=>$estoque];
        });
        $total=(float)$lancamento->despesas()->selectRaw('COALESCE(SUM(quantidade * custo), 0) as total')->value('total');
        return response()->json(['message'=>'Despesa excluída com sucesso.', 'total'=>$total] + $resultado);
    }

    public function concluir(Request $request, Lancamento $lancamento): JsonResponse
    {
        $this->ensureEditable($lancamento);
        abort_unless((int)$lancamento->etapa >= 3, 422, 'Acesse a etapa de despesas antes de concluir.');
        $data = $request->validate([
            'situacao_id'=>['required','integer',Rule::exists('manut_situacao_lancamento','id')->where(fn($q)=>$q->where('ativo',true)->whereNull('apagado_em'))],
            'observacao'=>['nullable','string','max:512'],
        ]);
        DB::transaction(function () use ($lancamento, $data): void {
            $atual = Lancamento::query()->lockForUpdate()->findOrFail($lancamento->id);
            $agenda = AgendaPreventiva::query()->lockForUpdate()->findOrFail($atual->agenda_id);
            abort_if($atual->data_arquivamento, 422, 'Esta manutenção já foi arquivada.');
            $agora = now();
            $proxima = $agora->copy()->addDays(max(0, (int)$agenda->periodicidade));
            $orcamento = $proxima->copy()->subDays(max(0, (int)$agenda->orcamento));
            $atual->update(['situacao_id'=>$data['situacao_id'], 'observacao'=>$data['observacao']??null, 'etapa'=>4, 'data_arquivamento'=>$agora, 'ativo'=>false]);
            $agenda->update(['ultima_agenda'=>$agora, 'proxima_agenda'=>$proxima, 'proximo_orcamento'=>$orcamento]);
            if ($agenda->ativo) $agenda->lancamentos()->create(['ativos_id'=>$atual->ativos_id?:$agenda->ativos_id, 'locais_id'=>$atual->locais_id?:$agenda->locais_id, 'solicitante'=>'Sistema', 'data_lancamento'=>$agora, 'data_orcamento'=>$orcamento, 'data_agendamento'=>$proxima, 'tipos_id'=>$atual->tipos_id, 'etapa'=>1, 'ativo'=>true]);
        });
        return response()->json(['message'=>'Manutenção concluída e arquivada.', 'redirect'=>route('manutencao.index', ['filtro'=>$request->input('filtro','hoje')])]);
    }

    private function ensurePreventiva(Lancamento $lancamento): void { abort_unless((int)$lancamento->agenda_id > 0, 404); }
    private function ensureEditable(Lancamento $lancamento): void
    {
        $this->ensurePreventiva($lancamento);
        abort_if($lancamento->data_arquivamento, 422, 'Esta manutenção está arquivada e é somente leitura.');
        abort_unless($lancamento->data_inicio, 422, 'Esta manutenção ainda não foi iniciada.');
    }
}
