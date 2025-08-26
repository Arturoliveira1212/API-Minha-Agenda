# 🔄 **FLUXO COMPLETO DE SESSÃO DA API**

## 📋 **Resumo do Fluxo Correto**

### ✅ **O QUE FAZER:**
1. **Login inicial**: Cria NOVA linha na tabela `sessao`
2. **Renovação**: ATUALIZA a linha existente (NÃO cria nova)
3. **Logout**: Marca a sessão como `revogado = 1`

### ❌ **O QUE NÃO FAZER:**
- **NÃO** criar nova linha a cada renovação de token
- **NÃO** manter múltiplas sessões ativas do mesmo usuário desnecessariamente

---

## 🚀 **1. FLUXO DE LOGIN INICIAL**

```
POST /api/auth/login
{
    "email": "cliente@email.com",
    "senha": "minhasenha123"
}
```

**Processo:**
1. Valida credenciais do usuário
2. Gera `access_token` (15 minutos) + `refresh_token` (30 dias)
3. **CRIA NOVA LINHA** na tabela `sessao`
4. Retorna os tokens para o cliente

**SQL executado:**
```sql
INSERT INTO sessao (
    idUsuario, accessToken, refreshToken, 
    dataCriacaoAccessToken, dataExpiracaoAccessToken,
    dataCriacaoRefreshToken, dataExpiracaoRefreshToken,
    dataCriacao, dataAtualizacao, revogado, ativo
) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 0, 1);
```

**Resposta:**
```json
{
    "status": "success",
    "message": "Login realizado com sucesso",
    "dados": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhb...",
        "refresh_token": "def50200abc123...",
        "expires_in": 900,
        "token_type": "Bearer"
    }
}
```

---

## 🔄 **2. FLUXO DE RENOVAÇÃO DE TOKEN**

```
POST /api/auth/refresh
{
    "refresh_token": "def50200abc123..."
}
```

**Processo:**
1. Valida o `refresh_token` (se existe, não está expirado, não foi revogado)
2. Gera NOVOS `access_token` + `refresh_token`
3. **ATUALIZA A LINHA EXISTENTE** na tabela `sessao`
4. Retorna os novos tokens

**SQL executado:**
```sql
-- 1. Busca a sessão pelo refresh_token
SELECT * FROM sessao 
WHERE refreshToken = ? 
  AND dataExpiracaoRefreshToken > NOW() 
  AND revogado = 0 
  AND ativo = 1;

-- 2. ATUALIZA a sessão existente (NÃO cria nova!)
UPDATE sessao SET
    accessToken = ?,
    dataCriacaoAccessToken = NOW(),
    dataExpiracaoAccessToken = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
    refreshToken = ?,
    dataCriacaoRefreshToken = NOW(),
    dataExpiracaoRefreshToken = DATE_ADD(NOW(), INTERVAL 30 DAY),
    dataAtualizacao = NOW()
WHERE id = ?;
```

**Resposta:**
```json
{
    "status": "success",
    "message": "Token renovado com sucesso",
    "dados": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhb...",
        "refresh_token": "def50200xyz789...",
        "expires_in": 900,
        "token_type": "Bearer"
    }
}
```

---

## 🚪 **3. FLUXO DE LOGOUT**

```
POST /api/auth/logout
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhb...
```

**Processo:**
1. Identifica a sessão pelo `access_token`
2. Marca a sessão como revogada
3. Token não pode mais ser usado

**SQL executado:**
```sql
UPDATE sessao 
SET revogado = 1, dataAtualizacao = NOW() 
WHERE accessToken = ? AND ativo = 1;
```

---

## 🛡️ **4. VALIDAÇÃO DE ACCESS TOKEN**

```
GET /api/usuarios/perfil
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhb...
```

**Processo:**
1. Extrai o token do header `Authorization`
2. Valida se o token existe, não expirou e não foi revogado
3. Retorna dados do usuário autenticado

