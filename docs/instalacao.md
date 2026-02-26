# Instalação

## Pré-requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

## Passos

1. **Clone e dependências**
   ```bash
   git clone <repo> pjc
   cd pjc
   composer install
   npm install
   ```

2. **Configuração**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Banco de dados**
   Edite `.env`:
   ```
   DB_CONNECTION=mysql
   DB_DATABASE=pjc
   DB_USERNAME=root
   DB_PASSWORD=sua_senha
   ```

   Crie o banco e rode as migrations:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Assets**
   ```bash
   npm run build
   ```

5. **Storage**
   ```bash
   php artisan storage:link
   ```

6. **Executar**
   ```bash
   php artisan serve
   ```

   Acesse: http://localhost:8000

## Usuário padrão (após seed)

- Email: test@example.com
- Senha: password
- Perfil: admin

## Configurar permissões

O `RolePermissionSeeder` cria:
- Roles: admin, tesouraria, visualizador
- Permissões: lancamentos.*, prestacao-contas.*

Atribua roles aos usuários via `$user->assignRole('tesouraria')` ou via código.
