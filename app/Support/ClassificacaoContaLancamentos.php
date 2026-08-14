<?php

namespace App\Support;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\ControleSaldo;
use App\Models\Lancamento;
use App\Services\ExtratoBancarioParser;
use Illuminate\Support\Facades\Schema;

class ClassificacaoContaLancamentos
{
    /**
     * A partir desta data o sistema passou a usar a conta Bradesco atual.
     * Lançamentos manuais criados antes disso (sem extrato) são da conta antiga do CS.
     */
    public const INICIO_CONTA_ATUAL = '2026-08-13';

    /**
     * Marca lançamentos da conta Bradesco atual vs. conta antiga do CS.
     *
     * Conta atual: documento do extrato Bradesco, ou lançamento lançado depois
     * da virada de conta (cadastro manual na conta nova).
     * Conta antiga: todo o restante.
     *
     * @return array{conta_atual: int, legado: int, saldo_legado: float}
     */
    public static function executar(): array
    {
        $documentos = self::documentosExtratoAtual();

        $idsExtrato = Lancamento::query()
            ->whereNotNull('documento')
            ->get(['id', 'documento'])
            ->filter(fn (Lancamento $l) => in_array(ltrim((string) $l->documento, '0'), $documentos, true))
            ->pluck('id')
            ->all();

        $idsDoBanco = Lancamento::query()
            ->whereNotNull('historico_bancario')
            ->where('historico_bancario', '!=', '')
            ->pluck('id')
            ->all();

        $idsNovosManuais = Lancamento::query()
            ->where('created_at', '>=', self::INICIO_CONTA_ATUAL)
            ->where(function ($q) {
                $q->whereNull('historico_bancario')->orWhere('historico_bancario', '');
            })
            ->pluck('id')
            ->all();

        $idsContaAtual = array_values(array_unique(array_merge($idsExtrato, $idsDoBanco, $idsNovosManuais)));

        $idsLegado = Lancamento::query()
            ->when($idsContaAtual !== [], fn ($q) => $q->whereNotIn('id', $idsContaAtual))
            ->pluck('id')
            ->all();

        if ($idsContaAtual !== []) {
            Lancamento::query()->whereIn('id', $idsContaAtual)->update(['is_historico' => false]);
        }

        if ($idsLegado !== []) {
            Lancamento::query()->whereIn('id', $idsLegado)->update(['is_historico' => true]);
        }

        $saldoLegado = self::calcularSaldoLegado();
        self::gravarSaldoLegado($saldoLegado);

        return [
            'conta_atual' => count($idsContaAtual),
            'legado' => count($idsLegado),
            'saldo_legado' => $saldoLegado,
        ];
    }

    /**
     * @return list<string>
     */
    public static function documentosExtratoAtual(): array
    {
        $csv = database_path('seeders/data/extrato-conta-atual.csv');
        if (! is_file($csv)) {
            return [];
        }

        $documentos = [];
        foreach (app(ExtratoBancarioParser::class)->parse($csv) as $linha) {
            $doc = ltrim((string) ($linha['documento'] ?? ''), '0');
            if ($doc !== '') {
                $documentos[] = $doc;
            }
        }

        return array_values(array_unique($documentos));
    }

    public static function calcularSaldoLegado(): float
    {
        $query = Lancamento::query()->historico();

        $entradas = (clone $query)
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->sum('valor');

        $saidas = (clone $query)
            ->where('tipo', TipoLancamentoEnum::Saida)
            ->where('categoria', '!=', CategoriaLancamentoEnum::Reembolso)
            ->sum('valor');

        return round((float) $entradas - (float) $saidas, 2);
    }

    public static function gravarSaldoLegado(float $valor): void
    {
        if (! Schema::hasTable('controle_saldos') || ! Schema::hasColumn('controle_saldos', 'saldo_legado')) {
            return;
        }

        ControleSaldo::registro()->update(['saldo_legado' => $valor]);
    }
}
