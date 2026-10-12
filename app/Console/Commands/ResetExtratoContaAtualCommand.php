<?php

namespace App\Console\Commands;

use App\Models\Lancamento;
use App\Services\SaldoService;
use App\Support\ResetExtratoContaAtual;
use Illuminate\Console\Command;

class ResetExtratoContaAtualCommand extends Command
{
    protected $signature = 'pastoral:reset-extrato-conta
                            {--force : Confirma a exclusão de todos os lançamentos atuais e a reimportação}';

    protected $description = 'Arquiva saldo antigo do CS, apaga lançamentos da conta atual e reimporta o extrato Bradesco completo';

    public function handle(SaldoService $saldoService): int
    {
        $this->info('Situação atual:');
        $this->table(
            ['Indicador', 'Valor'],
            [
                ['Lançamentos (conta atual)', (string) Lancamento::contaAtual()->count()],
                ['Lançamentos (histórico em lancamentos)', (string) Lancamento::historico()->count()],
                ['Saldo acumulado', 'R$ '.number_format($saldoService->saldoAcumulado(), 2, ',', '.')],
                ['Saldo em mãos', 'R$ '.number_format($saldoService->saldoEmMaos(), 2, ',', '.')],
                ['Saldo em conta (tela)', 'R$ '.number_format($saldoService->saldoEmConta(), 2, ',', '.')],
                ['Saldo antigo (CS)', 'R$ '.number_format($saldoService->saldoLegado(), 2, ',', '.')],
            ]
        );

        $this->newLine();
        $this->line('Extrato completo esperado: 135 lançamentos, saldo em conta R$ 422,87 (12/10/2026).');

        if (! $this->option('force') && ! $this->confirm('Apagar TODOS os lançamentos e reimportar do extrato?', false)) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $resultado = ResetExtratoContaAtual::executar();

        $this->newLine();
        $this->info('Reset concluído:');
        $this->table(
            ['Indicador', 'Valor'],
            [
                ['Arquivados agora (legado CS)', (string) $resultado['arquivados']],
                ['Lançamentos importados', (string) $resultado['lancamentos_importados']],
                ['Saldo acumulado', 'R$ '.number_format($resultado['saldo_acumulado'], 2, ',', '.')],
                ['Saldo em conta', 'R$ '.number_format($resultado['saldo_em_conta'], 2, ',', '.')],
                ['Saldo antigo (CS)', 'R$ '.number_format($resultado['saldo_legado'], 2, ',', '.')],
            ]
        );

        if (abs($resultado['saldo_em_conta'] - 422.87) > 0.01) {
            $this->error('Saldo em conta diferente do extrato (R$ 422,87). Verifique duplicatas ou CSV desatualizado.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
