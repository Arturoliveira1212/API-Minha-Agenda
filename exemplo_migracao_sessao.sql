-- ======================================================
-- MIGRAÇÃO PARA TABELA SESSAO - ABORDAGEM UNIFICADA
-- ======================================================

-- 1. Criar tabela de usuários unificada
CREATE TABLE usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'funcionario', 'gerente', 'administrador') NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    dataCriacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    dataAtualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_tipo (tipo),
    INDEX idx_ativo (ativo)
) ENGINE=INNODB;

-- 2. Migrar dados das tabelas cliente e administrador para usuario
-- (Assumindo que as tabelas cliente e administrador existem)
INSERT INTO usuario (nome, email, senha, tipo, ativo, dataCriacao, dataAtualizacao)
SELECT nome, email, senha, 'cliente' as tipo, ativo, dataCriacao, dataAtualizacao
FROM cliente
WHERE ativo = 1;

INSERT INTO usuario (nome, email, senha, tipo, ativo, dataCriacao, dataAtualizacao)
SELECT nome, email, senha, tipo, ativo, dataCriacao, dataAtualizacao
FROM administrador
WHERE ativo = 1;

-- 3. Criar tabela de sessões
CREATE TABLE sessao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    idUsuario INT NOT NULL,
    accessToken VARCHAR(512) NOT NULL,
    dataCriacaoAccessToken DATETIME NOT NULL,
    dataExpiracaoAccessToken DATETIME NOT NULL,
    refreshToken VARCHAR(512) NOT NULL,
    dataCriacaoRefreshToken DATETIME NOT NULL,
    dataExpiracaoRefreshToken DATETIME NOT NULL,
    revogado TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    dataCriacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    dataAtualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_sessao_usuario FOREIGN KEY (idUsuario) 
        REFERENCES usuario(id) ON DELETE CASCADE ON UPDATE NO ACTION,
    
    INDEX idx_access_token (accessToken),
    INDEX idx_refresh_token (refreshToken),
    INDEX idx_id_usuario (idUsuario),
    INDEX idx_revogado (revogado),
    INDEX idx_ativo (ativo)
) ENGINE=INNODB;

-- 4. Exemplos de consultas que você pode fazer:

-- Buscar sessões de todos os tipos de usuário
SELECT s.*, u.nome, u.email, u.tipo
FROM sessao s
INNER JOIN usuario u ON s.idUsuario = u.id
WHERE s.ativo = 1 AND s.revogado = 0;

-- Buscar sessões apenas de clientes
SELECT s.*, u.nome, u.email
FROM sessao s
INNER JOIN usuario u ON s.idUsuario = u.id
WHERE u.tipo = 'cliente' AND s.ativo = 1 AND s.revogado = 0;

-- Buscar sessões de administradores (funcionarios, gerentes, administradores)
SELECT s.*, u.nome, u.email, u.tipo
FROM sessao s
INNER JOIN usuario u ON s.idUsuario = u.id
WHERE u.tipo IN ('funcionario', 'gerente', 'administrador') 
  AND s.ativo = 1 AND s.revogado = 0;

-- Validar token de acesso
SELECT s.*, u.nome, u.email, u.tipo
FROM sessao s
INNER JOIN usuario u ON s.idUsuario = u.id
WHERE s.accessToken = 'token_aqui' 
  AND s.dataExpiracaoAccessToken > NOW()
  AND s.revogado = 0 
  AND s.ativo = 1
  AND u.ativo = 1;
