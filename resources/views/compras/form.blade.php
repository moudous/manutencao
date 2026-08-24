@extends('layouts.app')
@section('title',$isEdit?'Editar compra':'Adicionar compra')
@section('content')
<div class="mb-4"><h1 class="page-title">{{ $isEdit?'Editar compra':'Adicionar compra' }}</h1><p class="page-description mb-0">Preencha os dados da compra.</p></div>
<form method="POST" action="{{ $isEdit?route('compras.update',$compra->id):route('compras.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="card content-card"><div class="card-header"><h5>Dados da compra</h5></div><div class="card-body p-4">
@if($errors->any())<div class="alert alert-danger"><strong>Verifique os campos.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row g-3">
    <div class="col-md-8"><label class="form-label" for="titulo">Título</label><input id="titulo" name="titulo" maxlength="100" class="form-control" value="{{ old('titulo',$compra->titulo) }}"></div>
    <div class="col-md-4"><label class="form-label" for="unidade">Unidade de medida</label><select id="unidade" name="unidade" class="form-select"><option value="">Selecione</option>@foreach(['lts'=>'Litros (lts)','kg'=>'Quilogramas (kg)','un'=>'Unidade (un)'] as $value=>$label)<option value="{{ $value }}" @selected(old('unidade',$compra->unidade)===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="quantidade">Quantidade de pacotes</label><input id="quantidade" name="quantidade" type="number" min="0" step="any" class="form-control" value="{{ old('quantidade',$compra->quantidade) }}"></div>
    <div class="col-md-4"><label class="form-label" for="quantidade_unitaria">Quantidade unitária no pacote</label><input id="quantidade_unitaria" name="quantidade_unitaria" type="number" min="0" step="any" class="form-control" value="{{ old('quantidade_unitaria',$compra->quantidade_unitaria) }}"></div>
    <div class="col-md-4"><label class="form-label" for="preco">Preço</label><div class="input-group"><span class="input-group-text">R$</span><input id="preco" name="preco" type="number" min="0" step="0.01" class="form-control" value="{{ old('preco',$compra->preco) }}"></div></div>
    <div class="col-md-6"><label class="form-label" for="comprador">Comprador</label><input id="comprador" name="comprador" maxlength="50" class="form-control" value="{{ old('comprador',$compra->comprador) }}"></div>
    <div class="col-md-6"><label class="form-label" for="data_compra">Data da compra</label><input id="data_compra" name="data_compra" type="date" class="form-control" value="{{ old('data_compra',$compra->data_compra?->format('Y-m-d')) }}"></div>
    <div class="col-12"><input type="hidden" name="disponivel" value="0"><div class="form-check form-switch"><input id="disponivel" name="disponivel" value="1" type="checkbox" class="form-check-input" @checked((bool)old('disponivel',$compra->disponivel))><label for="disponivel" class="form-check-label">Disponível</label></div></div>
</div>

<h6 class="fw-bold mt-4 mb-3">Resumo de quantidades e valores</h6>
<div class="row g-3">
    <div class="col-sm-6 col-xl-3"><div class="card border-primary-subtle bg-primary-subtle h-100"><div class="card-body"><label for="quantidade_total" class="form-label fw-semibold text-primary-emphasis">Quantidade total</label><input id="quantidade_total" class="form-control form-control-lg fw-bold text-primary-emphasis bg-white" disabled></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card border-danger-subtle bg-danger-subtle h-100"><div class="card-body"><label for="qtde_utilizada" class="form-label fw-semibold text-danger-emphasis">Quantidade utilizada</label><input id="qtde_utilizada" name="qtde_utilizada" class="form-control form-control-lg fw-bold text-danger bg-white" value="{{ old('qtde_utilizada',$compra->qtde_utilizada??0) }}" disabled></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card border-success-subtle bg-success-subtle h-100"><div class="card-body"><label for="quantidade_disponivel" class="form-label fw-semibold text-success-emphasis">Quantidade disponível</label><input id="quantidade_disponivel" class="form-control form-control-lg fw-bold text-success bg-white" disabled></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card border-danger-subtle bg-danger-subtle h-100"><div class="card-body"><label for="valor_gasto" class="form-label fw-semibold text-danger-emphasis">Valor gasto</label><input id="valor_gasto" class="form-control form-control-lg fw-bold text-danger bg-white" disabled></div></div></div>
</div>
<div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('compras.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $isEdit?'Salvar':'Cadastrar' }}</button></div>
</div></div></form>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const number=id=>Number.parseFloat(document.getElementById(id).value)||0;
    const decimal=value=>new Intl.NumberFormat('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(value);
    const currency=value=>new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(value);
    const calculate=()=>{const total=number('quantidade')*number('quantidade_unitaria');const used=number('qtde_utilizada');document.getElementById('quantidade_total').value=decimal(total);document.getElementById('quantidade_disponivel').value=decimal(total-used);document.getElementById('valor_gasto').value=currency(used*number('preco'));};
    ['quantidade','quantidade_unitaria','preco'].forEach(id=>document.getElementById(id).addEventListener('input',calculate));calculate();
});
</script>
@endpush
