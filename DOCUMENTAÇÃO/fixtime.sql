CREATE DATABASE IF NOT EXISTS fixTime;
USE fixTime;


CREATE TABLE IF NOT EXISTS cliente (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome_usuario VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone_usuario VARCHAR(15),
    email_usuario VARCHAR(100) NOT NULL UNIQUE,
    senha_usuario VARCHAR(255) NOT NULL,
    data_cadastro_usuario TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



CREATE TABLE IF NOT EXISTS oficina (
    id_oficina INT AUTO_INCREMENT PRIMARY KEY,
    categoria ENUM('Borracharia', 'Auto Elétrica', 'Oficina Mecânica', 'Lava Car') NOT NULL,
    nome_oficina VARCHAR(100) NOT NULL,
    cep_oficina VARCHAR(9) NOT NULL,
    cnpj VARCHAR(18) NOT NULL UNIQUE,
    endereco_oficina VARCHAR(100) NOT NULL,
    numero_oficina VARCHAR(10) NOT NULL,
    complemento VARCHAR(50),
    bairro_oficina VARCHAR(50) NOT NULL,
    cidade_oficina VARCHAR(50) NOT NULL,
    estado_oficina CHAR(2) NOT NULL,
    telefone_oficina VARCHAR(15) NOT NULL,
    email_oficina VARCHAR(100) NOT NULL UNIQUE,
    senha_oficina VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS servicos_padrao (
    id_servico_padrao INT AUTO_INCREMENT PRIMARY KEY,
    nome_servico VARCHAR(100) NOT NULL,
    categoria ENUM('Borracharia', 'Auto Elétrica', 'Oficina Mecânica', 'Lava Car') NOT NULL
);


CREATE TABLE IF NOT EXISTS oficina_servicos (
    id_oficina_servico INT AUTO_INCREMENT PRIMARY KEY,
    id_oficina INT NOT NULL,
    id_servico_padrao INT NOT NULL,
    FOREIGN KEY (id_oficina) REFERENCES oficina(id_oficina),
    FOREIGN KEY (id_servico_padrao) REFERENCES servicos_padrao(id_servico_padrao)
);


CREATE TABLE veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_veiculo ENUM('carro', 'moto', 'caminhao', 'van', 'onibus') NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    ano INT NOT NULL,
    cor VARCHAR(30) NOT NULL,
    placa VARCHAR(10) NOT NULL UNIQUE,
    quilometragem DECIMAL(10,2) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_usuario INT NOT NULL, 
    FOREIGN KEY (id_usuario) REFERENCES cliente(id_usuario)
);

CREATE TABLE IF NOT EXISTS funcionarios (
    id_funcionario INT AUTO_INCREMENT PRIMARY KEY,
    nome_funcionario VARCHAR(100) NOT NULL,
    cargo_funcionario VARCHAR(50) NOT NULL,
    telefone_funcionario VARCHAR(15),
    email_funcionario VARCHAR(100) UNIQUE,
    cpf_funcionario VARCHAR(14) NOT NULL,
    data_admissao DATE NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_oficina INT NOT NULL,
    FOREIGN KEY (id_oficina) REFERENCES oficina(id_oficina)
);

CREATE TABLE avaliacao (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_oficina INT NOT NULL,
    id_servico INT NOT NULL,
    estrelas INT CHECK(estrelas BETWEEN 1 AND 5),
    data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES cliente(id_usuario),
    FOREIGN KEY (id_oficina) REFERENCES oficina(id_oficina),
    FOREIGN KEY (id_servico) REFERENCES servico(id_servico),
    UNIQUE KEY unique_avaliacao_servico (id_servico)
);



CREATE TABLE IF NOT EXISTS servico (
    id_servico INT AUTO_INCREMENT PRIMARY KEY,
    data_agendada DATE NOT NULL,
    horario TIME NOT NULL,
    data_entrega DATE NULL,
    status ENUM(
        'Pendente', 
        'Em Andamento', 
        'Aguardando Peças', 
        'Aguardando Retirada', 
        'Concluído', 
        'Cancelado'
    ) DEFAULT 'Pendente',
    descricao_servico VARCHAR(1000) NULL,
    id_veiculo INT NOT NULL,
    id_oficina INT NOT NULL,
    id_funcionario_responsavel INT NULL, 
    FOREIGN KEY (id_veiculo) REFERENCES veiculos(id),
    FOREIGN KEY (id_oficina) REFERENCES oficina(id_oficina),
    FOREIGN KEY (id_funcionario_responsavel) REFERENCES funcionarios(id_funcionario), 
    UNIQUE (id_oficina, data_agendada, horario)
);


CREATE TABLE IF NOT EXISTS servico_funcionario (
    id_servico_funcionario INT AUTO_INCREMENT PRIMARY KEY,
    id_servico INT NOT NULL,
    id_funcionario INT NOT NULL,
    FOREIGN KEY (id_servico) REFERENCES servico(id_servico),
    FOREIGN KEY (id_funcionario) REFERENCES funcionarios(id_funcionario)
);

INSERT INTO servicos_padrao (nome_servico, categoria) VALUES
-- BORRACHARIA (15)
('Pneu furado', 'Borracharia'),
('Troca de pneu', 'Borracharia'),
('Alinhamento', 'Borracharia'),
('Balanceamento', 'Borracharia'),
('Calibragem de pneus', 'Borracharia'),
('Reparo na câmara de ar', 'Borracharia'),
('Vulcanização de pneus', 'Borracharia'),
('Rodízio de pneus', 'Borracharia'),
('Desgaste de pneus', 'Borracharia'),
('Montagem/desmontagem pneu', 'Borracharia'),
('Inspeção de rodas', 'Borracharia'),
('Conserto de roda', 'Borracharia'),
('Ajuste de pressão', 'Borracharia'),
('Remendo rápido', 'Borracharia'),
('Serviço emergencial', 'Borracharia'),

-- OFICINA MECÂNICA (15)
('Troca de óleo', 'Oficina Mecânica'),
('Troca de filtros', 'Oficina Mecânica'),
('Revisão preventiva', 'Oficina Mecânica'),
('Troca de pastilhas', 'Oficina Mecânica'),
('Troca de amortecedor', 'Oficina Mecânica'),
('Reparo no arrefecimento', 'Oficina Mecânica'),
('Troca da correia dentada', 'Oficina Mecânica'),
('Manutenção do motor', 'Oficina Mecânica'),
('Troca de embreagem', 'Oficina Mecânica'),
('Alinhamento/balanceamento', 'Oficina Mecânica'),
('Substituir velas', 'Oficina Mecânica'),
('Reparo de suspensão', 'Oficina Mecânica'),
('Regulagem de freio', 'Oficina Mecânica'),
('Verificação geral', 'Oficina Mecânica'),
('Troca de bicos', 'Oficina Mecânica'),

-- LAVA CAR (15)
('Lavagem simples', 'Lava Car'),
('Lavagem completa', 'Lava Car'),
('Lavagem de motor', 'Lava Car'),
('Lavagem a seco', 'Lava Car'),
('Polimento de pintura', 'Lava Car'),
('Higienização interna', 'Lava Car'),
('Enceramento', 'Lava Car'),
('Cristalização pintura', 'Lava Car'),
('Hidratação bancos couro', 'Lava Car'),
('Descontaminação pintura', 'Lava Car'),
('Aromatização interna', 'Lava Car'),
('Limpeza de carpete', 'Lava Car'),
('Lavagem de rodas', 'Lava Car'),
('Lavagem de teto', 'Lava Car'),
('Lavagem ecológica', 'Lava Car'),

-- AUTO ELÉTRICA (15)
('Diagnóstico elétrico', 'Auto Elétrica'),
('Troca de bateria', 'Auto Elétrica'),
('Reparo do alternador', 'Auto Elétrica'),
('Reparo motor de partida', 'Auto Elétrica'),
('Instalação som automotivo', 'Auto Elétrica'),
('Instalação de alarme', 'Auto Elétrica'),
('Troca de lâmpadas', 'Auto Elétrica'),
('Correção curto-circuito', 'Auto Elétrica'),
('Instalação de rastreador', 'Auto Elétrica'),
('Reparo injeção eletrônica', 'Auto Elétrica'),
('Instalação de farol', 'Auto Elétrica'),
('Reparo painel digital', 'Auto Elétrica'),
('Troca de fusíveis', 'Auto Elétrica'),
('Verificação chicote', 'Auto Elétrica'),
('Instalação de buzina', 'Auto Elétrica');



SELECT * FROM cliente;
SELECT * FROM veiculos;
SELECT * FROM oficina;
SELECT * FROM funcionarios;
SELECT * FROM servicos_padrao;
SELECT * FROM servico;
SELECT * FROM oficina_servicos;


-- Seleciona todas as colunas de uma tabela
SELECT * FROM tabela;

-- Seleciona colunas específicas
SELECT nome, email FROM usuarios;

-- Com condição
SELECT * FROM usuarios WHERE idade > 18;

-- Ordena os resultados
SELECT * FROM produtos ORDER BY preco DESC;

ALTER TABLE nome_tabela ADD nome_coluna VARCHAR(20);

ALTER TABLE usuarios CHANGE nome_antigo nome_completo VARCHAR(100);

UPDATE usuarios
SET idade = 31
WHERE nome = 'João';

DELETE FROM usuarios
WHERE idade < 18;

ALTER TABLE nome_da_tabela DROP COLUMN nome_da_coluna;

