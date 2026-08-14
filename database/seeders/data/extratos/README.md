# Extratos bancários

PDFs oficiais do banco, versionados no Git. A prestação de contas anexa automaticamente os arquivos cujo período coincide com o relatório.

## Como incluir um extrato novo

1. Salve o PDF nesta pasta com um nome estável, por exemplo:
   `2026-08-a-2026-08_bradesco-ag2617-conta-71077-6.pdf`
2. Extraia CSV/contrapartes (se for gerar seeder) em `database/seeders/data/`.
3. Inclua uma entrada em `catalogo.php`:

```php
[
    'arquivo' => '2026-08-a-2026-08_bradesco-ag2617-conta-71077-6.pdf',
    'inicio' => '2026-08-01',
    'fim' => '2026-08-31',
    'titulo' => 'Extrato Bradesco — Ag 2617 | Conta 71077-6 (ago/2026)',
    'emitido_em' => '2026-09-01',
    'seeder' => 'NomeDoSeeder', // ou null
],
```

4. Commit o PDF + `catalogo.php` (+ seeder/CSV, se houver).

Não altere o período do arquivo já publicado: crie um registro novo para o mês seguinte.
