<?php

namespace App\Support;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\ControleSaldo;
use App\Models\Lancamento;
use App\Services\ExtratoBancarioParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClassificacaoContaLancamentos
{
    /**
     * Marca lançamentos da conta Bradesco atual vs. legado (conta antiga do CS, inacessível).
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

        $idsComSegmento = Schema::hasTable('lancamento_segmento')
            ? DB::table('lancamento_segmento')->distinct()->pluck('lancamento_id')->all()
            : [];

        $idsLegadoPorData = Lancamento::query()
            ->whereDate('data', '<', '2026-03-05')
            ->when($idsExtrato !== [], fn ($q) => $q->whereNotIn('id', $idsExtrato))
            ->pluck('id')
            ->all();

        $idsLegado = array_values(array_unique(array_merge($idsComSegmento, $idsLegadoPorData)));
        $idsLegado = array_values(array_diff($idsLegado, $idsExtrato));

        if ($idsExtrato !== []) {
            Lancamento::query()->whereIn('id', $idsExtrato)->update(['is_historico' => false]);
        }

        if ($idsLegado !== []) {
            Lancamento::query()->whereIn('id', $idsLegado)->update(['is_historico' => true]);
        }

        $saldoLegado = self::calcularSaldoLegado();
        self::gravarSaldoLegado($saldoLegado);

        return [
            'conta_atual' => count($idsExtrato),
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
