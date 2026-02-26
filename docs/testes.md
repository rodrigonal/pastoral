# Testes

## Executar todos os testes

```bash
php artisan test
```

## Executar por pasta

```bash
php artisan test tests/Feature/Lancamento/
php artisan test tests/Feature/Permissions/
php artisan test tests/Feature/PrestacaoContas/
php artisan test tests/Feature/Anexo/
```

## Testes implementados

| Teste | Descrição |
|-------|-----------|
| CreateLancamentoTest | Criação de entrada com dados válidos |
| CreateSaidaTest | Criação de saída (compra) |
| CreateArrecadacaoTest | Criação de arrecadação com segmento |
| SaldoCalculationTest | Cálculo correto de saldo |
| RolePermissionTest | Admin/tesouraria/visualizador acessos |
| PdfGenerationTest | Geração de PDF mensal |
| UploadValidoTest | Upload de anexo PDF/imagem válido |
| UploadInvalidoBloqueioTest | Bloqueio de arquivo inválido |

## Configuração

- `RefreshDatabase` em Feature tests
- Factories: User, Segmento, Lancamento
- RolePermissionSeeder executado em `beforeEach` onde necessário
