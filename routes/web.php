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
        $directory = Http::withToken($context['access_token'])->acceptJson()->timeout(10)
            ->get(rtrim(config('gi.gi_url'), '/').'/api/integracoes/v1/usuarios');
        abort_unless($directory->successful(), 502, 'Não foi possível atualizar os usuários pelo GI.');
        $total = app(GiPessoaSynchronizer::class)->syncMany((array) $directory->json('data', []));
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
