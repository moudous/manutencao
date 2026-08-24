<?php

namespace App\Http\Controllers;

use App\Models\Local;
use App\Models\Unidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocalController extends Controller
{
    public function index(Request $request): View
    {
        $includeDeleted = $request->boolean('include_deleted');
        return view('locais.index', [
            'includeDeleted' => $includeDeleted,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Local::query()->with('unidade');
        if ($request->boolean('include_deleted')) {
            $query->withTrashed();
        }

        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('titulo', 'like', "%{$search}%")
                    ->orWhereHas('unidade', fn ($unidade) => $unidade->where('titulo', 'like', "%{$search}%"));
            });
        }

        $filtered = (clone $query)->count();
        $columns = ['id', 'titulo', 'unidades_id', 'ativo', 'criado_em', 'atualizado_em', 'apagado_em'];
        $column = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $length = min(max((int) $request->input('length', 10), 1), 100);

        $rows = $query->orderBy($column, $direction)->skip(max((int) $request->input('start', 0), 0))->take($length)->get()
            ->map(fn (Local $local) => [
                'id' => $local->id,
                'titulo' => e($local->titulo ?: '—'),
                'unidade' => e($local->unidade?->titulo ?? '—'),
                'status' => view('locais._status', compact('local'))->render(),
                'criado_em' => $local->criado_em?->format('d/m/Y H:i') ?? '—',
                'atualizado_em' => $local->atualizado_em?->format('d/m/Y H:i') ?? '—',
                'apagado_em' => $local->apagado_em?->format('d/m/Y H:i') ?? '—',
                'acoes' => view('locais._actions', compact('local'))->render(),
            ]);

        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows]);
    }

    public function create(): View
    {
        return view('locais.form', [
            'local' => new Local(['ativo' => true]),
            'isEdit' => false,
            'unidades' => Unidade::query()->where('ativo', true)->orderBy('titulo')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Local::create($this->validateLocal($request));

        return redirect()->route('locais.index')->with('status', 'Local cadastrado com sucesso.');
    }

    public function show(int $id): View
    {
        return view('locais.show', ['local' => Local::withTrashed()->findOrFail($id)]);
    }

    public function edit(int $id): View
    {
        $local = Local::withTrashed()->findOrFail($id);

        return view('locais.form', [
            'local' => $local,
            'isEdit' => true,
            'unidades' => Unidade::withTrashed()
                ->where(fn ($query) => $query->where(fn ($active) => $active->where('ativo', true)->whereNull('apagado_em'))
                    ->orWhere('id', $local->unidades_id))
                ->orderBy('titulo')
                ->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        Local::withTrashed()->findOrFail($id)->update($this->validateLocal($request));

        return redirect()->route('locais.index')->with('status', 'Local atualizado com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Local::findOrFail($id)->delete();

        return redirect()->route('locais.index', ['include_deleted' => 1])->with('status', 'Local movido para os registros apagados.');
    }

    public function restore(int $id): RedirectResponse
    {
        Local::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('locais.index', ['include_deleted' => 1])->with('status', 'Local restaurado com sucesso.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $local = Local::onlyTrashed()->findOrFail($id);
        abort_if($local->ativos()->withTrashed()->exists(), 409, 'Este local não pode ser excluído permanentemente porque está vinculado a ativos.');
        $local->forceDelete();

        return redirect()->route('locais.index', ['include_deleted' => 1])->with('status', 'Local excluído permanentemente.');
    }

    private function validateLocal(Request $request): array
    {
        return $request->validate([
            'titulo' => ['nullable', 'string', 'max:250'],
            'unidades_id' => ['nullable', 'integer', 'exists:manut_unidades,id'],
            'ativo' => ['nullable', 'boolean'],
        ]);
    }
}
