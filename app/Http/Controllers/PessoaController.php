<?php

namespace App\Http\Controllers;

use App\Models\Local;
use App\Models\Pessoa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PessoaController extends Controller
{
    public function index(): View
    {
        return view('pessoas.index');
    }

    public function data(Request $request): JsonResponse
    {
        $query = Pessoa::query()->with('local');
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('nome', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('perfil', 'like', "%{$search}%")
                    ->orWhereHas('local', fn ($local) => $local->where('titulo', 'like', "%{$search}%"));
            });
        }

        $filtered = (clone $query)->count();
        $columns = ['id', 'nome', 'email', 'perfil', 'perfil_id', 'locais_id', 'ativo', 'atualizado_em'];
        $column = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $length = min(max((int) $request->input('length', 10), 1), 100);

        $rows = $query->orderBy($column, $direction)->skip(max((int) $request->input('start', 0), 0))->take($length)->get()
            ->map(fn (Pessoa $pessoa) => [
                'id' => $pessoa->id,
                'nome' => e($pessoa->nome),
                'email' => e($pessoa->email),
                'perfil' => e($pessoa->perfil ?: '—'),
                'perfil_id' => $pessoa->perfil_id ?? '—',
                'local' => e($pessoa->local?->titulo ?? '—'),
                'status' => '<span class="badge '.($pessoa->ativo ? 'text-bg-success' : 'text-bg-secondary').'">'.($pessoa->ativo ? 'Ativa' : 'Inativa').'</span>',
                'atualizado_em' => $pessoa->atualizado_em?->format('d/m/Y H:i') ?? '—',
                'acoes' => view('pessoas._actions', compact('pessoa'))->render(),
            ]);

        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows]);
    }

    public function show(Pessoa $pessoa): View
    {
        $pessoa->load('local');

        return view('pessoas.show', compact('pessoa'));
    }

    public function edit(Pessoa $pessoa): View
    {
        return view('pessoas.form', [
            'pessoa' => $pessoa,
            'locais' => Local::withTrashed()
                ->where(fn ($query) => $query->where(fn ($active) => $active->where('ativo', true)->whereNull('apagado_em'))
                    ->orWhere('id', $pessoa->locais_id))
                ->orderBy('titulo')
                ->get(),
        ]);
    }

    public function update(Request $request, Pessoa $pessoa): RedirectResponse
    {
        $data = $request->validate(['locais_id' => ['nullable', 'integer', 'exists:manut_locais,id']]);
        $pessoa->update($data);

        return redirect()->route('pessoas.show', $pessoa)->with('status', 'Local da pessoa atualizado com sucesso.');
    }
}
