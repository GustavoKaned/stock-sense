-- =============================================================
-- MIGRAÇÃO: LOGIN MULTIEMPRESA
-- =============================================================
-- Use este arquivo APENAS se o banco stocksense já existe e você
-- não quer perder os dados já cadastrados.
--
-- Se puder recriar o banco do zero, rode stocksense.sql no lugar.
-- =============================================================

USE stocksense;


-- -------------------------------------------------------------
-- 1. Garantir que todo galpão tenha uma empresa
-- -------------------------------------------------------------

INSERT INTO empresas (nome, cnpj, email, ativo)
SELECT 'Empresa Padrão', NULL, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM empresas);

UPDATE galpoes
SET empresa_id = (SELECT MIN(id) FROM empresas)
WHERE empresa_id IS NULL;


-- -------------------------------------------------------------
-- 2. Índices que o filtro por empresa usa em toda consulta
-- -------------------------------------------------------------

CREATE INDEX idx_galpoes_empresa ON galpoes (empresa_id);
CREATE INDEX idx_prateleiras_galpao ON prateleiras (galpao_id);
CREATE INDEX idx_ocupacoes_prateleira ON ocupacoes (prateleira_id, data_saida);


-- -------------------------------------------------------------
-- 3. Usuário administrador
-- -------------------------------------------------------------
-- A senha abaixo é o hash bcrypt de "123456".
-- TROQUE a senha após o primeiro acesso.

INSERT INTO usuarios (empresa_id, nome, email, senha, cargo, ativo)
VALUES (
    (SELECT MIN(id) FROM empresas),
    'Administrador',
    'admin@stocksense.com',
    '$2y$10$MWwuAagrUNGGH/TldXJiG.EOa6b3S6ny9woWXlQvovy1bS7KKzCG2',
    'Administrador',
    1
);


-- -------------------------------------------------------------
-- 4. Converter senhas antigas em texto puro
-- -------------------------------------------------------------
-- Se já existiam usuários com senha em texto puro, elas NÃO
-- funcionarão mais (password_verify exige hash). Gere um novo
-- hash pelo PHP e aplique:
--
--   <?php echo password_hash("suaSenha", PASSWORD_DEFAULT); ?>
--
--   UPDATE usuarios SET senha = '<hash gerado>' WHERE email = '...';
