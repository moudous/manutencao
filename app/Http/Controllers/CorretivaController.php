<?php

namespace App\Http\Controllers;

use App\Models\Ativo;
use App\Models\Lancamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorretivaController extends Controller
{
    public function index(): View { return view('corretivas.index'); }

    public function data(Request $request): JsonResponse
    {
        $query = Lancamento::query()->where('tipos_id', 2)->with('equipamento.local.unidade');
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));
        if ($search !== '') $query->where(fn ($q) => $q->where('solicitante', 'like', "%{$search}%")->orWhere('observacao', 'like', "%{$search}%")->orWhereHas('equipamento', fn ($a) => $a->where('titulo', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%")));
        $filtered = (clone $query)->count();
        $columns = ['id', 'ativos_id', 'locais_id', 'solicitante', 'data_agendamento', 'observacao', 'ativo'];
        $column = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $rows = $query->orderBy($column, $direction)->skip(max((int) $request->input('start', 0), 0))->take($length)->get()->map(fn (Lancamento $corretiva) => [
            'id'=>$corretiva->id,
            'equipamento'=>e(($corretiva->equipamento?->titulo ?: '—').' ('.($corretiva->equipamento?->codigo ?: 'sem código').')'),
            'local'=>e(($corretiva->equipamento?->local?->titulo ?: '—').' - '.($corretiva->equipamento?->local?->unidade?->titulo ?: 'Unidade não informada')),
            'solicitante'=>e($corretiva->solicitante ?: '—'),
            'data_manutencao'=>$corretiva->data_agendamento?->format('d/m/Y') ?? '—',
            'descricao'=>e($corretiva->observacao ?: '—'),
            'status'=>'<span class="badge '.($corretiva->ativo ? 'text-bg-success' : 'text-bg-secondary').'">'.($corretiva->ativo ? 'Ativada' : 'Desativada').'</span>',
            'acoes'=>view('corretivas._actions', compact('corretiva'))->render(),
        ]);
        return response()->json(['draw'=>(int) $request->input('draw'), 'recordsTotal'=>$total, 'recordsFiltered'=>$filtered, 'data'=>$rows]);
    }

    public function create(Request $request): View
    {
        return $this->formView(new Lancamento(['solicitante'=>$request->session()->get('gi_context.usuario.nome'), 'data_agendamento'=>today(), 'ativo'=>true]), false);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $ativo = Ativo::withTrashed()->findOrFail($data['ativos_id']);
        Lancamento::create($data + ['agenda_id'=>0, 'locais_id'=>$ativo->locais_id, 'tipos_id'=>2, 'data_lancamento'=>now(), 'data_orcamento'=>null, 'data_inicio'=>null, 'etapa'=>1]);
        return redirect()->route('corretiva.index')->with('status', 'Manutenção corretiva cadastrada com sucesso.');
    }

    public function show(int $id): View
    {
        $corretiva = $this->find($id)->load('equipamento.local.unidade');
        return view('corretivas.show', compact('corretiva'));
    }

    public function edit(int $id): View { return $this->formView($this->find($id), true); }

    public function update(Request $request, int $id): RedirectResponse
    {
        $corretiva = $this->find($id);
        $data = $this->validated($request);
        $ativo = Ativo::withTrashed()->findOrFail($data['ativos_id']);
        $corretiva->update($data + ['agenda_id'=>0, 'locais_id'=>$ativo->locais_id, 'tipos_id'=>2]);
        return redirect()->route('corretiva.index')->with('status', 'Manutenção corretiva atualizada com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->find($id)->delete();
        return redirect()->route('corretiva.index')->with('status', 'Manutenção corretiva excluída com sucesso.');
    }

    private function find(int $id): Lancamento { return Lancamento::query()->where('tipos_id', 2)->findOrFail($id); }
    private function validated(Request $request): array
    {
        return $request->validate(['ativos_id'=>['required','integer','exists:manut_ativos,id'], 'solicitante'=>['required','string','max:50'], 'data_agendamento'=>['required','date'], 'observacao'=>['nullable','string','max:512'], 'ativo'=>['nullable','boolean']]);
    }
    private function formView(Lancamento $corretiva, bool $isEdit): View
    {
        $ativos = Ativo::with('local.unidade')->where('ativo', true)->when($corretiva->ativos_id, fn ($q, $id) => $q->orWhere('id', $id))->orderBy('titulo')->get();
        return view('corretivas.form', compact('corretiva', 'isEdit', 'ativos'));
    }
}
