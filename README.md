# Atelier de Costura — Sistema de Agendamento
## Guia de Instalação Rápida

### Pré-requisitos
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- Apache com `mod_rewrite` habilitado
- phpMyAdmin (opcional, mas recomendado)

---

### 1. Instalação

1. Extraia a pasta `atelier/` para dentro do seu `htdocs/` (XAMPP) ou `www/` (WAMP):
   ```
   htdocs/atelier/
   ```

2. Acesse **phpMyAdmin** e crie o banco de dados importando o arquivo:
   ```
   atelier_costura.sql
   ```
   *(Arquivo → Importar → selecione o .sql)*

---

### 2. Configuração

Abra `config/config.php` e ajuste:

```php
// Conexão com o banco
private const HOST   = 'localhost';
private const DBNAME = 'atelier_costura';
private const USER   = 'root';
private const PASS   = '';           // Sua senha do MySQL

// Dados da loja
define('WHATSAPP_LOJA', '5596999990000'); // DDI+DDD+número
define('BASE_URL', 'http://localhost/atelier');
```

---

### 3. Gerar senhas para os usuários iniciais

O banco é inserido com hashes placeholder. Para definir as senhas reais,
execute este script PHP **uma única vez** e substitua os hashes no banco:

```php
<?php
echo password_hash('SuaSenhaAdmin123', PASSWORD_BCRYPT, ['cost' => 12]);
```

Ou use diretamente no phpMyAdmin:

```sql
UPDATE usuarios SET senha_hash = '<hash_gerado>' WHERE email = 'admin@atelier.com';
UPDATE usuarios SET senha_hash = '<hash_gerado>' WHERE email = 'bianca@atelier.com';
-- (repita para as demais funcionárias)
```

---

### 4. Acesso

| URL | Descrição |
|-----|-----------|
| `http://localhost/atelier/` | Tela de agendamento (cliente) |
| `http://localhost/atelier/views/login.php` | Login admin / funcionária |
| `http://localhost/atelier/views/admin/dashboard.php` | Painel Admin |
| `http://localhost/atelier/views/funcionario/dashboard.php` | Painel Funcionária |

---

### 5. Estrutura de Pastas

```
atelier/
├── .htaccess
├── index.php                          ← Redireciona para agendamento
├── atelier_costura.sql                ← Importe no phpMyAdmin
│
├── config/
│   ├── config.php                     ← ⚠️ Configure aqui
│   └── Database.php                   ← Singleton PDO
│
├── models/
│   ├── Agendamento.php
│   ├── Profissional.php
│   ├── Servico.php
│   └── Usuario.php
│
├── controllers/
│   ├── AgendamentoController.php
│   ├── AutenticacaoController.php
│   ├── DashboardController.php
│   ├── logica_login.php
│   └── logica_logout.php
│
├── views/
│   ├── login.php
│   ├── client/
│   │   └── agendamento.php            ← SPA 5 passos (cliente)
│   ├── admin/
│   │   └── dashboard.php              ← Painel master
│   └── funcionario/
│       └── dashboard.php              ← Painel restrito
│
└── api/
    ├── agendar.php                    ← POST: salva agendamento
    ├── horarios.php                   ← GET: slots disponíveis
    ├── dias-disponiveis.php           ← GET: dias do mês
    ├── servicos.php                   ← POST: toggle ativo/inativo
    └── status-agendamento.php        ← POST: atualiza status
```

---

### Segurança

- Sessões com `httponly`, `samesite=Lax`, `strict_mode`
- Funcionárias **jamais** acessam dados de outras via SQL escopo
- Senhas com `bcrypt` (cost 12)
- Todas as entradas sanitizadas via `filter_input`
- `.htaccess` bloqueia acesso direto a `config/` e `models/`

---

> **Próximos passos sugeridos:**
> - Implementar envio de WhatsApp automatizado via API (Z-API, WPPConnect)
> - Adicionar catálogo de peças com fotos
> - Integrar notificações de lembrete 24h antes
> - Exportar relatório de faturamento mensal (PDF)
