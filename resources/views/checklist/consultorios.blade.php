@extends('layouts.app')
@section('title', 'Consultórios')

@push('styles')
<style>
    .overview-card .card-header { overflow-x: auto; }
    .overview-title { white-space: nowrap; font-size: 1rem; font-weight: 700; margin: 0; }
    .overview-title .badge { font-size: .78rem; vertical-align: .08rem; }
    .room-grid { display: flex; flex-wrap: wrap; gap: .75rem; }
    .room-button { width: 52px; height: 52px; border: 0; border-radius: 50%; display: grid; place-items: center; font-weight: 700; }
    .room-button { transition: transform .2s, box-shadow .2s; }
    .room-button:hover { transform: translateY(-2px); box-shadow: 0 .3rem .7rem rgba(0, 0, 0, .15); }
    .room-empty { background: #e9ecef; color: #6c757d; }
    .room-partial { background: var(--bs-info); color: #052c65; }
    .room-complete { background: var(--bs-success); color: #fff; }
    .room-warning { background: var(--bs-warning); color: #332701; }
    .room-danger { background: var(--bs-danger); color: #fff; }
    .filters-card .btn-check + .btn { min-width: 112px; }
</style>
@endpush

@section('content')
@php($turnos = ['m' => 'Manhã', 't' => 'Tarde', 'n' => 'Noite'])
<div class="container-fluid py-4 px-3 px-lg-4">
    <div class="mb-4">
        <h1 class="page-title">Consultórios</h1>
        <p class="page-description mb-0">Visão geral dos consultórios verificados em todas as clínicas.</p>
    </div>

    <form id="consultoriosFilters" method="GET" action="{{ route('consultorios.index') }}" class="card content-card filters-card mb-4">
        <div class="card-header"><h5>Filtros</h5></div>
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label for="clinica" class="form-label">Clínicas</label>
                    <select id="clinica" name="clinica" class="form-select">
                        <option value="">Todas as clínicas</option>
                        @foreach($clinicas as $clinica)
                            <option value="{{ $clinica->id }}" @selected((string) request('clinica') === (string) $clinica->id)>{{ $clinica->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-7">
                    <div class="form-label">Período</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['hoje' => 'Hoje', 'esta_semana' => 'Esta semana', 'semana_passada' => 'Semana passada', 'este_mes' => 'Este mês', 'personalizada' => 'Data personalizada'] as $valor => $rotulo)
                            <input class="btn-check" type="radio" name="periodo" id="periodo_{{ $valor }}" value="{{ $valor }}" @checked($periodo === $valor)>
                            <label class="btn btn-outline-primary" for="periodo_{{ $valor }}">{{ $rotulo }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="col-sm-8 col-lg-2">
                    <label for="data" class="form-label">Data</label>
                    <input id="data" name="data" type="date" class="form-control @error('data') is-invalid @enderror" value="{{ request('data') }}">
                    @error('data')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('consultorios.index') }}" class="btn btn-outline-secondary">Limpar</a>
                    <button class="btn btn-primary"><i class="bi bi-funnel-fill me-1"></i>Filtrar</button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-4">
        @if($checklists->isEmpty())
            <div class="col-12">
                <div class="card content-card"><div class="card-body p-5 text-center text-body-secondary">
                    <i class="bi bi-search fs-1 d-block mb-2"></i>Nenhum checklist encontrado para os filtros selecionados.
                </div></div>
            </div>
        @else
            @foreach($checklists as $checklist)
            @php($duracaoFim = $checklist->fim ?? now())
            @php($duracao = $checklist->inicio ? $checklist->inicio->diff($duracaoFim)->format('%H:%I:%S') : '—')
            @php($naoResolvidos = (int) $checklist->problemas_nao_resolvidos)
            @php($resolvidos = (int) $checklist->problemas_resolvidos)
            <div class="col-12">
                <div class="card content-card overview-card h-100">
                    <div class="card-header py-3">
                        <h2 class="overview-title">
                            {{ $checklist->clinica?->titulo ?: 'Clínica não informada' }} -
                            {{ $checklist->inicio?->format('d/m/Y') ?: '—' }} -
                            {{ $turnos[$checklist->turno] ?? '—' }} -
                            ({{ (int) $checklist->clinica?->consultorios }} consultórios) -
                            ({{ $duracao }}) -
                            @if($naoResolvidos > 0)<span class="badge text-bg-danger">{{ $naoResolvidos }} {{ $naoResolvidos === 1 ? 'problema não resolvido' : 'problemas não resolvidos' }}</span> -@endif
                            @if($resolvidos > 0)<span class="badge text-bg-warning">{{ $resolvidos }} {{ $resolvidos === 1 ? 'problema resolvido' : 'problemas resolvidos' }}</span> -@endif
                            Responsável: {{ $checklist->pessoaResponsavel?->nome ?: '—' }}
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="room-grid">
                            @for($room = 1; $room <= (int) $checklist->clinica?->consultorios; $room++)
                                @php($status = $checklist->status_consultorios[$room] ?? 'empty')
                                <button type="button" class="room-button room-{{ $status }}" data-checklist="{{ $checklist->id }}" data-room="{{ $room }}" title="Visualizar consultório {{ $room }}" aria-label="Visualizar consultório {{ $room }}">{{ $room }}</button>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    @if($checklists->hasPages())
        <nav class="mt-4" aria-label="Paginação dos checklists">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item @disabled($checklists->onFirstPage())">
                    <a class="page-link" href="{{ $checklists->previousPageUrl() ?: '#' }}">Anterior</a>
                </li>
                <li class="page-item disabled"><span class="page-link">Página {{ $checklists->currentPage() }} de {{ $checklists->lastPage() }}</span></li>
                <li class="page-item @disabled(! $checklists->hasMorePages())">
                    <a class="page-link" href="{{ $checklists->nextPageUrl() ?: '#' }}">Próxima</a>
                </li>
            </ul>
        </nav>
    @endif
</div>

<div class="modal fade" id="consultorioModal" tabindex="-1" aria-labelledby="consultorioModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 id="consultorioModalTitle" class="modal-title">Consultório</h5>
                    <div id="consultorioModalSubtitle" class="small text-body-secondary"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div id="consultorioModalBody" class="modal-body"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filters = document.getElementById('consultoriosFilters');
    const clinic = document.getElementById('clinica');
    const date = document.getElementById('data');
    const custom = document.getElementById('periodo_personalizada');
    const syncDate = () => { date.disabled = !custom.checked; if (!custom.checked) date.value = ''; };
    const submitFilters = () => filters.requestSubmit();

    clinic.addEventListener('change', submitFilters);
    document.querySelectorAll('[name="periodo"]').forEach(input => input.addEventListener('change', () => {
        syncDate();
        if (input.value === 'personalizada') {
            date.focus();
            return;
        }
        submitFilters();
    }));
    date.addEventListener('change', () => {
        if (!date.value) return;
        custom.checked = true;
        date.disabled = false;
        submitFilters();
    });
    syncDate();

    const checklists = @json($dadosModal);
    const modalElement = document.getElementById('consultorioModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const title = document.getElementById('consultorioModalTitle');
    const subtitle = document.getElementById('consultorioModalSubtitle');
    const body = document.getElementById('consultorioModalBody');
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));

    document.querySelectorAll('.room-button[data-checklist]').forEach(button => button.addEventListener('click', () => {
        const checklist = checklists[button.dataset.checklist];
        const room = Number(button.dataset.room);
        const items = checklist?.itens.filter(item => item.consultorio === room) ?? [];
        title.textContent = `Consultório ${room}`;
        subtitle.textContent = `${checklist?.clinica ?? '—'} - ${checklist?.data ?? '—'}`;
        body.innerHTML = items.length ? items.map(item => `
            <div class="equipment-row py-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <strong>${escapeHtml(item.equipamento)}</strong>
                    <span class="badge ${item.estado === 1 ? 'text-bg-success' : (item.estado === 2 ? 'text-bg-warning' : 'text-bg-danger')}">${item.estado === 1 ? 'OK' : (item.estado === 2 ? 'Problema resolvido' : 'Problema não resolvido')}</span>
                </div>
                ${item.problema ? `<div class="alert ${item.estado === 0 ? 'alert-danger' : 'alert-warning'} mt-2 mb-0">${escapeHtml(item.problema)}</div>` : ''}
            </div>`).join('') : '<div class="text-center text-body-secondary py-4"><i class="bi bi-info-circle fs-2 d-block mb-2"></i>Nenhuma verificação registrada neste consultório.</div>';
        modal.show();
    }));
});
</script>
@endpush
