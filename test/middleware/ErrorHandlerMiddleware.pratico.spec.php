<?php

use MinhaAgenda\Middleware\ErrorHandlerMiddleware;
use MinhaAgenda\Exception\ValidacaoException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Psr7\Factory\ServerRequestFactory;

describe('ErrorHandlerMiddleware - Cenários Práticos', function () {

    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->errorHandler = new ErrorHandlerMiddleware();
    });

    describe('cenários de API REST', function () {
        it('deve tratar erro de usuário não encontrado', function () {
            $request = $this->requestFactory->createServerRequest('GET', '/api/usuarios/999');
            $exception = new HttpNotFoundException($request);

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
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar erro de autenticação em rota protegida', function () {
            $request = $this->requestFactory->createServerRequest('GET', '/api/admin/dashboard');
            $exception = new HttpUnauthorizedException($request);

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
            expect($data['sucess'])->toBe(false);
        });

        it('deve tratar erro de permissão insuficiente', function () {
            $request = $this->requestFactory->createServerRequest('DELETE', '/api/admin/usuarios/1');
            $exception = new HttpForbiddenException($request);

            $response = $this->errorHandler->__invoke(
                $request,
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

        it('deve tratar método HTTP não suportado', function () {
            $request = $this->requestFactory->createServerRequest('PATCH', '/api/usuarios');
            $exception = new HttpMethodNotAllowedException($request);

            $response = $this->errorHandler->__invoke(
                $request,
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
    });

    describe('validação de formulários', function () {
        it('deve tratar erro de cadastro de usuário com dados inválidos', function () {
            $request = $this->requestFactory->createServerRequest('POST', '/api/usuarios');

            $errosValidacao = [
                'nome' => [
                    'O campo nome é obrigatório.',
                    'O nome deve ter pelo menos 3 caracteres.'
                ],
                'email' => [
                    'O campo email é obrigatório.',
                    'O email deve ter um formato válido.'
                ],
                'senha' => [
                    'A senha deve ter pelo menos 8 caracteres.',
                    'A senha deve conter pelo menos uma letra maiúscula.',
                    'A senha deve conter pelo menos um número.'
                ]
            ];

            $exception = new ValidacaoException($errosValidacao);

            $response = $this->errorHandler->__invoke(
                $request,
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
            expect($data['nome'])->toBe($errosValidacao['nome']);
            expect($data['email'])->toBe($errosValidacao['email']);
            expect($data['senha'])->toBe($errosValidacao['senha']);
        });

        it('deve tratar erro de atualização de perfil', function () {
            $request = $this->requestFactory->createServerRequest('PUT', '/api/usuarios/perfil');

            $errosValidacao = [
                'data_nascimento' => ['A data de nascimento deve ser válida.'],
                'telefone' => ['O telefone deve ter o formato (XX) XXXXX-XXXX.'],
                'endereco' => [
                    'cep' => ['O CEP deve ter 8 dígitos.'],
                    'cidade' => ['A cidade é obrigatória.']
                ]
            ];

            $exception = new ValidacaoException($errosValidacao);

            $response = $this->errorHandler->__invoke(
                $request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($data['data_nascimento'])->toBe(['A data de nascimento deve ser válida.']);
            expect($data['telefone'])->toBe(['O telefone deve ter o formato (XX) XXXXX-XXXX.']);
            expect($data['endereco']['cep'])->toBe(['O CEP deve ter 8 dígitos.']);
            expect($data['endereco']['cidade'])->toBe(['A cidade é obrigatória.']);
        });

        it('deve tratar erro de login com credenciais inválidas', function () {
            $request = $this->requestFactory->createServerRequest('POST', '/api/auth/login');

            $errosValidacao = [
                'email' => ['O email é obrigatório.'],
                'senha' => ['A senha é obrigatória.']
            ];

            $exception = new ValidacaoException($errosValidacao);

            $response = $this->errorHandler->__invoke(
                $request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect($data['email'])->toBe(['O email é obrigatório.']);
            expect($data['senha'])->toBe(['A senha é obrigatória.']);
        });
    });

    describe('erros de sistema e infraestrutura', function () {
        it('deve tratar erro de conexão com banco de dados', function () {
            $request = $this->requestFactory->createServerRequest('GET', '/api/usuarios');
            $exception = new \Exception('Connection refused - Database not available');

            $response = $this->errorHandler->__invoke(
                $request,
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

        it('deve tratar erro de falta de memória', function () {
            $request = $this->requestFactory->createServerRequest('POST', '/api/relatorio/completo');
            $exception = new \Exception('Fatal error: Allowed memory size exhausted');

            $response = $this->errorHandler->__invoke(
                $request,
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

        it('deve tratar erro de timeout', function () {
            $request = $this->requestFactory->createServerRequest('GET', '/api/sync/external');
            $exception = new \Exception('Operation timed out after 30 seconds');

            $response = $this->errorHandler->__invoke(
                $request,
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

    describe('integração com diferentes clients', function () {
        it('deve funcionar para requisições de aplicativo mobile', function () {
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/mobile/usuarios')
                ->withHeader('User-Agent', 'MinhaAgenda-Mobile/1.0.0');

            $errosValidacao = [
                'device_id' => ['Device ID é obrigatório para aplicativo mobile.'],
                'versao_app' => ['Versão do aplicativo é obrigatória.']
            ];

            $exception = new ValidacaoException($errosValidacao);

            $response = $this->errorHandler->__invoke(
                $request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['device_id'])->toBe(['Device ID é obrigatório para aplicativo mobile.']);
            expect($data['versao_app'])->toBe(['Versão do aplicativo é obrigatória.']);
        });

        it('deve funcionar para requisições de SPA frontend', function () {
            $request = $this->requestFactory
                ->createServerRequest('PUT', '/api/web/configuracoes')
                ->withHeader('X-Requested-With', 'XMLHttpRequest');

            $exception = new HttpUnauthorizedException($request);

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
            expect($data['sucess'])->toBe(false);
        });

        it('deve funcionar para requisições de API externa', function () {
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/webhook/pagamento')
                ->withHeader('Authorization', 'Bearer invalid-token');

            $exception = new HttpForbiddenException($request);

            $response = $this->errorHandler->__invoke(
                $request,
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
    });

    describe('performance e escalabilidade', function () {
        it('deve processar múltiplos erros rapidamente', function () {
            $request = $this->requestFactory->createServerRequest('POST', '/api/bulk/usuarios');

            $errosGrandes = [];
            for ($i = 1; $i <= 50; $i++) {
                $errosGrandes["usuario_$i"] = [
                    "Nome do usuário $i é obrigatório.",
                    "Email do usuário $i é inválido.",
                    "Senha do usuário $i é muito fraca."
                ];
            }

            $exception = new ValidacaoException($errosGrandes);

            $inicio = microtime(true);
            $response = $this->errorHandler->__invoke(
                $request,
                $exception,
                false,
                false,
                false
            );
            $tempoExecucao = microtime(true) - $inicio;

            expect($response->getStatusCode())->toBe(422);
            expect($tempoExecucao)->toBeLessThan(1.0); // Deve executar em menos de 1 segundo

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data['message'])->toBe('Dados de entrada inválidos.');
            expect(count($data))->toBeGreaterThan(50); // 50 campos + message + sucess
        });

        it('deve lidar com erro de validação muito complexo', function () {
            $request = $this->requestFactory->createServerRequest('POST', '/api/configuracao/sistema');

            $erroComplexo = [
                'configuracao' => [
                    'database' => [
                        'conexoes' => [
                            'principal' => [
                                'host' => ['Host é obrigatório.'],
                                'porta' => ['Porta deve ser um número.'],
                                'usuario' => ['Usuário é obrigatório.']
                            ],
                            'backup' => [
                                'host' => ['Host de backup é obrigatório.'],
                                'ssl' => ['SSL deve ser ativado para backup.']
                            ]
                        ]
                    ],
                    'cache' => [
                        'redis' => [
                            'url' => ['URL do Redis é inválida.'],
                            'timeout' => ['Timeout deve ser maior que 0.']
                        ]
                    ],
                    'email' => [
                        'smtp' => [
                            'servidor' => ['Servidor SMTP é obrigatório.'],
                            'autenticacao' => [
                                'usuario' => ['Usuário SMTP é obrigatório.'],
                                'senha' => ['Senha SMTP é obrigatória.']
                            ]
                        ]
                    ]
                ]
            ];

            $exception = new ValidacaoException($erroComplexo);

            $response = $this->errorHandler->__invoke(
                $request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            // Verificar que a estrutura complexa foi preservada
            expect($data['configuracao']['database']['conexoes']['principal']['host'])->toBe(['Host é obrigatório.']);
            expect($data['configuracao']['email']['smtp']['autenticacao']['usuario'])->toBe(['Usuário SMTP é obrigatório.']);

            // Verificar que o JSON é válido
            expect($data)->not->toBe(null);
            expect(json_last_error())->toBe(JSON_ERROR_NONE);
        });
    });

    describe('casos extremos de produção', function () {
        it('deve lidar com exception com mensagem contendo caracteres especiais', function () {
            $request = $this->requestFactory->createServerRequest('GET', '/api/test');
            $exception = new \Exception('Erro: "aspas", \'aspas simples\', & símbolos especiais <tag>');

            $response = $this->errorHandler->__invoke(
                $request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(500);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data)->not->toBe(null);
            expect($data['message'])->toBe('Houve um erro interno no servidor');
        });

        it('deve manter consistência mesmo com dados UTF-8 complexos', function () {
            $request = $this->requestFactory->createServerRequest('POST', '/api/internacional');

            $errosUTF8 = [
                'nome_japones' => ['名前は必須です'],
                'nome_chines' => ['姓名是必需的'],
                'nome_russo' => ['Имя обязательно'],
                'nome_arabe' => ['الاسم مطلوب'],
                'emojis' => ['Campo não pode conter 😀 🎉 🚀 emojis']
            ];

            $exception = new ValidacaoException($errosUTF8);

            $response = $this->errorHandler->__invoke(
                $request,
                $exception,
                false,
                false,
                false
            );

            expect($response->getStatusCode())->toBe(422);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            expect($data)->not->toBe(null);
            expect($data['nome_japones'])->toBe(['名前は必須です']);
            expect($data['emojis'])->toBe(['Campo não pode conter 😀 🎉 🚀 emojis']);
        });
    });
});
