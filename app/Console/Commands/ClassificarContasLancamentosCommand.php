<?php

namespace App\Console\Commands;

use App\Services\SaldoService;
use App\Support\ClassificacaoContaLancamentos;
use Illuminate\Console\Command;

class ClassificarContasLancamentosCommand extends Command
{
    protected $signature = 'tesouraria:classificar-contas';

    protected $description = 'Marca lançamentos do extrato Bradesco como conta atual e o restante como saldo antigo do CS';

    public function handle(SaldoService $saldoService): int
    {
        $resultado = ClassificacaoContaLancamentos::executar();

        $this->info(sprintf(
            'Conta atual: %d lançamento(s). Saldo antigo (CS): %d lançamento(s).',
            $resultado['conta_atual'],
            $resultado['legado']
        ));
        $this->info('Saldo em conta: R$ '.number_format($saldoService->saldoEmConta(), 2, ',', '.'));
        $this->info('Saldo antigo (CS): R$ '.number_format($saldoService->saldoLegado(), 2, ',', '.'));

        return self::SUCCESS;
    }
}
