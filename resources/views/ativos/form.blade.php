@extends('layouts.app')

@section('title', $isEdit ? 'Editar ativo' : 'Adicionar ativo')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="mb-4"><h1 class="page-title">{{ $isEdit ? 'Editar ativo' : 'Adicionar ativo' }}</h1><p class="page-description mb-0">{{ $isEdit ? 'Atualize os dados do ativo.' : 'Preencha os dados do novo ativo.' }}</p></div>
<form method="POST" action="{{ $isEdit ? route('ativos.update', $ativo->id) : route('ativos.store') }}">
    @csrf @if ($isEdit) @method('PUT') @endif
    <div class="card content-card">
        <div class="card-header"><h2 class="h5 fw-bold mb-0">Dados do ativo</h2></div>
        <div class="card-body p-4">
            @if ($errors->any())<div class="alert alert-danger"><strong>Verifique os campos informados.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-md-4"><label for="codigo" class="form-label">Código</label><input id="codigo" name="codigo" class="form-control @error('codigo') is-invalid @enderror" maxlength="50" autofocus value="{{ old('codigo', $ativo->codigo) }}">@error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-8"><label for="titulo" class="form-label">Título</label><input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" maxlength="250" value="{{ old('titulo', $ativo->titulo) }}">@error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label for="data_aquisicao" class="form-label">Data de aquisição</label><input id="data_aquisicao" name="data_aquisicao" type="date" class="form-control @error('data_aquisicao') is-invalid @enderror" value="{{ old('data_aquisicao', $ativo->data_aquisicao?->format('Y-m-d')) }}">@error('data_aquisicao')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label for="locais_id" class="form-label">Local</label><select id="locais_id" name="locais_id" class="form-select @error('locais_id') is-invalid @enderror"><option value="">Selecione um local</option>@foreach ($locais as $local)<option value="{{ $local->id }}" @selected((string) old('locais_id', $ativo->locais_id) === (string) $local->id)>{{ $local->titulo ?: "Local #{$local->id}" }}{{ $local->trashed() ? ' (apagado)' : (! $local->ativo ? ' (inativo)' : '') }}</option>@endforeach</select>@error('locais_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label for="descricao" class="form-label">Descrição</label><textarea id="descricao" name="descricao" class="form-control @error('descricao') is-invalid @enderror" rows="4">{{ old('descricao', $ativo->descricao) }}</textarea>@error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><input type="hidden" name="ativo" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked((bool) old('ativo', $ativo->ativo))><label class="form-check-label" for="ativo">Ativo</label></div></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('ativos.index') }}" class="btn btn-outline-secondary">Cancelar</a><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Salvar' : 'Cadastrar' }}</button></div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        $('#locais_id').select2({theme: 'bootstrap-5', placeholder: 'Selecione ou pesquise um local', allowClear: true, width: '100%'});
    });
</script>
@endpush
