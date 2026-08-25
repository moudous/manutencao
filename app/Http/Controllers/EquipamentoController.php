<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipamentoController extends Controller
{
    public function index(Request $request): View { return view('equipamentos.index', ['includeDeleted'=>$request->boolean('include_deleted')]); }
    public function data(Request $request): JsonResponse
    {
        $query = Equipamento::query();
        if ($request->boolean('include_deleted')) $query->withTrashed();
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));
        if ($search !== '') $query->where('titulo', 'like', "%{$search}%");
        $filtered = (clone $query)->count();
        $columns = ['id', 'titulo', 'ativo', 'criado_em', 'alterado_em', 'apagado_em'];
        $column = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $rows = $query->orderBy($column, $direction)->skip(max((int) $request->input('start', 0), 0))->take($length)->get()->map(fn (Equipamento $equipamento) => [
            'id'=>$equipamento->id, 'titulo'=>e($equipamento->titulo ?: '—'), 'status'=>view('equipamentos._status', compact('equipamento'))->render(),
            'criado_em'=>$equipamento->criado_em?->format('d/m/Y H:i') ?? '—', 'alterado_em'=>$equipamento->alterado_em?->format('d/m/Y H:i') ?? '—',
            'apagado_em'=>$equipamento->apagado_em?->format('d/m/Y H:i') ?? '—', 'acoes'=>view('equipamentos._actions', compact('equipamento'))->render(),
        ]);
        return response()->json(['draw'=>(int) $request->input('draw'), 'recordsTotal'=>$total, 'recordsFiltered'=>$filtered, 'data'=>$rows]);
    }
    public function create(): View { return view('equipamentos.form', ['equipamento'=>new Equipamento(['ativo'=>true]), 'isEdit'=>false]); }
    public function store(Request $request): RedirectResponse { Equipamento::create($this->validated($request)); return redirect()->route('equipamentos.index')->with('status', 'Equipamento cadastrado com sucesso.'); }
    public function show(int $id): View { return view('equipamentos.show', ['equipamento'=>Equipamento::withTrashed()->findOrFail($id)]); }
    public function edit(int $id): View { return view('equipamentos.form', ['equipamento'=>Equipamento::withTrashed()->findOrFail($id), 'isEdit'=>true]); }
    public function update(Request $request, int $id): RedirectResponse { Equipamento::withTrashed()->findOrFail($id)->update($this->validated($request)); return redirect()->route('equipamentos.index')->with('status', 'Equipamento atualizado com sucesso.'); }
    public function destroy(int $id): RedirectResponse { Equipamento::findOrFail($id)->delete(); return redirect()->route('equipamentos.index', ['include_deleted'=>1])->with('status', 'Equipamento movido para os registros apagados.'); }
    public function restore(int $id): RedirectResponse { Equipamento::onlyTrashed()->findOrFail($id)->restore(); return redirect()->route('equipamentos.index', ['include_deleted'=>1])->with('status', 'Equipamento restaurado com sucesso.'); }
    public function forceDestroy(int $id): RedirectResponse { Equipamento::onlyTrashed()->findOrFail($id)->forceDelete(); return redirect()->route('equipamentos.index', ['include_deleted'=>1])->with('status', 'Equipamento excluído permanentemente.'); }
    private function validated(Request $request): array { return $request->validate(['titulo'=>['required','string','max:100'], 'ativo'=>['nullable','boolean']]); }
}
