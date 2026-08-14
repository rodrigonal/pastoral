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
     * Conta atual = lançamentos do extrato Bradesco (documento ou histórico bancário).
     * Todo o restante é saldo antigo da conta do CS.
     *
     * @return array{conta_atual: int, legado: int, saldo_legado: float}
     */
    public static function executar(): array
    {
        $idsContaAtual = self::idsContaAtual();

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
     * @return list<int>
     */
    public static function idsContaAtual(): array
    {
        $documentos = self::documentosExtratoAtual();

        $idsPorDocumento = Lancamento::query()
            ->whereNotNull('documento')
            ->get(['id', 'documento'])
            ->filter(fn (Lancamento $l) => in_array(ltrim((string) $l->documento, '0'), $documentos, true))
            ->pluck('id')
            ->all();

        $idsDoExtrato = Lancamento::query()
            ->whereNotNull('historico_bancario')
            ->where('historico_bancario', '!=', '')
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($idsPorDocumento, $idsDoExtrato)));
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
