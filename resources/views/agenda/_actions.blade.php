<div class="d-inline-flex gap-1">
@if($giPermissoes->permite('agenda.visualizar'))<a href="{{ route('agenda.show',$agenda->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar"><i class="bi bi-eye-fill"></i></a>@endif
@if(!$agenda->trashed())
@if($giPermissoes->permite('agenda.criar'))<a href="{{ route('agenda.edit',$agenda->id) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Editar"><i class="bi bi-pencil-square"></i></a>@endif
@if($giPermissoes->permite('agenda.excluir'))<form method="POST" action="{{ route('agenda.destroy',$agenda->id) }}" class="excluir-agenda-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger listagem-acao" title="Excluir"><i class="bi bi-trash-fill"></i></button></form>@endif
@else
@if($giPermissoes->permite('agenda.restaurar'))<form method="POST" action="{{ route('agenda.restore',$agenda->id) }}" class="restaurar-agenda-form">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success listagem-acao" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></button></form>@endif
@if($giPermissoes->permite('agenda.excluir_permanentemente'))<form method="POST" action="{{ route('agenda.force-destroy',$agenda->id) }}" class="apagar-agenda-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-dark listagem-acao" title="Excluir permanentemente"><i class="bi bi-trash-fill"></i></button></form>@endif
@endif
</div>
