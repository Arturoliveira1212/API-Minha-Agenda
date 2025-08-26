# 🚦 **SISTEMA DE RATE LIMITING - API MINHA AGENDA**

## 📋 **RESUMO DO SISTEMA**

O sistema de Rate Limiting controla o número de requisições que um usuário/IP pode fazer em determinados períodos, protegendo a API contra:
- **🛡️ Ataques DDoS**
- **🔒 Brute force em login**
- **📊 Uso abusivo da API**
- **⚡ Sobrecarga do servidor**

---

## 🏗️ **ARQUITETURA**

### **Componentes:**
1. **`RateLimitAvancadoMiddleware`** - Middleware principal
2. **Tabela `rate_limit`** - Armazena histórico de requisições
3. **`RateLimitLimpezaCommand`** - Comando de limpeza automática
4. **Configurações flexíveis** - Limites por endpoint/usuário

### **Fluxo de Execução:**
```
Requisição → Rate Limit Middleware → Verificação → [Bloqueio OU Prosseguir] → Registro
```

---

## ⚙️ **CONFIGURAÇÃO DOS LIMITES**

### **Limites Padrão (IP não autenticado):**
```php
'limitesDefault' => [
    'minuto' => 60,     // 60 requests/minuto
    'hora' => 1000,     // 1.000 requests/hora  
    'dia' => 10000      // 10.000 requests/dia
]
```

### **Limites por Endpoint:**
```php
// Login - Muito restritivo
'POST:/api/auth/login' => [
    'minuto' => 5,      // Apenas 5 tentativas por minuto
    'hora' => 20,       // 20 tentativas por hora
    'dia' => 100        // 100 tentativas por dia
]

// Consultas - Menos restritivo  
'GET:/api/clientes' => [
    'minuto' => 100,    // 100 consultas por minuto
    'hora' => 1000,     // 1.000 consultas por hora
    'dia' => 5000       // 5.000 consultas por dia
]
```

### **Limites por Tipo de Usuário:**
```php
// Usuários autenticados (2x mais permissivo)
'authenticated' => [
    'minuto' => 120,
    'hora' => 2000, 
    'dia' => 20000
]

// Administradores (5x mais permissivo)
'admin' => [
    'minuto' => 300,
    'hora' => 5000,
    'dia' => 50000
]
```

---

## 📊 **COMO FUNCIONA**

### **1. Identificação do Cliente:**
```php
// Usuário autenticado
"user:{user_id}" 

// IP não autenticado  
"ip:{ip_address}"
```

### **2. Verificação de Limites:**
Para cada requisição, verifica:
- ✅ Quantas requests no último minuto
- ✅ Quantas requests na última hora  
- ✅ Quantas requests no último dia

### **3. Resposta quando Limite Excedido:**
```json
{
    "status": "error",
    "message": "Rate limit excedido. Limite de 5 requisições por minuto atingido.",
    "dados": {
        "erro": "RATE_LIMIT_EXCEEDED",
        "limite_atual": 5,
        "periodo": "minuto", 
        "tentativas_atuais": 6,
        "reset_em_segundos": 45,
        "limites_configurados": {
            "por_minuto": 5,
            "por_hora": 20,
            "por_dia": 100
        }
    }
}
```

**Status HTTP:** `429 Too Many Requests`

### **4. Headers de Response:**
```http
X-RateLimit-Limit-Minute: 60
X-RateLimit-Limit-Hour: 1000  
X-RateLimit-Limit-Day: 10000
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1692820800
X-RateLimit-Used: 15
```

---

## 🛠️ **INSTALAÇÃO E CONFIGURAÇÃO**

### **1. Executar Migração:**
```bash
# Via Phinx
php vendor/bin/phinx migrate

# Ou diretamente no MySQL
mysql -u usuario -p < db/migrations/20250823_cria_tabela_rate_limit.sql
```

### **2. Integrar no index.php:**
```php
use MinhaAgenda\Middleware\RateLimitAvancadoMiddleware;
use MinhaAgenda\Database\BancoDadosRelacional;

$bancoDados = new BancoDadosRelacional();

$apiV1->add(new RateLimitAvancadoMiddleware($bancoDados));
```

