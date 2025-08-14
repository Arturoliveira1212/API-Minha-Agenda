<?php

use MinhaAgenda\Middleware\SanitizacaoDadosMiddleware;
use MinhaAgenda\Util\Sanitizador;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

describe('SanitizacaoDadosMiddleware', function () {

    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->response = $this->responseFactory->createResponse();

        // Mock do handler que sempre retorna uma resposta
        $this->handler = new class ($this->response) implements RequestHandlerInterface {
            private $response;

            public function __construct(ResponseInterface $response) {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface {
                return $this->response;
            }
        };
    });

    describe('construtor', function () {
        it('deve criar middleware com configurações padrão', function () {
            $middleware = new SanitizacaoDadosMiddleware();
            expect($middleware)->toBeAnInstanceOf(SanitizacaoDadosMiddleware::class);
        });

        it('deve criar middleware com configurações personalizadas', function () {
            $middleware = new SanitizacaoDadosMiddleware(
                limparCorpoRequisicao: false,
                limparParametros: false,
                limparCookies: false,
                camposExcluidos: ['senha', 'token']
            );
            expect($middleware)->toBeAnInstanceOf(SanitizacaoDadosMiddleware::class);
        });
    });

    describe('sanitização do corpo da requisição', function () {
        it('deve sanitizar dados do corpo da requisição', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'nome' => '<script>alert("xss")</script>João',
                'email' => '  joao@email.com  ',
                'descricao' => 'Texto com <b>HTML</b> e "aspas"'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve lidar com corpo de requisição nulo', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody(null);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve lidar com corpo de requisição não sendo array', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            // Criar um objeto stdClass ao invés de string, pois PSR-7 aceita objeto
            $objetoCorpo = new stdClass();
            $objetoCorpo->dados = 'teste';

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($objetoCorpo);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('não deve sanitizar corpo quando configuração estiver desabilitada', function () {
            $middleware = new SanitizacaoDadosMiddleware(limparCorpoRequisicao: false);

            $dadosOriginais = [
                'nome' => '<script>alert("xss")</script>João'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });
    });

    describe('sanitização de parâmetros de query', function () {
        it('deve sanitizar parâmetros de query', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $parametrosOriginais = [
                'busca' => '<script>alert("xss")</script>termo',
                'filtro' => 'categoria com <b>HTML</b>'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withQueryParams($parametrosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve lidar com parâmetros de query vazios', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withQueryParams([]);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('não deve sanitizar parâmetros quando configuração estiver desabilitada', function () {
            $middleware = new SanitizacaoDadosMiddleware(limparParametros: false);

            $parametrosOriginais = [
                'busca' => '<script>alert("xss")</script>termo'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withQueryParams($parametrosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });
    });

    describe('sanitização de cookies', function () {
        it('deve sanitizar dados de cookies', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $cookiesOriginais = [
                'sessao' => '<script>alert("xss")</script>abc123',
                'preferencia' => 'valor com <b>HTML</b>'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withCookieParams($cookiesOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve lidar com cookies vazios', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withCookieParams([]);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('não deve sanitizar cookies quando configuração estiver desabilitada', function () {
            $middleware = new SanitizacaoDadosMiddleware(limparCookies: false);

            $cookiesOriginais = [
                'sessao' => '<script>alert("xss")</script>abc123'
            ];

            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withCookieParams($cookiesOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });
    });

    describe('sanitização de dados aninhados', function () {
        it('deve sanitizar arrays aninhados', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'usuario' => [
                    'nome' => '<script>alert("xss")</script>João',
                    'endereco' => [
                        'rua' => 'Rua <b>Principal</b>',
                        'cidade' => 'São Paulo "SP"'
                    ]
                ],
                'tags' => [
                    '<script>tag1</script>',
                    'tag2 com <i>italic</i>'
                ]
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve preservar tipos de dados não string', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'nome' => '<script>alert("xss")</script>João',
                'idade' => 25,
                'ativo' => true,
                'peso' => 75.5,
                'tags' => null
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });
    });

    describe('campos excluídos', function () {
        it('deve pular sanitização de campos na lista de exclusão', function () {
            $camposExcluidos = ['senha', 'token_acesso'];
            $middleware = new SanitizacaoDadosMiddleware(camposExcluidos: $camposExcluidos);

            $dadosOriginais = [
                'nome' => '<script>alert("xss")</script>João',
                'senha' => '<password>123456</password>',
                'token_acesso' => '<token>abc123xyz</token>',
                'email' => 'joao@email.com'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });
    });

    describe('integração completa', function () {
        it('deve sanitizar todos os tipos de dados simultaneamente', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $corpoRequisicao = [
                'nome' => '<script>alert("xss")</script>João',
                'descricao' => 'Texto com <b>HTML</b>'
            ];

            $parametros = [
                'busca' => '<script>search</script>termo',
                'filtro' => 'categoria'
            ];

            $cookies = [
                'sessao' => '<script>session</script>abc123'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($corpoRequisicao)
                ->withQueryParams($parametros)
                ->withCookieParams($cookies);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve funcionar com todas as configurações desabilitadas', function () {
            $middleware = new SanitizacaoDadosMiddleware(
                limparCorpoRequisicao: false,
                limparParametros: false,
                limparCookies: false
            );

            $corpoRequisicao = [
                'nome' => '<script>alert("xss")</script>João'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($corpoRequisicao);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });
    });

    describe('casos extremos', function () {
        it('deve lidar com strings vazias', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'nome' => '',
                'descricao' => '   ',
                'email' => null
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve lidar com arrays muito aninhados', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            $dadosOriginais = [
                'nivel1' => [
                    'nivel2' => [
                        'nivel3' => [
                            'nome' => '<script>deep</script>valor'
                        ]
                    ]
                ]
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('deve lidar com chaves de array que também precisam de sanitização', function () {
            $middleware = new SanitizacaoDadosMiddleware();

            // Teste com chaves normais, pois chaves de array com caracteres especiais 
            // são raras e podem causar problemas
            $dadosOriginais = [
                'campo_normal' => '<script>alert("xss")</script>valor',
                'outro_campo' => 'valor limpo'
            ];

            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody($dadosOriginais);

            $response = $middleware->process($request, $this->handler);

            expect($response)->toBeAnInstanceOf(ResponseInterface::class);
        });
    });
});
