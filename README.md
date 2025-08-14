# API-Minha-Agenda

Uma API REST para gerenciamento de agenda desenvolvida em PHP.

## 🐳 Como levantar o ambiente com Docker

### Pré-requisitos
- [Docker](https://www.docker.com/) instalado
- [Docker Compose](https://docs.docker.com/compose/) instalado

### Passos para inicializar o ambiente

1. **Clone o repositório**
   ```bash
   git clone https://github.com/Arturoliveira1212/API-Minha-Agenda.git
   cd API-Minha-Agenda
   ```

2. **Configure as variáveis de ambiente**
   ```bash
   cp .env.example .env
   ```
   
   Edite o arquivo `.env` com suas configurações:
   ```bash
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=minha_agenda
   DB_USERNAME=api_user
   DB_PASSWORD=senha123
   DB_PASSWORD_ROOT=root123
   
   SECRET_KEY_JWT=sua_chave_secreta_jwt_aqui
   ```

3. **Levante os contêineres (automatizado)**
   ```bash
   docker compose up -d --build
   ```
   
   🎯 **Tudo acontece automaticamente:**
   - ✅ Dependências do Composer instaladas
   - ✅ Migrações do banco executadas
   - ✅ Servidor web iniciado

4. **Verifique se tudo está funcionando**
   ```bash
   docker compose ps
   docker compose logs api
   ```

### Serviços disponíveis

Após executar `docker-compose up -d`, os seguintes serviços estarão disponíveis:

- **API**: http://localhost:8080
- **MySQL**: localhost:3306
- **phpMyAdmin**: http://localhost:8081

### Comandos úteis

- **Ver logs dos contêineres**
  ```bash
  docker-compose logs -f
  ```

- **Parar os contêineres**
  ```bash
  docker-compose down
  ```

- **Reiniciar os contêineres**
  ```bash
  docker-compose restart
  ```

- **Executar comandos dentro do contêiner da API**
  ```bash
  docker-compose exec api bash
  ```

- **Acessar o MySQL via linha de comando**
  ```bash
  docker-compose exec db mysql -u root -p
  ```

### 🧪 Executar Testes

O projeto utiliza o framework **Kahlan** para testes unitários e de integração.

- **Executar todos os testes**
  ```bash
  docker-compose exec api vendor/bin/kahlan --spec=test
  ```

- **Executar um arquivo específico**
  ```bash
  docker-compose exec api vendor/bin/kahlan --spec=test/Teste.spec.php
  ```

- **Executar com mais verbosidade**
  ```bash
  docker-compose exec api vendor/bin/kahlan --spec=test --reporter=verbose
  ```

- **Executar com coverage (se configurado)**
  ```bash
  docker-compose exec api vendor/bin/kahlan --spec=test --coverage=4
  ```

**📝 Estrutura recomendada para testes:**
```php
<?php

describe('NomeDoComponente', function () {
    
    beforeEach(function () {
        // Configuração que roda antes de cada teste
    });
    
    it('deve fazer algo específico', function () {
        // Arrange
        $valor = true;
        
        // Act & Assert
        expect($valor)->toBe(true);
    });
});
```

**🔧 Matchers comuns do Kahlan:**
- `expect($valor)->toBe($esperado)` - Igualdade estrita
- `expect($valor)->toEqual($esperado)` - Igualdade não estrita
- `expect($valor)->toBeNull()` - Verifica null
- `expect($valor)->toBeEmpty()` - Verifica vazio
- `expect($valor)->toContain($item)` - Array/string contém
- `expect($closure)->toThrow()` - Verifica exceções

### Estrutura dos contêineres

- **api**: Contêiner principal com PHP 8.2 + Apache, expondo a API na porta 8080
- **db**: Contêiner MySQL 8.0 para persistência de dados
- **phpmyadmin**: Interface web para gerenciamento do banco de dados

### Troubleshooting

- Se houver problemas de permissão, execute: `sudo chown -R $USER:$USER .`
- Para recriar os contêineres: `docker-compose down && docker-compose up -d --build`
- Para limpar volumes: `docker-compose down -v`

## 🔧 Para Desenvolvedores

Para informações técnicas detalhadas, problemas comuns, configurações avançadas e guias de desenvolvimento, consulte o **[Guia do Desenvolvedor](DEVELOPER-GUIDE.md)**.

O guia contém:
- Estrutura detalhada do projeto
- Gerenciamento de migrações com Phinx
- Soluções para problemas comuns
- Comandos úteis para desenvolvimento
- Boas práticas de segurança
