<div class="d-inline-flex gap-1">
    @if ($giPermissoes->permite('unidades.visualizar'))<a href="{{ route('unidades.show', $unidade->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar unidade"><i class="bi bi-eye-fill"></i></a>@endif
    @if (! $unidade->trashed())
        @if ($giPermissoes->permite('unidades.criar'))<a href="{{ route('unidades.edit', $unidade->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Editar unidade"><i class="bi bi-pencil-square"></i></a>@endif
        @if ($giPermissoes->permite('unidades.excluir'))<form method="POST" action="{{ route('unidades.destroy', $unidade->id) }}" class="d-inline excluir-unidade-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger listagem-acao" title="Excluir unidade"><i class="bi bi-trash-fill"></i></button></form>@endif
    @else
        @if ($giPermissoes->permite('unidades.restaurar'))<form method="POST" action="{{ route('unidades.restore', $unidade->id) }}" class="d-inline restaurar-unidade-form" data-name="{{ $unidade->titulo }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success listagem-acao" title="Restaurar unidade"><i class="bi bi-arrow-counterclockwise"></i></button></form>@endif
        @if ($giPermissoes->permite('unidades.excluir_permanentemente'))<form method="POST" action="{{ route('unidades.force-destroy', $unidade->id) }}" class="d-inline apagar-definitivamente-form" data-name="{{ $unidade->titulo }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-dark listagem-acao" title="Excluir permanentemente"><i class="bi bi-trash-fill"></i></button></form>@endif
    @endif
</div>
