@extends('layouts.app')

@section('title', 'Visualizar local')

@section('content')
<div class="mb-4"><h1 class="page-title">Visualizar local</h1><p class="page-description mb-0">Consulte todos os dados cadastrais do local.</p></div>
<div class="card content-card"><div class="card-header"><h5>Dados do local</h5></div><div class="card-body p-4">
    @php $campos = [['ID', $local->id], ['Título', $local->titulo], ['Unidade', $local->unidade?->titulo], ['Status', $local->trashed() ? 'Apagado' : ($local->ativo ? 'Ativo' : 'Inativo')], ['Cadastrado em', $local->criado_em?->format('d/m/Y H:i')], ['Atualizado em', $local->atualizado_em?->format('d/m/Y H:i')], ['Excluído em', $local->apagado_em?->format('d/m/Y H:i')]]; @endphp
    <div class="row g-3">@foreach ($campos as [$rotulo, $valor])<div class="col-md-6"><div class="form-label">{{ $rotulo }}</div><div class="form-control bg-body-tertiary h-auto text-break">{{ filled($valor) ? $valor : '—' }}</div></div>@endforeach</div>
    <div class="d-flex justify-content-end mt-4"><a href="{{ route('locais.index', ['include_deleted' => $local->trashed() ? 1 : null]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
</div></div>
@endsection
