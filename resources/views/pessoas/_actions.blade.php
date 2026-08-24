<div class="d-inline-flex gap-1">
    @if ($giPermissoes->permite('pessoas.visualizar'))<a href="{{ route('pessoas.show', $pessoa) }}" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar pessoa"><i class="bi bi-eye-fill"></i></a>@endif
    @if ($giPermissoes->permite('pessoas.vincular_locais'))
        <a href="{{ route('pessoas.edit', $pessoa) }}" class="btn btn-sm btn-outline-primary listagem-acao" title="Adicionar local à pessoa" aria-label="Adicionar local a {{ $pessoa->nome }}">
            <i class="bi bi-geo-alt-fill"></i>
        </a>
    @endif
</div>
