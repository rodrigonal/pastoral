# Build e Deploy

## Build para produção

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
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
