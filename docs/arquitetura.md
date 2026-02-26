# Arquitetura do Sistema PJC

## Visão Geral

Sistema de Prestação de Contas da Pastoral de Rua (PJC) - Fraternidade O Caminho.

## Stack Tecnológica

- Laravel 12+
- Livewire 4
- Laravel Volt (modo classe)
- Tailwind CSS
- Spatie Laravel Permission
- Pest (testes)
- MySQL
- barryvdh/laravel-dompdf (geração de PDF)
- Storage local (anexos)

## Estrutura de Pastas

```
app/
├── Actions/Lancamento/     # Regras de criação, atualização e exclusão
├── Enums/                  # TipoLancamentoEnum, CategoriaLancamentoEnum
├── Services/               # SaldoService, PrestacaoContasPdfService
├── Models/                 # User, Segmento, Lancamento
├── Http/Controllers/       # PrestacaoContasController (PDF)
└── Providers/              # VoltServiceProvider

resources/views/
├── livewire/               # Componentes Volt
│   ├── dashboard.blade.php
│   ├── lancamentos/
│   └── prestacao-contas/
└── pdf/                    # Template PDF

database/
├── migrations/
├── seeders/
└── factories/
```

## Fluxo de Dados

```
Volt (UI) → Actions → Models
         → Services → PDF/Model
```

- **Volt:** Orquestra formulários e listagens; delega regras a Actions e Services.
- **Actions:** Regras de criação/atualização/exclusão; interagem com Model e Storage.
- **Services:** Cálculos (saldo) e geração de PDF.
- **Models:** Acesso a dados; sem lógica de negócio pesada.

## Camadas

| Camada | Responsabilidade |
|--------|------------------|
| Volt | Formulários, listagens, filtros |
| Actions | Validação, persistência, upload de anexos |
| Services | Saldo, PDF |
| Models | Relacionamentos, casts |