### **3. Configurar Limpeza Automática:**
```bash
# Adicionar ao crontab
crontab -e

# Limpar a cada hora
0 * * * * php /caminho/projeto/scripts/limpar-rate-limit.php
```

---

## 📈 **MONITORAMENTO**

### **Obter Estatísticas:**
```php
$comando = new RateLimitLimpezaCommand($bancoDados);
$stats = $comando->obterEstatisticas();

// Retorna:
// - Total de registros
// - Registros últimas 24h  
// - Top 10 IPs mais ativos
// - Top 10 endpoints mais acessados
// - Distribuição por hora
```

### **Consultas Úteis no MySQL:**
```sql
-- IPs mais ativos hoje
SELECT ip, COUNT(*) as requests 
FROM rate_limit 
WHERE DATE(timestamp) = CURDATE() 
GROUP BY ip 
ORDER BY requests DESC 
LIMIT 10;

-- Endpoints mais acessados
SELECT endpoint, COUNT(*) as requests
FROM rate_limit 
WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY endpoint 
ORDER BY requests DESC;

-- Verificar requisições de um IP específico
SELECT * FROM rate_limit 
WHERE ip = '192.168.1.100' 
AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY timestamp DESC;
```

---

## ⚠️ **CONSIDERAÇÕES IMPORTANTES**

### **Performance:**
- ✅ **Índices otimizados** na tabela para consultas rápidas
- ✅ **Limpeza automática** evita crescimento descontrolado
- ✅ **Execução rápida** - não impacta significativamente a API

### **Segurança:**
- 🔒 **Proteção por IP** para usuários não autenticados
- 🔒 **Proteção por usuário** para autenticados  
- 🔒 **Limites específicos** para endpoints críticos
- 🔒 **Logs completos** para auditoria

### **Flexibilidade:**
- ⚙️ **Configuração por endpoint** - limites específicos
- ⚙️ **Diferentes tipos de usuário** - admins têm mais limite
- ⚙️ **Fácil ajuste** dos limites sem alterar código
- ⚙️ **Desabilitação rápida** para emergências

---

## 🎯 **CENÁRIOS DE USO**

### **Endpoint de Login:**
```
❌ Tentativa 1-5: Permitidas
❌ Tentativa 6+: Bloqueadas por 1 minuto
→ Protege contra ataques de força bruta
```

### **API de Consulta:**
```  
✅ Requests 1-100: Permitidas no minuto
❌ Request 101+: Bloqueada até próximo minuto
→ Evita sobrecarga do banco
```

### **Usuário Admin:**
```
✅ Até 300 requests/minuto permitidas
→ Administradores têm acesso liberado
```

---

## 🐛 **TROUBLESHOOTING**

### **Rate Limit não está funcionando:**
1. ✅ Verificar se middleware está registrado
2. ✅ Verificar se tabela `rate_limit` existe
3. ✅ Verificar logs de erro
4. ✅ Testar com usuário não autenticado

### **Muitos falsos positivos:**
1. ⚙️ Ajustar limites por endpoint
2. ⚙️ Verificar se proxy/load balancer está passando IP real
3. ⚙️ Considerar usar hash do IP + User-Agent

### **Performance lenta:**
1. 📊 Verificar índices da tabela
2. 🧹 Executar limpeza mais frequente  
3. 🔧 Otimizar consultas de verificação

---

## 🚀 **PRÓXIMOS PASSOS**

### **Melhorias Possíveis:**
- 📊 **Dashboard web** para visualizar estatísticas
- 🔔 **Alertas automáticos** quando limites são atingidos
- 🌐 **Cache Redis** para melhor performance
- 📱 **Whitelist de IPs** confiáveis
- 🤖 **Detecção de bots** automatizada

### **Integração com Outras Ferramentas:**
- 📈 **Grafana** para dashboards
- 🔍 **ELK Stack** para logs avançados  
- 📧 **Notificações por email** para administradores
