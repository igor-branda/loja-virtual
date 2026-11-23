<div align="center">

# TechWave Store
### Loja Virtual de Eletrônicos & Tecnologia

[![PHP 8+](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-InnoDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/pt-BR/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/pt-BR/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/pt-BR/docs/Web/JavaScript)
[![Status Online](https://img.shields.io/badge/Status-Online%20em%20Produção-22C55E?style=for-the-badge)](http://techwave.infinityfreeapp.com)

**[Acessar Loja Online](http://techwave.infinityfreeapp.com)** | **[Painel Administrativo](http://techwave.infinityfreeapp.com/admin/login.php)** | **[Repositório no GitHub](https://github.com/igor-branda/loja-virtual)**

</div>

---

## 1. Identificação do Projeto e Grupo Discente

| Campo | Detalhe |
| :--- | :--- |
| **Instituição** | UNAMA - Universidade da Amazônia (Campus Alcindo Cacela) |
| **Disciplina** | PROGRAMAÇÃO - WEB CODING |
| **Professor** | Mauricio Aldenor Souza dos Santos |
| **Curso** | Ciência da Computação |
| **Título** | Projeto Final – Loja Virtual TechWave Store |
| **Loja Online** | [http://techwave.infinityfreeapp.com](http://techwave.infinityfreeapp.com) |
| **Repositório** | [https://github.com/igor-branda/loja-virtual](https://github.com/igor-branda/loja-virtual) |

### Integrantes da Equipe

| Nome Completo | Matrícula | Curso |
| :--- | :---: | :--- |
| **Igor Brandão Coelho** | 0420-7633 | Ciência da Computação |
| **Rafael Portilho** | 0421-4105 | Ciência da Computação |
| **Amalia Arero** | 0422-3320 | Ciência da Computação |
| **Geovana Araújo** | 0421-3599 | Ciência da Computação |
| **Talita Bastos** | 0418-6770 | Ciência da Computação |

---

## 2. Tecnologias e Arquitetura

* **Frontend:**
  - **HTML5:** Marcação semântica (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`, `<form>` com validações nativas e acessibilidade).
  - **CSS3 Moderno:** Mobile-first, Flexbox, CSS Grid, Variáveis CSS (`:root`), efeitos de hover, microanimações e responsividade total para celulares, tablets e computadores.
  - **JavaScript Puro (ES6+):** Sem bibliotecas externas; manipulação assíncrona com `fetch` (AJAX), máscaras de entrada (CPF, CEP, telefone), validação de formulários em tempo real e integração com a API ViaCEP.
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
  - **MySQL / MariaDB (XAMPP e InfinityFree):** Charset `utf8mb4` e collation `utf8mb4_unicode_ci`.
  - **Engine InnoDB:** Suporte obrigatório a integridade referencial, transações ACID e chaves estrangeiras (`FOREIGN KEY`) visíveis no **Designer do phpMyAdmin**.
* **API Externa:**
  - **ViaCEP:** `https://viacep.com.br/ws/{CEP}/json/` para preenchimento de endereço e cálculo de frete por estado.

---

## 3. Modelo de Relacionamento do Banco de Dados

O banco de dados relacional é normalizado e possui 6 tabelas estruturadas em **InnoDB**:

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

### Acesso do Cliente
* **Online:** [http://techwave.infinityfreeapp.com/login.php](http://techwave.infinityfreeapp.com/login.php)
* **Local (XAMPP):** `http://localhost/loja/login.php`
* **Usuário 1:** `carlos@email.com` | **Senha:** `cliente123` *(Cliente com compras no histórico)*
* **Usuário 2:** `mariana@email.com` | **Senha:** `cliente123`

### Acesso Administrativo (Lojista)
* **Online:** [http://techwave.infinityfreeapp.com/admin/login.php](http://techwave.infinityfreeapp.com/admin/login.php)
* **Local (XAMPP):** `http://localhost/loja/admin/login.php`
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
   - Clique em **Escolher arquivo** e selecione: `C:\xampp\htdocs\loja\sql\banco.sql`
   - Role até o final da página e clique em **Importar / Executar**.

4. **Acesse a Aplicação:**
   - **Loja Virtual:** `http://localhost/loja`
   - **Painel Administrativo:** `http://localhost/loja/admin/login.php`

---

## 7. Estrutura Completa de Pastas e Arquivos

```
loja/
├── index.php                  # Vitrine com busca e filtro por categoria
├── produto.php                # Detalhes, especificações e estoque em tempo real
├── carrinho.php               # Carrinho de compras e cálculo de frete
├── checkout.php               # Checkout com transação ACID e lock de estoque
├── pedido_confirmado.php      # Recibo de compra com número e dados de pagamento
├── meus_pedidos.php           # Histórico de compras do cliente logado
├── cadastro_cliente.php       # Cadastro com autopreenchimento ViaCEP
├── login.php                  # Login com verificação de hash
├── logout.php                 # Encerramento seguro de sessão
├── admin/
│   ├── login.php              # Acesso restrito de administradores
│   ├── index.php              # Dashboard gerencial com métricas
│   ├── cadastro_produto.php   # Cadastro de produtos com upload seguro
│   ├── listar_produtos.php    # Catálogo com edição e inativação por integridade
│   ├── editar_produto.php     # Edição de características, estoque e foto
│   ├── listar_compras.php     # Gestão de compras e alteração de status
│   ├── logout.php             # Encerramento de sessão administrativa
│   ├── admin_header.php       # Topbar de navegação restrita
│   └── admin_footer.php       # Rodapé do painel administrativo
├── api/
│   ├── carrinho.php           # API RESTful (JSON) para manipulação assíncrona
│   └── frete.php              # API (JSON) para cálculo de frete regional
├── includes/
│   ├── conexao.php            # Conexão PDO híbrida (InfinityFree ou XAMPP)
│   ├── funcoes.php            # Funções utilitárias (sessão, CSRF, XSS, validações)
│   ├── header.php             # Top banner, barra de busca, menu e badge
│   └── footer.php             # Rodapé institucional e selos de segurança
├── assets/
│   ├── css/
│   │   └── style.css          # Estilização completa, variáveis :root e responsividade
│   ├── js/
│   │   ├── carrinho.js        # Lógica AJAX do carrinho e notificações
│   │   ├── cep.js             # Integração com ViaCEP e cálculo dinâmico de frete
│   │   └── validacoes.js      # Máscaras de entrada (CPF, CEP, telefone) e validações
│   └── img/                   # Logotipo oficial e 12 ilustrações vetoriais dos produtos
├── uploads/                   # Diretório de destino dos uploads do painel admin
├── sql/
│   ├── banco.sql              # Script DDL/DML para XAMPP local
│   └── banco_infinityfree.sql # Script DDL/DML configurado para InfinityFree
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
