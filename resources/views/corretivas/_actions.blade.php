<div class="d-inline-flex gap-1">
@if($giPermissoes->permite('corretiva.visualizar'))<a href="{{ route('corretiva.show',$corretiva->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar"><i class="bi bi-eye-fill"></i></a>@endif
@if($giPermissoes->permite('corretiva.editar'))<a href="{{ route('corretiva.edit',$corretiva->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Editar"><i class="bi bi-pencil-square"></i></a>@endif
@if($giPermissoes->permite('corretiva.excluir'))<form method="POST" action="{{ route('corretiva.destroy',$corretiva->id) }}" class="excluir-corretiva-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger listagem-acao" title="Excluir"><i class="bi bi-trash-fill"></i></button></form>@endif
</div>
