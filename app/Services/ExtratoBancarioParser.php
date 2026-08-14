<?php

namespace App\Services;

class ExtratoBancarioParser
{
    /**
     * @return list<array{
     *     data: string,
     *     historico: string,
     *     documento: string,
     *     valor: float,
     *     tipo: string,
     *     categoria: string,
     *     descricao: string,
     *     observacao: string,
     *     classificado: bool
     * }>
     */
    public function parse(string $path): array
    {
        $conteudo = file_get_contents($path);
        if ($conteudo === false) {
            throw new \RuntimeException("Não foi possível ler o extrato em {$path}");
        }

        $linhas = preg_split("/\r\n|\n|\r/", $conteudo) ?: [];
        $lancamentos = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            $colunas = str_getcsv($linha, ';');
            $dataBr = trim((string) ($colunas[0] ?? ''));

            if (! preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataBr)) {
                continue;
            }

            $historico = trim((string) ($colunas[1] ?? ''));
            $documento = trim((string) ($colunas[2] ?? ''));
            $credito = $this->parseValor($colunas[3] ?? '');
            $debito = $this->parseValor($colunas[4] ?? '');

            if ($historico === 'COD. LANC. 0' && $credito <= 0 && $debito <= 0) {
                continue;
            }

            if ($credito <= 0 && $debito <= 0) {
                continue;
            }

            $mapeado = $this->mapear($historico, $documento, $credito, $debito);
            if ($mapeado === null) {
                continue;
            }

            [$dia, $mes, $ano] = explode('/', $dataBr);

            $lancamentos[] = [
                'data' => "{$ano}-{$mes}-{$dia}",
                'historico' => $historico,
                'documento' => $documento,
                ...$mapeado,
            ];
        }

        return $lancamentos;
    }

    /**
     * @return array{valor: float, tipo: string, categoria: string, descricao: string, observacao: string, classificado: bool}|null
     */
    private function mapear(string $historico, string $documento, float $credito, float $debito): ?array
    {
        $doc = $documento !== '' ? $documento : 's/n';
        $historicoUpper = mb_strtoupper($historico);

        if ($credito > 0) {
            if (str_contains($historicoUpper, 'RENTAB')) {
                return [
                    'valor' => $credito,
                    'tipo' => 'entrada',
                    'categoria' => 'outro',
                    'descricao' => 'Rendimento Invest Fácil Cred',
                    'observacao' => "Rentabilidade da aplicação Invest Fácil Cred (doc. {$doc}). Histórico bancário: {$historico}.",
                    'classificado' => true,
                ];
            }

            return [
                'valor' => $credito,
                'tipo' => 'entrada',
                'categoria' => 'arrecadacao',
                'descricao' => 'PIX recebido',
                'observacao' => "Doação via PIX (doc. {$doc}). Benfeitor não identificado no extrato — classificar depois.",
                'classificado' => false,
            ];
        }

        if (str_contains($historicoUpper, 'QR CODE ESTATICO')) {
            return [
                'valor' => $debito,
                'tipo' => 'saida',
                'categoria' => 'compra',
                'descricao' => 'PIX QR Code estático',
                'observacao' => "Pagamento via PIX QR Code estático (doc. {$doc}). Confirme o título e a descrição.",
                'classificado' => false,
            ];
        }

        if (str_contains($historicoUpper, 'QR CODE DINAMICO')) {
            return [
                'valor' => $debito,
                'tipo' => 'saida',
                'categoria' => 'compra',
                'descricao' => 'PIX QR Code dinâmico',
                'observacao' => "Pagamento via PIX QR Code dinâmico (doc. {$doc}). Confirme o título e a descrição.",
                'classificado' => false,
            ];
        }

        if (str_contains($historicoUpper, 'PIX ENVIADO')) {
            return [
                'valor' => $debito,
                'tipo' => 'saida',
                'categoria' => 'compra',
                'descricao' => 'PIX enviado',
                'observacao' => "Pagamento via PIX (doc. {$doc}). Confirme o título e a descrição.",
                'classificado' => false,
            ];
        }

        return [
            'valor' => $debito,
            'tipo' => 'saida',
            'categoria' => 'outro',
            'descricao' => $historico,
            'observacao' => "Lançamento do extrato (doc. {$doc}): {$historico}. Classifique título e descrição.",
            'classificado' => false,
        ];
    }

    private function parseValor(mixed $valor): float
    {
        $valor = trim((string) $valor);
        if ($valor === '' || $valor === '-') {
            return 0.0;
        }

        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

        return round((float) $valor, 2);
    }
}
