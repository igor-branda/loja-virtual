# TechWave Store - Loja Virtual de Eletrônicos & Tecnologia

Projeto final completo desenvolvido para a disciplina de **Web Coding / Programação Web Full Stack**. Aplicação web robusta de comércio eletrônico (e-commerce) desenvolvida com **HTML5 semântico, CSS3 responsivo, JavaScript puro (ES6+), PHP 8 (PDO com Prepared Statements) e banco de dados relacional MySQL (InnoDB)**, executado localmente via **XAMPP (Apache + MariaDB/phpMyAdmin)** e integração com a API pública do **ViaCEP**.

---

## 1. Identificação do Projeto e Grupo Discente

* **Instituição de Ensino Superior:** UNAMA - Universidade da Amazônia (Campus Alcindo Cacela)
* **Disciplina:** PROGRAMAÇÃO - WEB CODING
* **Professor Responsável:** Mauricio Aldenor Souza dos Santos
* **Curso:** Ciência da Computação
* **Título do Projeto:** Projeto Final – Loja Virtual TechWave Store
* **Integrantes do Grupo:**
  1. **Igor Brandão Coelho** — Matrícula: 0420-7633 — Curso: Ciência da Computação
  2. **Rafael Portilho** — Matrícula: 0421-4105 — Curso: Ciência da Computação
  3. **Amalia Arero** — Matrícula: 0422-3320 — Curso: Ciência da Computação
  4. **Geovana Araújo** — Matrícula: 0421-3599 — Curso: Ciência da Computação
  5. **Talita Bastos** — Matrícula: 0418-6770 — Curso: Ciência da Computação

---

## 2. Tecnologias e Arquitetura

