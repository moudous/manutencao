<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AtivoController;
use App\Http\Controllers\LocalController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\AgendaPreventivaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\LancamentoController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\DespesaController;
use App\Http\Controllers\CorretivaController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ManutencaoController;
use App\Services\GiPessoaSynchronizer;

Route::get('/auth/gi', function (Request $request) {
    abort_unless($request->filled('code'), 400, 'Código ausente.');

    $response = Http::asForm()->timeout(10)->post(
        rtrim(config('gi.gi_url'), '/').'/integracoes/gi/trocar-codigo',
        [
            'client_id' => config('gi.client_id'),
            'client_secret' => config('gi.client_secret'),
            'code' => $request->string('code')->toString(),
        ],
    );

    abort_unless($response->successful(), 401, 'Não foi possível autenticar pelo GI.');

    $context = (array) $response->json('data');
    app(GiPessoaSynchronizer::class)->sync($context);
    if (! empty($context['atualizar'])) {
        $total = app(GiPessoaSynchronizer::class)->syncFromGi((string) $context['access_token']);
        $context['atualizacao_usuarios'] = ['realizada' => true, 'total' => $total];
    }
    $request->session()->regenerate();
    $request->session()->put('gi_context', $context);

    $destination = (string) $response->json('data.caminho', '/');
    if (! str_starts_with($destination, '/')
        || str_starts_with($destination, '//')
        || str_contains($destination, '\\')
        || str_contains($destination, '..')) {
        $destination = '/';
    }

    return redirect($destination);
})->name('auth.gi');

Route::get('/', function (Request $request) {
    abort_unless($request->session()->has('gi_context'), 401, 'Abra esta aplicação pelo menu do GI.');

    $visibleContext = $request->session()->get('gi_context');
    unset($visibleContext['access_token']);

    return response()
        ->view('session', ['context' => $visibleContext])
        ->header('Cache-Control', 'no-store');
});

Route::post('/manutencao/{acao}', function (Request $request, string $acao) {
    abort_unless($request->session()->has('gi_context'), 401);
    $comandos = ['optimize-clear' => 'optimize:clear', 'config-cache' => 'config:cache'];
    abort_unless(isset($comandos[$acao]), 404);

    $codigo = Artisan::call($comandos[$acao]);
    $mensagem = $codigo === 0
        ? "Comando php artisan {$comandos[$acao]} executado com sucesso."
        : "O comando php artisan {$comandos[$acao]} terminou com código {$codigo}.";

    return redirect('/')->with('manutencao', $mensagem);
})->name('manutencao.executar');

Route::prefix('manutencao')->name('manutencao.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [ManutencaoController::class, 'index'])->middleware('gi.permission:manutencao.listar')->name('index');
    Route::post('/{lancamento}/iniciar', [ManutencaoController::class, 'iniciar'])->middleware('gi.permission:manutencao.editar')->name('iniciar');
    Route::post('/{lancamento}/despesas/visualizar', [ManutencaoController::class, 'visualizarDespesas'])->middleware('gi.permission:manutencao.editar')->name('despesas.visualizar');
    Route::post('/{lancamento}/despesas', [ManutencaoController::class, 'adicionarDespesa'])->middleware('gi.permission:manutencao.editar')->name('despesas.store');
    Route::delete('/{lancamento}/despesas/{despesa}', [ManutencaoController::class, 'excluirDespesa'])->middleware('gi.permission:manutencao.editar')->name('despesas.destroy');
    Route::post('/{lancamento}/concluir', [ManutencaoController::class, 'concluir'])->middleware('gi.permission:manutencao.editar')->name('concluir');
});

