<div class="d-inline-flex gap-1">
    @if ($giPermissoes->permite('ativos.visualizar'))<a href="{{ route('ativos.show', $ativo->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar ativo"><i class="bi bi-eye-fill"></i></a>@endif
    @if (! $ativo->trashed())
        @if ($giPermissoes->permite('ativos.criar'))<a href="{{ route('ativos.edit', $ativo->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Editar ativo"><i class="bi bi-pencil-square"></i></a>@endif
        @if ($giPermissoes->permite('ativos.excluir'))<form method="POST" action="{{ route('ativos.destroy', $ativo->id) }}" class="d-inline excluir-ativo-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger listagem-acao" title="Excluir ativo"><i class="bi bi-trash-fill"></i></button></form>@endif
    @else
        @if ($giPermissoes->permite('ativos.restaurar'))<form method="POST" action="{{ route('ativos.restore', $ativo->id) }}" class="d-inline restaurar-ativo-form" data-name="{{ $ativo->titulo }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success listagem-acao" title="Restaurar ativo"><i class="bi bi-arrow-counterclockwise"></i></button></form>@endif
        @if ($giPermissoes->permite('ativos.excluir_permanentemente'))<form method="POST" action="{{ route('ativos.force-destroy', $ativo->id) }}" class="d-inline apagar-definitivamente-form" data-name="{{ $ativo->titulo }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-dark listagem-acao" title="Excluir permanentemente"><i class="bi bi-trash-fill"></i></button></form>@endif
    @endif
</div>
