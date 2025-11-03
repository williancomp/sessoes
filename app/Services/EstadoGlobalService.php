<?php

namespace App\Services;

use App\Models\Pauta;
use App\Models\Sessao;
use App\Models\Vereador;
use App\Models\Voto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciar estado global persistente
 * Garante sincronização entre múltiplas abas/sessões
 */
class EstadoGlobalService
{
    // Chaves do cache para diferentes estados
    const CACHE_SESSAO_ATIVA = 'estado_global:sessao_ativa';
    const CACHE_TELAO_LAYOUT = 'estado_global:telao_layout';
    const CACHE_VOTACAO_ATIVA = 'estado_global:votacao_ativa';
    const CACHE_CONTAGEM_VOTOS = 'estado_global:contagem_votos';
    const CACHE_PRESENCA = 'estado_global:presenca';
    const CACHE_PALAVRA_ATIVA = 'estado_global:palavra_ativa';
    
    // TTL padrão para cache (24 horas)
    const CACHE_TTL = 86400;

    /**
     * Obtém o estado completo do sistema
     */
    public function getEstadoCompleto(): array
    {
        return [
            'sessao_ativa' => $this->getSessaoAtiva(),
            'telao_layout' => $this->getTelaoLayout(),
            'votacao_ativa' => $this->getVotacaoAtiva(),
            'contagem_votos' => $this->getContagemVotos(),
            'presenca' => $this->getPresenca(),
            'palavra_ativa' => $this->getPalavraAtiva(),
            'timestamp' => now()->timestamp,
        ];
    }

    /**
     * Sessão Ativa
     */
    public function getSessaoAtiva(): ?array
    {
        $cached = Cache::get(self::CACHE_SESSAO_ATIVA);
        
        if (!$cached) {
            $sessao = Sessao::where('status', 'em_andamento')->first();
            if ($sessao) {
                $cached = [
                    'id' => $sessao->id,
                    'data' => $sessao->data->format('Y-m-d'),
                    'tipo' => $sessao->tipo,
                    'status' => $sessao->status,
                ];
                $this->setSessaoAtiva($cached);
            }
        }
        
        return $cached;
    }

    public function setSessaoAtiva(?array $sessao): void
    {
        if ($sessao) {
            Cache::put(self::CACHE_SESSAO_ATIVA, $sessao, self::CACHE_TTL);
        } else {
            Cache::forget(self::CACHE_SESSAO_ATIVA);
        }
        
        Log::info('Estado Global: Sessão ativa atualizada', ['sessao' => $sessao]);
    }

    /**
     * Layout do Telão
     */
    public function getTelaoLayout(): array
    {
        return Cache::get(self::CACHE_TELAO_LAYOUT, [
            'layout' => 'layout-normal',
            'dados' => null,
            'timestamp' => now()->timestamp,
        ]);
    }

    public function setTelaoLayout(string $layout, ?array $dados = null): void
    {
        $estado = [
            'layout' => $layout,
            'dados' => $dados,
            'timestamp' => now()->timestamp,
        ];
        
        Cache::put(self::CACHE_TELAO_LAYOUT, $estado, self::CACHE_TTL);
        Log::info('Estado Global: Layout do telão atualizado', $estado);
    }

    /**
     * Votação Ativa
     */
    public function getVotacaoAtiva(): ?array
    {
        $cached = Cache::get(self::CACHE_VOTACAO_ATIVA);
        
        if (!$cached) {
            $sessao = $this->getSessaoAtiva();
            if ($sessao) {
                $pauta = Pauta::where('sessao_id', $sessao['id'])
                    ->where('status', 'em_votacao')
                    ->first();
                    
                if ($pauta) {
                    $cached = [
                        'pauta_id' => $pauta->id,
                        'pauta_numero' => $pauta->numero,
                        'pauta_descricao' => $pauta->descricao,
                        'pauta_autor' => $pauta->autor,
                        'iniciada_em' => now()->timestamp,
                    ];
                    $this->setVotacaoAtiva($cached);
                }
            }
        }
        
        return $cached;
    }

    public function setVotacaoAtiva(?array $votacao): void
    {
        if ($votacao) {
            Cache::put(self::CACHE_VOTACAO_ATIVA, $votacao, self::CACHE_TTL);
        } else {
            Cache::forget(self::CACHE_VOTACAO_ATIVA);
        }
        
        Log::info('Estado Global: Votação ativa atualizada', ['votacao' => $votacao]);
    }

    /**
     * Contagem de Votos
     */
    public function getContagemVotos(): array
    {
        $cached = Cache::get(self::CACHE_CONTAGEM_VOTOS);
        
        if (!$cached) {
            $votacao = $this->getVotacaoAtiva();
            if ($votacao) {
                $cached = $this->calcularContagemVotos($votacao['pauta_id']);
                $this->setContagemVotos($cached);
            } else {
                $cached = ['sim' => 0, 'nao' => 0, 'abst' => 0, 'total' => 0];
            }
        }
        
        return $cached;
    }

