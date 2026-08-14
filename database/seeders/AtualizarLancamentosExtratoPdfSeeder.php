<?php

namespace Database\Seeders;

use App\Models\Benfeitor;
use App\Models\Lancamento;
use App\Support\NomeExtrato;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AtualizarLancamentosExtratoPdfSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('seeders/data/extrato-conta-atual-contrapartes.csv');

        if (! is_file($csv)) {
            $this->command?->error('Arquivo de contrapartes não encontrado: '.$csv);

            return;
        }

        $porDocumento = Lancamento::query()
            ->contaAtual()
            ->get()
            ->keyBy(fn (Lancamento $l) => ltrim((string) $l->documento, '0'));

        if ($porDocumento->isEmpty()) {
            $this->command?->warn('Nenhum lançamento da conta atual para atualizar. Rode o ExtratoContaAtualSeeder antes.');

            return;
        }

        $linhas = preg_split("/\r\n|\n|\r/", (string) file_get_contents($csv)) ?: [];
        $atualizados = 0;
        $naoEncontrados = 0;
        $benfeitoresNovos = 0;

        foreach ($linhas as $i => $linha) {
            $linha = trim($linha);
            if ($linha === '' || $i === 0) {
                continue;
            }

            [$documento, $sentido, $nome] = array_pad(str_getcsv($linha, ';'), 3, '');
            $documento = ltrim(trim((string) $documento), '0');
            $sentido = trim((string) $sentido);
            $nome = NomeExtrato::formatar((string) $nome);

            if ($documento === '' || $nome === '') {
                continue;
            }

            /** @var Lancamento|null $lancamento */
            $lancamento = $porDocumento->get($documento);

            if (! $lancamento instanceof Lancamento) {
                $naoEncontrados++;
                $this->command?->warn("Lançamento não encontrado para o documento {$documento} ({$nome}).");

                continue;
            }

            if ($sentido === 'entrada') {
                $existia = Benfeitor::query()->where('nome', $nome)->exists();
                $benfeitor = Benfeitor::firstOrCreate(
                    ['nome' => $nome],
                    ['ativo' => true]
                );
                if (! $existia) {
                    $benfeitoresNovos++;
                }

                $lancamento->update([
                    'benfeitor_id' => $benfeitor->id,
                    'descricao' => 'PIX recebido — '.$nome,
                    'observacao' => "Doação via PIX de {$nome} (doc. {$lancamento->documento}).",
                    'classificado' => true,
                ]);
            } else {
                $lancamento->update([
                    'descricao' => $nome,
                    'observacao' => "Pagamento via PIX para {$nome} (doc. {$lancamento->documento}).",
                    'classificado' => true,
                ]);
            }

            $atualizados++;
        }

        $this->command?->info("{$atualizados} lançamentos atualizados com nomes do extrato PDF.");
        $this->command?->info("{$benfeitoresNovos} benfeitores novos cadastrados.");

        if ($naoEncontrados > 0) {
            $this->command?->warn("{$naoEncontrados} documentos do PDF não bateram com lançamentos existentes.");
        }
    }
}
