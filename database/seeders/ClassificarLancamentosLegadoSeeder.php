<?php

namespace Database\Seeders;

use App\Support\ClassificacaoContaLancamentos;
use Illuminate\Database\Seeder;

class ClassificarLancamentosLegadoSeeder extends Seeder
{
    public function run(): void
    {
        $resultado = ClassificacaoContaLancamentos::executar();

        $this->command?->info(sprintf(
            'Classificação: %d lançamento(s) da conta atual, %d legado(s). Saldo legado: R$ %s.',
            $resultado['conta_atual'],
            $resultado['legado'],
            number_format($resultado['saldo_legado'], 2, ',', '.')
        ));
    }
}
