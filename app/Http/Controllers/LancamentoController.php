<?php

namespace App\Http\Controllers;

use App\Models\AgendaPreventiva;
use App\Models\Lancamento;
use App\Models\Pessoa;
use App\Models\Compra;
use App\Models\SituacaoLancamento;
use App\Services\GiPermissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LancamentoController extends Controller
{
    public function index(Request $request, AgendaPreventiva $agenda): View
    {
        $agenda->load('equipamento.local.unidade');
        $proximoLancamento = $agenda->lancamentos()
            ->whereNull('data_arquivamento')
            ->with(['equipamento.local.unidade', 'local.unidade', 'tecnico'])
            ->latest('id')
            ->first();

        $usuarioId = (int) $request->session()->get('gi_context.usuario.id');
        $supervisor = app(GiPermissionService::class)->permite('agenda.supervisor', $request);
        $tecnicos = $supervisor
            ? Pessoa::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome'])
            : Pessoa::query()->whereKey($usuarioId)->get(['id', 'nome']);
        $compras = Compra::query()
            ->where('disponivel', true)
            ->whereRaw('(COALESCE(quantidade, 0) * COALESCE(quantidade_unitaria, 0)) - COALESCE(qtde_utilizada, 0) > 0')
            ->orderBy('titulo')
            ->get();
        $situacoes = SituacaoLancamento::query()->where('ativo', true)->orderBy('titulo')->get(['id', 'titulo']);

        return view('lancamentos.index', compact('agenda', 'proximoLancamento', 'usuarioId', 'supervisor', 'tecnicos', 'compras', 'situacoes'));
    }
    public function data(Request $request,AgendaPreventiva $agenda): JsonResponse
    {
        $query=$agenda->lancamentos()->whereNotNull('data_arquivamento')->with(['equipamento','local']); $total=(clone $query)->count(); $search=trim((string)$request->input('search.value'));
        if($search!=='')$query->where(fn($q)=>$q->where('solicitante','like',"%{$search}%")->orWhere('problema','like',"%{$search}%")->orWhere('observacao','like',"%{$search}%"));
        $filtered=(clone $query)->count(); $columns=['id','data_lancamento','solicitante','problema','data_orcamento','data_agendamento','etapa','ativo']; $column=$columns[(int)$request->input('order.0.column',0)]??'id'; $direction=$request->input('order.0.dir')==='asc'?'asc':'desc'; $length=min(max((int)$request->input('length',10),1),100);
        $rows=$query->orderBy($column,$direction)->skip(max((int)$request->input('start',0),0))->take($length)->get()->map(fn(Lancamento $l)=>['id'=>$l->id,'data_lancamento'=>$l->data_lancamento?->format('d/m/Y H:i')??'—','solicitante'=>e($l->solicitante?:'—'),'problema'=>e($l->problema?:'—'),'data_orcamento'=>$l->data_orcamento?->format('d/m/Y')??'—','data_agendamento'=>$l->data_agendamento?->format('d/m/Y')??'—','etapa'=>$l->etapa??'—','status'=>'<span class="badge '.($l->ativo?'text-bg-success':'text-bg-secondary').'">'.($l->ativo?'Ativo':'Inativo').'</span>','acoes'=>view('lancamentos._actions',['agenda'=>$agenda,'lancamento'=>$l])->render()]);
        return response()->json(['draw'=>(int)$request->input('draw'),'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$rows]);
    }
    public function show(AgendaPreventiva $agenda,Lancamento $lancamento): View
    {
        abort_unless((int)$lancamento->agenda_id===(int)$agenda->id,404); $lancamento->load(['equipamento.local.unidade','local','tecnico']);
        return view('lancamentos.show',compact('agenda','lancamento'));
    }

    public function updateEtapaDois(Request $request, AgendaPreventiva $agenda, Lancamento $lancamento): JsonResponse
    {
        abort_unless((int) $lancamento->agenda_id === (int) $agenda->id, 404);
        abort_if($lancamento->data_arquivamento !== null, 422, 'Não é possível alterar um lançamento arquivado.');

        $supervisor = app(GiPermissionService::class)->permite('agenda.supervisor', $request);
        $data = $request->validate([
            'tecnicos_id' => ['nullable', 'integer', 'exists:manut_pessoas,id'],
            'data_inicio' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
        ]);
        $tecnicoId = $supervisor
            ? (int) ($data['tecnicos_id'] ?: $request->session()->get('gi_context.usuario.id'))
            : (int) $request->session()->get('gi_context.usuario.id');

        $lancamento->update([
            'tecnicos_id' => $tecnicoId,
            'data_inicio' => Carbon::createFromFormat('Y-m-d H:i', $data['data_inicio'].' '.$data['hora_inicio']),
            'etapa' => max(3, (int) $lancamento->etapa),
        ]);

        return response()->json(['message' => 'Etapa 2 salva com sucesso.', 'etapa' => 3]);
    }

    public function concluir(Request $request, AgendaPreventiva $agenda, Lancamento $lancamento): JsonResponse
    {
        abort_unless((int) $lancamento->agenda_id === (int) $agenda->id, 404);
        $data = $request->validate([
            'situacao_id' => ['required', 'integer', Rule::exists('manut_situacao_lancamento', 'id')->where(fn ($query) => $query->where('ativo', true)->whereNull('apagado_em'))],
            'observacao' => ['nullable', 'string', 'max:512'],
        ]);

        DB::transaction(function () use ($request, $agenda, $lancamento, $data): void {
            $agendaAtual = AgendaPreventiva::query()->lockForUpdate()->findOrFail($agenda->id);
            $atual = Lancamento::query()->lockForUpdate()->findOrFail($lancamento->id);
            abort_if($atual->data_arquivamento !== null, 422, 'Esta manutenção já foi arquivada.');

            $agora = now();
            $proximaAgenda = $agora->copy()->addDays(max(0, (int) $agendaAtual->periodicidade));
            $proximoOrcamento = $proximaAgenda->copy()->subDays(max(0, (int) $agendaAtual->orcamento));
            $atual->update(['situacao_id'=>$data['situacao_id'], 'observacao'=>$data['observacao'] ?? null, 'etapa'=>4, 'data_arquivamento'=>$agora, 'ativo'=>false]);
            $agendaAtual->update(['ultima_agenda'=>$agora, 'proxima_agenda'=>$proximaAgenda, 'proximo_orcamento'=>$proximoOrcamento]);
            $agendaAtual->lancamentos()->create([
                'ativos_id'=>$atual->ativos_id ?: $agendaAtual->ativos_id,
                'locais_id'=>$atual->locais_id ?: $agendaAtual->locais_id,
                'solicitante'=>'Sistema',
                'data_lancamento'=>$agora,
                'data_orcamento'=>$proximoOrcamento,
                'data_agendamento'=>$proximaAgenda,
                'tipos_id'=>$atual->tipos_id,
                'etapa'=>1,
                'ativo'=>true,
            ]);
        });

        $request->session()->flash('status', 'Manutenção Arquivada com Sucesso!');
        return response()->json(['message'=>'Manutenção Arquivada com Sucesso!', 'redirect'=>route('agenda.lancamentos.index', $agenda)]);
    }
}
