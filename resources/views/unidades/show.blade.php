@extends('layouts.app')
@section('title', 'Visualizar unidade')
@section('content')
<div class="mb-4"><h1 class="page-title">Visualizar unidade</h1><p class="page-description mb-0">Consulte todos os dados cadastrais da unidade.</p></div>
<div class="card content-card"><div class="card-header"><h5>Dados da unidade</h5></div><div class="card-body p-4">
@php $campos = [['ID', $unidade->id], ['Título', $unidade->titulo], ['Status', $unidade->trashed() ? 'Apagada' : ($unidade->ativo ? 'Ativa' : 'Inativa')], ['Cadastrada em', $unidade->criado_em?->format('d/m/Y H:i')], ['Atualizada em', $unidade->atualizado_em?->format('d/m/Y H:i')], ['Excluída em', $unidade->apagado_em?->format('d/m/Y H:i')]]; @endphp
<div class="row g-3">@foreach ($campos as [$rotulo, $valor])<div class="col-md-6"><div class="form-label">{{ $rotulo }}</div><div class="form-control bg-body-tertiary h-auto text-break">{{ filled($valor) ? $valor : '—' }}</div></div>@endforeach</div>
<div class="d-flex justify-content-end mt-4"><a href="{{ route('unidades.index', ['include_deleted' => $unidade->trashed() ? 1 : null]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
</div></div>
@endsection
