<?php

namespace MinhaAgenda\Service;

use MinhaAgenda\Database\BancoDadosRedis;

/**
 * Service de Rate Limiting usando apenas Redis
 * Solução ultra-rápida e simples (~0.1ms por verificação)
 */
class RateLimitService {
    
    private BancoDadosRedis $redis;
    private int $ttlMinuto = 60;
    private int $ttlHora = 3600;
    private int $ttlDia = 86400;

    public function __construct(BancoDadosRedis $redis) {
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
        $requestsMinuto = $this->obterContador($chaveMinuto);
        $requestsHora = $this->obterContador($chaveHora);
        $requestsDia = $this->obterContador($chaveDia);

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
        $this->redis->iniciarTransacao();
        
        $this->redis->executar("INCR {$chaveMinuto}");
        $this->redis->executar("EXPIRE {$chaveMinuto}", [$this->ttlMinuto]);
        
        $this->redis->executar("INCR {$chaveHora}");
        $this->redis->executar("EXPIRE {$chaveHora}", [$this->ttlHora]);
        
        $this->redis->executar("INCR {$chaveDia}");
        $this->redis->executar("EXPIRE {$chaveDia}", [$this->ttlDia]);
        
        $this->redis->finalizarTransacao();
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
            'minuto' => $this->obterContador($chaveMinuto),
            'hora' => $this->obterContador($chaveHora),
            'dia' => $this->obterContador($chaveDia)
        ];
    }

    /**
     * Limpa todos os dados de rate limit (útil para desenvolvimento/testes)
     */
    public function limparTodos(): int {
        $resultado = $this->redis->consultar("KEYS rate_limit:*");
        $chaves = $resultado ?? [];
        
        if (empty($chaves)) {
            return 0;
        }
        
        $removidas = 0;
        foreach ($chaves as $chave) {
            if ($this->redis->executar("DEL {$chave}")) {
                $removidas++;
            }
        }
        
        return $removidas;
    }

    /**
     * Obtém estatísticas gerais (apenas do Redis)
     */
    public function obterEstatisticas(): array {
        $resultado = $this->redis->consultar("KEYS rate_limit:*");
        $chaves = $resultado ?? [];
        
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
            
            $valor = $this->obterContador($chave);
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

    /**
     * Helper: Obtém contador do Redis
     */
    private function obterContador(string $chave): int {
        $resultado = $this->redis->consultar("GET {$chave}");
        return intval($resultado[0] ?? 0);
    }
}
