# Regras de Negócio - Pastoral de Rua PJC

## Contexto

A Pastoral de Rua é uma ação mensal. Cada segmento arrecada valores e envia ao Centro Social. São feitos repasses para compras (alimentos, pães, descartáveis) e reembolsos quando membros antecipam valores.

## Tipos de Lançamento

- **Entrada:** Arrecadações dos segmentos
- **Saída:** Repasses, compras, reembolsos

## Categorias

| Categoria | Tipo | Segmento |
|-----------|------|----------|
| Arrecadação | Entrada | Obrigatório |
| Repasse | Saída | Opcional |
| Compra | Saída | Opcional |
| Reembolso | Saída | Opcional |
| Outro | Entrada ou Saída | Opcional |

## Cálculo de Saldo

- Saldo = Soma(Entradas) - Soma(Saídas)
- Calculado dinamicamente via `SaldoService`
- Não armazenar saldo fixo no banco

## Segmentos (seed)

Freis, Sede Sóbrios, JC (Juventude Caminho), Comunicação, Segmento São José, Leigos, Intercessão, Cura, Juventude.

## Anexos

- Formatos permitidos: PDF, JPEG, JPG, PNG
- Tamanho máximo: 5MB
- Armazenamento: `storage/app/lancamentos` (disco local)

## Perfis e Permissões

| Perfil | Acesso |
|--------|--------|
| admin | Total |
| tesouraria | CRUD lançamentos, gerar PDF |
| visualizador | Apenas leitura |
