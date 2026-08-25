<div class="d-inline-flex gap-1">
@if($giPermissoes->permite('clinica.visualizar'))<a href="{{ route('clinicas.show',$clinica->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar"><i class="bi bi-eye-fill"></i></a>@endif
@if(!$clinica->trashed())
@if($giPermissoes->permite('clinica.editar'))<a href="{{ route('clinicas.edit',$clinica->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Editar"><i class="bi bi-pencil-square"></i></a>@endif
@if($giPermissoes->permite('clinica.excluir'))<form method="POST" action="{{ route('clinicas.destroy',$clinica->id) }}" class="excluir-clinica-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger listagem-acao" title="Excluir"><i class="bi bi-trash-fill"></i></button></form>@endif
@else
@if($giPermissoes->permite('clinica.restaurar'))<form method="POST" action="{{ route('clinicas.restore',$clinica->id) }}" class="restaurar-clinica-form">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success listagem-acao" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></button></form>@endif
@if($giPermissoes->permite('clinica.excluir_permanentemente'))<form method="POST" action="{{ route('clinicas.force-destroy',$clinica->id) }}" class="apagar-clinica-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-dark listagem-acao" title="Excluir permanentemente"><i class="bi bi-trash-fill"></i></button></form>@endif
@endif
</div>
