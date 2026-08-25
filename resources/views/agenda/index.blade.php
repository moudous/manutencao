@extends('layouts.app')
@section('title','Agenda Preventiva')
@push('styles')<link href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css" rel="stylesheet"><style>.prazo-toggle{border:0;background:transparent;padding:.2rem .45rem;text-decoration:underline;border-radius:.35rem;color:var(--bs-primary);font-weight:600}.prazo-toggle:hover,.prazo-toggle:focus-visible{color:var(--bs-primary-text-emphasis);background:var(--bs-primary-bg-subtle)}.prazo-toggle.prazo-warning{color:var(--bs-warning-text-emphasis);background:var(--bs-warning-bg-subtle)}.prazo-toggle.prazo-danger{color:var(--bs-danger-text-emphasis);background:var(--bs-danger-bg-subtle)}.prazo-details{display:none;margin-top:.4rem;padding-top:.4rem;border-top:1px solid var(--bs-border-color);font-size:.75rem;line-height:1.35;color:var(--bs-secondary-color);white-space:normal}.prazo-details.is-open{display:block}.prazo-detail{display:block}.prazo-detail strong{color:var(--bs-body-color);font-weight:600}</style>@endpush
@section('content')
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4"><div><h1 class="page-title">Agendamento de Manutenções Preventivas</h1><p class="page-description mb-0">Consulte e gerencie a agenda preventiva dos equipamentos.</p></div><div class="d-flex gap-2"><a href="{{ url('/') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>@if($giPermissoes->permite('agenda.criar'))<a href="{{ route('agenda.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Adicionar agendamento</a>@endif</div></div>
@if(session('status'))<div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="card content-card"><div class="card-header d-flex justify-content-between align-items-center gap-3"><h5>Agenda preventiva</h5><div class="form-check form-switch mb-0"><input id="visualizarApagados" class="form-check-input" type="checkbox" @checked($includeDeleted)><label class="form-check-label" for="visualizarApagados">Visualizar apagadas</label></div></div><div class="card-body p-0"><div class="table-responsive"><table id="agendaTable" class="table table-hover align-middle w-100 mb-0"><thead><tr><th>ID</th><th>Ativo / Equipamento</th><th>Local - Unidade</th><th>Prazo<br>manutenção</th><th>Prazo<br>orçamento</th><th>Qtde.<br>lanç.</th><th>Status</th><th data-dt-order="disable">Ações</th></tr></thead><tbody></tbody></table></div></div></div>
@endsection
@push('scripts')<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script><script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap5.min.js"></script><script>
document.addEventListener('DOMContentLoaded',()=>{
	const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
	const prazoHtml=(data,rowData,kind)=>{
		if(data==='—')return escapeHtml(data);
		const details=kind==='manutencao'
			? `<span class="prazo-detail"><strong>Próxima:</strong> ${escapeHtml(rowData.proxima_agenda)}</span><span class="prazo-detail"><strong>Última:</strong> ${escapeHtml(rowData.ultima_agenda)}</span><span class="prazo-detail"><strong>Periodicidade:</strong> ${escapeHtml(rowData.periodicidade)} dia(s)</span>`
			: `<span class="prazo-detail"><strong>Próximo:</strong> ${escapeHtml(rowData.proximo_orcamento)}</span><span class="prazo-detail"><strong>Antecedência:</strong> ${escapeHtml(rowData.orcamento)} dia(s)</span>`;
		const severity=escapeHtml(rowData[`prazo_${kind}_classe`]);
		return `<button type="button" class="prazo-toggle ${severity}" data-kind="${kind}" aria-expanded="false">${escapeHtml(data)}</button><span class="prazo-details">${details}</span>`;
	};

	const table=new DataTable('#agendaTable',{
		processing:true,
		serverSide:true,
		ajax:@json(route('agenda.data',['include_deleted'=>$includeDeleted?1:null],false)),
		order:[[3,'desc'],[4,'desc']],
		columns:[
			{data:'id'},
			{data:'equipamento'},
			{data:'local'},
			{data:'prazo_manutencao',render:(data,type,row)=>type==='display'?prazoHtml(data,row,'manutencao'):data},
			{data:'prazo_orcamento',render:(data,type,row)=>type==='display'?prazoHtml(data,row,'orcamento'):data},
			{data:'quantidade_lancamentos',searchable:false},
			{data:'status',searchable:false},
			{data:'acoes',orderable:false,searchable:false}
		],
		language:{processing:'Carregando...',search:'Pesquisar:',lengthMenu:'Exibir _MENU_ registros',info:'Exibindo _START_ a _END_ de _TOTAL_ agendamentos',infoEmpty:'Nenhum agendamento encontrado',emptyTable:'Nenhum agendamento cadastrado',zeroRecords:'Nenhum registro encontrado',paginate:{first:'Primeira',last:'Última',next:'Próxima',previous:'Anterior'}}
	});

	document.querySelector('#agendaTable tbody').addEventListener('click',event=>{
		const trigger=event.target.closest('.prazo-toggle');
		if(!trigger)return;
		const details=trigger.nextElementSibling;
		const opening=!details.classList.contains('is-open');
		details.classList.toggle('is-open',opening);
		trigger.setAttribute('aria-expanded',String(opening));
	});

	document.addEventListener('submit',e=>{let m=null;if(e.target.matches('.excluir-agenda-form'))m='Deseja mover este agendamento para os registros apagados?';if(e.target.matches('.restaurar-agenda-form'))m='Deseja restaurar este agendamento?';if(e.target.matches('.apagar-agenda-form'))m='ATENÇÃO: deseja excluir este agendamento permanentemente?';if(m&&!confirm(m))e.preventDefault()});
	document.getElementById('visualizarApagados').onchange=function(){const u=new URL(location.href);this.checked?u.searchParams.set('include_deleted','1'):u.searchParams.delete('include_deleted');location.href=u};
});
</script>@endpush
