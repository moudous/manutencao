@extends('layouts.app')

@section('title', 'Cadastro de pessoas')

@push('styles')
<link href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="mb-4"><h1 class="page-title">Cadastro de pessoas</h1><p class="page-description mb-0">Consulte as pessoas sincronizadas automaticamente com o GI.</p></div>
@if (session('status'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>@endif
<div class="card content-card">
    <div class="card-header"><h5>Pessoas cadastradas</h5></div>
    <div class="card-body p-0"><div class="table-responsive"><table id="pessoasTable" class="table table-hover align-middle w-100 mb-0">
        <thead><tr><th>ID GI</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>ID perfil</th><th>Local</th><th>Status</th><th>Última sincronização</th><th class="text-center" data-dt-order="disable">Ações</th></tr></thead><tbody></tbody>
    </table></div></div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new DataTable('#pessoasTable', {processing:true, serverSide:true, ajax:@json(route('pessoas.data', [], false)), order:[[0,'desc']], columns:[{data:'id'},{data:'nome'},{data:'email'},{data:'perfil'},{data:'perfil_id'},{data:'local'},{data:'status',searchable:false},{data:'atualizado_em'},{data:'acoes',orderable:false,searchable:false,className:'text-center text-nowrap'}], language:{processing:'Carregando...',emptyTable:'Nenhuma pessoa cadastrada.',info:'Exibindo _START_ a _END_ de _TOTAL_ pessoas',infoEmpty:'Nenhuma pessoa encontrada',lengthMenu:'Exibir _MENU_ registros',search:'Pesquisar:',zeroRecords:'Nenhuma pessoa encontrada.',paginate:{first:'Primeira',last:'Última',next:'Próxima',previous:'Anterior'}}});
});
</script>
@endpush
