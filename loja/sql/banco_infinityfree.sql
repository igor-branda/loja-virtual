-- ==============================================================================
-- PROJETO FINAL: LOJA VIRTUAL - TechWave Store (Eletrônicos & Tecnologia)
-- Script para Importação na Nuvem (InfinityFree / phpMyAdmin)
-- Compatibilidade: MySQL 5.7+ / MariaDB 10.4+
-- Charset: utf8mb4 | Collation: utf8mb4_unicode_ci | Engine: InnoDB
-- ==============================================================================

SET default_storage_engine = InnoDB;

-- 1. Categorias
DROP TABLE IF EXISTS itens_compra;
DROP TABLE IF EXISTS compras;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS administradores;
DROP TABLE IF EXISTS categorias;

CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. Clientes
CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  telefone VARCHAR(20),
  cep CHAR(8),
  logradouro VARCHAR(150),
  numero VARCHAR(10),
  complemento VARCHAR(80),
  bairro VARCHAR(80),
  cidade VARCHAR(80),
  uf CHAR(2),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Administradores
CREATE TABLE administradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 4. Produtos
CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  descricao TEXT,
  preco DECIMAL(10,2) NOT NULL CHECK (preco >= 0),
  estoque INT NOT NULL DEFAULT 0 CHECK (estoque >= 0),
  imagem VARCHAR(255),
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) 
    REFERENCES categorias(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. Compras
CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  data_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal DECIMAL(10,2) NOT NULL,
  frete DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL,
  forma_pagamento VARCHAR(30) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Pendente',
  cep_entrega CHAR(8) NOT NULL,
  endereco_entrega VARCHAR(255) NOT NULL,
  CONSTRAINT fk_compras_cliente FOREIGN KEY (cliente_id) 
    REFERENCES clientes(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 6. Itens de Compra
CREATE TABLE itens_compra (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL CHECK (quantidade > 0),
  preco_unitario DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_itens_compra FOREIGN KEY (compra_id) 
    REFERENCES compras(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_itens_produto FOREIGN KEY (produto_id) 
    REFERENCES produtos(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- CARGA DE DADOS DE EXEMPLO

-- Categorias
INSERT INTO categorias (id, nome) VALUES
(1, 'Smartphones & Celulares'),
(2, 'Notebooks & Computadores'),
(3, 'Áudio & Vídeo'),
(4, 'Periféricos & Acessórios');

-- Administrador (admin / admin123)
INSERT INTO administradores (id, usuario, senha_hash) VALUES
(1, 'admin', '$2y$12$kwHPWkUf7BUlMrJQ1kV0ZudaWf02F/bEZXV8mdQGyUydD05rWRuKu');

-- Clientes de Teste (carlos@email.com e mariana@email.com / cliente123)
INSERT INTO clientes (id, nome, email, senha_hash, cpf, telefone, cep, logradouro, numero, complemento, bairro, cidade, uf) VALUES
(1, 'Carlos Alberto Silva', 'carlos@email.com', '$2y$12$vi5Wx4spzKgph8omdGyqLuOxh74GQho7XZFiV1kr/B0oGf350kQb.', '11144477735', '(91) 98111-2233', '66050000', 'Avenida Nazaré', '500', 'Apto 402', 'Nazaré', 'Belém', 'PA'),
(2, 'Mariana Fernandes Costa', 'mariana@email.com', '$2y$12$vi5Wx4spzKgph8omdGyqLuOxh74GQho7XZFiV1kr/B0oGf350kQb.', '22255588846', '(11) 99222-3344', '01310100', 'Avenida Paulista', '1578', 'Conjunto 12', 'Bela Vista', 'São Paulo', 'SP');

-- 12 Produtos TechWave
INSERT INTO produtos (id, categoria_id, nome, descricao, preco, estoque, imagem, ativo) VALUES
(1, 1, 'Smartphone Samsung Galaxy S24 Ultra 256GB', 'Processador Snapdragon 8 Gen 3, Câmera Quadrupla de 200MP, Tela Dynamic AMOLED 2X de 6.8 polegadas e caneta S-Pen integrada.', 6499.00, 15, 's24-ultra.svg', 1),
(2, 1, 'iPhone 15 Pro Max 256GB Titânio Preto', 'Chip A17 Pro inovador, estrutura aeroespacial em titânio, sistema de câmera tripla de 48MP com teleobjetiva de 5x e conector USB-C.', 7899.00, 10, 'iphone-15-pro-max.svg', 1),
(3, 1, 'Smartphone Xiaomi Redmi Note 13 Pro 5G', 'Tela AMOLED de 120Hz, câmera principal de 200MP com OIS, carregamento ultra rápido turbo de 67W e bateria de 5000mAh.', 1999.00, 25, 'redmi-note-13-pro.svg', 1),
(4, 2, 'Notebook Gamer Dell G15 Intel Core i7 RTX 4050', 'Intel Core i7-13650HX, 16GB DDR5, SSD 512GB NVMe, Placa de Vídeo NVIDIA GeForce RTX 4050 6GB e tela Full HD de 165Hz.', 5899.00, 8, 'dell-g15.svg', 1),
(5, 2, 'Apple MacBook Air 13 M2 256GB SSD 8GB RAM', 'Chip Apple M2 com CPU de 8 núcleos e GPU de 8 núcleos, design ultrafino sem ventoinha, tela Liquid Retina brilhante e bateria de até 18 horas.', 6999.00, 12, 'macbook-air-m2.svg', 1),
(6, 2, 'Notebook Lenovo ThinkPad E14 AMD Ryzen 7', 'AMD Ryzen 7 7730U, 16GB RAM, SSD 512GB, tela antirreflexo de 14 polegadas WUXGA, teclado ergonômico resistente a respingos.', 3799.00, 0, 'thinkpad-e14.svg', 1),
(7, 3, 'Fone de Ouvido Sony WH-1000XM5 Noise Cancelling', 'Cancelamento de ruído líder de mercado com 8 microfones, áudio de alta resolução Hi-Res, chamadas cristalinas e até 30 horas de autonomia.', 2199.00, 18, 'sony-wh1000xm5.svg', 1),
(8, 3, 'Apple AirPods Pro 2ª Geração com Estojo MagSafe', 'Cancelamento Ativo de Ruído até 2x mais eficiente, Áudio Espacial personalizado com rastreamento dinâmico da cabeça e conector USB-C.', 1899.00, 20, 'airpods-pro-2.svg', 1),
(9, 3, 'Caixa de Som Bluetooth JBL Charge 5 40W', 'Driver de longa excursão otimizado, tweeter separado e dois radiadores passivos de graves JBL. Bateria de até 20 horas e certificação IP67.', 849.00, 30, 'jbl-charge-5.svg', 1),
(10, 4, 'Teclado Mecânico Gamer Redragon Kumara RGB', 'Switches mecânicos Outemu Blue, iluminação RGB Chroma personalizável, layout ABNT2 compacto TKL e estrutura reforçada em metal.', 249.90, 40, 'teclado-redragon.svg', 1),
(11, 4, 'Mouse Gamer Sem Fio Logitech G502 LIGHTSPEED', 'Sensor HERO 25K com até 25.600 DPI, tecnologia sem fio Lightspeed de 1ms, 11 botões programáveis e pesos personalizáveis.', 529.00, 22, 'mouse-logitech-g502.svg', 1),
(12, 4, 'Monitor Gamer Curvo LG UltraGear 34 WQHD 160Hz', 'Tela UltraWide 21:9 WQHD (3440 x 1440), taxa de atualização de 160Hz, tempo de resposta de 1ms MBR, HDR10 e compatibilidade AMD FreeSync Premium.', 2599.00, 7, 'monitor-lg-ultragear-34.svg', 1);

-- Compra inicial
INSERT INTO compras (id, cliente_id, data_compra, subtotal, frete, total, forma_pagamento, status, cep_entrega, endereco_entrega) VALUES
(1, 1, '2026-11-20 14:35:00', 2448.90, 0.00, 2448.90, 'Pix', 'Pago', '66050000', 'Avenida Nazaré, 500, Apto 402, Nazaré, Belém - PA');

-- Itens da compra
INSERT INTO itens_compra (id, compra_id, produto_id, quantidade, preco_unitario) VALUES
(1, 1, 7, 1, 2199.00),
(2, 1, 10, 1, 249.90);
