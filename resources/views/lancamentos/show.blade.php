@extends('layouts.app')
@section('title','Visualizar lançamento')
@section('content')
<div class="mb-4"><h1 class="page-title">Visualizar lançamento</h1><p class="page-description mb-0">Detalhes do lançamento da agenda preventiva.</p></div>
@php
    $equip = $lancamento->equipamento;
    $tipo = match((int)$lancamento->tipos_id) { 1=>'1 - Preventiva', 2=>'2 - Corretiva', default=>'—' };
    $campos = [
        ['ID',$lancamento->id,'col-6 col-md-2'],
        ['Agenda',$lancamento->agenda_id,'col-6 col-md-2'],
        ['Etapa',$lancamento->etapa,'col-6 col-md-2'],
        ['Status',$lancamento->ativo?'Ativo':'Inativo','col-6 col-md-2'],
        ['Tipo (ID)',$tipo,'col-6 col-md-2'],
        ['Situação',$lancamento->situacao?->titulo,'col-6 col-md-2'],
        ['Ativo / Equipamento',$equip?($equip->titulo.' ('.($equip->codigo?:'sem código').')'):'—','col-md-6'],
        ['Local - Unidade',($equip?->local?->titulo?:$lancamento->local?->titulo?:'—').' - '.($equip?->local?->unidade?->titulo?:'Unidade não informada'),'col-md-6'],
        ['Solicitante',$lancamento->solicitante,'col-md-6'],
        ['Técnico',$lancamento->tecnico?->nome?:$lancamento->tecnicos_id,'col-md-6'],
        ['Problema',$lancamento->problema,'col-12'],
        ['Data do lançamento',$lancamento->data_lancamento?->format('d/m/Y H:i'),'col-sm-6 col-lg-3'],
        ['Data do orçamento',$lancamento->data_orcamento?->format('d/m/Y H:i'),'col-sm-6 col-lg-3'],
        ['Data do agendamento',$lancamento->data_agendamento?->format('d/m/Y H:i'),'col-sm-6 col-lg-3'],
        ['Data de arquivamento',$lancamento->data_arquivamento?->format('d/m/Y H:i'),'col-sm-6 col-lg-3'],
        ['Criado em',$lancamento->criado_em?->format('d/m/Y H:i'),'col-sm-6 col-lg-3'],
        ['Atualizado em',$lancamento->atualizado_em?->format('d/m/Y H:i'),'col-sm-6 col-lg-3'],
    ];
@endphp
<div class="card content-card"><div class="card-header"><h5>Dados do lançamento</h5></div><div class="card-body p-4"><div class="row g-3">
    @foreach($campos as [$label,$value,$grid])
        <div class="{{ $grid }}"><div class="form-label">{{ $label }}</div><div class="form-control bg-body-tertiary h-auto text-break">{{ filled($value)?$value:'—' }}</div></div>
    @endforeach
    <div class="col-12"><label for="observacao" class="form-label">Observação</label><textarea id="observacao" class="form-control bg-body-tertiary" rows="3" readonly>{{ $lancamento->observacao }}</textarea></div>
</div><div class="d-flex justify-content-end mt-4"><a href="{{ route('agenda.lancamentos.index',$agenda) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div></div></div>
@endsection
