# Casinha Finance

Sistema de gestão financeira em PHP/POO com MySQL (PDO), Tailwind (CDN) e layout responsivo. Permite controle de receitas e despesas, centros de custos, tipos de contas, lançamentos com parcelas e baixas, usuários com níveis de acesso, dashboard com gráficos e importação via CSV.

## Recursos
- Receitas e despesas com parcelas e baixa por parcela
- Centros de custos e tipos de contas vinculados
- Clientes e fornecedores (validação CPF/CNPJ nas telas; importação sem obrigatoriedade)
- Usuários com papéis `admin`, `manager`, `viewer`; sessões e restrições
- Dashboard com filtros por datas e centro de custos, gráficos offline (JS local) e listagem detalhada do fluxo
- Importação de lançamentos via CSV com cabeçalhos flexíveis e baixa automática quando marcado como pago
- Layout em Tailwind usando paleta de cores personalizada; menu lateral com toggle para mobile
- Segurança: `.htaccess` redireciona para `public/` e evita listar diretórios

## Requisitos
- PHP 8.0+
- MySQL 5.7+/8+
- Servidor web (Apache com `mod_rewrite`) ou servidor embutido do PHP

## Instalação
1. Clone ou copie o projeto para seu servidor
2. Crie o arquivo de ambiente:
   - Copie `config/env.sample.php` para `config/env.php` e ajuste:
     - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
     - Opcional: `APP_VERSION`, `LOGIN_BG_URL`
3. Crie/verifique o banco e aplique migrações:
   - `php scripts/setup_db.php`
   - `php scripts/migrate.php`
4. (Opcional) Crie usuário admin padrão:
   - `php scripts/seed_admin.php`
   - Login: `admin@local` / `admin123` (alterar depois)

## Executando
### Servidor embutido do PHP
```
php -S 127.0.0.1:8000 -t public
```
Acesse `http://127.0.0.1:8000/login.php`

### Apache (XAMPP)
- Coloque o projeto em `htdocs/casinha`
- Garanta `AllowOverride All` e `mod_rewrite` ativos
- Acesse `http://localhost/casinha/` (roteado para `public/`)

## Importação CSV
Menu: Lançamentos → Importar.

Formato aceito (delimitador `,` ou `;`, cabeçalhos flexíveis):
```
data,valor,parte,descricao,documento,status
2025-12-05,680.00,Copacol,Marketing,2308,PAGO
```
- `data`: `yyyy-mm-dd` ou `dd/mm/aaaa` ou "terça-feira, 1 de julho de 2025"
- `valor`: normaliza "R$", pontos e vírgulas
- `parte`: cliente/fornecedor; se não existir é criado placeholder
- `descricao` e `documento`: opcionais
- `status`: `paid`, `baixado` ou `PAGO` → baixa automática

## Estrutura
- `public/` rotas (index, login, logout)
- `views/` layout, nav e footer
- `src/Core/` `Database`, `Auth`
- `src/Models/` `User`, `Customer`, `Supplier`, `CostCenter`, `AccountType`, `Account`, `Installment`
- `src/Services/Reports.php` (resumos e séries)
- `scripts/` `migrate.php`, `setup_db.php`, `seed_admin.php`, `seed_faturamento.php`
- `migrations/` arquivos SQL

## Paleta de cores
- Imperial Blue, Blue Bell, Ghost White, Magenta Bloom, Carbon Black
Configurada via Tailwind CDN no `head.php`.

## Segurança
- `.htaccess` na raiz redireciona para `public/`
- Evita listagem de diretórios

## Observações
- Não há dependências Composer; Tailwind via CDN com plugins `forms`, `typography`, `aspect-ratio`
- Gráficos do dashboard desenhados por `public/js/dashboard.js` (sem dependência externa)

## Licença
Uso interno. Ajuste conforme sua necessidade.

