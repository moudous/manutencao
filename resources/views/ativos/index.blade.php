@extends('layouts.app')

@section('title', 'Ativos cadastrados')

@push('styles')
<link href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div><h1 class="page-title">Cadastro de ativos</h1><p class="page-description mb-0">Consulte e gerencie os ativos do sistema.</p></div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ url('/') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
        @if ($giPermissoes->permite('ativos.criar'))<a href="{{ route('ativos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Adicionar ativo</a>@endif
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>
@endif

<div class="card content-card">
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <h5>Ativos cadastrados</h5>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="visualizarApagados" {{ $includeDeleted ? 'checked' : '' }}>
            <label class="form-check-label" for="visualizarApagados">Visualizar apagados</label>
        </div>
    </div>
    <div class="card-body p-0"><div class="table-responsive">
        <table id="ativosTable" class="table table-hover align-middle w-100 mb-0">
            <thead><tr>
                <th>ID</th><th>Código</th><th>Título</th><th>Status</th><th>Data de aquisição</th><th>Local</th><th>Cadastrado em</th><th>Excluído em</th><th class="text-center" data-dt-order="disable">Ações</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new DataTable('#ativosTable', {processing: true, serverSide: true, ajax: @json(route('ativos.data', ['include_deleted' => $includeDeleted ? 1 : null], false)), order: [[0, 'desc']], columns: [{data:'id'},{data:'codigo'},{data:'titulo'},{data:'status',orderable:true,searchable:false},{data:'data_aquisicao'},{data:'local'},{data:'criado_em'},{data:'apagado_em'},{data:'acoes',orderable:false,searchable:false,className:'text-center text-nowrap'}], language: {processing:'Carregando...', lengthMenu: 'Exibir _MENU_ registros', search: 'Pesquisar:', info: 'Exibindo _START_ a _END_ de _TOTAL_ ativos', infoEmpty: 'Exibindo 0 a 0 de 0 registros', emptyTable: 'Nenhum ativo cadastrado', zeroRecords: 'Nenhum registro encontrado', paginate: {first: 'Primeira', last: 'Última', next: 'Próxima', previous: 'Anterior'}}});
    document.addEventListener('submit', event => { const form=event.target; let message=null; if(form.matches('.excluir-ativo-form')) message='Deseja mover este ativo para os registros apagados?'; if(form.matches('.restaurar-ativo-form')) message=`Deseja restaurar o ativo “${form.dataset.name}”?`; if(form.matches('.apagar-definitivamente-form')) message=`ATENÇÃO: deseja excluir permanentemente o ativo “${form.dataset.name}”?\n\nEsta ação não poderá ser desfeita.`; if(message && !confirm(message)) event.preventDefault(); });
    document.getElementById('visualizarApagados')?.addEventListener('change', function () { const url = new URL(location.href); this.checked ? url.searchParams.set('include_deleted', '1') : url.searchParams.delete('include_deleted'); location.href = url; });
});
</script>
@endpush
