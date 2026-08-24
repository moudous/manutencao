@extends('layouts.app')

@section('title', $isEdit ? 'Editar local' : 'Adicionar local')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="mb-4"><h1 class="page-title">{{ $isEdit ? 'Editar local' : 'Adicionar local' }}</h1><p class="page-description mb-0">{{ $isEdit ? 'Atualize os dados do local.' : 'Preencha os dados do novo local.' }}</p></div>
<form method="POST" action="{{ $isEdit ? route('locais.update', $local->id) : route('locais.store') }}">@csrf @if ($isEdit) @method('PUT') @endif
    <div class="card content-card"><div class="card-header"><h5>Dados do local</h5></div><div class="card-body p-4">
        @if ($errors->any())<div class="alert alert-danger"><strong>Verifique os campos informados.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="row g-3">
            <div class="col-md-8"><label for="titulo" class="form-label">Título</label><input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" maxlength="250" autofocus value="{{ old('titulo', $local->titulo) }}">@error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label for="unidades_id" class="form-label">Unidade</label><select id="unidades_id" name="unidades_id" class="form-select @error('unidades_id') is-invalid @enderror"><option value="">Selecione uma unidade</option>@foreach ($unidades as $unidade)<option value="{{ $unidade->id }}" @selected((string) old('unidades_id', $local->unidades_id) === (string) $unidade->id)>{{ $unidade->titulo ?: "Unidade #{$unidade->id}" }}{{ $unidade->trashed() ? ' (apagada)' : (! $unidade->ativo ? ' (inativa)' : '') }}</option>@endforeach</select>@error('unidades_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><input type="hidden" name="ativo" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked((bool) old('ativo', $local->ativo))><label class="form-check-label" for="ativo">Ativo</label></div></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('locais.index') }}" class="btn btn-outline-secondary">Cancelar</a><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Salvar' : 'Cadastrar' }}</button></div>
    </div></div>
</form>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        $('#unidades_id').select2({theme: 'bootstrap-5', placeholder: 'Selecione ou pesquise uma unidade', allowClear: true, width: '100%'});
    });
</script>
@endpush
