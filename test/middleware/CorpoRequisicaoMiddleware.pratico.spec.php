<?php

use MinhaAgenda\Middleware\CorpoRequisicaoMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

describe('CorpoRequisicaoMiddleware - Cenários Práticos', function () {
    
    beforeEach(function () {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->response = $this->responseFactory->createResponse();
        
        // Mock do handler para simular processamento bem-sucedido
        $this->handler = new class($this->response) implements RequestHandlerInterface {
            private $response;
            
            public function __construct(ResponseInterface $response) {
                $this->response = $response;
            }
            
            public function handle(ServerRequestInterface $request): ResponseInterface {
                return $this->response->withStatus(201); // Simula criação bem-sucedida
            }
        };
    });

    describe('cenários de API REST', function () {
        it('deve validar criação de usuário via API', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosUsuario = [
                'nome' => 'João Silva',
                'email' => 'joao.silva@email.com',
                'senha' => 'senha123',
                'data_nascimento' => '1990-05-15',
                'endereco' => [
                    'rua' => 'Rua das Flores, 123',
                    'cidade' => 'São Paulo',
                    'cep' => '01234-567',
                    'estado' => 'SP'
                ],
                'configuracoes' => [
                    'notificacoes_email' => true,
                    'notificacoes_push' => false,
                    'tema' => 'escuro'
                ]
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/usuarios')
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($dadosUsuario);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve validar atualização de perfil de usuário', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosAtualizacao = [
                'nome' => 'João Silva Santos',
                'telefone' => '(11) 99999-8888',
                'endereco' => [
                    'rua' => 'Rua Nova, 456',
                    'cidade' => 'Rio de Janeiro',
                    'estado' => 'RJ'
                ]
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('PUT', '/api/usuarios/123')
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($dadosAtualizacao);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve rejeitar requisição de cadastro sem Content-Type', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosUsuario = [
                'nome' => 'João Silva',
                'email' => 'joao@email.com'
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/usuarios')
                ->withParsedBody($dadosUsuario);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(400);
            
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            expect($data['message'])->toBe('O corpo da requisição tem formato inválido.');
        });

        it('deve rejeitar requisição com Content-Type incorreto', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/usuarios')
                ->withHeader('Content-Type', 'text/plain')
                ->withParsedBody(['nome' => 'João']);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(400);
        });
    });

    describe('cenários de diferentes tipos de formulário', function () {
        it('deve processar formulário web tradicional', function () {
            $middleware = new CorpoRequisicaoMiddleware('application/x-www-form-urlencoded');
            
            $dadosFormulario = [
                'nome' => 'Maria Santos',
                'email' => 'maria@email.com',
                'mensagem' => 'Olá, gostaria de mais informações sobre o produto.',
                'newsletter' => '1',
                'categoria' => 'vendas'
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/contato')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withParsedBody($dadosFormulario);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve processar upload de arquivo multipart', function () {
            $middleware = new CorpoRequisicaoMiddleware('multipart/form-data');
            
            $dadosUpload = [
                'titulo' => 'Minha Foto de Perfil',
                'descricao' => 'Foto tirada nas férias',
                'categoria' => 'pessoal',
                'publico' => true,
                'arquivo_info' => [
                    'nome' => 'foto.jpg',
                    'tipo' => 'image/jpeg',
                    'tamanho' => 1024000
                ]
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/upload/imagem')
                ->withHeader('Content-Type', 'multipart/form-data; boundary=----WebKitFormBoundary')
                ->withParsedBody($dadosUpload);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve processar dados XML quando configurado', function () {
            $middleware = new CorpoRequisicaoMiddleware('application/xml');
            
            $dadosXML = [
                'produto' => [
                    'id' => 'P001',
                    'nome' => 'Smartphone XYZ',
                    'preco' => 999.99,
                    'categoria' => 'eletronicos',
                    'especificacoes' => [
                        'tela' => '6.1 polegadas',
                        'memoria' => '128GB',
                        'camera' => '48MP'
                    ]
                ]
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/produtos')
                ->withHeader('Content-Type', 'application/xml; charset=utf-8')
                ->withParsedBody($dadosXML);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });
    });

    describe('cenários de integração com APIs externas', function () {
        it('deve processar webhook de pagamento', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $webhookPagamento = [
                'evento' => 'pagamento.aprovado',
                'timestamp' => 1691680000,
                'dados' => [
                    'transacao_id' => 'TXN_123456789',
                    'valor' => 49.90,
                    'moeda' => 'BRL',
                    'metodo_pagamento' => 'cartao_credito',
                    'parcelas' => 1,
                    'cliente' => [
                        'id' => 'CLI_987654321',
                        'nome' => 'Ana Costa',
                        'email' => 'ana@email.com'
                    ],
                    'produto' => [
                        'id' => 'PROD_555',
                        'nome' => 'Plano Premium',
                        'tipo' => 'assinatura'
                    ]
                ],
                'assinatura' => 'sha256_hash_here'
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/webhooks/pagamento')
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($webhookPagamento);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve processar dados de sincronização de CRM', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosCRM = [
                'operacao' => 'sincronizar',
                'timestamp' => '2025-08-10T19:30:00Z',
                'origem' => 'CRM_EXTERNO',
                'lote' => [
                    'id' => 'LOTE_20250810_001',
                    'total_registros' => 3
                ],
                'contatos' => [
                    [
                        'id_externo' => 'CRM_001',
                        'nome' => 'Carlos Empresa',
                        'empresa' => 'Tech Solutions Ltda',
                        'cargo' => 'Diretor de TI',
                        'email' => 'carlos@techsolutions.com',
                        'telefone' => '(11) 3333-4444',
                        'status' => 'lead_qualificado',
                        'origem' => 'website'
                    ],
                    [
                        'id_externo' => 'CRM_002',
                        'nome' => 'Fernanda Marketing',
                        'empresa' => 'Digital Agency',
                        'cargo' => 'Gerente de Marketing',
                        'email' => 'fernanda@digitalagency.com',
                        'telefone' => '(21) 2222-3333',
                        'status' => 'cliente',
                        'origem' => 'indicacao'
                    ]
                ]
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/sync/crm')
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($dadosCRM);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve rejeitar webhook com formato inválido', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/webhooks/pagamento')
                ->withHeader('Content-Type', 'text/xml')
                ->withParsedBody(['evento' => 'teste']);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(400);
        });
    });

    describe('cenários de aplicações mobile', function () {
        it('deve processar requisição de app mobile iOS', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosMobile = [
                'device_info' => [
                    'plataforma' => 'iOS',
                    'versao_os' => '16.5',
                    'modelo' => 'iPhone 14',
                    'app_versao' => '2.1.0',
                    'device_id' => 'iOS_DEVICE_12345'
                ],
                'usuario' => [
                    'id' => 456,
                    'push_token' => 'apns_token_here',
                    'configuracoes' => [
                        'notificacoes_push' => true,
                        'localizacao' => true,
                        'tema' => 'automatico'
                    ]
                ],
                'acao' => 'login',
                'timestamp' => 1691680000
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/mobile/auth')
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('User-Agent', 'MinhaAgenda-iOS/2.1.0')
                ->withParsedBody($dadosMobile);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve processar requisição de app mobile Android', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosAndroid = [
                'device_info' => [
                    'plataforma' => 'Android',
                    'versao_os' => '13',
                    'modelo' => 'Samsung Galaxy S23',
                    'app_versao' => '2.1.0',
                    'device_id' => 'ANDROID_DEVICE_67890'
                ],
                'usuario' => [
                    'id' => 789,
                    'firebase_token' => 'fcm_token_here',
                    'configuracoes' => [
                        'notificacoes_push' => true,
                        'backup_automatico' => true,
                        'qualidade_imagem' => 'alta'
                    ]
                ],
                'acao' => 'sincronizar_agenda',
                'dados_agenda' => [
                    'eventos_modificados' => 5,
                    'eventos_novos' => 2,
                    'ultimo_sync' => 1691676400
                ]
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/mobile/sync')
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('User-Agent', 'MinhaAgenda-Android/2.1.0')
                ->withParsedBody($dadosAndroid);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });
    });

    describe('cenários de performance e volume', function () {
        it('deve processar requisição com grande volume de dados', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            // Simular importação em lote de eventos
            $eventosLote = [];
            for ($i = 1; $i <= 100; $i++) {
                $eventosLote[] = [
                    'titulo' => "Evento $i",
                    'descricao' => "Descrição do evento número $i para teste de performance",
                    'data_inicio' => '2025-08-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . 'T10:00:00Z',
                    'data_fim' => '2025-08-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . 'T11:00:00Z',
                    'categoria' => ['trabalho', 'pessoal', 'saude'][$i % 3],
                    'prioridade' => ['baixa', 'media', 'alta'][$i % 3],
                    'participantes' => [
                        ['nome' => "Participante A$i", 'email' => "a$i@email.com"],
                        ['nome' => "Participante B$i", 'email' => "b$i@email.com"]
                    ],
                    'local' => [
                        'nome' => "Local do evento $i",
                        'endereco' => "Rua $i, número $i",
                        'coordenadas' => [
                            'latitude' => -23.550520 + ($i * 0.001),
                            'longitude' => -46.633308 + ($i * 0.001)
                        ]
                    ]
                ];
            }
            
            $dadosImportacao = [
                'operacao' => 'importacao_lote',
                'origem' => 'calendar_export',
                'total_eventos' => count($eventosLote),
                'eventos' => $eventosLote
            ];
            
            $inicio = microtime(true);
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/eventos/importar')
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($dadosImportacao);
            
            $response = $middleware->process($request, $this->handler);
            
            $tempoExecucao = microtime(true) - $inicio;
            
            expect($response->getStatusCode())->toBe(201);
            expect($tempoExecucao)->toBeLessThan(1.0); // Deve executar em menos de 1 segundo
        });

        it('deve rejeitar rapidamente requisições inválidas', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $requestsInvalidas = [
                // Content-Type incorreto
                $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', 'text/plain')
                    ->withParsedBody(['dados' => 'teste']),
                
                // Sem Content-Type
                $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withParsedBody(['dados' => 'teste']),
                
                // Corpo vazio
                $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', 'application/json')
                    ->withParsedBody([])
            ];
            
            foreach ($requestsInvalidas as $request) {
                $inicio = microtime(true);
                $response = $middleware->process($request, $this->handler);
                $tempoExecucao = microtime(true) - $inicio;
                
                expect($response->getStatusCode())->toBe(400);
                expect($tempoExecucao)->toBeLessThan(0.1); // Deve falhar rapidamente
            }
        });
    });

    describe('casos extremos de produção', function () {
        it('deve lidar com caracteres especiais e unicode', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $dadosUnicode = [
                'nome_portugues' => 'João da Silva Araújo',
                'nome_japones' => '田中太郎',
                'nome_arabe' => 'محمد عبدالله',
                'nome_russo' => 'Александр Петров',
                'emojis' => 'Evento 🎉 importante 📅 para 👥 equipe',
                'caracteres_especiais' => 'Teste com & < > " \' / \\ caracteres',
                'acentos' => 'Acentuação: ção, não, são, então, coração',
                'simbolos' => '© ® ™ € $ £ ¥ § ¶ † ‡ • …'
            ];
            
            $request = $this->requestFactory
                ->createServerRequest('POST', '/api/eventos/internacional')
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withParsedBody($dadosUnicode);
            
            $response = $middleware->process($request, $this->handler);
            
            expect($response->getStatusCode())->toBe(201);
        });

        it('deve funcionar com diferentes versões de Content-Type', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            $contentTypesValidos = [
                'application/json',
                'application/json; charset=utf-8',
                'application/json;charset=UTF-8',
                'application/json; charset=iso-8859-1',
                'application/json; boundary=something',
                'application/json; charset=utf-8; boundary=test'
            ];
            
            foreach ($contentTypesValidos as $contentType) {
                $request = $this->requestFactory
                    ->createServerRequest('POST', '/test')
                    ->withHeader('Content-Type', $contentType)
                    ->withParsedBody(['teste' => 'dados']);
                
                $response = $middleware->process($request, $this->handler);
                
                expect($response->getStatusCode())->toBe(201);
            }
        });

        it('deve manter consistência em ambiente concorrente simulado', function () {
            $middleware = new CorpoRequisicaoMiddleware();
            
            // Simular múltiplas requisições simultâneas
            $requests = [];
            for ($i = 1; $i <= 10; $i++) {
                $requests[] = $this->requestFactory
                    ->createServerRequest('POST', "/api/test/$i")
                    ->withHeader('Content-Type', 'application/json')
                    ->withParsedBody(['request_id' => $i, 'dados' => "teste $i"]);
            }
            
            $responses = [];
            foreach ($requests as $request) {
                $responses[] = $middleware->process($request, $this->handler);
            }
            
            // Todas as respostas devem ser consistentes
            foreach ($responses as $response) {
                expect($response->getStatusCode())->toBe(201);
            }
        });
    });
});
