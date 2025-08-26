<?php

namespace MinhaAgenda\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use MinhaAgenda\Trait\RespostaAPI;
use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Service\RateLimitService;

/**
 * Middleware de Rate Limiting usando Redis
 * Ultra-rápido e eficiente
 */
class RateLimitMiddleware implements MiddlewareInterface {
    use RespostaAPI;

    private RateLimitService $rateLimitService;
    private int $limitePorMinuto;
    private int $limitePorHora;
    private int $limitePorDia;
    private bool $habilitado;

    public function __construct(
        RateLimitService $rateLimitService,
        int $limitePorMinuto = 60,    // 60 requests por minuto
        int $limitePorHora = 1000,    // 1000 requests por hora
        int $limitePorDia = 10000,    // 10.000 requests por dia
        bool $habilitado = true
    ) {
        $this->rateLimitService = $rateLimitService;
        $this->limitePorMinuto = $limitePorMinuto;
        $this->limitePorHora = $limitePorHora;
        $this->limitePorDia = $limitePorDia;
        $this->habilitado = $habilitado;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if (!$this->habilitado) {
            return $handler->handle($request);
        }

        // Obter identificador único para o rate limit
        $identificador = $this->obterIdentificador($request);

        // Definir limites para esta requisição
        $limites = [
            'minuto' => $this->limitePorMinuto,
            'hora' => $this->limitePorHora,
            'dia' => $this->limitePorDia
        ];

        // Verificar rate limit (SUPER RÁPIDO com Redis)
        $violacao = $this->rateLimitService->verificarRateLimit($identificador, $limites);

        if ($violacao) {
            return $this->enviarRespostaRateLimit($violacao, $limites);
        }

        // Registrar esta requisição
        $this->rateLimitService->registrarRequisicao($identificador);

        // Continuar processamento
        $response = $handler->handle($request);

        // Adicionar headers informativos
        return $this->adicionarHeadersRateLimit($response, $identificador, $limites);
    }

    private function obterIdentificador(ServerRequestInterface $request): string {
        // Tentar obter usuário autenticado
        $usuario = $request->getAttribute('usuario');
        if ($usuario && isset($usuario['id'])) {
            return "user:{$usuario['id']}";
        }

        // Fallback para IP + User-Agent (usuários anônimos)
        $ip = $this->obterIpReal($request);
        $userAgent = $request->getHeaderLine('User-Agent');
        $userAgentHash = substr(md5($userAgent), 0, 8);

        return "ip:{$ip}:ua:{$userAgentHash}";
    }

    private function obterIpReal(ServerRequestInterface $request): string {
        $serverParams = $request->getServerParams();

        // Verificar headers de proxy/load balancer
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Conexão direta
        ];

        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ip = trim(explode(',', $serverParams[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function enviarRespostaRateLimit(array $violacao, array $limites): ResponseInterface {
        $mensagem = match ($violacao['tipo']) {
            'minuto' => "Limite de {$limites['minuto']} requisições por minuto excedido",
            'hora' => "Limite de {$limites['hora']} requisições por hora excedido",
            'dia' => "Limite de {$limites['dia']} requisições por dia excedido",
            default => "Rate limit excedido"
        };

        $dados = [
            'erro' => 'Rate limit excedido',
            'mensagem' => $mensagem,
            'tipo' => $violacao['tipo'],
            'limite' => $violacao['limite'],
            'atual' => $violacao['atual'],
            'reset_em_segundos' => $violacao['reset_em']
        ];

        $response = new \Slim\Psr7\Response();
        return $this->enviarResposta(
            $response,
            StatusHttp::TOO_MANY_REQUESTS,
            $mensagem,
            $dados
        )->withHeader('Retry-After', $violacao['reset_em'])
            ->withHeader('X-RateLimit-Limit', $violacao['limite'])
            ->withHeader('X-RateLimit-Remaining', 0)
            ->withHeader('X-RateLimit-Reset', time() + $violacao['reset_em']);
    }

    private function adicionarHeadersRateLimit(
        ResponseInterface $response,
        string $identificador,
        array $limites
    ): ResponseInterface {
        // Obter contadores atuais
        $contadores = $this->rateLimitService->obterContadores($identificador);

        $restante = max(0, $limites['minuto'] - $contadores['minuto']);

        return $response
            ->withHeader('X-RateLimit-Limit', $limites['minuto'])
            ->withHeader('X-RateLimit-Remaining', $restante)
            ->withHeader('X-RateLimit-Reset', time() + 60)
            ->withHeader('X-RateLimit-Used', $contadores['minuto']);
    }
}
