@extends('layouts.app')
@section('title','Visualizar agenda preventiva')
@section('content')
<div class="mb-4"><h1 class="page-title">Agendamento de Manutenções Preventivas</h1><p class="page-description mb-0">Consulte todos os dados do agendamento.</p></div>
<div class="card content-card"><div class="card-header"><h5>Dados do agendamento</h5></div><div class="card-body p-4">
@php $equip=$agenda->equipamento; $localUnidade=($equip?->local?->titulo?:'Local não informado').' - '.($equip?->local?->unidade?->titulo?:'Unidade não informada'); $campos=[['ID',$agenda->id],['Ativo / Equipamento',$equip?($equip->titulo.' ('.($equip->codigo?:'sem código').')'):'—'],['Local - Unidade',$localUnidade],['Última Agenda',$agenda->ultima_agenda?->format('d/m/Y')],['Periodicidade (nº dias)',$agenda->periodicidade],['Próxima Agenda',$agenda->proxima_agenda?->format('d/m/Y')],['Orçamento (nº dias)',$agenda->orcamento],['Próximo Orçamento',$agenda->proximo_orcamento?->format('d/m/Y')],['Status',$agenda->trashed()?'Apagada':($agenda->ativo?'Ativada':'Desativada')],['Observações',$agenda->obs],['Criado por',$agenda->criador?->nome],['Criado em',$agenda->criado_em?->format('d/m/Y H:i')],['Atualizado em',$agenda->atualizado_em?->format('d/m/Y H:i')]]; @endphp
<div class="row g-3">@foreach($campos as [$label,$value])<div class="col-md-6"><div class="form-label">{{ $label }}</div><div class="form-control bg-body-tertiary h-auto text-break">{{ filled($value)?$value:'—' }}</div></div>@endforeach</div><div class="d-flex justify-content-end mt-4"><a href="{{ route('agenda.index',['include_deleted'=>$agenda->trashed()?1:null]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
</div></div>
@endsection
