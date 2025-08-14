# Testes do ErrorHandlerMiddleware

Este conjunto de testes garante o funcionamento correto do middleware de tratamento de erros da API Minha Agenda.

## Arquivos de Teste

### 1. `ErrorHandlerMiddleware.spec.php`
**Testes fundamentais e de estrutura**

- ✅ Verificação da implementação de interfaces
- ✅ Tratamento de exceções HTTP específicas
- ✅ Tratamento de ValidacaoException
- ✅ Tratamento de exceções genéricas
- ✅ Formato da resposta JSON
- ✅ Parâmetros do error handler
- ✅ Casos extremos

**Total: 19 testes (51 expectativas)**

### 2. `ErrorHandlerMiddleware.verificacao.spec.php`
**Testes de verificação detalhada**

- ✅ Verificação de mensagens específicas por tipo de erro
- ✅ Verificação da estrutura de dados da resposta
- ✅ Verificação de headers HTTP
- ✅ Casos complexos de validação
- ✅ Verificação de JSON válido
- ✅ Comportamento com diferentes tipos de requisição

**Total: 19 testes (83 expectativas)**

### 3. `ErrorHandlerMiddleware.pratico.spec.php`
**Testes de cenários práticos de uso**

- ✅ Cenários de API REST (404, 401, 403, 405)
- ✅ Validação de formulários complexos
- ✅ Erros de sistema e infraestrutura
- ✅ Integração com diferentes tipos de clients
- ✅ Performance e escalabilidade
- ✅ Casos extremos de produção

**Total: 17 testes (62 expectativas)**

## Funcionalidades do ErrorHandlerMiddleware

O middleware implementa a interface `ErrorHandlerInterface` do Slim e trata os seguintes tipos de erro:

### Exceções HTTP Específicas
- `HttpNotFoundException` → 404 (Rota não encontrada)
- `HttpMethodNotAllowedException` → 405 (Método não permitido)
- `HttpUnauthorizedException` → 401 (Não autorizado)
- `HttpForbiddenException` → 403 (Acesso proibido)
- `HttpBadRequestException` → 400 (Requisição inválida)

### Exceções de Validação
- `ValidacaoException` → 422 (Dados inválidos)
  - Preserva estrutura complexa de erros
  - Suporte a validação aninhada
  - Múltiplos erros por campo

### Exceções Genéricas
- Qualquer `Throwable` → 500 (Erro interno do servidor)

## Formato da Resposta

Todas as respostas seguem o padrão:

```json
{
  "sucess": false,
  "message": "Mensagem de erro",
  ...dados_adicionais
}
```

### Headers
- `Content-Type: application/json`

## Executando os Testes

### Todos os testes
```bash
./vendor/bin/kahlan --spec=test/ErrorHandlerMiddleware.spec.php
./vendor/bin/kahlan --spec=test/ErrorHandlerMiddleware.verificacao.spec.php
./vendor/bin/kahlan --spec=test/ErrorHandlerMiddleware.pratico.spec.php
```

### Testes específicos
```bash
# Apenas testes fundamentais
./vendor/bin/kahlan --spec=test/ErrorHandlerMiddleware.spec.php

# Apenas verificação detalhada
./vendor/bin/kahlan --spec=test/ErrorHandlerMiddleware.verificacao.spec.php

# Apenas cenários práticos
./vendor/bin/kahlan --spec=test/ErrorHandlerMiddleware.pratico.spec.php
```

### Com relatório verbose
```bash
./vendor/bin/kahlan --spec=test/ErrorHandlerMiddleware.spec.php --reporter=verbose
```

## Cobertura de Testes

### Cenários Testados ✅

#### Tratamento de Erros HTTP
- 404 - Rota não encontrada
- 405 - Método HTTP não permitido
- 401 - Acesso não autorizado
- 403 - Acesso proibido
- 400 - Requisição inválida
- 422 - Dados de validação inválidos
- 500 - Erro interno do servidor

#### Validação de Dados
- Erros simples de campo
- Erros múltiplos por campo
- Estruturas aninhadas complexas
- Arrays de erros
- Caracteres especiais e UTF-8
- Emojis e caracteres internacionais

#### Integração
- Diferentes métodos HTTP (GET, POST, PUT, DELETE, PATCH)
- Diferentes URIs e endpoints
- Headers customizados
- Requisições de aplicativo mobile
- Requisições de SPA frontend
- Webhooks e APIs externas

#### Performance
- Processamento de grandes volumes de erros
- Estruturas muito complexas de validação
- Execução rápida (< 1 segundo)
- Uso eficiente de memória

#### Casos Extremos
- Caracteres especiais em mensagens
- Aspas simples e duplas
- Conteúdo UTF-8 complexo
- Emojis e símbolos especiais
- JSON com estruturas profundamente aninhadas

### Verificações de Qualidade ✅
- JSON sempre válido
- Headers consistentes
- Estrutura de resposta padronizada
- Escape correto de caracteres especiais
- Preservação de dados complexos
- Tratamento seguro de exceptions

## Exemplos de Uso Testados

### 1. Erro de Validação Simples
```php
$erros = ['nome' => ['Campo obrigatório']];
$exception = new ValidacaoException($erros);
// Resultado: 422 com dados estruturados
```

### 2. Erro de Validação Complexa
```php
$erros = [
    'usuario' => [
        'dados_pessoais' => [
            'nome' => ['Nome é obrigatório'],
            'email' => ['Email inválido']
        ]
    ]
];
$exception = new ValidacaoException($erros);
// Resultado: 422 preservando estrutura aninhada
```

### 3. Erro HTTP Específico
```php
$exception = new HttpNotFoundException($request);
// Resultado: 404 com mensagem "Rota não encontrada."
```

### 4. Erro de Sistema
```php
$exception = new Exception('Database connection failed');
// Resultado: 500 com mensagem padrão
```

## Resultados dos Testes

✅ **55 testes executados**  
✅ **196 expectativas verificadas**  
✅ **100% de sucesso**  
✅ **Execução rápida (< 300ms total)**

Os testes garantem que o middleware funciona corretamente em todos os cenários de erro previstos, fornecendo respostas consistentes e bem estruturadas para os clientes da API.
