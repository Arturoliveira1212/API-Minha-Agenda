<?php

use MinhaAgenda\Middleware\ErrorHandlerMiddleware;
use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Exception\ValidacaoException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Psr7\Factory\ServerRequestFactory;

describe('ErrorHandlerMiddleware - Verificação Detalhada', function () {

    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->request = $this->requestFactory->createServerRequest('GET', '/test');
        $this->errorHandler = new ErrorHandlerMiddleware();
    });

    describe('verificação de mensagens específicas', function () {
        it('deve retornar mensagem correta para rota não encontrada', function () {
            $exception = new HttpNotFoundException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Rota não encontrada.');
            expect($response->getStatusCode())->toBe(StatusHttp::NOT_FOUND->value);
        });

        it('deve retornar mensagem correta para método não permitido', function () {
            $exception = new HttpMethodNotAllowedException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Método HTTP não permitido para esta rota.');
            expect($response->getStatusCode())->toBe(StatusHttp::METHOD_NOT_ALLOWED->value);
        });

        it('deve retornar mensagem correta para acesso não autorizado', function () {
            $exception = new HttpUnauthorizedException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Acesso não autorizado.');
            expect($response->getStatusCode())->toBe(StatusHttp::UNAUTHORIZED->value);
        });

        it('deve retornar mensagem correta para acesso proibido', function () {
            $exception = new HttpForbiddenException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Acesso proibido.');
            expect($response->getStatusCode())->toBe(StatusHttp::FORBIDDEN->value);
        });

        it('deve retornar mensagem correta para requisição inválida', function () {
            $exception = new HttpBadRequestException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Requisição inválida.');
            expect($response->getStatusCode())->toBe(StatusHttp::BAD_REQUEST->value);
        });

        it('deve retornar mensagem correta para dados inválidos', function () {
            $erros = ['nome' => ['Campo obrigatório']];
            $exception = new ValidacaoException($erros);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($response->getStatusCode())->toBe(StatusHttp::UNPROCESSABLE_ENTITY->value);
        });

        it('deve retornar mensagem padrão para erro interno', function () {
            $exception = new \Exception('Erro genérico');

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Houve um erro interno no servidor');
            expect($response->getStatusCode())->toBe(StatusHttp::INTERNAL_SERVER_ERROR->value);
        });
    });

    describe('verificação da estrutura de dados', function () {
        it('deve incluir flag de sucesso sempre como false', function () {
            $exceptions = [
                new HttpNotFoundException($this->request),
                new HttpBadRequestException($this->request),
                new ValidacaoException(['erro' => ['teste']]),
                new \Exception('Erro genérico')
            ];

            foreach ($exceptions as $exception) {
                $response = $this->errorHandler->__invoke(
                    $this->request,
                    $exception,
                    false,
                    false,
                    false
                );

                $body = (string) $response->getBody();
                $data = json_decode($body, true);

                expect($data['sucess'])->toBe(false);
            }
        });

        it('deve sempre incluir campo message', function () {
            $exceptions = [
                new HttpNotFoundException($this->request),
                new HttpUnauthorizedException($this->request),
                new ValidacaoException([]),
                new \RuntimeException('Erro de runtime')
            ];

            foreach ($exceptions as $exception) {
                $response = $this->errorHandler->__invoke(
                    $this->request,
                    $exception,
                    false,
                    false,
                    false
                );

                $body = (string) $response->getBody();
                $data = json_decode($body, true);

                expect(isset($data['message']))->toBe(true);
                expect($data['message'])->toBeA('string');
                expect(strlen($data['message']))->toBeGreaterThan(0);
            }
        });

        it('deve preservar dados de validação corretamente', function () {
            $errosValidacao = [
                'nome' => ['Nome é obrigatório', 'Nome deve ter pelo menos 3 caracteres'],
                'email' => ['Email deve ter formato válido'],
                'idade' => ['Idade deve ser um número'],
                'telefone' => ['Telefone deve ter 11 dígitos']
            ];

            $exception = new ValidacaoException($errosValidacao);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            // Verificar que todos os campos de erro foram preservados
            expect($data['nome'])->toBe(['Nome é obrigatório', 'Nome deve ter pelo menos 3 caracteres']);
            expect($data['email'])->toBe(['Email deve ter formato válido']);
            expect($data['idade'])->toBe(['Idade deve ser um número']);
            expect($data['telefone'])->toBe(['Telefone deve ter 11 dígitos']);
        });
    });

    describe('verificação de headers de resposta', function () {
        it('deve sempre definir Content-Type como application/json', function () {
            $exceptions = [
                new HttpNotFoundException($this->request),
                new HttpBadRequestException($this->request),
                new ValidacaoException(['erro' => ['teste']]),
                new \Exception('Erro')
            ];

            foreach ($exceptions as $exception) {
                $response = $this->errorHandler->__invoke(
                    $this->request,
                    $exception,
                    false,
                    false,
                    false
                );

                $contentType = $response->getHeader('Content-type');
                expect($contentType)->toContain('application/json');
            }
        });

        it('deve manter headers consistentes independente da exceção', function () {
            $exception1 = new HttpNotFoundException($this->request);
            $exception2 = new ValidacaoException(['teste' => ['erro']]);

            $response1 = $this->errorHandler->__invoke($this->request, $exception1, false, false, false);
            $response2 = $this->errorHandler->__invoke($this->request, $exception2, false, false, false);

            $headers1 = $response1->getHeaders();
            $headers2 = $response2->getHeaders();

            expect($headers1['Content-type'])->toBe($headers2['Content-type']);
        });
    });

    describe('casos complexos de validação', function () {
        it('deve lidar com estrutura aninhada de erros de validação', function () {
            $errosComplexos = [
                'usuario' => [
                    'dados_pessoais' => [
                        'nome' => ['Nome é obrigatório'],
                        'email' => ['Email inválido']
                    ],
                    'endereco' => [
                        'cep' => ['CEP deve ter 8 dígitos'],
                        'cidade' => ['Cidade é obrigatória']
                    ]
                ],
                'configuracoes' => [
                    'tema' => ['Tema deve ser "claro" ou "escuro"'],
                    'notificacoes' => ['Configuração inválida']
                ]
            ];

            $exception = new ValidacaoException($errosComplexos);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            // Verificar estrutura aninhada preservada
            expect($data['usuario']['dados_pessoais']['nome'])->toBe(['Nome é obrigatório']);
            expect($data['usuario']['endereco']['cep'])->toBe(['CEP deve ter 8 dígitos']);
            expect($data['configuracoes']['tema'])->toBe(['Tema deve ser "claro" ou "escuro"']);

            // Verificar que ainda mantém estrutura base
            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($data['sucess'])->toBe(false);
        });

        it('deve lidar com arrays simples de erros', function () {
            $errosSimples = [
                'erro_geral_1',
                'erro_geral_2',
                'erro_geral_3'
            ];

            $exception = new ValidacaoException($errosSimples);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($response->getStatusCode())->toBe(422);
            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($data['sucess'])->toBe(false);
        });

        it('deve lidar com erros de validação vazios', function () {
            $exception = new ValidacaoException([]);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($response->getStatusCode())->toBe(422);
            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($data['sucess'])->toBe(false);

            // Verificar que não há campos extras
            $expectedKeys = ['message', 'sucess'];
            $actualKeys = array_keys($data);
            expect(count($actualKeys))->toBe(count($expectedKeys));
        });
    });

    describe('verificação de JSON válido', function () {
        it('deve sempre retornar JSON válido', function () {
            $exceptions = [
                new HttpNotFoundException($this->request),
                new HttpMethodNotAllowedException($this->request),
                new ValidacaoException(['nome' => ['Erro com "aspas" e \'aspas simples\'']]),
                new \Exception('Erro com caracteres especiais: ç, ã, õ, é')
            ];

            foreach ($exceptions as $exception) {
                $response = $this->errorHandler->__invoke(
                    $this->request,
                    $exception,
                    false,
                    false,
                    false
                );

                $body = (string) $response->getBody();
                $data = json_decode($body, true);

                // Se json_decode retorna null, houve erro no parsing
                expect($data)->not->toBe(null);
                expect(json_last_error())->toBe(JSON_ERROR_NONE);
            }
        });

        it('deve escapar caracteres especiais corretamente', function () {
            $errosComCaracteresEspeciais = [
                'campo_quotes' => ['Erro com "aspas duplas" e \'aspas simples\''],
                'campo_unicode' => ['Erro com acentos: ção, ação, não'],
                'campo_special' => ['Erro com & < > " \' / \ chars']
            ];

            $exception = new ValidacaoException($errosComCaracteresEspeciais);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data)->not->toBe(null);
            expect($data['campo_quotes'])->toBeA('array');
            expect($data['campo_unicode'])->toBeA('array');
            expect($data['campo_special'])->toBeA('array');
        });
    });

    describe('comportamento com diferentes requisições', function () {
        it('deve funcionar com diferentes métodos HTTP', function () {
            $requests = [
                $this->requestFactory->createServerRequest('GET', '/api/users'),
                $this->requestFactory->createServerRequest('POST', '/api/users'),
                $this->requestFactory->createServerRequest('PUT', '/api/users/1'),
                $this->requestFactory->createServerRequest('DELETE', '/api/users/1'),
                $this->requestFactory->createServerRequest('PATCH', '/api/users/1')
            ];

            $exception = new HttpUnauthorizedException($this->request);

            foreach ($requests as $request) {
                $response = $this->errorHandler->__invoke(
                    $request,
                    $exception,
                    false,
                    false,
                    false
                );

                expect($response->getStatusCode())->toBe(401);

                $body = (string) $response->getBody();
                $data = json_decode($body, true);
                expect($data['message'])->toBe('Acesso não autorizado.');
            }
        });

        it('deve funcionar com diferentes URIs', function () {
            $uris = [
                '/api/users',
                '/api/users/123',
                '/api/admin/dashboard',
                '/public/info',
                '/'
            ];

            $exception = new HttpNotFoundException($this->request);

            foreach ($uris as $uri) {
                $request = $this->requestFactory->createServerRequest('GET', $uri);

                $response = $this->errorHandler->__invoke(
                    $request,
                    $exception,
                    false,
                    false,
                    false
                );

                expect($response->getStatusCode())->toBe(404);

                $body = (string) $response->getBody();
                $data = json_decode($body, true);
                expect($data['message'])->toBe('Rota não encontrada.');
            }
        });
    });
});
