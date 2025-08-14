# 🔧 Guia do Desenvolvedor - API Minha Agenda

Este documento contém informações técnicas importantes para desenvolvedores que trabalham neste projeto.

## 📋 Índice
- [Configuração do Ambiente](#configuração-do-ambiente)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Banco de Dados e Migrações](#banco-de-dados-e-migrações)
- [Problemas Comuns e Soluções](#problemas-comuns-e-soluções)
- [Comandos Úteis](#comandos-úteis)

---

## 🐳 Configuração do Ambiente

### Pré-requisitos
- Docker e Docker Compose instalados
- Git configurado

### Configuração Inicial

1. **Clone e configure o projeto:**
   ```bash
   git clone https://github.com/Arturoliveira1212/API-Minha-Agenda.git
   cd API-Minha-Agenda
   cp .env.example .env
   ```

2. **Configure as variáveis de ambiente no `.env`:**
   ```bash
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=minha-agenda
   DB_USERNAME=api
   DB_PASSWORD=api
   DB_PASSWORD_ROOT=root
   
   SECRET_KEY_JWT="sua_chave_secreta_jwt_aqui"
   ```

3. **Levante o ambiente (automatizado):**
   ```bash
   docker compose up -d --build
   ```

   ✨ **Automatização incluída:**
   - ✅ Dependências do Composer instaladas automaticamente
   - ✅ Migrações executadas automaticamente
   - ✅ Apache iniciado após tudo estar pronto

4. **Verificar logs (opcional):**
   ```bash
   docker compose logs api
   ```

---

## 🏗️ Estrutura do Projeto

```
API-Minha-Agenda/
├── src/                    # Código fonte da aplicação
│   ├── controller/         # Controllers da API
│   ├── model/             # Models e entidades
│   ├── service/           # Serviços de negócio
│   ├── database/          # Configurações de banco
│   ├── middleware/        # Middlewares
│   └── ...
├── public/                # Ponto de entrada da aplicação
├── db/                    # Migrações e seeds do banco
│   ├── migrations/        # Arquivos de migração
│   ├── seeds/            # Dados de seed
│   └── templates/        # Templates do Phinx
├── routes/               # Definições de rotas
├── docker/              # 🐳 Configurações Docker
│   ├── apache.conf       # Configuração customizada do Apache
│   └── start.sh         # Script de inicialização do contêiner
├── .env                 # Variáveis de ambiente
├── .dockerignore        # Arquivos ignorados no build Docker
├── bootstrap.php        # Inicialização da aplicação
├── phinx.php           # Configuração do Phinx
└── docker-compose.yml  # Orquestração dos contêineres
```

### **📁 Pasta `docker/`**
Contém todos os arquivos relacionados à configuração Docker:

- **`apache.conf`**: Configuração personalizada do Apache
  - Define DocumentRoot como `/var/www/html/public`
  - Habilita mod_rewrite para URLs amigáveis
  - Configura permissões adequadas

- **`start.sh`**: Script de inicialização do contêiner
  - Aguarda MySQL estar disponível
  - Executa migrações automaticamente
  - Executa seeds (se existirem)
  - Inicia Apache com logs informativos

---

## 🗄️ Banco de Dados e Migrações

### Phinx - Gerenciamento de Migrações

O projeto usa o **Phinx** para gerenciamento de banco de dados.

#### Comandos do Phinx

```bash
# Ver status das migrações
docker compose exec api vendor/bin/phinx status

# Criar nova migração
docker compose exec api vendor/bin/phinx create NomeDaMigracao

# Executar migrações
docker compose exec api vendor/bin/phinx migrate

# Rollback da última migração
docker compose exec api vendor/bin/phinx rollback

# Executar seeds
docker compose exec api vendor/bin/phinx seed:run
```

#### Estrutura de uma Migração

```php
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CriarTabelaUsuarios extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios');
        $table->addColumn('nome', 'string', ['limit' => 100])
              ->addColumn('email', 'string', ['limit' => 150])
              ->addColumn('senha', 'string', ['limit' => 255])
              ->addTimestamps()
              ->create();
    }
}
```

### 🌱 Gerenciamento de Seeds (Dados Iniciais)

Seeds são usados para popular o banco de dados com dados iniciais ou de teste.

#### Comandos de Seeds

```bash
# Criar um novo seed
docker compose exec api vendor/bin/phinx seed:create NomeDoSeeder

# Exemplos de criação
docker compose exec api vendor/bin/phinx seed:create UsuarioSeeder
docker compose exec api vendor/bin/phinx seed:create CategoriaSeeder
docker compose exec api vendor/bin/phinx seed:create DadosTesteSeeder

# Executar todos os seeds
docker compose exec api vendor/bin/phinx seed:run

# Executar seed específico
docker compose exec api vendor/bin/phinx seed:run -s UsuarioSeeder

# Simular execução (dry-run)
docker compose exec api vendor/bin/phinx seed:run --dry-run

# Ver status dos seeds
docker compose exec api vendor/bin/phinx status
```

#### Estrutura de um Seed

```php
<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UsuarioSeeder extends AbstractSeed {
    
    // Define dependências (seeds que devem executar antes)
    public function getDependencies(): array {
        return []; // ou ['OutroSeeder'] se depender de outro
    }

    public function run(): void {
        // Método 1: Usando table() (recomendado)
        $dados = [
            [
                'nome' => 'Admin',
                'email' => 'admin@example.com',
                'senha' => password_hash('123456', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'nome' => 'João Silva',
                'email' => 'joao@example.com',
                'senha' => password_hash('123456', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Limpa a tabela antes de inserir
        $this->execute('DELETE FROM usuarios');
        
        // Insere os dados
        $this->table('usuarios')
             ->insert($dados)
             ->save();

        // Método 2: Usando SQL direto (alternativo)
        /*
        $sql = <<<SQL
            DELETE FROM usuarios;
            
            INSERT INTO usuarios (nome, email, senha, created_at, updated_at) VALUES
            ('Admin', 'admin@example.com', 'hash_da_senha', NOW(), NOW()),
            ('João', 'joao@example.com', 'hash_da_senha', NOW(), NOW())
        SQL;
        $this->execute($sql);
        */
    }
}
```

#### Boas Práticas para Seeds

1. **Sempre limpe antes de inserir:**
   ```php
   $this->execute('DELETE FROM tabela');
   ```

2. **Use nomes descritivos:**
   - `UsuarioSeeder` - para usuários
   - `CategoriaSeeder` - para categorias
   - `DadosIniciais` - para dados básicos

3. **Defina dependências se necessário:**
   ```php
   public function getDependencies(): array {
       return ['CategoriaSeeder']; // Execute CategoriaSeeder primeiro
   }
   ```

4. **Hash senhas adequadamente:**
   ```php
   'senha' => password_hash('123456', PASSWORD_DEFAULT)
   ```

5. **Use timestamps:**
   ```php
   'created_at' => date('Y-m-d H:i:s'),
   'updated_at' => date('Y-m-d H:i:s')
   ```

6. **Organize por ambiente:**
   - Seeds de desenvolvimento: dados de teste
   - Seeds de produção: dados mínimos necessários

---

## ⚠️ Problemas Comuns e Soluções

### 1. Erro: "Unable to read environment file"

**Problema:** Phinx não encontra o arquivo `.env`

**Solução:** Verificar se o `ROOT_PATH` no `bootstrap.php` está correto:
```php
define('ROOT_PATH', __DIR__); // ✅ Correto
// define('ROOT_PATH', dirname(__DIR__)); // ❌ Incorreto
```

### 2. Erro: Variáveis com caracteres especiais no .env

**Problema:** Docker Compose interpreta `$`, `%`, `&` como variáveis

**Solução:** Usar aspas duplas e remover caracteres problemáticos:
```bash
# ❌ Problemático
SECRET_KEY_JWT=chave$com%caracteres&especiais

# ✅ Correto
SECRET_KEY_JWT="chave_com_caracteres_especiais"
```

### 3. Mudanças no .env não aplicadas

**Problema:** Contêineres mantêm configurações antigas

**Solução:** Reiniciar contêineres:
```bash
docker compose down
docker compose up -d
```

### 4. Erro de conexão com banco após mudança no DB_DATABASE

**Problema:** Volume antigo mantém banco com nome anterior

**Solução:** Remover volumes e recriar:
```bash
docker compose down -v
docker compose up -d
```

---

## 🛠️ Comandos Úteis

### Docker

```bash
# Ver logs dos contêineres
docker compose logs -f

# Entrar no contêiner da API
docker compose exec api bash

# Entrar no MySQL
docker compose exec db mysql -u root -proot

# Ver status dos contêineres
docker compose ps

# Parar ambiente
docker compose down

# Recriar contêineres
docker compose down && docker compose up -d --build
```

### Composer

```bash
# Instalar dependências
docker compose exec api composer install

# Atualizar dependências
docker compose exec api composer update

# Instalar nova dependência
docker compose exec api composer require nome/pacote

# Instalar dependência de desenvolvimento
docker compose exec api composer require --dev nome/pacote
```

### Testes

```bash
# Executar testes (se configurado)
docker compose exec api vendor/bin/kahlan
```

---

## 🌐 Serviços Disponíveis

Após executar `docker compose up -d`:

| Serviço | URL | Descrição |
|---------|-----|-----------|
| API | http://localhost:8080 | Aplicação principal |
| MySQL | localhost:3306 | Banco de dados |
| phpMyAdmin | http://localhost:8081 | Interface web para MySQL |

---

## 📝 Arquivos de Configuração Importantes

### `bootstrap.php`
- Carrega autoloader do Composer
- Define constantes do projeto
- Carrega variáveis de ambiente
- Configura relatório de erros

### `phinx.php`
- Configuração do Phinx
- Conexão com banco via PDO
- Definição de ambientes (dev, prod, test)

### `docker-compose.yml`
- Orquestração dos serviços
- Configuração de portas e volumes
- Variáveis de ambiente dos contêineres

### `Dockerfile` - Automatização
O Dockerfile foi configurado para automação completa:

#### **Processo de Inicialização:**
1. **Build**: Instala dependências do sistema e Composer
2. **Setup**: Copia código e instala dependências PHP
3. **Runtime**: Executa script de inicialização que:
   - Aguarda MySQL estar disponível
   - Executa migrações automaticamente
   - Executa seeds (se existirem)
   - Inicia Apache

#### **Script de Inicialização** (`docker/start.sh`):
```bash
#!/bin/bash
# Script de inicialização para o contêiner da API

echo "=== Iniciando API Minha Agenda ==="

# Aguarda o MySQL estar disponível
echo "🔄 Aguardando MySQL estar disponível..."
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    echo "   ⏳ MySQL ainda não está pronto, aguardando..."
    sleep 2
done
echo "✅ MySQL está disponível!"

# Executa as migrações do banco de dados
echo "🔄 Executando migrações do banco de dados..."
if vendor/bin/phinx migrate; then
    echo "✅ Migrações executadas com sucesso!"
else
    echo "❌ Erro ao executar migrações!"
    exit 1
fi

# Executa seeds se existirem (opcional)
echo "🔄 Verificando se há seeds para executar..."
if [ -n "$(ls -A db/seeds 2>/dev/null)" ]; then
    echo "📊 Executando seeds..."
    vendor/bin/phinx seed:run
    echo "✅ Seeds executados com sucesso!"
else
    echo "ℹ️  Nenhum seed encontrado, continuando..."
fi

# Inicia o servidor Apache
echo "🚀 Iniciando servidor Apache..."
echo "🌐 API estará disponível em http://localhost:8080"
exec apache2-foreground
```

#### **Benefícios da Nova Estrutura:**
- 🚀 **Zero configuração manual** após `docker compose up`
- ⚡ **Setup instantâneo** para novos desenvolvedores
- 🔄 **Consistência** entre ambientes
- 🛡️ **Menos erros** humanos

---

## 🔒 Segurança

### Variáveis Sensíveis
- ❌ Nunca commitar arquivo `.env`
- ✅ Manter `.env.example` atualizado
- ✅ Usar senhas fortes em produção
- ✅ Regenerar `SECRET_KEY_JWT` em produção

### Boas Práticas
- Usar HTTPS em produção
- Validar todas as entradas
- Sanitizar dados antes de inserir no banco
- Implementar rate limiting
- Logs de auditoria para ações sensíveis

---

## 📚 Recursos Adicionais

- [Documentação do Phinx](https://book.cakephp.org/phinx/0/en/index.html)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [PHP 8.2 Documentation](https://www.php.net/manual/en/)

---

*Este documento deve ser mantido atualizado conforme o projeto evolui.*
