<?php

namespace App\Services;

use App\Models\Pessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class GiPessoaSynchronizer
{
    public function syncFromGi(string $accessToken): int
    {
        $response = Http::withToken($accessToken)->acceptJson()->timeout(30)
            ->get(rtrim(config('gi.gi_url'), '/').'/api/integracoes/v1/usuarios');

        abort_unless($response->successful(), 502, 'Não foi possível importar as pessoas do GI.');

        return $this->syncMany((array) $response->json('data', []));
    }

    public function syncMany(array $usuarios): int
    {
        $total = 0;
        foreach ($usuarios as $usuario) {
            if (! is_array($usuario)) {
                continue;
            }

            $this->sync([
                'usuario' => $usuario,
                'perfil' => $usuario['perfil'] ?? ($usuario['perfis'][0] ?? []),
                'perfis' => $usuario['perfis'] ?? [],
            ]);
            $total++;
        }

        return $total;
    }

    public function sync(array $context): Pessoa
    {
        $usuario = (array) ($context['usuario'] ?? []);
        $perfil = (array) ($context['perfil'] ?? []);
        $id = filter_var($usuario['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            throw new UnexpectedValueException('O GI não informou um usuário válido.');
        }

        $data = [
            'nome' => trim((string) ($usuario['nome'] ?? '')),
            'email' => trim((string) ($usuario['email'] ?? '')),
            'perfil' => trim((string) ($perfil['nome'] ?? '')) ?: null,
            'perfil_id' => isset($perfil['id']) ? (int) $perfil['id'] : null,
            'ativo' => array_key_exists('ativo', $usuario) ? (bool) $usuario['ativo'] : true,
        ];

        if (array_key_exists('perfis', $context)) {
            $data['perfis'] = collect((array) $context['perfis'])
                ->filter(fn ($item): bool => is_array($item))
                ->map(fn (array $item): array => [
                    'id' => isset($item['id']) ? (int) $item['id'] : null,
                    'nome' => trim((string) ($item['nome'] ?? '')),
                    'ultimo_login_em' => $item['ultimo_login_em'] ?? null,
                ])
                ->filter(fn (array $item): bool => $item['id'] !== null && $item['nome'] !== '')
                ->values()
                ->all();
        }

        if (array_key_exists('ultimo_acesso', $usuario)) {
            $data['ultimo_login_em'] = filled($usuario['ultimo_acesso'])
                ? (string) $usuario['ultimo_acesso']
                : null;
        }

        if ($data['nome'] === '' || $data['email'] === '') {
            throw new UnexpectedValueException('O GI não informou nome e e-mail do usuário.');
        }

        return DB::transaction(function () use ($id, $data): Pessoa {
            $pessoa = Pessoa::query()->find($id);
            if (! $pessoa) {
                $pessoa = Pessoa::query()->where('email', $data['email'])->first();
                if ($pessoa) {
                    $pessoa->id = $id;
                }
            }

            $pessoa ??= new Pessoa(['id' => $id]);
            $pessoa->fill($data)->save();

            return $pessoa;
        });
    }
}
