DROP DATABASE IF EXISTS stocksense;

CREATE DATABASE stocksense
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE stocksense;


-- ============================================
-- EMPRESAS
-- ============================================

CREATE TABLE empresas (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(150) NOT NULL,

    cnpj VARCHAR(18),

    email VARCHAR(150),

    telefone VARCHAR(20),

    endereco VARCHAR(255),

    ativo TINYINT(1) NOT NULL DEFAULT 1,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ============================================
-- USUARIOS
-- ============================================

CREATE TABLE usuarios (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    empresa_id INT UNSIGNED,

    nome VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    senha VARCHAR(255) NOT NULL,

    cargo VARCHAR(100),

    ativo TINYINT(1) NOT NULL DEFAULT 1,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
        ON DELETE SET NULL
);


-- ============================================
-- GALPOES
-- ============================================

CREATE TABLE galpoes (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    empresa_id INT UNSIGNED NOT NULL,

    nome VARCHAR(80) NOT NULL,

    descricao VARCHAR(255),

    metros_cubicos DECIMAL(10,2) DEFAULT 0.00,

    ativo TINYINT(1) NOT NULL DEFAULT 1,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
        ON DELETE CASCADE
);


-- ============================================
-- PRATELEIRAS
-- ============================================

CREATE TABLE prateleiras (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    galpao_id INT UNSIGNED NOT NULL,

    codigo VARCHAR(20) NOT NULL,

    descricao VARCHAR(255),

    metros_cubicos DECIMAL(10,2) DEFAULT 0.00,

    setor VARCHAR(100),

    pct_ocupado DECIMAL(5,2) DEFAULT 0.00,

    -- Percentual informado pelo sensor, separado do cadastro de ocupações.
    pct_sensor DECIMAL(5,2) DEFAULT NULL,

    ativo TINYINT(1) DEFAULT 1,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (galpao_id, codigo),

    FOREIGN KEY (galpao_id)
        REFERENCES galpoes(id)
        ON DELETE CASCADE
);


-- ============================================
-- OCUPACOES
-- ============================================

CREATE TABLE ocupacoes (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    prateleira_id INT UNSIGNED NOT NULL,

    produto VARCHAR(150) NOT NULL,

    setor VARCHAR(100) NOT NULL,

    volume_m3 DECIMAL(10,2) DEFAULT 0.00,

    data_entrada DATE NOT NULL,

    data_saida DATE DEFAULT NULL,

    observacao VARCHAR(255),

    usuario_id INT UNSIGNED,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (prateleira_id)
        REFERENCES prateleiras(id)
        ON DELETE CASCADE,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
);


-- ============================================
-- DISPOSITIVOS / SENSORES
-- ============================================

CREATE TABLE dispositivos (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    prateleira_id INT UNSIGNED NOT NULL,

    nome VARCHAR(80) NOT NULL,

    tipo_sensor VARCHAR(50) NOT NULL DEFAULT 'HC-SR04',

    token VARCHAR(128) NOT NULL UNIQUE,

    ativo TINYINT(1) NOT NULL DEFAULT 1,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (prateleira_id)
        REFERENCES prateleiras(id)
        ON DELETE CASCADE
);


-- ============================================
-- LEITURAS DOS SENSORES
-- ============================================

CREATE TABLE leituras (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    dispositivo_id INT UNSIGNED NOT NULL,

    prateleira_id INT UNSIGNED NOT NULL,

    distancia_cm DECIMAL(10,2) NOT NULL,

    percentual_ocupado DECIMAL(5,2) NOT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (dispositivo_id)
        REFERENCES dispositivos(id)
        ON DELETE CASCADE,

    FOREIGN KEY (prateleira_id)
        REFERENCES prateleiras(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_dispositivos_prateleira ON dispositivos (prateleira_id);
CREATE INDEX idx_leituras_dispositivo_data ON leituras (dispositivo_id, criado_em);
CREATE INDEX idx_leituras_prateleira_data ON leituras (prateleira_id, criado_em);


-- ============================================
-- ÍNDICES
-- ============================================
-- O sistema filtra tudo por empresa, então estes índices
-- sustentam as consultas mais frequentes.

CREATE INDEX idx_galpoes_empresa ON galpoes (empresa_id);
CREATE INDEX idx_prateleiras_galpao ON prateleiras (galpao_id);
CREATE INDEX idx_ocupacoes_prateleira ON ocupacoes (prateleira_id, data_saida);


-- ============================================
-- DADOS DE TESTE
-- ============================================
-- Duas empresas para demonstrar o isolamento dos dados:
-- cada login enxerga apenas o próprio estoque.

INSERT INTO empresas
(nome, cnpj, email, telefone, endereco)
VALUES
(
    'Reptec Ferramentas',
    '00.000.000/0001-00',
    'contato@reptec.com',
    '(14) 99999-9999',
    'Ourinhos - SP'
),
(
    'Nortec Distribuidora',
    '11.111.111/0001-11',
    'contato@nortec.com',
    '(11) 98888-8888',
    'Mogi das Cruzes - SP'
);


-- ============================================
-- USUARIOS
-- ============================================
-- As senhas são gravadas como hash bcrypt (password_hash do PHP).
-- Nunca em texto puro — nem nos dados de teste.
--
-- Acessos de demonstração (a senha das duas contas é 123456):
--   admin@reptec.com  -> Reptec Ferramentas
--   admin@nortec.com  -> Nortec Distribuidora

INSERT INTO usuarios
(empresa_id, nome, email, senha, cargo)
VALUES
(
    1,
    'Administrador Reptec',
    'admin@reptec.com',
    '$2y$10$sPfAgNPVs/GEWYIkxLNmYeXn2jd3B44DZiKBTZNXJ5lvkPHRpW0YS',
    'Administrador'
),
(
    2,
    'Administrador Nortec',
    'admin@nortec.com',
    '$2y$10$PRwjIKLKLL0I.BcEDQ8lYOy0cvIaeAW0RZZ.zUvduQycgTuIKeWAi',
    'Administrador'
);


-- ============================================
-- GALPOES
-- ============================================

INSERT INTO galpoes
(empresa_id, nome, descricao, metros_cubicos)
VALUES
(1, 'Galpão A', 'Galpão principal de ferramentas', 500.00),
(1, 'Galpão B', 'Galpão secundário', 300.00),
(2, 'Centro de Distribuição', 'Unidade de Mogi das Cruzes', 800.00);


-- ============================================
-- PRATELEIRAS
-- ============================================

INSERT INTO prateleiras
(galpao_id, codigo, descricao, metros_cubicos, setor)
VALUES
(1, 'A1', 'Prateleira superior esquerda', 5.00, 'Usinagem'),
(1, 'A2', 'Prateleira superior direita', 5.00, 'Usinagem'),
(1, 'B1', 'Prateleira inferior esquerda', 8.00, 'Manutenção'),
(2, 'C1', 'Prateleira do galpão B', 6.00, 'Almoxarifado'),
(3, 'D1', 'Corredor 1 - nível 1', 12.00, 'Expedição'),
(3, 'D2', 'Corredor 1 - nível 2', 12.00, 'Expedição');


-- ============================================
-- OCUPACOES
-- ============================================

INSERT INTO ocupacoes
(prateleira_id, produto, setor, volume_m3, data_entrada, observacao, usuario_id)
VALUES
(1, 'Ferramentas de usinagem', 'Usinagem', 2.00, '2026-08-18', 'Lote inicial', 1),
(3, 'Peças de reposição', 'Manutenção', 6.50, '2026-08-20', NULL, 1),
(5, 'Caixas para expedição', 'Expedição', 9.00, '2026-08-22', 'Pedido 4471', 2);


-- ============================================
-- PERCENTUAIS INICIAIS
-- ============================================
-- Calculados a partir das ocupações ativas acima.

UPDATE prateleiras p
SET pct_ocupado = LEAST(100, COALESCE((
    SELECT SUM(o.volume_m3) / p.metros_cubicos * 100
    FROM ocupacoes o
    WHERE o.prateleira_id = p.id
    AND o.data_saida IS NULL
), 0));
