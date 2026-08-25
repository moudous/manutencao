<?php

namespace App\Http\Controllers;

use App\Models\AgendaPreventiva;
use App\Models\Lancamento;
use App\Models\Orcamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OrcamentoController extends Controller
{
    public function data(AgendaPreventiva $agenda, Lancamento $lancamento): JsonResponse
    {
        $this->ensureLancamento($agenda, $lancamento);
        $rows = $lancamento->orcamentos()->latest('id')->get()->map(fn (Orcamento $orcamento) => [
            'descricao' => e($orcamento->descricao ?: '—'),
            'centro_custo' => e($orcamento->centro_custo ?: '—'),
            'foto' => $orcamento->link
                ? '<a href="'.e($this->imageUrl($orcamento->link)).'" target="_blank" rel="noopener"><img src="'.e($this->imageUrl($orcamento->link)).'" alt="Foto da requisição" class="img-thumbnail" style="width:72px;height:54px;object-fit:cover"></a>'
                : '—',
            'acoes' => '<button type="button" class="btn btn-sm btn-outline-danger excluir-orcamento" data-url="'.e(route('agenda.lancamentos.orcamentos.destroy', [$agenda, $lancamento, $orcamento], false)).'" title="Excluir"><i class="bi bi-trash"></i></button>',
        ]);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request, AgendaPreventiva $agenda, Lancamento $lancamento): JsonResponse
    {
        $this->ensureLancamento($agenda, $lancamento);
        abort_if($lancamento->data_arquivamento !== null, 422, 'Não é possível alterar um lançamento arquivado.');
        $data = $request->validate([
            'descricao' => ['required', 'string'],
            'centro_custo' => ['required', 'string', 'max:50'],
            'tipo_imagem' => ['required', 'in:foto,link'],
            'link' => ['nullable', 'required_if:tipo_imagem,link', 'url', 'max:300'],
            'foto' => ['nullable', 'required_if:tipo_imagem,foto', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('foto')) {
            $directory = public_path('uploads/orcamentos');
            File::ensureDirectoryExists($directory);
            $file = $request->file('foto');
            $name = Str::uuid().'.'.$file->extension();
            $file->move($directory, $name);
            $data['link'] = 'uploads/orcamentos/'.$name;
        }

        $lancamento->orcamentos()->create($data);

        return response()->json(['message' => 'Requisição de orçamento cadastrada com sucesso.']);
    }

    public function destroy(AgendaPreventiva $agenda, Lancamento $lancamento, Orcamento $orcamento): JsonResponse
    {
        $this->ensureLancamento($agenda, $lancamento);
        abort_if($lancamento->data_arquivamento !== null, 422, 'Não é possível alterar um lançamento arquivado.');
        abort_unless((int) $orcamento->lancamentos_id === (int) $lancamento->id, 404);
        $orcamento->delete();

        return response()->json(['message' => 'Requisição excluída com sucesso.']);
    }

    private function ensureLancamento(AgendaPreventiva $agenda, Lancamento $lancamento): void
    {
        abort_unless((int) $lancamento->agenda_id === (int) $agenda->id, 404);
    }

    private function imageUrl(string $link): string
    {
        return filter_var($link, FILTER_VALIDATE_URL) ? $link : asset($link);
    }
}
