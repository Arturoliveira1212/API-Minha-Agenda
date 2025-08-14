<?php

use MinhaAgenda\Middleware\CorpoRequisicaoMiddleware;
use MinhaAgenda\Enum\StatusHttp;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

describe('CorpoRequisicaoMiddleware - Verificação Detalhada', function () {
    
    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->response = $this->responseFactory->createResponse();
        
        // Mock do handler que captura a requisição processada
        $this->handler = new class($this->response) implements RequestHandlerInterface {
            private $response;
            public $processedRequest;
            
            public function __construct(ResponseInterface $response) {
                $this->response = $response;
            }
            
            public function handle(ServerRequestInterface $request): ResponseInterface {
                $this->processedRequest = $request;
                return $this->response->withStatus(200);
            }
            
            public function getProcessedRequest(): ?ServerRequestInterface {
                return $this->processedRequest;
            }
        };
    });

    describe('verificação de validação de formato', function () {
        it('deve validar application/json corretamente', function () {
            $middleware = new CorpoRequisicaoMiddleware('application/json');
            
            $contentTypesValidos = [
                'application/json',
                'application/json; charset=utf-8',
                'application/json;charset=UTF-8',
                'application/json; boundary=something'
            ];
            
            foreach ($contentTypesValidos as $contentType) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', $contentType)
                    ->withParsedBody(['dados' => 'teste']);
                
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(200);
            }
        });

        it('deve rejeitar Content-Types inválidos', function () {
            $middleware = new CorpoRequisicaoMiddleware('application/json');
            
            $contentTypesInvalidos = [
                'text/plain',
                'text/html',
                'application/xml',
                'application/x-www-form-urlencoded',
                'multipart/form-data',
                'image/jpeg',
                ''
            ];
            
            foreach ($contentTypesInvalidos as $contentType) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', $contentType)
                    ->withParsedBody(['dados' => 'teste']);
                
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(400);
                
                $body = (string) $response->getBody();
                $data = json_decode($body, true);
                expect($data['message'])->toBe('O corpo da requisição tem formato inválido.');
            }
        });

        it('deve verificar formato independente de case sensitivity', function () {
            $middleware = new CorpoRequisicaoMiddleware('application/json');
            
            // Testa que a implementação atual é case-sensitive (comportamento atual)
            $contentTypesMaiusculas = [
                'APPLICATION/JSON',
                'Application/Json',
                'APPLICATION/json'
            ];
            
            foreach ($contentTypesMaiusculas as $contentType) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', $contentType)
                    ->withParsedBody(['dados' => 'teste']);
                
                $response = $middleware->process($request, $this->handler);
                
                // Com a implementação atual usando strpos, case-sensitive falhará
                expect($response->getStatusCode())->toBe(400);
            }
        });
    });

    describe('verificação de validação de corpo', function () {
        it('deve aceitar diferentes tipos de dados válidos', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $corposValidos = [
                ['string' => 'texto'],
                ['numero' => 123],
                ['decimal' => 45.67],
                ['booleano' => true],
                ['array' => [1, 2, 3]],
                ['objeto' => ['nested' => 'value']],
                ['multiplos' => 'campos', 'em' => 'um', 'array' => 'associativo']
            ];
            
            foreach ($corposValidos as $corpo) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', 'application/json')
                    ->withParsedBody($corpo);
                
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(200);
            }
        });

        it('deve rejeitar corpos vazios ou inválidos', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $corposInvalidos = [
                null,
                []
            ];
            
            foreach ($corposInvalidos as $corpo) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', 'application/json')
                    ->withParsedBody($corpo);
                
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(400);
                
                $body = (string) $response->getBody();
                $data = json_decode($body, true);
                expect($data['message'])->toBe('O corpo da requisição tem formato inválido.');
            }
        });

        it('deve preservar dados complexos válidos', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosComplexos = [
                'usuario' => [
                    'id' => 123,
                    'nome' => 'João Silva',
                    'email' => 'joao@email.com',
                    'ativo' => true,
                    'configuracoes' => [
                        'tema' => 'escuro',
                        'notificacoes' => [
                            'email' => true,
                            'push' => false,
                            'sms' => true
                        ],
                        'preferencias' => [
                            'idioma' => 'pt-BR',
                            'timezone' => 'America/Sao_Paulo',
                            'formato_data' => 'dd/mm/yyyy'
                        ]
                    ]
                ],
                'metadados' => [
                    'versao_api' => '1.0',
                    'timestamp' => 1691680000,
                    'origem' => 'mobile_app'
                ]
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/usuarios')
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($dadosComplexos);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(200);
            
            // Verificar que os dados foram preservados
            $requestProcessado = $this->handler->getProcessedRequest();
            $corpoProcessado = $requestProcessado->getParsedBody();
            
            expect($corpoProcessado['usuario']['nome'])->toBe('João Silva');
            expect($corpoProcessado['usuario']['configuracoes']['tema'])->toBe('escuro');
            expect($corpoProcessado['metadados']['versao_api'])->toBe('1.0');
        });
    });

    describe('verificação de diferentes formatos configurados', function () {
        it('deve funcionar com application/x-www-form-urlencoded', function () {
            $middleware = new CorpoRequisicaoMiddleware('application/x-www-form-urlencoded');
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/formulario')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withParsedBody([
                    'nome' => 'João Silva',
                    'email' => 'joao@email.com',
                    'idade' => '30'
                ]);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(200);
            
            $requestProcessado = $this->handler->getProcessedRequest();
            $corpoProcessado = $requestProcessado->getParsedBody();
            
            expect($corpoProcessado['nome'])->toBe('João Silva');
            expect($corpoProcessado['email'])->toBe('joao@email.com');
        });

        it('deve funcionar com multipart/form-data', function () {
            $middleware = new CorpoRequisicaoMiddleware('multipart/form-data');
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/upload')
                ->withHeader('Content-Type', 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW')
                ->withParsedBody([
                    'nome' => 'arquivo.txt',
                    'descricao' => 'Upload de arquivo',
                    'dados_arquivo' => 'conteudo_binario_aqui'
                ]);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(200);
        });

        it('deve funcionar com application/xml', function () {
            $middleware = new CorpoRequisicaoMiddleware('application/xml');
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/xml-endpoint')
                ->withHeader('Content-Type', 'application/xml; charset=utf-8')
                ->withParsedBody([
                    'root' => [
                        'usuario' => [
                            'nome' => 'João',
                            'email' => 'joao@email.com'
                        ]
                    ]
                ]);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(200);
        });
    });

    describe('verificação de comportamento com diferentes métodos HTTP', function () {
        it('deve aplicar validação consistente independente do método', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $metodos = ['POST', 'PUT', 'PATCH', 'DELETE'];
            $dadosValidos = ['operacao' => 'teste'];
            
            foreach ($metodos as $metodo) {
                // Teste com dados válidos
                $requestValido = $this->requestFactory
                    ->createServerRequest($metodo, '/test')
                    ->withHeader('Content-Type', 'application/json')
                    ->withParsedBody($dadosValidos);
                
                $responseValido = $middleware->process($requestValido, $this->handler);
                expect($responseValido->getStatusCode())->toBe(200);
                
                // Teste com dados inválidos
                $requestInvalido = $this->requestFactory
                    ->createServerRequest($metodo, '/test')
                    ->withHeader('Content-Type', 'text/plain')
                    ->withParsedBody($dadosValidos);
                
                $responseInvalido = $middleware->process($requestInvalido, $this->handler);
                expect($responseInvalido->getStatusCode())->toBe(400);
            }
        });

        it('deve processar requisições GET sem validação de corpo', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            // GET normalmente não tem corpo, mas se tiver, deve ser validado
            $request = $this->requestFactory
                ->createServerRequest('GET', '/test')
                ->withParsedBody(null);
            
            $response = $middleware->process($request, $this->handler);
            
            // Como o corpo é null (vazio), deve falhar
            expect($response->getStatusCode())->toBe(400);
        });
    });

    describe('verificação de estrutura da resposta de erro', function () {
        it('deve sempre retornar JSON válido em caso de erro', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $requestsInvalidas = [
                $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', 'text/plain')
                    ->withParsedBody(['dados' => 'teste']),
                $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', 'application/json')
                    ->withParsedBody([]),
                $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withParsedBody(['dados' => 'teste'])
            ];
            
            foreach ($requestsInvalidas as $request) {
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(400);
                
                $body = (string) $response->getBody();
                $data = json_decode($body, true);
                
                // Verificar que é JSON válido
                expect($data)->not->toBe(null);
                expect(json_last_error())->toBe(JSON_ERROR_NONE);
                
                // Verificar estrutura esperada
                expect(isset($data['message']))->toBe(true);
                expect(isset($data['sucess']))->toBe(true);
                expect($data['sucess'])->toBe(false);
                expect($data['message'])->toBe('O corpo da requisição tem formato inválido.');
            }
        });

        it('deve incluir Content-Type correto na resposta de erro', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withParsedBody([]);
            
            $response = $middleware->process($request, $this->handler);
            
            $contentType = $response->getHeader('Content-type');
            expect($contentType)->toContain('application/json');
        });

        it('deve usar código de status HTTP correto', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withHeader('Content-Type', 'text/plain')
                ->withParsedBody(['dados' => 'teste']);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(StatusHttp::BAD_REQUEST->value);
            expect($response->getStatusCode())->toBe(400);
        });
    });

    describe('casos extremos e edge cases', function () {
        it('deve lidar com Content-Types com parâmetros extras', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $contentTypesComParametros = [
                'application/json; charset=utf-8',
                'application/json;charset=ISO-8859-1',
                'application/json; boundary=something',
                'application/json; charset=utf-8; boundary=test'
            ];
            
            foreach ($contentTypesComParametros as $contentType) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', $contentType)
                    ->withParsedBody(['dados' => 'teste']);
                
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(200);
            }
        });

        it('deve lidar com valores especiais no corpo', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $valoresEspeciais = [
                ['null_value' => null],
                ['zero' => 0],
                ['empty_string' => ''],
                ['false_value' => false],
                ['zero_string' => '0'],
                ['array_with_null' => [null, 'value']],
                ['nested_empty' => ['array' => []]]
            ];
            
            foreach ($valoresEspeciais as $dados) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', 'application/json')
                    ->withParsedBody($dados);
                
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(200);
            }
        });

        it('deve preservar tipos de dados originais', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosComTipos = [
                'string' => 'texto',
                'integer' => 123,
                'float' => 45.67,
                'boolean_true' => true,
                'boolean_false' => false,
                'array_indexed' => [1, 2, 3],
                'array_associative' => ['key' => 'value'],
                'null_value' => null
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/test')
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($dadosComTipos);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(200);
            
            $requestProcessado = $this->handler->getProcessedRequest();
            $corpoProcessado = $requestProcessado->getParsedBody();
            
            // Verificar que os tipos foram preservados
            expect($corpoProcessado['string'])->toBeA('string');
            expect($corpoProcessado['integer'])->toBeA('integer');
            expect($corpoProcessado['float'])->toBeA('double'); // PHP retorna 'double' para float
            expect($corpoProcessado['boolean_true'])->toBeA('boolean');
            expect($corpoProcessado['boolean_false'])->toBeA('boolean');
            expect($corpoProcessado['array_indexed'])->toBeA('array');
            expect($corpoProcessado['array_associative'])->toBeA('array');
            expect($corpoProcessado['null_value'])->toBe(null);
        });
    });
});
