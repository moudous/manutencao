@extends('layouts.app')

@section('title', 'Visualizar pessoa')

@section('content')
@if (session('status'))<div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="mb-4"><h1 class="page-title">Visualizar pessoa</h1><p class="page-description mb-0">Consulte os dados sincronizados e o local associado.</p></div>
<div class="card content-card"><div class="card-header"><h5>Dados da pessoa</h5></div><div class="card-body p-4">
    @php $campos = [['ID do usuário no GI', $pessoa->id], ['Nome', $pessoa->nome], ['E-mail', $pessoa->email], ['Perfil', $pessoa->perfil], ['ID do perfil', $pessoa->perfil_id], ['Local', $pessoa->local?->titulo], ['Status', $pessoa->ativo ? 'Ativa' : 'Inativa'], ['Data de cadastro', $pessoa->criado_em?->format('d/m/Y H:i')], ['Última sincronização', $pessoa->atualizado_em?->format('d/m/Y H:i')]]; @endphp
    <div class="row g-3">@foreach ($campos as [$rotulo, $valor])<div class="col-md-6"><div class="form-label">{{ $rotulo }}</div><div class="form-control bg-body-tertiary h-auto text-break">{{ filled($valor) ? $valor : '—' }}</div></div>@endforeach</div>
    <div class="d-flex justify-content-end gap-2 mt-4">@if ($giPermissoes->permite('pessoas.vincular_locais'))<a href="{{ route('pessoas.edit', $pessoa) }}" class="btn btn-primary"><i class="bi bi-geo-alt-fill me-1"></i>Associar local</a>@endif<a href="{{ route('pessoas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
</div></div>
@endsection
