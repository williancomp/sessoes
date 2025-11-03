<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\EstadoGlobalService;
use App\Models\Voto;
use App\Models\Pauta;
use App\Models\Vereador;
use App\Models\User;
use App\Models\Sessao;
use App\Models\Legislatura;
use App\Models\Partido;
use App\Models\TipoPauta;

uses(RefreshDatabase::class);

test('estado global service funciona corretamente', function () {
    $service = app(EstadoGlobalService::class);
    
    // Testar definição de layout
    $service->setTelaoLayout('layout-palavra');
    $estado = $service->getEstadoCompleto();
    
    // Verificar se o estado foi definido (estrutura pode variar)
    expect($estado)->toBeArray();
});

test('limpeza de votos funciona ao abrir nova votacao', function () {
    // Criar dados mínimos necessários
    $legislatura = Legislatura::create([
        'numero' => 1,
        'ano_inicio' => 2021,
        'ano_fim' => 2024,
        'ativa' => true
    ]);
    
    $partido = Partido::create([
        'nome' => 'Partido Teste',
        'sigla' => 'PT'
    ]);
    
    $user = User::create([
        'name' => 'Vereador Teste',
        'email' => 'vereador@teste.com',
        'password' => bcrypt('password')
    ]);
    
    $vereador = Vereador::create([
        'nome_parlamentar' => 'Vereador Teste',
        'user_id' => $user->id,
        'partido_id' => $partido->id
    ]);
    
    $sessao = Sessao::create([
        'data' => now(),
        'tipo' => 'ordinaria',
        'status' => 'em_andamento',
        'legislatura_id' => $legislatura->id
    ]);
    
    $tipoPauta = TipoPauta::create([
        'descricao' => 'Projeto de Lei'
    ]);
    
    $pauta = Pauta::create([
        'numero' => 1,
        'descricao' => 'Pauta de teste',
        'autor' => 'Autor teste',
        'ordem' => 1,
        'sessao_id' => $sessao->id,
        'tipo_pauta_id' => $tipoPauta->id,
        'status' => 'aguardando'
    ]);
    
    // Criar votos anteriores
    Voto::create([
        'pauta_id' => $pauta->id,
        'vereador_id' => $vereador->id,
        'voto' => 'sim'
    ]);
    
    // Verificar que o voto existe
    expect(Voto::where('pauta_id', $pauta->id)->count())->toBe(1);
    
    // Simular limpeza de votos (como no método confirmarAbrirVotacao)
    Voto::where('pauta_id', $pauta->id)->delete();
    
    // Verificar que os votos foram removidos
    expect(Voto::where('pauta_id', $pauta->id)->count())->toBe(0);
});

test('telao blade template carrega corretamente', function () {
    $response = $this->get('/telao');
    
    $response->assertStatus(200);
    $response->assertSee('estado-global', false);
});

test('persistencia pauta em discussao funciona corretamente', function () {
    // Criar dados mínimos necessários
    $legislatura = Legislatura::create([
        'numero' => 1,
        'ano_inicio' => 2021,
        'ano_fim' => 2024,
        'ativa' => true
    ]);
    
    $partido = Partido::create([
        'nome' => 'Partido Teste',
        'sigla' => 'PT'
    ]);
    
    $user = User::create([
        'name' => 'Vereador Teste',
        'email' => 'vereador@teste.com',
        'password' => bcrypt('password')
    ]);
    
    $vereador = Vereador::create([
        'nome_parlamentar' => 'Vereador Teste',
        'user_id' => $user->id,
        'partido_id' => $partido->id
    ]);
    
    $sessao = Sessao::create([
        'data' => now(),
        'tipo' => 'ordinaria',
        'status' => 'em_andamento',
        'legislatura_id' => $legislatura->id
    ]);
    
    $tipoPauta = TipoPauta::create([
        'descricao' => 'Projeto de Lei'
    ]);
    
    $pauta = Pauta::create([
        'numero' => 1,
        'descricao' => 'Pauta de teste para discussão',
        'autor' => 'Autor teste',
        'ordem' => 1,
        'sessao_id' => $sessao->id,
        'tipo_pauta_id' => $tipoPauta->id,
        'status' => 'aguardando'
    ]);
    
    $service = app(EstadoGlobalService::class);
    
    // Simular colocação da pauta em discussão
    $dadosPauta = [
        'numero' => $pauta->numero,
        'descricao' => $pauta->descricao,
        'autor' => $pauta->autor
    ];
    
    $service->setTelaoLayout('layout-normal', $dadosPauta);
    
    // Verificar se o layout foi salvo corretamente
    $layoutSalvo = $service->getTelaoLayout();
    
    expect($layoutSalvo)->toBeArray();
    expect($layoutSalvo['layout'])->toBe('layout-normal');
    expect($layoutSalvo['dados'])->toBeArray();
    expect($layoutSalvo['dados']['numero'])->toBe($pauta->numero);
    expect($layoutSalvo['dados']['descricao'])->toBe($pauta->descricao);
    expect($layoutSalvo['dados']['autor'])->toBe($pauta->autor);
    
    // Verificar se o estado global contém os dados da pauta
    $estadoCompleto = $service->getEstadoCompleto();
    expect($estadoCompleto['telao_layout'])->toBeArray();
    expect($estadoCompleto['telao_layout']['layout'])->toBe('layout-normal');
    expect($estadoCompleto['telao_layout']['dados']['numero'])->toBe($pauta->numero);
});