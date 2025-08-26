<?php

namespace MinhaAgenda\Service;

use Redis;

/**
 * Service de Rate Limiting usando Redis puro
 * Solução ultra-rápida e simples (~0.1ms por verificação)
 */
class RateLimitService {
    
    private Redis $redis;
    private int $ttlMinuto = 60;
    private int $ttlHora = 3600;
    private int $ttlDia = 86400;

    public function __construct(Redis $redis) {
        $this->redis = $redis;
    }

    /**
     * Verifica rate limit usando Redis (MUITO RÁPIDO ~0.1ms)
     */
    public function verificarRateLimit(string $identificador, array $limites): ?array {
        $agora = time();
        
        // Chaves do Redis
        $chaveMinuto = "rate_limit:minute:{$identificador}:" . floor($agora / 60);
        $chaveHora = "rate_limit:hour:{$identificador}:" . floor($agora / 3600);
        $chaveDia = "rate_limit:day:{$identificador}:" . floor($agora / 86400);

        // Verificar limites no Redis (SUPER RÁPIDO)
        $requestsMinuto = intval($this->redis->get($chaveMinuto) ?: 0);
        $requestsHora = intval($this->redis->get($chaveHora) ?: 0);
        $requestsDia = intval($this->redis->get($chaveDia) ?: 0);

        // Verificar violações
        if ($requestsMinuto >= $limites['minuto']) {
            return $this->criarViolacao('minuto', $limites['minuto'], $requestsMinuto, 60);
        }
        
        if ($requestsHora >= $limites['hora']) {
            return $this->criarViolacao('hora', $limites['hora'], $requestsHora, 3600);
        }
        
        if ($requestsDia >= $limites['dia']) {
            return $this->criarViolacao('dia', $limites['dia'], $requestsDia, 86400);
        }

        return null; // Sem violação
    }

    /**
     * Registra requisição no Redis usando pipeline (máxima performance)
     */
    public function registrarRequisicao(string $identificador): void {
        $agora = time();
        
        // Chaves do Redis
        $chaveMinuto = "rate_limit:minute:{$identificador}:" . floor($agora / 60);
        $chaveHora = "rate_limit:hour:{$identificador}:" . floor($agora / 3600);
        $chaveDia = "rate_limit:day:{$identificador}:" . floor($agora / 86400);

        // Pipeline para máxima performance
        $pipe = $this->redis->pipeline();
        $pipe->incr($chaveMinuto);
        $pipe->expire($chaveMinuto, $this->ttlMinuto);
        $pipe->incr($chaveHora);
        $pipe->expire($chaveHora, $this->ttlHora);
        $pipe->incr($chaveDia);
        $pipe->expire($chaveDia, $this->ttlDia);
        $pipe->exec();
    }

    /**
     * Obtém contadores atuais do Redis
     */
    public function obterContadores(string $identificador): array {
        $agora = time();
        
        $chaveMinuto = "rate_limit:minute:{$identificador}:" . floor($agora / 60);
        $chaveHora = "rate_limit:hour:{$identificador}:" . floor($agora / 3600);
        $chaveDia = "rate_limit:day:{$identificador}:" . floor($agora / 86400);

        return [
            'minuto' => intval($this->redis->get($chaveMinuto) ?: 0),
            'hora' => intval($this->redis->get($chaveHora) ?: 0),
            'dia' => intval($this->redis->get($chaveDia) ?: 0)
        ];
    }

    /**
     * Limpa todos os dados de rate limit (útil para desenvolvimento/testes)
     */
    public function limparTodos(): int {
        $chaves = $this->redis->keys('rate_limit:*');
        
        if (empty($chaves)) {
            return 0;
        }
        
        return $this->redis->del($chaves);
    }

    /**
     * Obtém estatísticas gerais (apenas do Redis)
     */
    public function obterEstatisticas(): array {
        $chaves = $this->redis->keys('rate_limit:*');
        
        $stats = [
            'total_chaves' => count($chaves),
            'chaves_minuto' => 0,
            'chaves_hora' => 0,
            'chaves_dia' => 0,
            'total_requests' => 0
        ];
        
        foreach ($chaves as $chave) {
            if (strpos($chave, ':minute:') !== false) {
                $stats['chaves_minuto']++;
            } elseif (strpos($chave, ':hour:') !== false) {
                $stats['chaves_hora']++;
            } elseif (strpos($chave, ':day:') !== false) {
                $stats['chaves_dia']++;
            }
            
            $valor = intval($this->redis->get($chave) ?: 0);
            $stats['total_requests'] += $valor;
        }
        
        return $stats;
    }

    private function criarViolacao(string $tipo, int $limite, int $atual, int $resetEm): array {
        return [
            'tipo' => $tipo,
            'limite' => $limite,
            'atual' => $atual,
            'reset_em' => $resetEm
        ];
    }
}
