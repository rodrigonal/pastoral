<?php

namespace Database\Seeders;

use App\Models\Lancamento;
use App\Models\User;
use App\Services\ExtratoBancarioParser;
use Illuminate\Database\Seeder;

class ExtratoContaAtualSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('seeders/data/extrato-conta-atual.csv');

        if (! is_file($csv)) {
            $this->command?->warn('Arquivo de extrato não encontrado: '.$csv);

            return;
        }

        $user = User::query()->where('username', 'admin')->first()
            ?? User::query()->first();

        if ($user === null) {
            $this->command?->error('Nenhum usuário encontrado para vincular os lançamentos do extrato.');

            return;
        }

        $parser = app(ExtratoBancarioParser::class);
        $linhas = $parser->parse($csv);

        foreach ($linhas as $linha) {
            Lancamento::create([
                'data' => $linha['data'],
                'tipo' => $linha['tipo'],
                'categoria' => $linha['categoria'],
                'valor' => $linha['valor'],
                'descricao' => $linha['descricao'],
                'observacao' => $linha['observacao'],
                'user_id' => $user->id,
                'benfeitor_id' => null,
                'classificado' => $linha['classificado'],
                'is_historico' => false,
                'historico_bancario' => $linha['historico'],
                'documento' => $linha['documento'],
            ]);
        }

        $this->command?->info(count($linhas).' lançamentos importados do extrato da conta atual.');
    }
}
