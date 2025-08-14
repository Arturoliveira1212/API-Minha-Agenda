# Testes do SanitizacaoDadosMiddleware

Este conjunto de testes garante o funcionamento correto do middleware de sanitização de dados da API Minha Agenda.

## Arquivos de Teste

### 1. `SanitizacaoDadosMiddleware.spec.php`
**Testes fundamentais e de estrutura**

- ✅ Construtor e configurações
- ✅ Sanitização do corpo da requisição
- ✅ Sanitização de parâmetros de query
- ✅ Sanitização de cookies
- ✅ Sanitização de dados aninhados
- ✅ Campos excluídos
- ✅ Integração completa
- ✅ Casos extremos

**Total: 20 testes**

### 2. `SanitizacaoDadosMiddleware.verificacao.spec.php`
**Testes de verificação de sanitização**

- ✅ Verificação real da sanitização do corpo da requisição
- ✅ Verificação da sanitização de parâmetros de query
- ✅ Verificação da sanitização de cookies
- ✅ Verificação de campos excluídos
- ✅ Verificação de configurações desabilitadas
- ✅ Casos extremos de sanitização

**Total: 11 testes (48 expectativas)**

### 3. `SanitizacaoDadosMiddleware.pratico.spec.php`
**Testes de cenários práticos de uso**

- ✅ Proteção contra ataques XSS em formulários
- ✅ Configuração específica para API de blog
- ✅ Proteção de parâmetros de busca
- ✅ Middleware com configuração seletiva
- ✅ Performance com grandes volumes de dados
- ✅ Preservação de estruturas complexas

**Total: 6 testes (50 expectativas)**

## Como a Sanitização Funciona

O middleware utiliza a classe `Sanitizador::sanitizarString()` que aplica:

1. `trim()` - Remove espaços em branco no início e fim
2. `strip_tags()` - Remove tags HTML/XML
3. `htmlspecialchars()` - Escapa caracteres especiais HTML

### Exemplo de Sanitização:
```php
// Entrada
'<script>alert("xss")</script>João'

// Saída
'alert(&quot;xss&quot;)João'
```

## Configurações Suportadas

- `limparCorpoRequisicao` (bool): Sanitiza dados do corpo da requisição
- `limparParametros` (bool): Sanitiza parâmetros de query
- `limparCookies` (bool): Sanitiza cookies
- `camposExcluidos` (array): Lista de campos que não devem ser sanitizados

## Executando os Testes

### Todos os testes
```bash
./vendor/bin/kahlan --spec=test/SanitizacaoDadosMiddleware.spec.php
./vendor/bin/kahlan --spec=test/SanitizacaoDadosMiddleware.verificacao.spec.php
./vendor/bin/kahlan --spec=test/SanitizacaoDadosMiddleware.pratico.spec.php
```

### Testes específicos
```bash
# Apenas testes fundamentais
./vendor/bin/kahlan --spec=test/SanitizacaoDadosMiddleware.spec.php

# Apenas testes de verificação
./vendor/bin/kahlan --spec=test/SanitizacaoDadosMiddleware.verificacao.spec.php

# Apenas testes práticos
./vendor/bin/kahlan --spec=test/SanitizacaoDadosMiddleware.pratico.spec.php
```

## Cobertura de Testes

### Funcionalidades Testadas ✅
- Sanitização de strings com XSS
- Sanitização de arrays aninhados
- Preservação de tipos não-string
- Campos excluídos da sanitização
- Configurações seletivas do middleware
- Performance com grandes volumes
- Casos extremos e edge cases

### Proteções Verificadas ✅
- Scripts maliciosos (`<script>`)
- Tags HTML (`<b>`, `<i>`, `<div>`)
- Atributos perigosos (`onerror`, `onclick`)
- Caracteres especiais e aspas
- Tentativas de injeção de código

## Melhorias no Middleware

Durante o desenvolvimento dos testes, foi identificado e corrigido um bug no middleware onde a propriedade `$camposExcluidos` não estava sendo utilizada corretamente no método `sanitizarDados()`.

### Correção Aplicada:
```php
// Antes
$corpoRequisicaoLimpo = $this->sanitizarDados($corpoRequisicao);

// Depois
$corpoRequisicaoLimpo = $this->sanitizarDados($corpoRequisicao, $this->camposExcluidos);
```

## Resultados dos Testes

✅ **37 testes executados**  
✅ **118 expectativas verificadas**  
✅ **100% de sucesso**  
✅ **Execução rápida (< 1 segundo)**

Os testes garantem que o middleware funciona corretamente em todos os cenários de uso previstos, protegendo a aplicação contra ataques XSS e injeção de código malicioso.
