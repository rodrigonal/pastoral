# Build e Deploy

## Build para produção

```bash
# 1. Limpar caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Build
composer install --no-dev --optimize-autoloader
npm run build

# 3. Symlink Flux (flux.min.js deve apontar para flux-lite.min.js - contém ui-sidebar-toggle)
rm -rf public/flux
mkdir -p public/flux
ln -sf ../../vendor/livewire/flux/dist/flux-lite.min.js public/flux/flux.min.js
ln -sf ../../vendor/livewire/flux/dist/flux-lite.min.js public/flux/flux.js

# 4. Permissões
chmod -R 775 storage bootstrap/cache

# 5. Caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Migrações
php artisan migrate --force
```

## Variáveis de ambiente

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` = URL pública
- `DB_*` = credenciais MySQL
- `SESSION_DRIVER=database` ou `file`

## Storage

- `storage/app/lancamentos` - anexos
- Garantir permissões de escrita em `storage/` e `bootstrap/cache/`

## Gerar PDF

O PDF é gerado server-side via `barryvdh/laravel-dompdf`. Não requer serviços externos.
