<?php

namespace App\Http\Controllers;

use App\Models\AgendaPreventiva;
use App\Models\Compra;
use App\Models\Despesa;
use App\Models\Lancamento;
use App\Services\GiPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DespesaController extends Controller
{
    public function data(AgendaPreventiva $agenda, Lancamento $lancamento): JsonResponse
    {
        $this->ensureLancamento($agenda, $lancamento);
        $rows = $lancamento->despesas()->with('compra')->latest('id')->get()->map(fn (Despesa $despesa) => [
            'produto' => e($despesa->compra?->titulo ?: 'Produto não encontrado'),
            'quantidade' => $this->number($despesa->quantidade),
            'unidade' => e($despesa->unidade ?: '—'),
            'custo' => $this->money($despesa->custo),
            'subtotal' => $this->money(($despesa->quantidade ?? 0) * ($despesa->custo ?? 0)),
            'subtotal_valor' => ($despesa->quantidade ?? 0) * ($despesa->custo ?? 0),
            'acoes' => '<button type="button" class="btn btn-sm btn-outline-danger excluir-despesa" data-url="'.e(route('agenda.lancamentos.despesas.destroy', [$agenda, $lancamento, $despesa], false)).'" title="Excluir"><i class="bi bi-trash"></i></button>',
        ]);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request, AgendaPreventiva $agenda, Lancamento $lancamento): JsonResponse
    {
        $this->ensureLancamento($agenda, $lancamento);
        abort_if($lancamento->data_arquivamento !== null, 422, 'Não é possível alterar um lançamento arquivado.');
        abort_if(! $lancamento->data_inicio && ! app(GiPermissionService::class)->permite('agenda.supervisionar', $request), 403, 'Inicie a manutenção antes de adicionar despesas.');
        $data = $request->validate(['compra_id'=>['required','integer','exists:manut_compras,id'], 'quantidade'=>['required','numeric','gt:0']]);

        $resultado = DB::transaction(function () use ($data, $lancamento): array {
            $compra = Compra::query()->lockForUpdate()->findOrFail($data['compra_id']);
            $quantidade = (float) $data['quantidade'];
            $estoque = max(0, ((float) $compra->quantidade * (float) $compra->quantidade_unitaria) - (float) $compra->qtde_utilizada);
            if (! $compra->disponivel || $quantidade > $estoque + 0.000001) {
                throw ValidationException::withMessages(['quantidade' => 'A quantidade informada é maior que o estoque disponível.']);
            }
            if ($compra->unidade === 'un' && floor($quantidade) !== $quantidade) {
                throw ValidationException::withMessages(['quantidade' => 'Itens em unidade devem utilizar uma quantidade inteira.']);
            }
            $lancamento->despesas()->create(['compra_id'=>$compra->id, 'quantidade'=>$quantidade, 'custo'=>$compra->preco_unitario, 'unidade'=>$compra->unidade]);
            $compra->update(['qtde_utilizada'=>(float) $compra->qtde_utilizada + $quantidade]);
            return ['compra_id'=>$compra->id, 'estoque'=>$estoque-$quantidade];
        });

        return response()->json(['message' => 'Despesa cadastrada com sucesso.'] + $resultado);
    }

    public function destroy(AgendaPreventiva $agenda, Lancamento $lancamento, Despesa $despesa): JsonResponse
    {
        $this->ensureLancamento($agenda, $lancamento);
        abort_if($lancamento->data_arquivamento !== null, 422, 'Não é possível alterar um lançamento arquivado.');
        abort_unless((int) $despesa->lancamentos_id === (int) $lancamento->id, 404);
        $resultado = DB::transaction(function () use ($despesa): array {
            $compra = Compra::withTrashed()->lockForUpdate()->find($despesa->compra_id);
            $estoque = null;
            if ($compra) {
                $compra->update(['qtde_utilizada'=>max(0, (float) $compra->qtde_utilizada - (float) $despesa->quantidade)]);
                $estoque = max(0, ((float) $compra->quantidade * (float) $compra->quantidade_unitaria) - (float) $compra->qtde_utilizada);
            }
            $despesa->delete();
            return ['compra_id'=>$despesa->compra_id, 'estoque'=>$estoque];
        });

        return response()->json(['message' => 'Despesa excluída e estoque devolvido com sucesso.'] + $resultado);
    }

    private function ensureLancamento(AgendaPreventiva $agenda, Lancamento $lancamento): void { abort_unless((int) $lancamento->agenda_id === (int) $agenda->id, 404); }
    private function money(?float $value): string { return $value === null ? '—' : 'R$ '.number_format($value, 2, ',', '.'); }
    private function number(?float $value): string { return $value === null ? '—' : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ','); }
}
