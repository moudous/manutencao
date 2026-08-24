<?php

namespace App\Http\Controllers;

use App\Models\Ativo;
use App\Models\Local;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AtivoController extends Controller
{
    public function index(Request $request): View
    {
        $includeDeleted = $request->boolean('include_deleted');
        return view('ativos.index', [
            'includeDeleted' => $includeDeleted,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Ativo::query()->with('local');
        if ($request->boolean('include_deleted')) {
            $query->withTrashed();
        }

        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('codigo', 'like', "%{$search}%")
                    ->orWhere('titulo', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%")
                    ->orWhereHas('local', fn ($local) => $local->where('titulo', 'like', "%{$search}%"));
            });
        }

        $filtered = (clone $query)->count();
        $columns = ['id', 'codigo', 'titulo', 'ativo', 'data_aquisicao', 'locais_id', 'criado_em', 'apagado_em'];
        $column = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $length = min(max((int) $request->input('length', 10), 1), 100);

        $rows = $query->orderBy($column, $direction)->skip(max((int) $request->input('start', 0), 0))->take($length)->get()
            ->map(fn (Ativo $ativo) => [
                'id' => $ativo->id,
                'codigo' => e($ativo->codigo ?: '—'),
                'titulo' => e($ativo->titulo ?: '—'),
                'status' => view('ativos._status', compact('ativo'))->render(),
                'data_aquisicao' => $ativo->data_aquisicao?->format('d/m/Y') ?? '—',
                'local' => e($ativo->local?->titulo ?? '—'),
                'criado_em' => $ativo->criado_em?->format('d/m/Y H:i') ?? '—',
                'apagado_em' => $ativo->apagado_em?->format('d/m/Y H:i') ?? '—',
                'acoes' => view('ativos._actions', compact('ativo'))->render(),
            ]);

        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows]);
    }

    public function create(): View
    {
        return view('ativos.form', [
            'ativo' => new Ativo(['ativo' => true]),
            'isEdit' => false,
            'locais' => Local::query()->where('ativo', true)->orderBy('titulo')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Ativo::create($this->validateAtivo($request));

        return redirect()->route('ativos.index')->with('status', 'Ativo cadastrado com sucesso.');
    }

    public function show(int $id): View
    {
        return view('ativos.show', ['ativo' => Ativo::withTrashed()->findOrFail($id)]);
    }

    public function edit(int $id): View
    {
        $ativo = Ativo::withTrashed()->findOrFail($id);

        return view('ativos.form', [
            'ativo' => $ativo,
            'isEdit' => true,
            'locais' => Local::withTrashed()
                ->where(fn ($query) => $query->where(fn ($active) => $active->where('ativo', true)->whereNull('apagado_em'))
                    ->orWhere('id', $ativo->locais_id))
                ->orderBy('titulo')
                ->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $ativo = Ativo::withTrashed()->findOrFail($id);
        $ativo->update($this->validateAtivo($request));

        return redirect()->route('ativos.index')->with('status', 'Ativo atualizado com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Ativo::findOrFail($id)->delete();

        return redirect()->route('ativos.index', ['include_deleted' => 1])
            ->with('status', 'Ativo movido para os registros apagados.');
    }

    public function restore(int $id): RedirectResponse
    {
        Ativo::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('ativos.index', ['include_deleted' => 1])
            ->with('status', 'Ativo restaurado com sucesso.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        Ativo::onlyTrashed()->findOrFail($id)->forceDelete();

        return redirect()->route('ativos.index', ['include_deleted' => 1])
            ->with('status', 'Ativo excluído permanentemente.');
    }

    private function validateAtivo(Request $request): array
    {
        return $request->validate([
            'codigo' => ['nullable', 'string', 'max:50'],
            'titulo' => ['nullable', 'string', 'max:250'],
            'descricao' => ['nullable', 'string'],
            'data_aquisicao' => ['nullable', 'date'],
            'locais_id' => ['nullable', 'integer', 'exists:manut_locais,id'],
            'ativo' => ['nullable', 'boolean'],
        ]);
    }
}
