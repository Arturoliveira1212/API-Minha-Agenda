<?php

use MinhaAgenda\Middleware\ErrorHandlerMiddleware;
use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Exception\ValidacaoException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Psr7\Factory\ServerRequestFactory;

describe('ErrorHandlerMiddleware', function () {

    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->request = $this->requestFactory->createServerRequest('GET', '/test');
        $this->errorHandler = new ErrorHandlerMiddleware();
    });

    describe('construtor e implementação', function () {
        it('deve implementar ErrorHandlerInterface', function () {
            expect($this->errorHandler)->toBeAnInstanceOf(\Slim\Interfaces\ErrorHandlerInterface::class);
        });

        it('deve usar o trait RespostaAPI', function () {
            $reflection = new ReflectionClass(ErrorHandlerMiddleware::class);
            $traits = $reflection->getTraitNames();
            expect($traits)->toContain('MinhaAgenda\Trait\RespostaAPI');
        });
    });

    describe('tratamento de exceções HTTP específicas', function () {
        it('deve tratar HttpNotFoundException corretamente', function () {
            $exception = new HttpNotFoundException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(404);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Rota não encontrada.');
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar HttpMethodNotAllowedException corretamente', function () {
            $exception = new HttpMethodNotAllowedException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(405);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Método HTTP não permitido para esta rota.');
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar HttpUnauthorizedException corretamente', function () {
            $exception = new HttpUnauthorizedException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(401);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Acesso não autorizado.');
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar HttpForbiddenException corretamente', function () {
            $exception = new HttpForbiddenException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(403);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Acesso proibido.');
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar HttpBadRequestException corretamente', function () {
            $exception = new HttpBadRequestException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(400);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Requisição inválida.');
            expect($data['sucess'])->toBe(false);
        });
    });

    describe('tratamento de ValidacaoException', function () {
        it('deve tratar ValidacaoException com erros', function () {
            $erros = [
                'nome' => ['O campo nome é obrigatório'],
                'email' => ['O email deve ter um formato válido']
            ];
            $exception = new ValidacaoException($erros);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($data['sucess'])->toBe(false);
            expect($data['nome'])->toBe(['O campo nome é obrigatório']);
            expect($data['email'])->toBe(['O email deve ter um formato válido']);
        });

        it('deve tratar ValidacaoException com array vazio de erros', function () {
            $exception = new ValidacaoException([]);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($data['sucess'])->toBe(false);
        });
    });

    describe('tratamento de exceções genéricas', function () {
        it('deve tratar Exception genérica como erro interno', function () {
            $exception = new \Exception('Erro genérico de teste');

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(500);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Houve um erro interno no servidor');
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar RuntimeException como erro interno', function () {
            $exception = new \RuntimeException('Erro de runtime');

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(500);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Houve um erro interno no servidor');
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar Throwable personalizado como erro interno', function () {
            $exception = new class extends \Exception {
                public function __construct() {
                    parent::__construct('Erro personalizado');
                }
            };

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(500);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Houve um erro interno no servidor');
            expect($data['sucess'])->toBe(false);
        });
    });

    describe('formato da resposta', function () {
        it('deve retornar resposta no formato JSON', function () {
            $exception = new HttpNotFoundException($this->request);

            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                false,
                false,
                false
            );

            $contentType = $response->getHeader('Content-type');
            expect($contentType)->toContain('application/json');
        });

        it('deve retornar estrutura de resposta consistente', function () {
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

            expect(isset($data['message']))->toBe(true);
            expect(isset($data['sucess']))->toBe(true);
            expect($data['sucess'])->toBe(false);
        });

        it('deve incluir dados adicionais quando fornecidos', function () {
            $erros = [
                'campo1' => ['Erro no campo 1'],
                'campo2' => ['Erro no campo 2']
            ];
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

            expect(isset($data['message']))->toBe(true);
            expect(isset($data['sucess']))->toBe(true);
            expect(isset($data['campo1']))->toBe(true);
            expect(isset($data['campo2']))->toBe(true);
        });
    });

    describe('parâmetros do error handler', function () {
        it('deve funcionar independente dos parâmetros de debug', function () {
            $exception = new HttpNotFoundException($this->request);

            // Testando com diferentes combinações de parâmetros
            $response1 = $this->errorHandler->__invoke($this->request, $exception, true, true, true);
            $response2 = $this->errorHandler->__invoke($this->request, $exception, false, false, false);

            expect($response1->getStatusCode())->toBe($response2->getStatusCode());

            $body1 = (string) $response1->getBody();
            $body2 = (string) $response2->getBody();

            expect($body1)->toBe($body2);
        });

        it('deve aceitar diferentes tipos de request', function () {
            $postRequest = $this->requestFactory->createServerRequest('POST', '/api/test');
            $putRequest = $this->requestFactory->createServerRequest('PUT', '/api/update');

            $exception = new HttpUnauthorizedException($this->request);

            $response1 = $this->errorHandler->__invoke($postRequest, $exception, false, false, false);
            $response2 = $this->errorHandler->__invoke($putRequest, $exception, false, false, false);

            expect($response1->getStatusCode())->toBe(401);
            expect($response2->getStatusCode())->toBe(401);
        });
    });

    describe('casos extremos', function () {
        it('deve lidar com ValidacaoException com estrutura complexa de erros', function () {
            $errosComplexos = [
                'usuario' => [
                    'nome' => ['Nome é obrigatório', 'Nome deve ter pelo menos 3 caracteres'],
                    'email' => ['Email inválido']
                ],
                'endereco' => [
                    'cep' => ['CEP inválido'],
                    'cidade' => ['Cidade é obrigatória']
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

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['usuario']['nome'])->toBe(['Nome é obrigatório', 'Nome deve ter pelo menos 3 caracteres']);
            expect($data['endereco']['cep'])->toBe(['CEP inválido']);
        });

        it('deve manter consistência mesmo com dados nulos', function () {
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

            expect($data['message'])->toBeA('string');
            expect($data['sucess'])->toBeA('boolean');
        });
    });
});