* **Frontend:**
  - **HTML5:** Marcação semântica (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`, `<form>` com validações nativas e acessibilidade).
  - **CSS3 Moderno:** Mobile-first, Flexbox, CSS Grid, Variáveis CSS (`:root`), efeitos de hover, microanimações e responsividade total para celulares, tablets e computadores.
  - **JavaScript Puro (ES6+):** Sem bibliotecas externas pesadas; manipulação assíncrona com `fetch` (AJAX), máscaras de entrada (CPF, CEP, telefone), validação de formulários em tempo real e integração com a API ViaCEP.
* **Backend:**
  - **PHP 8+:** Estruturado sem frameworks, com código limpo, modularizado e reutilizável (`includes/`).
  - **PDO (PHP Data Objects):** Prepared Statements nativos em 100% das consultas para blindagem contra **SQL Injection**.
  - **Segurança da Informação:**
    * Criptografia de senhas com algoritmo `password_hash()` e verificação com `password_verify()`.
    * Prevenção de ataques **XSS (Cross-Site Scripting)** através de sanitização estrita com `htmlspecialchars()`.
    * Proteção contra ataques **CSRF (Cross-Site Request Forgery)** com tokens criptográficos únicos por sessão.
    * Prevenção contra fixação de sessão com `session_regenerate_id(true)`.
    * **Transações Atômicas PDO (`beginTransaction`, `commit`, `rollBack`):** Bloqueio pessimista de registros (`SELECT ... FOR UPDATE`) durante o checkout, garantindo consistência no estoque e integridade de preços históricos.
* **Banco de Dados:**
  - **MySQL / MariaDB (XAMPP):** Charset `utf8mb4` e collation `utf8mb4_unicode_ci`.
  - **Engine InnoDB:** Suporte obrigatório a integridade referencial, transações ACID e chaves estrangeiras (`FOREIGN KEY`) visíveis no **Designer do phpMyAdmin**.
* **API Externa:**
  - **ViaCEP:** `https://viacep.com.br/ws/{CEP}/json/` para preenchimento de endereço e cálculo de frete por estado.

---

## 3. Modelo de Relacionamento do Banco de Dados

O banco de dados `loja_virtual` é normalizado e possui as 6 tabelas relacionais em **InnoDB**:

```
[ categorias ] (1) ───────< (N) [ produtos ]
                                    │ (1)
                                    │
                                    └───< (N) [ itens_compra ]
                                                  │ (N)
                                                  │
[ clientes ] (1) ─────────< (N) [ compras ] (1) ──┘
```

1. **`categorias` (1) — (N) `produtos`:** Cada categoria possui múltiplos produtos vinculados (`ON UPDATE CASCADE ON DELETE RESTRICT`).
2. **`clientes` (1) — (N) `compras`:** Cada cliente pode realizar várias compras ao longo do tempo.
3. **`compras` (1) — (N) `itens_compra`:** Cada compra possui um ou mais itens adquiridos (`ON UPDATE CASCADE ON DELETE CASCADE`).
4. **`produtos` (1) — (N) `itens_compra`:** Cada produto pode constar em múltiplos itens de compra com **preço unitário histórico congelado**.
5. **`administradores`:** Gestão dos usuários lojistas com senhas em hash.

> **Visualização no phpMyAdmin:**  
> Ao selecionar o banco `loja_virtual` no phpMyAdmin, clique em **Mais > Designer** para visualizar o diagrama interativo com todas as linhas de ligação das chaves estrangeiras.

---

## 4. Regras Oficiais de Frete (TechWave Store)

O frete é calculado dinamicamente com base na Unidade Federativa (UF) retornada pelo **ViaCEP** e no subtotal da compra:

1. **FRETE GRÁTIS:** Compras com subtotal igual ou superior a **R$ 200,00** recebem automaticamente **Frete Grátis** (R$ 0,00) para qualquer lugar do Brasil.
2. **Cálculo Regional (Subtotal < R$ 200,00):**
   * **Mesma UF da Loja (PA - Pará):** R$ 12,00 (Prazo: 1 a 3 dias úteis)
   * **Norte e Nordeste (outras UFs):** R$ 25,00 (Prazo: 4 a 7 dias úteis)
   * **Centro-Oeste (MT, MS, GO, DF):** R$ 30,00 (Prazo: 5 a 8 dias úteis)
   * **Sudeste (SP, RJ, MG, ES):** R$ 35,00 (Prazo: 6 a 10 dias úteis)
   * **Sul (PR, SC, RS):** R$ 40,00 (Prazo: 7 a 12 dias úteis)

---

## 5. Credenciais de Teste para Avaliação

O script SQL já inclui usuários prontos para teste imediato do professor:

### Acesso do Cliente
* **Página de Login:** `http://localhost/loja/login.php`
* **Usuário 1:** `carlos@email.com` | **Senha:** `cliente123` *(Cliente de Belém - PA com compras no histórico)*
* **Usuário 2:** `mariana@email.com` | **Senha:** `cliente123` *(Cliente de São Paulo - SP)*

### Acesso Administrativo (Lojista)
* **Página de Login Admin:** `http://localhost/loja/admin/login.php`
* **Usuário:** `admin` | **Senha:** `admin123`

---

## 6. Passo a Passo para Executar Localmente no XAMPP

1. **Instale e Inicie o XAMPP:**
   - Abra o **XAMPP Control Panel**.
   - Inicie os serviços **Apache** e **MySQL** clicando em **Start**.

2. **Copie a Pasta do Projeto:**
   - Copie a pasta `loja` deste projeto para o diretório raiz do servidor web:
     `C:\xampp\htdocs\loja`

3. **Importe o Banco de Dados no phpMyAdmin:**
   - Abra seu navegador e acesse: `http://localhost/phpmyadmin`
   - Clique na aba superior **Importar**.
   - Clique em **Escolher arquivo** e selecione o arquivo:  
     `C:\xampp\htdocs\loja\sql\banco.sql`
   - Role até o final da página e clique em **Importar / Executar**.  
     *(O script criará o banco `loja_virtual`, todas as tabelas em InnoDB com suas chaves estrangeiras e a carga inicial de produtos, categorias, clientes e compras).*

4. **Acesse a Aplicação:**
   - **Loja Virtual (Vitrine):** `http://localhost/loja` ou `http://localhost/loja/index.php`
   - **Painel Administrativo:** `http://localhost/loja/admin/login.php`

---

## 7. Estrutura Completa de Pastas e Arquivos

```
loja/
├── index.php                  # Página inicial / vitrine com busca e filtro
├── produto.php                # Página de detalhes, estoque e seletor de quantidade
├── carrinho.php               # Carrinho de compras com tabela e cálculo de frete
├── checkout.php               # Checkout com transação PDO (lock for update e baixa de estoque)
├── pedido_confirmado.php      # Recibo de compra com número e dados de pagamento
├── meus_pedidos.php           # Histórico de compras realizadas pelo cliente logado
├── cadastro_cliente.php       # Cadastro de clientes com autopreenchimento ViaCEP
├── login.php                  # Login de clientes com password_verify
├── logout.php                 # Encerramento de sessão do cliente
├── admin/
│   ├── login.php              # Login exclusivo de administrador
│   ├── index.php              # Dashboard gerencial com métricas de vendas e estoque
│   ├── cadastro_produto.php   # Cadastro de produtos com upload seguro de imagens
│   ├── listar_produtos.php    # Catálogo com edição e inativação por integridade
│   ├── editar_produto.php     # Edição de características, estoque e foto
│   ├── listar_compras.php     # Gestão de compras e alteração de status
│   ├── logout.php             # Encerramento de sessão administrativa
│   ├── admin_header.php       # Topbar de navegação restrita do administrador
│   └── admin_footer.php       # Rodapé do painel administrativo
├── api/
│   ├── carrinho.php           # API RESTful (JSON) para manipulação assíncrona do carrinho
│   └── frete.php              # API (JSON) para cálculo de frete com regras regionais
├── includes/
│   ├── conexao.php            # Conexão PDO segura com tratamento de erros
│   ├── funcoes.php            # Funções utilitárias (sessão, CSRF, XSS, CPF, formatações)
│   ├── header.php             # Top banner, barra de busca, menu e badge do carrinho
│   └── footer.php             # Rodapé institucional, selos de segurança e scripts
├── assets/
│   ├── css/
│   │   └── style.css          # Estilização completa, variáveis :root e responsividade
│   ├── js/
│   │   ├── carrinho.js        # Lógica AJAX do carrinho e notificação
│   │   ├── cep.js             # Integração com ViaCEP e cálculo dinâmico de frete
│   │   └── validacoes.js      # Máscaras de entrada (CPF, CEP, telefone) e validações
│   └── img/                   # Logotipo oficial e 12 ilustrações vetoriais dos produtos
├── uploads/                   # Diretório de destino dos uploads do painel admin
├── sql/
│   └── banco.sql              # Script DDL/DML completo e reimportável
└── README.md                  # Documentação completa do projeto
```

---

## 8. Checklist de Requisitos Atendidos

* [x] **HTML5 semântico, CSS3 organizado e JavaScript puro (ES6+):** Código limpo, sem dependências externas obrigatórias.
* [x] **Backend em PHP 8 puro com PDO:** Prepared Statements em todas as consultas, proteção contra SQL Injection e XSS.
* [x] **Transação Atômica no Checkout:** Uso de `beginTransaction()`, `commit()`, `rollBack()` e `SELECT ... FOR UPDATE` para evitar inconsistências de estoque concorrente.
* [x] **Senhas Criptografadas:** Uso rigoroso de `password_hash()` e `password_verify()`.
* [x] **Proteção CSRF:** Tokens validados em todos os formulários críticos (cadastro, login, checkout, admin).
* [x] **Banco de Dados MySQL / MariaDB (InnoDB):** Tabelas `categorias`, `clientes`, `produtos`, `compras`, `itens_compra` e `administradores` com todas as chaves primárias e estrangeiras configuradas.
* [x] **Carga Inicial Rica:** 4 categorias, 12 produtos no tema de tecnologia com imagens e estoques realistas (inclusive esgotado para teste), 2 clientes de teste, 1 administrador e compra de exemplo com itens gravados.
* [x] **Integridade Referencial:** Exclusão inteligente de produtos que inativa em vez de excluir caso o item já possua vendas vinculadas.
* [x] **Integração ViaCEP:** Busca automática de logradouro, bairro, cidade e UF no cadastro, no checkout e no cálculo de frete.
* [x] **Painel Administrativo Completo:** Gestão de catálogo com upload validado de fotos (limite de 2 MB, extensões permitidas e nomes únicos) e controle de status de pedidos.
* [x] **Identidade Visual Própria:** Marca *TechWave Store* com logotipo vetorial, paleta de cores moderna e design responsivo mobile-first.