Route::get('/gi/{resource}', function (Request $request, string $resource) {
    abort_unless($request->session()->has('gi_context'), 401);
    abort_unless(in_array($resource, ['perfis', 'usuarios', 'grupos'], true), 404);

    $upstreamResponse = Http::withToken($request->session()->get('gi_context.access_token'))
        ->acceptJson()->timeout(10)
        ->get(rtrim(config('gi.gi_url'), '/').'/api/integracoes/v1/'.$resource);

    return response($upstreamResponse->body(), $upstreamResponse->status())
        ->header(
            'Content-Type',
            $upstreamResponse->header('Content-Type') ?? 'application/json',
        );
});

Route::prefix('ativos')->name('ativos.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [AtivoController::class, 'index'])->middleware('gi.permission:ativos.listar')->name('index');
    Route::get('/dados', [AtivoController::class, 'data'])->middleware('gi.permission:ativos.listar')->name('data');
    Route::get('/create', [AtivoController::class, 'create'])->middleware('gi.permission:ativos.criar')->name('create');
    Route::post('/', [AtivoController::class, 'store'])->middleware('gi.permission:ativos.criar')->name('store');
    Route::get('/{id}', [AtivoController::class, 'show'])->middleware('gi.permission:ativos.visualizar')->name('show');
    Route::get('/{id}/edit', [AtivoController::class, 'edit'])->middleware('gi.permission:ativos.criar')->name('edit');
    Route::put('/{id}', [AtivoController::class, 'update'])->middleware('gi.permission:ativos.criar')->name('update');
    Route::patch('/{id}/restore', [AtivoController::class, 'restore'])->middleware('gi.permission:ativos.restaurar')->name('restore');
    Route::delete('/{id}/force', [AtivoController::class, 'forceDestroy'])->middleware('gi.permission:ativos.excluir_permanentemente')->name('force-destroy');
    Route::delete('/{id}', [AtivoController::class, 'destroy'])->middleware('gi.permission:ativos.excluir')->name('destroy');
});

Route::prefix('locais')->name('locais.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [LocalController::class, 'index'])->middleware('gi.permission:locais.listar')->name('index');
    Route::get('/dados', [LocalController::class, 'data'])->middleware('gi.permission:locais.listar')->name('data');
    Route::get('/create', [LocalController::class, 'create'])->middleware('gi.permission:locais.criar')->name('create');
    Route::post('/', [LocalController::class, 'store'])->middleware('gi.permission:locais.criar')->name('store');
    Route::get('/{id}', [LocalController::class, 'show'])->middleware('gi.permission:locais.visualizar')->name('show');
    Route::get('/{id}/edit', [LocalController::class, 'edit'])->middleware('gi.permission:locais.criar')->name('edit');
    Route::put('/{id}', [LocalController::class, 'update'])->middleware('gi.permission:locais.criar')->name('update');
    Route::patch('/{id}/restore', [LocalController::class, 'restore'])->middleware('gi.permission:locais.restaurar')->name('restore');
    Route::delete('/{id}/force', [LocalController::class, 'forceDestroy'])->middleware('gi.permission:locais.excluir_permanentemente')->name('force-destroy');
    Route::delete('/{id}', [LocalController::class, 'destroy'])->middleware('gi.permission:locais.excluir')->name('destroy');
});

Route::prefix('unidades')->name('unidades.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [UnidadeController::class, 'index'])->middleware('gi.permission:unidades.listar')->name('index');
    Route::get('/dados', [UnidadeController::class, 'data'])->middleware('gi.permission:unidades.listar')->name('data');
    Route::get('/create', [UnidadeController::class, 'create'])->middleware('gi.permission:unidades.criar')->name('create');
    Route::post('/', [UnidadeController::class, 'store'])->middleware('gi.permission:unidades.criar')->name('store');
    Route::get('/{id}', [UnidadeController::class, 'show'])->middleware('gi.permission:unidades.visualizar')->name('show');
    Route::get('/{id}/edit', [UnidadeController::class, 'edit'])->middleware('gi.permission:unidades.criar')->name('edit');
    Route::put('/{id}', [UnidadeController::class, 'update'])->middleware('gi.permission:unidades.criar')->name('update');
    Route::patch('/{id}/restore', [UnidadeController::class, 'restore'])->middleware('gi.permission:unidades.restaurar')->name('restore');
    Route::delete('/{id}/force', [UnidadeController::class, 'forceDestroy'])->middleware('gi.permission:unidades.excluir_permanentemente')->name('force-destroy');
    Route::delete('/{id}', [UnidadeController::class, 'destroy'])->middleware('gi.permission:unidades.excluir')->name('destroy');
});

