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
 * Rate Limiting Avançado com configurações específicas por endpoint
 * Usando Redis para máxima performance
 */
class RateLimitAvancadoMiddleware implements MiddlewareInterface {
    use RespostaAPI;

    private RateLimitService $rateLimitService;
    private array $configuracoes;
    private array $limitesDefault;

    public function __construct(RateLimitService $rateLimitService) {
        $this->rateLimitService = $rateLimitService;
        $this->configurarLimites();
    }

    private function configurarLimites(): void {
        // Limites padrão
        $this->limitesDefault = [
            'minuto' => 60,
            'hora' => 1000,
            'dia' => 10000
        ];

        // Configurações específicas por endpoint/método
        $this->configuracoes = [
            // Endpoints de autenticação - mais restritivos
            'POST:/api/auth/login' => [
                'minuto' => 5,    // Máximo 5 tentativas de login por minuto
                'hora' => 20,     // Máximo 20 tentativas de login por hora
                'dia' => 100      // Máximo 100 tentativas de login por dia
            ],
            'POST:/api/auth/refresh' => [
                'minuto' => 10,
                'hora' => 100,
                'dia' => 500
            ],
            
            // Endpoints de criação - moderadamente restritivos
            'POST:/api/clientes' => [
                'minuto' => 10,
                'hora' => 200,
                'dia' => 1000
            ],
            'POST:/api/sessoes' => [
                'minuto' => 20,
                'hora' => 500,
                'dia' => 2000
            ],
            
            // Endpoints de consulta - mais permissivos
            'GET:/api/clientes' => [
                'minuto' => 100,
                'hora' => 2000,
                'dia' => 20000
            ],
            'GET:/api/sessoes' => [
                'minuto' => 100,
                'hora' => 2000,
                'dia' => 20000
            ],
            
            // Endpoints administrativos - muito restritivos
            'DELETE:/api/clientes/*' => [
                'minuto' => 2,
                'hora' => 10,
                'dia' => 50
            ],
            'PUT:/api/admin/*' => [
                'minuto' => 5,
                'hora' => 50,
                'dia' => 200
            ]
        ];
    }

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        
        // Obter identificador e endpoint
        $identificador = $this->obterIdentificador($request);
        $endpoint = $this->obterEndpoint($request);
        
        // Obter limites específicos para este endpoint
        $limites = $this->obterLimitesParaEndpoint($endpoint);
        
        // Verificar rate limit
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
            // Identificador mais específico incluindo tipo de usuário
            $tipoUsuario = $usuario['tipo'] ?? 'unknown';
            return "user:{$tipoUsuario}:{$usuario['id']}";
        }

        // Fallback para IP + User-Agent (usuários anônimos)
        $ip = $this->obterIpReal($request);
        $userAgent = $request->getHeaderLine('User-Agent');
        $userAgentHash = substr(md5($userAgent), 0, 8);
        
        return "anon:ip:{$ip}:ua:{$userAgentHash}";
    }

    private function obterEndpoint(ServerRequestInterface $request): string {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        return "{$method}:{$path}";
    }

    private function obterLimitesParaEndpoint(string $endpoint): array {
        // Verificar configuração exata primeiro
        if (isset($this->configuracoes[$endpoint])) {
            return $this->configuracoes[$endpoint];
        }

        // Verificar padrões com wildcard
        foreach ($this->configuracoes as $padrao => $limites) {
            if (strpos($padrao, '*') !== false) {
                $regex = str_replace('*', '.*', preg_quote($padrao, '/'));
                if (preg_match("/^{$regex}$/", $endpoint)) {
                    return $limites;
                }
            }
        }

        // Retornar limites padrão
        return $this->limitesDefault;
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
        $mensagem = match($violacao['tipo']) {
            'minuto' => "Limite de {$limites['minuto']} requisições por minuto excedido",
            'hora' => "Limite de {$limites['hora']} requisições por hora excedido", 
            'dia' => "Limite de {$limites['dia']} requisições por dia excedido",
            default => "Rate limit excedido"
        };

        $dados = [
            'erro' => 'Rate limit excedido',
            'mensagem' => $mensagem,
            'detalhes' => [
                'tipo' => $violacao['tipo'],
                'limite' => $violacao['limite'],
                'atual' => $violacao['atual'],
                'reset_em_segundos' => $violacao['reset_em']
            ]
        ];

        $response = new \Slim\Psr7\Response();
        return $this->enviarResposta(
            $response, 
            StatusHttp::TOO_MANY_REQUESTS, 
            $mensagem, 
            $dados
        )->withHeader('Retry-After', $violacao['reset_em'])
         ->withHeader('X-RateLimit-Limit-Minute', $limites['minuto'])
         ->withHeader('X-RateLimit-Limit-Hour', $limites['hora'])
         ->withHeader('X-RateLimit-Limit-Day', $limites['dia'])
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
        $requestsAtuais = $contadores['minuto'];

        return $response
            ->withHeader('X-RateLimit-Limit-Minute', $limites['minuto'])
            ->withHeader('X-RateLimit-Limit-Hour', $limites['hora'])
            ->withHeader('X-RateLimit-Limit-Day', $limites['dia'])
            ->withHeader('X-RateLimit-Remaining', $restante)
            ->withHeader('X-RateLimit-Reset', time() + 60)
            ->withHeader('X-RateLimit-Used', $requestsAtuais);
    }
}
