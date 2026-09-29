USE stocksense;

-- Execute uma vez em um banco StockSense ja existente.
ALTER TABLE prateleiras
    ADD COLUMN pct_sensor DECIMAL(5,2) DEFAULT NULL AFTER pct_ocupado;

CREATE TABLE dispositivos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prateleira_id INT UNSIGNED NOT NULL,
    nome VARCHAR(80) NOT NULL,
    tipo_sensor VARCHAR(50) NOT NULL DEFAULT 'HC-SR04',
    token VARCHAR(128) NOT NULL UNIQUE,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prateleira_id) REFERENCES prateleiras(id) ON DELETE CASCADE
);

CREATE TABLE leituras (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id INT UNSIGNED NOT NULL,
    prateleira_id INT UNSIGNED NOT NULL,
    distancia_cm DECIMAL(10,2) NOT NULL,
    percentual_ocupado DECIMAL(5,2) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id) ON DELETE CASCADE,
    FOREIGN KEY (prateleira_id) REFERENCES prateleiras(id) ON DELETE CASCADE
);

CREATE INDEX idx_dispositivos_prateleira ON dispositivos (prateleira_id);
CREATE INDEX idx_leituras_dispositivo_data ON leituras (dispositivo_id, criado_em);
CREATE INDEX idx_leituras_prateleira_data ON leituras (prateleira_id, criado_em);

-- Depois, cadastre um dispositivo trocando os valores abaixo.
-- Gere um token longo e aleatorio e mantenha-o somente no servidor e no ESP.
-- INSERT INTO dispositivos (prateleira_id, nome, tipo_sensor, token)
-- VALUES (1, 'Sensor A1', 'HC-SR04', 'COLOQUE_UM_TOKEN_LONGO_AQUI');