    public function setContagemVotos(array $contagem): void
    {
        Cache::put(self::CACHE_CONTAGEM_VOTOS, $contagem, self::CACHE_TTL);
        Log::info('Estado Global: Contagem de votos atualizada', $contagem);
    }

    private function calcularContagemVotos(int $pautaId): array
    {
        $votos = Voto::where('pauta_id', $pautaId)->get();
        
        $contagem = [
            'sim' => $votos->where('voto', 'sim')->count(),
            'nao' => $votos->where('voto', 'nao')->count(),
            'abst' => $votos->where('voto', 'abst')->count(),
        ];
        
        $contagem['total'] = $contagem['sim'] + $contagem['nao'] + $contagem['abst'];
        
        return $contagem;
    }

    /**
     * Presença
     */
    public function getPresenca(): array
    {
        $cached = Cache::get(self::CACHE_PRESENCA);
        
        if (!$cached) {
            $sessao = $this->getSessaoAtiva();
            if ($sessao) {
                $cached = $this->calcularPresenca($sessao['id']);
                $this->setPresenca($cached);
            } else {
                // Se não há sessão ativa, ainda assim calcula baseado no total de vereadores
                $totalVereadores = Vereador::count();
                $cached = [
                    'presentes' => 0, 
                    'ausentes' => $totalVereadores, 
                    'total' => $totalVereadores
                ];
                
                Log::info('Estado Global: Presença calculada sem sessão ativa', $cached);
            }
        }
        
        return $cached;
    }

    public function setPresenca(array $presenca): void
    {
        Cache::put(self::CACHE_PRESENCA, $presenca, self::CACHE_TTL);
        Log::info('Estado Global: Presença atualizada', $presenca);
    }

    private function calcularPresenca(int $sessaoId): array
    {
        $totalVereadores = Vereador::count();
        
        // Se não há vereadores cadastrados, retorna zeros
        if ($totalVereadores === 0) {
            Log::warning('Estado Global: Nenhum vereador cadastrado no sistema');
            return [
                'presentes' => 0,
                'ausentes' => 0,
                'total' => 0,
            ];
        }
        
        $presentes = \App\Models\Presenca::where('sessao_id', $sessaoId)
            ->where('presente', true)
            ->count();
        
        $ausentes = $totalVereadores - $presentes;
        
        // Log para debug
        Log::debug('Estado Global: Presença calculada', [
            'sessao_id' => $sessaoId,
            'total_vereadores' => $totalVereadores,
            'presentes' => $presentes,
            'ausentes' => $ausentes
        ]);
        
        return [
            'presentes' => $presentes,
            'ausentes' => $ausentes,
            'total' => $totalVereadores,
        ];
    }

    public function getPalavraAtiva(): ?array
    {
        return Cache::get(self::CACHE_PALAVRA_ATIVA);
    }

    /**
     * NOVO: Define o estado da palavra
     * @param ?array $estado [
     * 'vereador' => ['id' => 1, 'nome_parlamentar' => '...'],
     * 'status' => 'running', 'paused', 'stopped',
     * 'segundos_restantes' => 300,
     * 'timestamp_inicio' => 123456789 (quando começou/retomou)
     * ]
     */
    public function setPalavraAtiva(?array $estado): void
    {
        if ($estado) {
            Cache::put(self::CACHE_PALAVRA_ATIVA, $estado, self::CACHE_TTL);
        } else {
            Cache::forget(self::CACHE_PALAVRA_ATIVA);
        }
        
        Log::info('Estado Global: Palavra ativa atualizada', $estado ?? []);
    }

    /**
     * Limpa todo o estado global
     */
    public function limparEstado(): void
    {
        Cache::forget(self::CACHE_SESSAO_ATIVA);
        Cache::forget(self::CACHE_TELAO_LAYOUT);
        Cache::forget(self::CACHE_VOTACAO_ATIVA);
        Cache::forget(self::CACHE_CONTAGEM_VOTOS);
        Cache::forget(self::CACHE_PRESENCA);
        Cache::forget(self::CACHE_PALAVRA_ATIVA);
        
        Log::info('Estado Global: Todos os estados foram limpos');
    }

    /**
     * Força atualização de todos os estados a partir do banco
     */
    public function atualizarEstadoDoBanco(): void
    {
        // Limpa cache atual
        $this->limparEstado();
        
        // Recarrega estados do banco
        $this->getSessaoAtiva();
        $this->getVotacaoAtiva();
        $this->getContagemVotos();
        $this->getPresenca();
        
        Log::info('Estado Global: Estados atualizados do banco de dados');
    }

    /**
     * Verifica se o estado está sincronizado
     */
    public function verificarSincronizacao(): bool
    {
        $sessao = $this->getSessaoAtiva();
        if (!$sessao) return true;
        
        // Verifica se a sessão ainda existe no banco
        $sessaoBanco = Sessao::find($sessao['id']);
        if (!$sessaoBanco || $sessaoBanco->status !== 'em_andamento') {
            $this->limparEstado();
            return false;
        }
        
        return true;
    }
}