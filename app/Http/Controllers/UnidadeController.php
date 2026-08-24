<?php

namespace App\Http\Controllers;

use App\Models\Unidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnidadeController extends Controller
{
    public function index(Request $request): View
    {
        $includeDeleted = $request->boolean('include_deleted');
        return view('unidades.index', [
            'includeDeleted' => $includeDeleted,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Unidade::query();
        if ($request->boolean('include_deleted')) {
            $query->withTrashed();
        }

        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));
        if ($search !== '') {
            $query->where('titulo', 'like', "%{$search}%");
        }

        $filtered = (clone $query)->count();
        $columns = ['id', 'titulo', 'ativo', 'criado_em', 'atualizado_em', 'apagado_em'];
        $column = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $length = min(max((int) $request->input('length', 10), 1), 100);

        $rows = $query->orderBy($column, $direction)->skip(max((int) $request->input('start', 0), 0))->take($length)->get()
            ->map(fn (Unidade $unidade) => [
                'id' => $unidade->id,
                'titulo' => e($unidade->titulo ?: '—'),
                'status' => view('unidades._status', compact('unidade'))->render(),
                'criado_em' => $unidade->criado_em?->format('d/m/Y H:i') ?? '—',
                'atualizado_em' => $unidade->atualizado_em?->format('d/m/Y H:i') ?? '—',
                'apagado_em' => $unidade->apagado_em?->format('d/m/Y H:i') ?? '—',
                'acoes' => view('unidades._actions', compact('unidade'))->render(),
            ]);

        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows]);
    }

    public function create(): View
    {
        return view('unidades.form', ['unidade' => new Unidade(['ativo' => true]), 'isEdit' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        Unidade::create($this->validateUnidade($request));

        return redirect()->route('unidades.index')->with('status', 'Unidade cadastrada com sucesso.');
    }

    public function show(int $id): View
    {
        return view('unidades.show', ['unidade' => Unidade::withTrashed()->findOrFail($id)]);
    }

    public function edit(int $id): View
    {
        return view('unidades.form', ['unidade' => Unidade::withTrashed()->findOrFail($id), 'isEdit' => true]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        Unidade::withTrashed()->findOrFail($id)->update($this->validateUnidade($request));

        return redirect()->route('unidades.index')->with('status', 'Unidade atualizada com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Unidade::findOrFail($id)->delete();

        return redirect()->route('unidades.index', ['include_deleted' => 1])->with('status', 'Unidade movida para os registros apagados.');
    }

    public function restore(int $id): RedirectResponse
    {
        Unidade::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('unidades.index', ['include_deleted' => 1])->with('status', 'Unidade restaurada com sucesso.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $unidade = Unidade::onlyTrashed()->findOrFail($id);
        abort_if($unidade->locais()->withTrashed()->exists(), 409, 'Esta unidade não pode ser excluída permanentemente porque está vinculada a locais.');
        $unidade->forceDelete();

        return redirect()->route('unidades.index', ['include_deleted' => 1])->with('status', 'Unidade excluída permanentemente.');
    }

    private function validateUnidade(Request $request): array
    {
        return $request->validate([
            'titulo' => ['nullable', 'string', 'max:250'],
            'ativo' => ['nullable', 'boolean'],
        ]);
    }
}