Route::prefix('pessoas')->name('pessoas.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [PessoaController::class, 'index'])->middleware('gi.permission:pessoas.listar')->name('index');
    Route::get('/dados', [PessoaController::class, 'data'])->middleware('gi.permission:pessoas.listar')->name('data');
    Route::post('/importar', [PessoaController::class, 'import'])->middleware('gi.permission:pessoas.listar')->name('import');
    Route::get('/{pessoa}', [PessoaController::class, 'show'])->middleware('gi.permission:pessoas.visualizar')->name('show');
    Route::get('/{pessoa}/edit', [PessoaController::class, 'edit'])->middleware('gi.permission:pessoas.vincular_locais')->name('edit');
    Route::put('/{pessoa}', [PessoaController::class, 'update'])->middleware('gi.permission:pessoas.vincular_locais')->name('update');
});

Route::prefix('agenda')->name('agenda.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [AgendaPreventivaController::class, 'index'])->middleware('gi.permission:agenda.listar')->name('index');
    Route::get('/dados', [AgendaPreventivaController::class, 'data'])->middleware('gi.permission:agenda.listar')->name('data');
    Route::get('/create', [AgendaPreventivaController::class, 'create'])->middleware('gi.permission:agenda.criar')->name('create');
    Route::post('/', [AgendaPreventivaController::class, 'store'])->middleware('gi.permission:agenda.criar')->name('store');
    Route::get('/{agenda}/lancamentos', [LancamentoController::class, 'index'])->middleware('gi.permission:agenda.listar')->name('lancamentos.index');
    Route::get('/{agenda}/lancamentos/dados', [LancamentoController::class, 'data'])->middleware('gi.permission:agenda.listar')->name('lancamentos.data');
    Route::patch('/{agenda}/lancamentos/{lancamento}/etapa-2', [LancamentoController::class, 'updateEtapaDois'])->middleware('gi.permission:agenda.listar')->name('lancamentos.etapa-dois');
    Route::post('/{agenda}/lancamentos/{lancamento}/concluir', [LancamentoController::class, 'concluir'])->middleware('gi.permission:agenda.listar')->name('lancamentos.concluir');
    Route::get('/{agenda}/lancamentos/{lancamento}/orcamentos/dados', [OrcamentoController::class, 'data'])->middleware('gi.permission:agenda.listar')->name('lancamentos.orcamentos.data');
    Route::post('/{agenda}/lancamentos/{lancamento}/orcamentos', [OrcamentoController::class, 'store'])->middleware('gi.permission:agenda.listar')->name('lancamentos.orcamentos.store');
    Route::delete('/{agenda}/lancamentos/{lancamento}/orcamentos/{orcamento}', [OrcamentoController::class, 'destroy'])->middleware('gi.permission:agenda.listar')->name('lancamentos.orcamentos.destroy');
    Route::get('/{agenda}/lancamentos/{lancamento}/despesas/dados', [DespesaController::class, 'data'])->middleware('gi.permission:agenda.listar')->name('lancamentos.despesas.data');
    Route::post('/{agenda}/lancamentos/{lancamento}/despesas', [DespesaController::class, 'store'])->middleware('gi.permission:agenda.listar')->name('lancamentos.despesas.store');
    Route::delete('/{agenda}/lancamentos/{lancamento}/despesas/{despesa}', [DespesaController::class, 'destroy'])->middleware('gi.permission:agenda.listar')->name('lancamentos.despesas.destroy');
    Route::get('/{agenda}/lancamentos/{lancamento}', [LancamentoController::class, 'show'])->middleware('gi.permission:agenda.visualizar')->name('lancamentos.show');
    Route::get('/{id}', [AgendaPreventivaController::class, 'show'])->middleware('gi.permission:agenda.visualizar')->name('show');
    Route::get('/{id}/edit', [AgendaPreventivaController::class, 'edit'])->middleware('gi.permission:agenda.criar')->name('edit');
    Route::put('/{id}', [AgendaPreventivaController::class, 'update'])->middleware('gi.permission:agenda.criar')->name('update');
    Route::patch('/{id}/restore', [AgendaPreventivaController::class, 'restore'])->middleware('gi.permission:agenda.restaurar')->name('restore');
    Route::delete('/{id}/force', [AgendaPreventivaController::class, 'forceDestroy'])->middleware('gi.permission:agenda.excluir_permanentemente')->name('force-destroy');
    Route::delete('/{id}', [AgendaPreventivaController::class, 'destroy'])->middleware('gi.permission:agenda.excluir')->name('destroy');
});

