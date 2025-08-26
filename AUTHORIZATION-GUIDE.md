# Sistema de Autorização e Permissões - API Minha Agenda

Este documento explica como usar o sistema de autorização implementado na API.

## Visão Geral

O sistema é composto por:

1. **AutenticacaoMiddleware** - Verifica se o usuário está logado
2. **AutorizacaoMiddleware** - Verifica permissões específicas
3. **PolicyManager** - Define regras de acesso por recurso
4. **AuthorizationHelper** - Facilita a criação de middlewares
5. **MiddlewareFactory** - Factory para criação de middlewares
6. **UsuarioAutenticado** - Trait para acessar dados do usuário nos controllers

## Como Usar

### 1. Aplicando Autenticação e Autorização nas Rotas

```php
<?php

use MinhaAgenda\Controller\UsuarioController;
use MinhaAgenda\Factory\MiddlewareFactory;

// Exemplo: Rotas de usuários com autorização baseada em recurso
$usuarios = $group->group('/usuarios', function ($group) {
    $group->post('', UsuarioController::class . ':novo');
    $group->put('/{id}', UsuarioController::class . ':atualizar');
    $group->get('', UsuarioController::class . ':obterTodos');
    $group->get('/{id}', UsuarioController::class . ':obterComId');
    $group->delete('/{id}', UsuarioController::class . ':excluirComId');
});

// Aplicar middlewares (ordem importa!)
$usuarios->add(MiddlewareFactory::autorizacaoRecurso('usuarios'));
$usuarios->add(MiddlewareFactory::autenticacao());
```

### 2. Tipos de Middleware Disponíveis

```php
// Autenticação básica (verifica se está logado)
MiddlewareFactory::autenticacao()

// Autorização baseada em recurso (usa PolicyManager)
MiddlewareFactory::autorizacaoRecurso('usuarios')
MiddlewareFactory::autorizacaoRecurso('servicos')
MiddlewareFactory::autorizacaoRecurso('agendamentos')

// Autorização para admins apenas
MiddlewareFactory::autorizacaoAdmins()

// Autorização para super admin apenas
MiddlewareFactory::autorizacaoSuperAdmin()

// Leitura pública (todos os usuários logados podem ler)
MiddlewareFactory::leituraPublica()
```

### 3. Políticas de Acesso Padrão

As políticas são definidas no `PolicyManager`:

#### Usuários
- **GET**: Todos podem ver (para buscar prestadores)
- **POST/PUT**: Apenas admins podem criar/atualizar
- **DELETE**: Apenas super admin pode deletar

#### Administradores
- **GET**: Apenas admins podem ver outros admins
- **POST/PUT/DELETE**: Apenas super admin

#### Serviços
- **GET**: Todos podem ver (usuários para agendar, admins para gerenciar)
- **POST/PUT/DELETE**: Apenas admins

#### Agendamentos
- **GET/POST/PUT/DELETE**: Todos podem (com lógica de negócio nos controllers)

### 4. Usando o Trait UsuarioAutenticado nos Controllers

```php
<?php

namespace MinhaAgenda\Controller;

use MinhaAgenda\Trait\UsuarioAutenticado;

class UsuarioController extends Controller 
{
    use UsuarioAutenticado;

    public function obterComId(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        // Verificar se o usuário pode acessar este recurso
        if (!$this->podeAcessarRecurso($request, $id)) {
            return $this->erro($response, 'Acesso negado', 403);
        }

        // Obter dados do usuário autenticado
        $usuarioLogado = $this->getUsuarioAutenticado($request);
        $usuarioLogadoId = $this->getUsuarioId($request);
        $papel = $this->getUsuarioPapel($request);

        // Verificar se é admin
        if ($this->isAdmin($request)) {
            // Admin pode ver todos os usuários
        } else {
            // Usuário comum só vê seus próprios dados
            if ($usuarioLogadoId !== $id) {
                return $this->erro($response, 'Acesso negado', 403);
            }
        }

        // Continuar com a lógica...
    }
}
```

### 5. Exemplo Prático: Serviços

Para o caso mencionado onde tanto usuários quanto admins podem ver serviços:

```php
<?php
// routes/servicos.php

use MinhaAgenda\Factory\MiddlewareFactory;

$servicos = $group->group('/servicos', function ($group) {
    // Todos os usuários autenticados podem ver serviços
    $group->get('', ServicoController::class . ':obterTodos');
    $group->get('/{id}', ServicoController::class . ':obterComId');
    
    // Apenas admins podem criar/modificar/deletar serviços
    $group->post('', ServicoController::class . ':novo');
    $group->put('/{id}', ServicoController::class . ':atualizar');
    $group->delete('/{id}', ServicoController::class . ':excluirComId');
});

$servicos->add(MiddlewareFactory::autorizacaoRecurso('servicos'));
$servicos->add(MiddlewareFactory::autenticacao());
```

### 6. Customizando Permissões

Para criar permissões customizadas:

```php
// Exemplo: Apenas usuários comuns podem criar agendamentos
use MinhaAgenda\Authorization\AuthorizationHelper;

$agendamentos = $group->group('/agendamentos', function ($group) {
    $group->post('', AgendamentoController::class . ':novo');
});

$agendamentos->add(AuthorizationHelper::paraMetodos(['POST'], ['usuario']));
$agendamentos->add(MiddlewareFactory::autenticacao());
```

## Headers de Autenticação

Para acessar rotas protegidas, incluir o header:
```
Authorization: Bearer <seu_token_jwt>
```

## Códigos de Resposta

- **401 Unauthorized**: Token ausente, inválido ou expirado
- **403 Forbidden**: Token válido mas sem permissões para a ação
- **200/201/204**: Acesso autorizado e operação realizada

## Boas Práticas

1. **Sempre aplicar autenticação antes de autorização**
2. **Usar o PolicyManager para definir regras centralizadas**
3. **Implementar lógica adicional nos controllers quando necessário**
4. **Usar o trait UsuarioAutenticado para acessar dados do usuário**
5. **Validar permissões tanto no middleware quanto na lógica de negócio**

## Extensibilidade

Para adicionar novos recursos:

1. **Adicionar políticas no PolicyManager**
2. **Criar rotas com os middlewares adequados**
3. **Implementar lógica específica nos controllers**
4. **Usar os helpers existentes ou criar novos quando necessário**
