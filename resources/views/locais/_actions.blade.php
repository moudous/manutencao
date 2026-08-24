<div class="d-inline-flex gap-1">
    @if ($giPermissoes->permite('locais.visualizar'))<a href="{{ route('locais.show', $local->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar local"><i class="bi bi-eye-fill"></i></a>@endif
    @if (! $local->trashed())
        @if ($giPermissoes->permite('locais.criar'))<a href="{{ route('locais.edit', $local->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Editar local"><i class="bi bi-pencil-square"></i></a>@endif
        @if ($giPermissoes->permite('locais.excluir'))<form method="POST" action="{{ route('locais.destroy', $local->id) }}" class="d-inline excluir-local-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger listagem-acao" title="Excluir local"><i class="bi bi-trash-fill"></i></button></form>@endif
    @else
        @if ($giPermissoes->permite('locais.restaurar'))<form method="POST" action="{{ route('locais.restore', $local->id) }}" class="d-inline restaurar-local-form" data-name="{{ $local->titulo }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success listagem-acao" title="Restaurar local"><i class="bi bi-arrow-counterclockwise"></i></button></form>@endif
        @if ($giPermissoes->permite('locais.excluir_permanentemente'))<form method="POST" action="{{ route('locais.force-destroy', $local->id) }}" class="d-inline apagar-definitivamente-form" data-name="{{ $local->titulo }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-dark listagem-acao" title="Excluir permanentemente"><i class="bi bi-trash-fill"></i></button></form>@endif
    @endif
</div>
