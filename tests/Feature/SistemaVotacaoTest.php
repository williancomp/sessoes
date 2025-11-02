<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vereador;
use App\Models\Pauta;
use App\Models\Voto;
use App\Services\EstadoGlobalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class SistemaVotacaoTest extends TestCase
{
    use RefreshDatabase;

    protected $estadoGlobalService;
    protected $vereador;
    protected $pauta;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->estadoGlobalService = app(EstadoGlobalService::class);
        
        // Criar um vereador para testes
        $user = User::factory()->create();
        $this->vereador = Vereador::factory()->create(['user_id' => $user->id]);
        
        // Criar uma pauta para testes
        $this->pauta = Pauta::factory()->create();
    }

    /** @test */
    public function pode_obter_estado_global_via_api()
    {
        $response = $this->getJson('/api/estado-global');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'layout_atual',
                    'pauta_ativa',
                    'votacao_aberta',
                    'contagem_votos',
                    'presencas'
                ]);
    }

    /** @test */
    public function estado_global_persiste_no_cache()
    {
        // Definir um estado
        $this->estadoGlobalService->setLayoutAtual('votacao');
        $this->estadoGlobalService->setPautaAtiva($this->pauta->id);
        
        // Verificar se foi salvo no cache
        $this->assertTrue(Cache::has('estado_global'));
        
        // Obter estado e verificar dados
        $estado = $this->estadoGlobalService->obterEstadoCompleto();
        $this->assertEquals('votacao', $estado['layout_atual']);
        $this->assertEquals($this->pauta->id, $estado['pauta_ativa']);
    }

    /** @test */
    public function contagem_votos_e_atualizada_corretamente()
    {
        // Criar alguns votos
        Voto::factory()->create(['vereador_id' => $this->vereador->id, 'pauta_id' => $this->pauta->id, 'voto' => 'sim']);
        Voto::factory()->create(['vereador_id' => $this->vereador->id, 'pauta_id' => $this->pauta->id, 'voto' => 'nao']);
        
        // Calcular contagem
        $contagem = $this->estadoGlobalService->calcularContagemVotos($this->pauta->id);
        
        $this->assertEquals(1, $contagem['sim']);
        $this->assertEquals(1, $contagem['nao']);
        $this->assertEquals(0, $contagem['abst']);
    }

    /** @test */
    public function telao_recebe_estado_global()
    {
        // Definir estado
        $this->estadoGlobalService->setLayoutAtual('layout-inicial');
        
        $response = $this->get('/telao');
        
        $response->assertStatus(200);
        $response->assertSee('estado-global', false); // Verificar se meta tag existe
    }

    /** @test */
    public function portal_votacao_registra_voto_corretamente()
    {
        $this->actingAs($this->vereador->user);
        
        // Abrir votação
        $this->estadoGlobalService->setVotacaoAberta(true);
        $this->estadoGlobalService->setPautaAtiva($this->pauta->id);
        
        $response = $this->post('/vereador/portal-votacao/registrar-voto', [
            'voto' => 'sim'
        ]);
        
        $response->assertStatus(200);
        
        // Verificar se voto foi registrado
        $this->assertDatabaseHas('votos', [
            'vereador_id' => $this->vereador->id,
            'pauta_id' => $this->pauta->id,
            'voto' => 'sim'
        ]);
    }
}