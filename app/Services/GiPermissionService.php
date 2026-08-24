<?php

namespace App\Services;

use Illuminate\Http\Request;

class GiPermissionService
{
    public function todas(?Request $request = null): array
    {
        $request ??= request();

        return collect((array) $request->session()->get('gi_context.permissoes', []))
            ->filter(fn ($permission): bool => is_string($permission) && trim($permission) !== '')
            ->map(fn (string $permission): string => trim($permission))
            ->unique()
            ->values()
            ->all();
    }

    public function permite(string $permission, ?Request $request = null): bool
    {
        return in_array($permission, $this->todas($request), true);
    }

    public function exigir(string $permission, ?Request $request = null): void
    {
        abort_unless($this->permite($permission, $request), 403, "Seu perfil não possui a permissão {$permission}.");
    }
}
