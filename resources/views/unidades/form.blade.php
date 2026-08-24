@extends('layouts.app')
@section('title', $isEdit ? 'Editar unidade' : 'Adicionar unidade')
@section('content')
<div class="mb-4"><h1 class="page-title">{{ $isEdit ? 'Editar unidade' : 'Adicionar unidade' }}</h1><p class="page-description mb-0">{{ $isEdit ? 'Atualize os dados da unidade.' : 'Preencha os dados da nova unidade.' }}</p></div>
<form method="POST" action="{{ $isEdit ? route('unidades.update', $unidade->id) : route('unidades.store') }}">@csrf @if ($isEdit) @method('PUT') @endif
<div class="card content-card"><div class="card-header"><h5>Dados da unidade</h5></div><div class="card-body p-4">
@if ($errors->any())<div class="alert alert-danger"><strong>Verifique os campos informados.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row g-3"><div class="col-12"><label for="titulo" class="form-label">Título</label><input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" maxlength="250" autofocus value="{{ old('titulo', $unidade->titulo) }}">@error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-12"><input type="hidden" name="ativo" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked((bool) old('ativo', $unidade->ativo))><label class="form-check-label" for="ativo">Ativa</label></div></div></div>
<div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('unidades.index') }}" class="btn btn-outline-secondary">Cancelar</a><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Salvar' : 'Cadastrar' }}</button></div>
</div></div></form>
@endsection