**SQL executado:**
```sql
SELECT s.*, u.nome, u.email, u.tipo
FROM sessao s
INNER JOIN usuario u ON s.idUsuario = u.id
WHERE s.accessToken = ?
  AND s.dataExpiracaoAccessToken > NOW()
  AND s.revogado = 0
  AND s.ativo = 1
  AND u.ativo = 1;
```

---

## 📊 **5. ESTADOS DA SESSÃO NA TABELA**

| Campo | Valor | Significado |
|-------|-------|------------|
| `revogado` | `0` | Sessão ativa e válida |
| `revogado` | `1` | Sessão revogada (logout) |
| `ativo` | `1` | Registro ativo no sistema |
| `ativo` | `0` | Registro desativado (limpeza) |
| `dataExpiracaoAccessToken` | `> NOW()` | Access token válido |
| `dataExpiracaoAccessToken` | `< NOW()` | Access token expirado |
| `dataExpiracaoRefreshToken` | `> NOW()` | Refresh token válido |
| `dataExpiracaoRefreshToken` | `< NOW()` | Refresh token expirado |

---

## 🧹 **6. LIMPEZA AUTOMÁTICA**

Recomendo criar um job/cronjob que execute periodicamente:

```sql
-- Remove sessões completamente expiradas
UPDATE sessao 
SET ativo = 0 
WHERE dataExpiracaoRefreshToken < NOW() 
  AND ativo = 1;

-- Ou delete definitivo após 90 dias
DELETE FROM sessao 
WHERE dataExpiracaoRefreshToken < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 💡 **EXEMPLO PRÁTICO DO FLUXO**

### Situação: Cliente faz login e depois renova o token

```
1. LOGIN INICIAL:
   POST /api/auth/login → Cria linha ID=1 na tabela sessao
   
   Tabela sessao:
   | id | idUsuario | accessToken | refreshToken | revogado |
   |----|-----------|-------------|--------------|----------|
   | 1  | 123       | token_abc   | refresh_xyz  | 0        |

2. RENOVAÇÃO (após 10 minutos):
   POST /api/auth/refresh → ATUALIZA linha ID=1 (NÃO cria nova!)
   
   Tabela sessao:
   | id | idUsuario | accessToken | refreshToken | revogado |
   |----|-----------|-------------|--------------|----------|
   | 1  | 123       | token_def   | refresh_uvw  | 0        |
   
3. LOGOUT:
   POST /api/auth/logout → Marca linha ID=1 como revogada
   
   Tabela sessao:
   | id | idUsuario | accessToken | refreshToken | revogado |
   |----|-----------|-------------|--------------|----------|
   | 1  | 123       | token_def   | refresh_uvw  | 1        |
```

### ✅ **RESULTADO ESPERADO**: 
- **1 única linha** na tabela para toda a sessão do usuário
- **Atualização** da linha existente na renovação
- **Não proliferação** de linhas desnecessárias

---

## 🎯 **VANTAGENS DESTA ABORDAGEM**

1. **🗄️ Economia de espaço**: Não acumula linhas desnecessárias
2. **⚡ Performance**: Consultas mais rápidas
3. **🔒 Segurança**: Controle único de sessão por usuário
4. **📈 Escalabilidade**: Não cresce descontroladamente
5. **🧹 Manutenção**: Mais fácil de gerenciar e limpar

---

## ⚠️ **ATENÇÃO ESPECIAL**

Se você quiser permitir **múltiplas sessões simultâneas** (usuário logado no celular + desktop), aí sim você criaria uma nova linha a cada login em um dispositivo diferente. Mas ainda assim, a renovação de cada sessão individual deveria **atualizar** a linha existente, não criar nova.

**Exemplo com múltiplas sessões:**
```
Login no Desktop  → Cria linha ID=1 
Login no Mobile   → Cria linha ID=2
Renova Desktop    → ATUALIZA linha ID=1
Renova Mobile     → ATUALIZA linha ID=2
```
