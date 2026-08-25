<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicaController extends Controller
{
    public function index(Request $request): View { return view('clinicas.index', ['includeDeleted'=>$request->boolean('include_deleted')]); }

    public function data(Request $request): JsonResponse
    {
        $query = Clinica::query();
        if ($request->boolean('include_deleted')) $query->withTrashed();
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));
        if ($search !== '') $query->where('titulo', 'like', "%{$search}%");
        $filtered = (clone $query)->count();
        $columns = ['id', 'titulo', 'consultorios', 'ativo', 'criado_em', 'alterado_em', 'apagado_em'];
        $column = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $rows = $query->orderBy($column, $direction)->skip(max((int) $request->input('start', 0), 0))->take($length)->get()->map(fn (Clinica $clinica) => [
            'id'=>$clinica->id, 'titulo'=>e($clinica->titulo ?: '—'), 'consultorios'=>$clinica->consultorios ?? '—',
            'status'=>view('clinicas._status', compact('clinica'))->render(), 'criado_em'=>$clinica->criado_em?->format('d/m/Y H:i') ?? '—',
            'alterado_em'=>$clinica->alterado_em?->format('d/m/Y H:i') ?? '—', 'apagado_em'=>$clinica->apagado_em?->format('d/m/Y H:i') ?? '—',
            'acoes'=>view('clinicas._actions', compact('clinica'))->render(),
        ]);
        return response()->json(['draw'=>(int) $request->input('draw'), 'recordsTotal'=>$total, 'recordsFiltered'=>$filtered, 'data'=>$rows]);
    }

    public function create(): View { return view('clinicas.form', ['clinica'=>new Clinica(['ativo'=>true]), 'isEdit'=>false]); }
    public function store(Request $request): RedirectResponse { Clinica::create($this->validated($request)); return redirect()->route('clinicas.index')->with('status', 'Clínica cadastrada com sucesso.'); }
    public function show(int $id): View { return view('clinicas.show', ['clinica'=>Clinica::withTrashed()->findOrFail($id)]); }
    public function edit(int $id): View { return view('clinicas.form', ['clinica'=>Clinica::withTrashed()->findOrFail($id), 'isEdit'=>true]); }
    public function update(Request $request, int $id): RedirectResponse { Clinica::withTrashed()->findOrFail($id)->update($this->validated($request)); return redirect()->route('clinicas.index')->with('status', 'Clínica atualizada com sucesso.'); }
    public function destroy(int $id): RedirectResponse { Clinica::findOrFail($id)->delete(); return redirect()->route('clinicas.index', ['include_deleted'=>1])->with('status', 'Clínica movida para os registros apagados.'); }
    public function restore(int $id): RedirectResponse { Clinica::onlyTrashed()->findOrFail($id)->restore(); return redirect()->route('clinicas.index', ['include_deleted'=>1])->with('status', 'Clínica restaurada com sucesso.'); }
    public function forceDestroy(int $id): RedirectResponse { Clinica::onlyTrashed()->findOrFail($id)->forceDelete(); return redirect()->route('clinicas.index', ['include_deleted'=>1])->with('status', 'Clínica excluída permanentemente.'); }
    private function validated(Request $request): array { return $request->validate(['titulo'=>['required','string','max:50'], 'consultorios'=>['required','integer','min:0'], 'ativo'=>['nullable','boolean']]); }
}
