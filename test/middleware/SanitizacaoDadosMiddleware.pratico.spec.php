<?php

use MinhaAgenda\Middleware\SanitizacaoDadosMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

describe('SanitizacaoDadosMiddleware - Exemplos Práticos', function () {

    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->response = $this->responseFactory->createResponse();

        // Mock do handler que captura a requisição processada
        $this->handler = new class ($this->response) implements \Psr\Http\Server\RequestHandlerInterface {
            private $response;
            public $processedRequest;

            public function __construct(\Psr\Http\Message\ResponseInterface $response) {
                $this->response = $response;
            }

            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
                $this->processedRequest = $request;
                return $this->response;
            }

            public function getProcessedRequest(): ?\Psr\Http\Message\ServerRequestInterface {
                return $this->processedRequest;
            }
        };
    });

    describe('cenários de uso real', function () {
        it('deve proteger contra ataques XSS em formulário de usuário', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            // Simula dados de um formulário de cadastro malicioso
            $dadosFormulario = [
                'nome' => 'João<script>document.location="http://malicious-site.com"</script>',
                'email' => 'joao@email.com<script>alert("xss")</script>',
                'biografia' => 'Desenvolvedor PHP <img src="x" onerror="alert(\'xss\')">',
                'website' => 'https://joao.dev"><script>evil()</script>'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/usuarios')
                ->withParsedBody($dadosFormulario);

            $middleware->process($request, $this->handler);
            $dadosLimpos = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que scripts maliciosos foram removidos (tags removidas, conteúdo escapado)
            expect($dadosLimpos['nome'])->not->toContain('<script>');
            expect($dadosLimpos['nome'])->toContain('document.location'); // conteúdo preservado, mas escapado
            expect($dadosLimpos['nome'])->toContain('João');

            expect($dadosLimpos['email'])->not->toContain('<script>');
            expect($dadosLimpos['email'])->toContain('joao@email.com');

            expect($dadosLimpos['biografia'])->not->toContain('<img');
            expect($dadosLimpos['biografia'])->not->toContain('onerror');
            expect($dadosLimpos['biografia'])->toContain('Desenvolvedor PHP');

            expect($dadosLimpos['website'])->not->toContain('<script>');
            expect($dadosLimpos['website'])->toContain('https://joao.dev');
        });

        it('deve permitir configuração específica para API de blog', function () {
            // Para uma API de blog, pode querer preservar alguns campos HTML
            $camposExcluidos = ['conteudo_html', 'codigo_css'];
            $middleware = new SanitizacaoDadosMiddleware(camposExcluidos: $camposExcluidos);

            $dadosPost = [
                'titulo' => 'Como usar <script>alert("xss")</script> JavaScript',
                'resumo' => 'Um artigo sobre <b>JavaScript</b> seguro',
                'conteudo_html' => '<div class="highlight"><code>console.log("Hello World");</code></div>',
                'codigo_css' => '.highlight { background: #f5f5f5; }',
                'tags' => ['javascript', 'web<script>evil()</script>']
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/posts')
                ->withParsedBody($dadosPost);

            $middleware->process($request, $this->handler);
            $dadosLimpos = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que campos normais foram sanitizados
            expect($dadosLimpos['titulo'])->not->toContain('<script>');
            expect($dadosLimpos['titulo'])->toContain('Como usar');
            expect($dadosLimpos['titulo'])->toContain('JavaScript');

            expect($dadosLimpos['resumo'])->not->toContain('<b>');
            expect($dadosLimpos['resumo'])->toContain('JavaScript');

            // Verificar que campos excluídos foram preservados
            expect($dadosLimpos['conteudo_html'])->toBe('<div class="highlight"><code>console.log("Hello World");</code></div>');
            expect($dadosLimpos['codigo_css'])->toBe('.highlight { background: #f5f5f5; }');

            // Verificar que arrays também são sanitizados
            expect($dadosLimpos['tags'][0])->toBe('javascript');
            expect($dadosLimpos['tags'][1])->not->toContain('<script>');
            expect($dadosLimpos['tags'][1])->toContain('web');
        });

        it('deve proteger parâmetros de busca contra injection', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            // Simula uma busca maliciosa
            $parametrosBusca = [
                'q' => 'term<script>window.location="http://evil.com"</script>',
                'categoria' => 'tech<img src=x onerror=alert(1)>',
                'ordem' => 'nome',
                'limite' => '10'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/buscar')
                ->withQueryParams($parametrosBusca);

            $middleware->process($request, $this->handler);
            $parametrosLimpos = $this->handler->getProcessedRequest()->getQueryParams();

            // Verificar que códigos maliciosos foram removidos (tags removidas, conteúdo escapado)
            expect($parametrosLimpos['q'])->not->toContain('<script>');
            expect($parametrosLimpos['q'])->toContain('window.location'); // conteúdo preservado, mas escapado
            expect($parametrosLimpos['q'])->toContain('term');

            expect($parametrosLimpos['categoria'])->not->toContain('<img');
            expect($parametrosLimpos['categoria'])->not->toContain('onerror');
            expect($parametrosLimpos['categoria'])->toContain('tech');

            // Verificar que parâmetros seguros não foram alterados
            expect($parametrosLimpos['ordem'])->toBe('nome');
            expect($parametrosLimpos['limite'])->toBe('10');
        });

        it('deve funcionar com middleware desabilitado seletivamente', function () {
            // Cenário: API que só precisa sanitizar corpo da requisição
            $middleware = new SanitizacaoDadosMiddleware(
                limparCorpoRequisicao: true,
                limparParametros: false,
                limparCookies: false
            );

            $request = $this->requestFactory
                ->createServerRequest('POST', '/dados')
                ->withParsedBody(['nome' => '<script>evil()</script>João'])
                ->withQueryParams(['debug' => '<script>console.log(1)</script>'])
                ->withCookieParams(['sessao' => '<script>steal()</script>abc123']);

            $middleware->process($request, $this->handler);
            $requestProcessado = $this->handler->getProcessedRequest();

            // Corpo deve ser sanitizado
            $corpo = $requestProcessado->getParsedBody();
            expect($corpo['nome'])->not->toContain('<script>');
            expect($corpo['nome'])->toContain('João');

            // Parâmetros e cookies NÃO devem ser sanitizados
            $parametros = $requestProcessado->getQueryParams();
            expect($parametros['debug'])->toBe('<script>console.log(1)</script>');

            $cookies = $requestProcessado->getCookieParams();
            expect($cookies['sessao'])->toBe('<script>steal()</script>abc123');
        });
    });

    describe('casos de performance e edge cases', function () {
        it('deve lidar eficientemente com grandes volumes de dados', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            // Simula upload de muitos dados
            $dadosGrandes = [];
            for ($i = 0; $i < 100; $i++) {
                $dadosGrandes["campo_$i"] = "valor $i com <script>alert($i)</script>";
            }

            $request = $this->requestFactory
                ->createServerRequest('POST', '/upload')
                ->withParsedBody($dadosGrandes);

            $inicio = microtime(true);
            $middleware->process($request, $this->handler);
            $tempoExecucao = microtime(true) - $inicio;

            $dadosLimpos = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que todos os campos foram processados
            expect(count($dadosLimpos))->toBe(100);

            // Verificar uma amostra dos dados
            expect($dadosLimpos['campo_0'])->not->toContain('<script>');
            expect($dadosLimpos['campo_50'])->toContain('valor 50');
            expect($dadosLimpos['campo_99'])->toContain('alert'); // conteúdo preservado, mas escapado

            // Verificar que a execução foi razoavelmente rápida (menos de 1 segundo)
            expect($tempoExecucao)->toBeLessThan(1.0);
        });

        it('deve preservar estruturas de dados complexas', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $estruturaComplexa = [
                'usuario' => [
                    'id' => 123,
                    'nome' => 'João<script>evil()</script>',
                    'configuracoes' => [
                        'tema' => 'dark',
                        'notificacoes' => true,
                        'bio' => 'Desenvolvedor <b>PHP</b>',
                        'redes_sociais' => [
                            'twitter' => '@joao<script>hack()</script>',
                            'github' => 'joao-dev'
                        ]
                    ]
                ],
                'permissoes' => ['read', 'write<script>perm()</script>'],
                'metadados' => [
                    'criado_em' => '2025-01-01',
                    'ativo' => true,
                    'score' => 95.5
                ]
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/usuario/atualizar')
                ->withParsedBody($estruturaComplexa);

            $middleware->process($request, $this->handler);
            $dadosLimpos = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que a estrutura foi preservada
            expect($dadosLimpos['usuario']['id'])->toBe(123);
            expect($dadosLimpos['usuario']['configuracoes']['tema'])->toBe('dark');
            expect($dadosLimpos['usuario']['configuracoes']['notificacoes'])->toBe(true);
            expect($dadosLimpos['metadados']['ativo'])->toBe(true);
            expect($dadosLimpos['metadados']['score'])->toBe(95.5);

            // Verificar que strings foram sanitizadas
            expect($dadosLimpos['usuario']['nome'])->not->toContain('<script>');
            expect($dadosLimpos['usuario']['nome'])->toContain('João');

            expect($dadosLimpos['usuario']['configuracoes']['bio'])->not->toContain('<b>');
            expect($dadosLimpos['usuario']['configuracoes']['bio'])->toContain('Desenvolvedor');

            expect($dadosLimpos['usuario']['configuracoes']['redes_sociais']['twitter'])->not->toContain('<script>');
            expect($dadosLimpos['usuario']['configuracoes']['redes_sociais']['twitter'])->toContain('@joao');

            expect($dadosLimpos['permissoes'][1])->not->toContain('<script>');
            expect($dadosLimpos['permissoes'][1])->toContain('write');
        });
    });
});