Route::prefix('compras')->name('compras.')->middleware('gi.session')->group(function (): void {
    Route::get('/',[CompraController::class,'index'])->middleware('gi.permission:compras.listar')->name('index');
    Route::get('/dados',[CompraController::class,'data'])->middleware('gi.permission:compras.listar')->name('data');
    Route::get('/create',[CompraController::class,'create'])->middleware('gi.permission:compras.criar')->name('create');
    Route::post('/',[CompraController::class,'store'])->middleware('gi.permission:compras.criar')->name('store');
    Route::get('/{id}',[CompraController::class,'show'])->middleware('gi.permission:compras.visualizar')->name('show');
    Route::get('/{id}/edit',[CompraController::class,'edit'])->middleware('gi.permission:compras.criar')->name('edit');
    Route::put('/{id}',[CompraController::class,'update'])->middleware('gi.permission:compras.criar')->name('update');
    Route::patch('/{id}/restore',[CompraController::class,'restore'])->middleware('gi.permission:compras.restaurar')->name('restore');
    Route::delete('/{id}/force',[CompraController::class,'forceDestroy'])->middleware('gi.permission:compras.excluir_permanentemente')->name('force-destroy');
    Route::delete('/{id}',[CompraController::class,'destroy'])->middleware('gi.permission:compras.excluir')->name('destroy');
});

Route::prefix('corretiva')->name('corretiva.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [CorretivaController::class, 'index'])->middleware('gi.permission:corretiva.listar')->name('index');
    Route::get('/dados', [CorretivaController::class, 'data'])->middleware('gi.permission:corretiva.listar')->name('data');
    Route::get('/create', [CorretivaController::class, 'create'])->middleware('gi.permission:corretiva.criar')->name('create');
    Route::post('/', [CorretivaController::class, 'store'])->middleware('gi.permission:corretiva.criar')->name('store');
    Route::get('/{id}', [CorretivaController::class, 'show'])->middleware('gi.permission:corretiva.visualizar')->name('show');
    Route::get('/{id}/edit', [CorretivaController::class, 'edit'])->middleware('gi.permission:corretiva.editar')->name('edit');
    Route::put('/{id}', [CorretivaController::class, 'update'])->middleware('gi.permission:corretiva.editar')->name('update');
    Route::delete('/{id}', [CorretivaController::class, 'destroy'])->middleware('gi.permission:corretiva.excluir')->name('destroy');
});

