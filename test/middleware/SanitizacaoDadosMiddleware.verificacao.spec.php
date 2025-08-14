<?php

use MinhaAgenda\Middleware\SanitizacaoDadosMiddleware;
use MinhaAgenda\Util\Sanitizador;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

describe('SanitizacaoDadosMiddleware - Verificação de Sanitização', function () {

    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->response = $this->responseFactory->createResponse();

        // Mock do handler que captura a requisição processada
        $this->processedRequest = null;
        $this->handler = new class ($this->response) implements RequestHandlerInterface {
            private $response;
            public $processedRequest;

            public function __construct(ResponseInterface $response) {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface {
                $this->processedRequest = $request;
                return $this->response;
            }

            public function getProcessedRequest(): ?ServerRequestInterface {
                return $this->processedRequest;
            }
        };
    });

    describe('verificação da sanitização do corpo da requisição', function () {
        it('deve sanitizar corretamente scripts XSS do corpo da requisição', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'nome' => '<script>alert("xss")</script>João',
                'descricao' => 'Texto com <b>HTML</b> e "aspas"',
                'email' => '  joao@email.com  '
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $middleware->process($request, $this->handler);

            $dadosProcessados = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que scripts foram removidos mas o conteúdo preservado
            expect($dadosProcessados['nome'])->not->toContain('<script>');
            expect($dadosProcessados['nome'])->toContain('alert'); // strip_tags remove tags mas não conteúdo
            expect($dadosProcessados['nome'])->toContain('João');

            // Verificar que HTML foi escapado
            expect($dadosProcessados['descricao'])->not->toContain('<b>');
            expect($dadosProcessados['descricao'])->toContain('Texto com');

            // Verificar que espaços foram removidos do email
            expect($dadosProcessados['email'])->toBe('joao@email.com');
        });

        it('deve sanitizar arrays aninhados corretamente', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'usuario' => [
                    'nome' => '<script>alert("hack")</script>Maria',
                    'endereco' => [
                        'rua' => 'Rua <b>Central</b> número 123',
                        'cidade' => 'São Paulo'
                    ]
                ]
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $middleware->process($request, $this->handler);

            $dadosProcessados = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar sanitização do nome
            expect($dadosProcessados['usuario']['nome'])->not->toContain('<script>');
            expect($dadosProcessados['usuario']['nome'])->toContain('Maria');

            // Verificar sanitização da rua
            expect($dadosProcessados['usuario']['endereco']['rua'])->not->toContain('<b>');
            expect($dadosProcessados['usuario']['endereco']['rua'])->toContain('Rua');
            expect($dadosProcessados['usuario']['endereco']['rua'])->toContain('Central');

            // Verificar que cidade não foi alterada (não tinha tags)
            expect($dadosProcessados['usuario']['endereco']['cidade'])->toBe('São Paulo');
        });

        it('deve preservar tipos não-string', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'nome' => '<script>João</script>',
                'idade' => 25,
                'ativo' => true,
                'peso' => 75.5,
                'observacoes' => null
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $middleware->process($request, $this->handler);

            $dadosProcessados = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que string foi sanitizada
            expect($dadosProcessados['nome'])->not->toContain('<script>');

            // Verificar que outros tipos permaneceram inalterados
            expect($dadosProcessados['idade'])->toBe(25);
            expect($dadosProcessados['ativo'])->toBe(true);
            expect($dadosProcessados['peso'])->toBe(75.5);
            expect($dadosProcessados['observacoes'])->toBe(null);
        });
    });

    describe('verificação da sanitização de parâmetros de query', function () {
        it('deve sanitizar parâmetros de query corretamente', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $parametrosOriginais = [
                'busca' => '<script>alert("xss")</script>termo',
                'categoria' => 'tecnologia & <b>inovação</b>',
                'limite' => '10'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withQueryParams($parametrosOriginais);

            $middleware->process($request, $this->handler);

            $parametrosProcessados = $this->handler->getProcessedRequest()->getQueryParams();

            // Verificar que script foi removido
            expect($parametrosProcessados['busca'])->not->toContain('<script>');
            expect($parametrosProcessados['busca'])->toContain('termo');

            // Verificar que HTML foi escapado
            expect($parametrosProcessados['categoria'])->not->toContain('<b>');
            expect($parametrosProcessados['categoria'])->toContain('tecnologia');
            expect($parametrosProcessados['categoria'])->toContain('inovação');

            // Verificar que string numérica foi preservada
            expect($parametrosProcessados['limite'])->toBe('10');
        });
    });

    describe('verificação da sanitização de cookies', function () {
        it('deve sanitizar cookies corretamente', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $cookiesOriginais = [
                'sessao' => '<script>alert("hack")</script>abc123',
                'preferencia' => 'dark_theme & <i>compact</i>',
                'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withCookieParams($cookiesOriginais);

            $middleware->process($request, $this->handler);

            $cookiesProcessados = $this->handler->getProcessedRequest()->getCookieParams();

            // Verificar que script foi removido
            expect($cookiesProcessados['sessao'])->not->toContain('<script>');
            expect($cookiesProcessados['sessao'])->toContain('abc123');

            // Verificar que HTML foi escapado
            expect($cookiesProcessados['preferencia'])->not->toContain('<i>');
            expect($cookiesProcessados['preferencia'])->toContain('dark_theme');
            expect($cookiesProcessados['preferencia'])->toContain('compact');

            // Verificar que token JWT foi preservado
            expect($cookiesProcessados['token'])->toBe('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9');
        });
    });

    describe('verificação de campos excluídos', function () {
        it('deve preservar campos excluídos sem sanitização', function () {
            $camposExcluidos = ['senha', 'token_acesso'];
            $middleware = new SanitizacaoDadosMiddleware(camposExcluidos: $camposExcluidos);

            $dadosOriginais = [
                'nome' => '<script>alert("xss")</script>João',
                'senha' => '<password>123456</password>',
                'token_acesso' => '<token>abc123xyz</token>',
                'descricao' => 'Descrição com <b>HTML</b>'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $middleware->process($request, $this->handler);

            $dadosProcessados = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que campos normais foram sanitizados
            expect($dadosProcessados['nome'])->not->toContain('<script>');
            expect($dadosProcessados['descricao'])->not->toContain('<b>');

            // Verificar que campos excluídos não foram sanitizados
            expect($dadosProcessados['senha'])->toBe('<password>123456</password>');
            expect($dadosProcessados['token_acesso'])->toBe('<token>abc123xyz</token>');
        });
    });

    describe('verificação de configurações desabilitadas', function () {
        it('não deve sanitizar corpo quando configuração estiver desabilitada', function () {
            $middleware = new SanitizacaoDadosMiddleware(limparCorpoRequisicao: false);

            $dadosOriginais = [
                'nome' => '<script>alert("xss")</script>João'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $middleware->process($request, $this->handler);

            $dadosProcessados = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que dados não foram sanitizados
            expect($dadosProcessados['nome'])->toBe('<script>alert("xss")</script>João');
        });

        it('não deve sanitizar parâmetros quando configuração estiver desabilitada', function () {
            $middleware = new SanitizacaoDadosMiddleware(limparParametros: false);

            $parametrosOriginais = [
                'busca' => '<script>alert("xss")</script>termo'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withQueryParams($parametrosOriginais);

            $middleware->process($request, $this->handler);

            $parametrosProcessados = $this->handler->getProcessedRequest()->getQueryParams();

            // Verificar que parâmetros não foram sanitizados
            expect($parametrosProcessados['busca'])->toBe('<script>alert("xss")</script>termo');
        });

        it('não deve sanitizar cookies quando configuração estiver desabilitada', function () {
            $middleware = new SanitizacaoDadosMiddleware(limparCookies: false);

            $cookiesOriginais = [
                'sessao' => '<script>alert("xss")</script>abc123'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withCookieParams($cookiesOriginais);

            $middleware->process($request, $this->handler);

            $cookiesProcessados = $this->handler->getProcessedRequest()->getCookieParams();

            // Verificar que cookies não foram sanitizados
            expect($cookiesProcessados['sessao'])->toBe('<script>alert("xss")</script>abc123');
        });
    });

    describe('casos extremos de sanitização', function () {
        it('deve lidar com strings com múltiplas tags HTML', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'conteudo' => '<div><script>alert("xss")</script><p>Parágrafo <b>negrito</b> <i>itálico</i></p></div>'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $middleware->process($request, $this->handler);

            $dadosProcessados = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que todas as tags foram removidas/escapadas
            expect($dadosProcessados['conteudo'])->not->toContain('<div>');
            expect($dadosProcessados['conteudo'])->not->toContain('<script>');
            expect($dadosProcessados['conteudo'])->not->toContain('<p>');
            expect($dadosProcessados['conteudo'])->not->toContain('<b>');
            expect($dadosProcessados['conteudo'])->not->toContain('<i>');

            // Verificar que o texto foi preservado
            expect($dadosProcessados['conteudo'])->toContain('Parágrafo');
            expect($dadosProcessados['conteudo'])->toContain('negrito');
            expect($dadosProcessados['conteudo'])->toContain('itálico');
        });

        it('deve lidar com strings com aspas e caracteres especiais', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'citacao' => 'Ele disse: "Isso é \'importante\'" & necessário.'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $middleware->process($request, $this->handler);

            $dadosProcessados = $this->handler->getProcessedRequest()->getParsedBody();

            // Verificar que aspas foram escapadas corretamente
            expect($dadosProcessados['citacao'])->toContain('Ele disse:');
            expect($dadosProcessados['citacao'])->toContain('importante');
            expect($dadosProcessados['citacao'])->toContain('necessário');
            // As aspas devem ser escapadas
            expect($dadosProcessados['citacao'])->not->toBe('Ele disse: "Isso é \'importante\'" & necessário.');
        });
    });
});
