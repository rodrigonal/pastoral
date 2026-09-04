<?php

namespace Database\Seeders;

use App\Models\Benfeitor;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\ExtratoBancarioParser;
use App\Support\NomeExtrato;
use Illuminate\Database\Seeder;

class ExtratoAgoSet2026Seeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('seeders/data/extrato-2026-08-17-a-2026-09-03.csv');
        $contrapartes = database_path('seeders/data/extrato-2026-08-17-a-2026-09-03-contrapartes.csv');

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
        $nomesPorDocumento = $this->carregarContrapartes($contrapartes);

        $criados = 0;
        $atualizados = 0;

        foreach ($linhas as $linha) {
            $existente = Lancamento::query()
                ->whereDate('data', $linha['data'])
                ->where('documento', $linha['documento'])
                ->where('valor', $linha['valor'])
                ->where('historico_bancario', $linha['historico'])
                ->first();

            $atributos = [
                'data' => $linha['data'],
                'tipo' => $linha['tipo'],
                'categoria' => $linha['categoria'],
                'valor' => $linha['valor'],
                'descricao' => $linha['descricao'],
                'observacao' => $linha['observacao'],
                'user_id' => $user->id,
                'classificado' => $linha['classificado'],
                'is_historico' => false,
                'historico_bancario' => $linha['historico'],
                'documento' => $linha['documento'],
            ];

            $docKey = ltrim((string) $linha['documento'], '0');
            $contraparte = $nomesPorDocumento[$docKey] ?? null;

            if ($contraparte !== null) {
                $atributos = [...$atributos, ...$this->atributosComContraparte($linha, $contraparte)];
            }

            if ($existente instanceof Lancamento) {
                $existente->update($atributos);
                $atualizados++;

                continue;
            }

            Lancamento::create($atributos);
            $criados++;
        }

        $this->command?->info("Extrato ago/set 2026: {$criados} criados, {$atualizados} atualizados.");
    }

    /**
     * @return array<string, array{sentido: string, nome: string}>
     */
    private function carregarContrapartes(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $mapa = [];
        $linhas = preg_split("/\r\n|\n|\r/", (string) file_get_contents($path)) ?: [];

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

            $mapa[$documento] = [
                'sentido' => $sentido,
                'nome' => $nome,
            ];
        }

        return $mapa;
    }

    /**
     * @param  array{documento: string, tipo: string}  $linha
     * @param  array{sentido: string, nome: string}  $contraparte
     * @return array{descricao: string, observacao: string, classificado: bool, benfeitor_id?: int|null}
     */
    private function atributosComContraparte(array $linha, array $contraparte): array
    {
        $nome = $contraparte['nome'];
        $doc = $linha['documento'] !== '' ? $linha['documento'] : 's/n';

        if ($contraparte['sentido'] === 'entrada') {
            $benfeitor = Benfeitor::firstOrCreate(
                ['nome' => $nome],
                ['ativo' => true]
            );

            return [
                'benfeitor_id' => $benfeitor->id,
                'descricao' => 'PIX recebido — '.$nome,
                'observacao' => "Doação via PIX de {$nome} (doc. {$doc}).",
                'classificado' => true,
            ];
        }

        return [
            'descricao' => $nome,
            'observacao' => "Pagamento via PIX para {$nome} (doc. {$doc}).",
            'classificado' => true,
        ];
    }
}
