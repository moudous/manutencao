<div class="d-inline-flex gap-1">
@if($giPermissoes->permite('equipamentos.visualizar'))<a href="{{ route('equipamentos.show',$equipamento->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar"><i class="bi bi-eye-fill"></i></a>@endif
@if(!$equipamento->trashed())
@if($giPermissoes->permite('equipamentos.editar'))<a href="{{ route('equipamentos.edit',$equipamento->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Editar"><i class="bi bi-pencil-square"></i></a>@endif
@if($giPermissoes->permite('equipamentos.excluir'))<form method="POST" action="{{ route('equipamentos.destroy',$equipamento->id) }}" class="excluir-equipamento-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger listagem-acao" title="Excluir"><i class="bi bi-trash-fill"></i></button></form>@endif
@else
@if($giPermissoes->permite('equipamentos.restaurar'))<form method="POST" action="{{ route('equipamentos.restore',$equipamento->id) }}" class="restaurar-equipamento-form">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success listagem-acao" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></button></form>@endif
@if($giPermissoes->permite('equipamentos.excluir_permanentemente'))<form method="POST" action="{{ route('equipamentos.force-destroy',$equipamento->id) }}" class="apagar-equipamento-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-dark listagem-acao" title="Excluir permanentemente"><i class="bi bi-trash-fill"></i></button></form>@endif
@endif
</div>
