@extends('layouts.app')

@section('title', 'Manutenção')

@section('content')
<div class="container-fluid px-0">
    <header class="mb-4">
        <h1 class="page-title">Manutenção</h1>
        <p class="page-description mb-0">Sessão criada com segurança pelo GI.</p>
    </header>

    @if(session('manutencao'))
        <div class="alert alert-success d-flex align-items-start gap-2" role="alert"><i class="bi bi-check-circle-fill"></i><div><strong class="d-block">Manutenção</strong>{{ session('manutencao') }}</div></div>
    @endif
    @if(data_get($context, 'atualizacao_usuarios.realizada'))
        <div class="alert alert-success d-flex align-items-start gap-2" role="alert"><i class="bi bi-people-fill"></i><div><strong class="d-block">Usuários atualizados</strong>O GI informou acréscimo de usuários e enviou {{ data_get($context, 'atualizacao_usuarios.total', 0) }} cadastro(s).</div></div>
    @endif
    <div id="executionContext" class="alert d-flex align-items-start gap-2" role="status"></div>

    <section class="card content-card mb-4">
        <div class="card-header"><h2 class="h6 fw-bold mb-0"><i class="bi bi-person-badge me-2"></i>Contexto do perfil</h2></div>
        <div class="card-body p-4"><div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3"><div class="card context-card"><div class="card-body"><small class="text-page-muted d-block mb-1">Usuário</small><strong>{{ data_get($context, 'usuario.nome', 'Não informado') }}</strong><div class="text-body-secondary small text-break">{{ data_get($context, 'usuario.email') }}</div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card context-card"><div class="card-body"><small class="text-page-muted d-block mb-1">Sistema</small><strong>{{ data_get($context, 'sistema.nome', 'Não informado') }}</strong><div class="text-body-secondary small">ID {{ data_get($context, 'sistema.id', '—') }}</div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card context-card"><div class="card-body"><small class="text-page-muted d-block mb-1">Perfil</small><strong>{{ data_get($context, 'perfil.nome', 'Não informado') }}</strong><div class="text-body-secondary small">ID {{ data_get($context, 'perfil.id', '—') }}</div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card context-card"><div class="card-body"><small class="text-page-muted d-block mb-1">Caminho solicitado</small><strong>{{ data_get($context, 'caminho', '/') }}</strong><div class="text-body-secondary small">Emitido em {{ data_get($context, 'emitido_em', '—') }}</div></div></div></div>
        </div></div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-header"><h2 class="h6 fw-bold mb-0"><i class="bi bi-shield-check me-2"></i>Permissões entregues para este perfil</h2></div>
        <div class="card-body p-4 d-flex flex-wrap gap-2">
            @forelse((array)data_get($context, 'permissoes', []) as $permission)
                <code class="badge text-bg-primary permission-badge px-2 py-2">{{ $permission }}</code>
            @empty
                <span class="text-body-secondary">Nenhuma permissão foi concedida.</span>
            @endforelse
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-6"><section class="card content-card h-100">
            <div class="card-header"><h2 class="h6 fw-bold mb-0"><i class="bi bi-cloud-download me-2"></i>Dados do GI</h2></div>
            <div class="card-body p-4 d-flex flex-wrap gap-2 align-content-start">
                <button class="btn btn-primary" data-resource="perfis"><i class="bi bi-person-vcard me-1"></i>Carregar perfis</button>
                <button class="btn btn-primary" data-resource="usuarios"><i class="bi bi-people me-1"></i>Carregar usuários</button>
                <button class="btn btn-primary" data-resource="grupos"><i class="bi bi-collection me-1"></i>Carregar grupos</button>
            </div>
        </section></div>
        <div class="col-12 col-xl-6"><section class="card content-card h-100">
            <div class="card-header"><h2 class="h6 fw-bold mb-0"><i class="bi bi-tools me-2"></i>Manutenção do Laravel</h2></div>
            <div class="card-body p-4 d-flex flex-wrap gap-2 align-content-start">
                <form method="POST" action="{{ route('manutencao.executar', 'optimize-clear') }}">@csrf<button class="btn btn-outline-primary" type="submit">php artisan optimize:clear</button></form>
                <form method="POST" action="{{ route('manutencao.executar', 'config-cache') }}">@csrf<button class="btn btn-outline-primary" type="submit">php artisan config:cache</button></form>
            </div>
        </section></div>
    </div>

    <section class="card content-card mb-4">
        <div class="card-header"><h2 class="h6 fw-bold mb-0"><i class="bi bi-braces me-2"></i>Contexto JSON recebido</h2></div>
        <div class="card-body p-4"><pre class="json-output">{{ json_encode($context, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre><pre id="result" class="json-output mt-3" hidden></pre></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    const contextBlock = document.getElementById('executionContext');
    const isInsideGi = window.self !== window.top;
    contextBlock.classList.add(isInsideGi ? 'alert-success' : 'alert-warning');
    contextBlock.innerHTML = isInsideGi
        ? '<i class="bi bi-check-circle-fill"></i><div><strong class="d-block">Executando dentro do GI</strong>Esta página está sendo exibida no ambiente integrado do sistema GI.</div>'
        : '<i class="bi bi-exclamation-triangle-fill"></i><div><strong class="d-block">Executando fora do GI</strong>Esta página foi aberta diretamente, fora do ambiente integrado do sistema GI.</div>';
    document.querySelectorAll('[data-resource]').forEach(button => button.addEventListener('click', async () => {
        const result = document.getElementById('result');
        result.hidden = false;
        result.textContent = 'Carregando...';
        try {
            const response = await fetch('/gi/' + button.dataset.resource, {headers: {'Accept': 'application/json'}});
            const json = await response.json();
            result.textContent = JSON.stringify(json, null, 2);
        } catch (error) {
            result.textContent = JSON.stringify({message: error.message}, null, 2);
        }
    }));
</script>
@endpush
