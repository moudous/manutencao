@extends('layouts.app')

@section('title', 'Visualizar ativo')

@section('content')
<div class="mb-4"><h1 class="page-title">Visualizar ativo</h1><p class="page-description mb-0">Consulte todos os dados cadastrais do ativo.</p></div>
<div class="card content-card">
    <div class="card-header"><h2 class="h5 fw-bold mb-0">Dados do ativo</h2></div>
    <div class="card-body p-4">
        @php
            $campos = [
                ['ID', $ativo->id, 'col-md-4'], ['Código', $ativo->codigo, 'col-md-4'], ['Status', $ativo->trashed() ? 'Apagado' : ($ativo->ativo ? 'Ativo' : 'Inativo'), 'col-md-4'],
                ['Título', $ativo->titulo, 'col-12'], ['Descrição', $ativo->descricao, 'col-12'], ['Data de aquisição', $ativo->data_aquisicao?->format('d/m/Y'), 'col-md-4'],
                ['Local', $ativo->local?->titulo, 'col-md-4'], ['Cadastrado em', $ativo->criado_em?->format('d/m/Y H:i'), 'col-md-4'], ['Atualizado em', $ativo->atualizado_em?->format('d/m/Y H:i'), 'col-md-4'], ['Excluído em', $ativo->apagado_em?->format('d/m/Y H:i'), 'col-md-4'],
            ];
        @endphp
        <div class="row g-3">@foreach ($campos as [$rotulo, $valor, $coluna])<div class="{{ $coluna }}"><div class="form-label">{{ $rotulo }}</div><div class="form-control bg-body-tertiary h-auto text-break">{{ filled($valor) ? $valor : '—' }}</div></div>@endforeach</div>
        <div class="d-flex justify-content-end mt-4"><a href="{{ route('ativos.index', ['include_deleted' => $ativo->trashed() ? 1 : null]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
    </div>
</div>
@endsection