Route::prefix('clinicas')->name('clinicas.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [ClinicaController::class, 'index'])->middleware('gi.permission:clinica.listar')->name('index');
    Route::get('/dados', [ClinicaController::class, 'data'])->middleware('gi.permission:clinica.listar')->name('data');
    Route::get('/create', [ClinicaController::class, 'create'])->middleware('gi.permission:clinica.criar')->name('create');
    Route::post('/', [ClinicaController::class, 'store'])->middleware('gi.permission:clinica.criar')->name('store');
    Route::get('/{id}', [ClinicaController::class, 'show'])->middleware('gi.permission:clinica.visualizar')->name('show');
    Route::get('/{id}/edit', [ClinicaController::class, 'edit'])->middleware('gi.permission:clinica.editar')->name('edit');
    Route::put('/{id}', [ClinicaController::class, 'update'])->middleware('gi.permission:clinica.editar')->name('update');
    Route::patch('/{id}/restore', [ClinicaController::class, 'restore'])->middleware('gi.permission:clinica.restaurar')->name('restore');
    Route::delete('/{id}/force', [ClinicaController::class, 'forceDestroy'])->middleware('gi.permission:clinica.excluir_permanentemente')->name('force-destroy');
    Route::delete('/{id}', [ClinicaController::class, 'destroy'])->middleware('gi.permission:clinica.excluir')->name('destroy');
});

Route::prefix('equipamentos')->name('equipamentos.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [EquipamentoController::class, 'index'])->middleware('gi.permission:equipamentos.listar')->name('index');
    Route::get('/dados', [EquipamentoController::class, 'data'])->middleware('gi.permission:equipamentos.listar')->name('data');
    Route::get('/create', [EquipamentoController::class, 'create'])->middleware('gi.permission:equipamentos.criar')->name('create');
    Route::post('/', [EquipamentoController::class, 'store'])->middleware('gi.permission:equipamentos.criar')->name('store');
    Route::get('/{id}', [EquipamentoController::class, 'show'])->middleware('gi.permission:equipamentos.visualizar')->name('show');
    Route::get('/{id}/edit', [EquipamentoController::class, 'edit'])->middleware('gi.permission:equipamentos.editar')->name('edit');
    Route::put('/{id}', [EquipamentoController::class, 'update'])->middleware('gi.permission:equipamentos.editar')->name('update');
    Route::patch('/{id}/restore', [EquipamentoController::class, 'restore'])->middleware('gi.permission:equipamentos.restaurar')->name('restore');
    Route::delete('/{id}/force', [EquipamentoController::class, 'forceDestroy'])->middleware('gi.permission:equipamentos.excluir_permanentemente')->name('force-destroy');
    Route::delete('/{id}', [EquipamentoController::class, 'destroy'])->middleware('gi.permission:equipamentos.excluir')->name('destroy');
});

Route::prefix('checklist')->name('checklist.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [ChecklistController::class, 'index'])->middleware('gi.permission:checklist.listar')->name('index');
    Route::post('/iniciar', [ChecklistController::class, 'start'])->middleware('gi.permission:checklist.criar')->name('start');
    Route::put('/{checklist}/item', [ChecklistController::class, 'updateItem'])->middleware('gi.permission:checklist.editar')->name('item');
    Route::post('/{checklist}/finalizar', [ChecklistController::class, 'finish'])->middleware('gi.permission:checklist.finalizar')->name('finish');
});

Route::prefix('checklist_terminados')->name('checklist_terminados.')->middleware('gi.session')->group(function (): void {
    Route::get('/', [ChecklistController::class, 'completedIndex'])->middleware('gi.permission:checklist.listar')->name('index');
    Route::get('/dados', [ChecklistController::class, 'completedData'])->middleware('gi.permission:checklist.listar')->name('data');
    Route::get('/{checklist}', [ChecklistController::class, 'completedShow'])->middleware('gi.permission:checklist.visualizar')->name('show');
});

Route::get('/consultorios', [ChecklistController::class, 'consultorios'])
    ->middleware(['gi.session', 'gi.permission:checklist.listar'])
    ->name('consultorios.index');
