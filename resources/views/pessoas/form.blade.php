@extends('layouts.app')

@section('title', 'Associar local')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="mb-4"><h1 class="page-title">Associar local</h1><p class="page-description mb-0">Selecione o local associado a {{ $pessoa->nome }}.</p></div>
<form method="POST" action="{{ route('pessoas.update', $pessoa) }}">@csrf @method('PUT')
    <div class="card content-card"><div class="card-header"><h5>Local da pessoa</h5></div><div class="card-body p-4">
        @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
        <label for="locais_id" class="form-label">Local</label>
        <select id="locais_id" name="locais_id" class="form-select @error('locais_id') is-invalid @enderror"><option value="">Nenhum local</option>@foreach ($locais as $local)<option value="{{ $local->id }}" @selected((string) old('locais_id', $pessoa->locais_id) === (string) $local->id)>{{ $local->titulo ?: "Local #{$local->id}" }}{{ $local->trashed() ? ' (apagado)' : (! $local->ativo ? ' (inativo)' : '') }}</option>@endforeach</select>
        <div class="form-text">Cada pessoa pode ser associada a apenas um local.</div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('pessoas.show', $pessoa) }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar local</button></div>
    </div></div>
</form>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>$(function () { $('#locais_id').select2({theme:'bootstrap-5',width:'100%',allowClear:true,placeholder:'Selecione ou pesquise um local',language:{noResults:()=> 'Nenhum local encontrado'}}); });</script>
@endpush
