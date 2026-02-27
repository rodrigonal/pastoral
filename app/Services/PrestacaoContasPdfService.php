<?php

namespace App\Services;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;

class PrestacaoContasPdfService
{
    public function __construct(
        private readonly SaldoService $saldoService
    ) {}

    /**
     * Gera PDF para um período de vários meses (mês inicial até mês final).
     */
    public function gerarPeriodo(int $mesInicio, int $anoInicio, int $mesFim, int $anoFim): Response
    {
        $dataInicio = Carbon::createFromDate($anoInicio, $mesInicio, 1);
        $dataFim = Carbon::createFromDate($anoFim, $mesFim, 1);
        if ($dataInicio->gt($dataFim)) {
            return response()->json(['message' => 'Período inválido: data inicial deve ser anterior à final.'], 422);
        }

        $fpdi = new Fpdi;
        $tempFiles = [];

        try {
            $mesAtual = (clone $dataInicio)->startOfMonth();
            while ($mesAtual->lte($dataFim)) {
                $tempFile = $this->gerarPdfUnicoMesTemp($mesAtual->month, $mesAtual->year);
                $tempFiles[] = $tempFile;

                $pageCount = $fpdi->setSourceFile($tempFile);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $fpdi->importPage($i);
                    $fpdi->AddPage();
                    $fpdi->useTemplate($tplId);
                }
                $mesAtual->addMonth();
            }

            $output = $fpdi->Output('S');
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        $mesInicioNome = Carbon::create()->month($mesInicio)->locale('pt_BR')->translatedFormat('F');
        $mesFimNome = Carbon::create()->month($mesFim)->locale('pt_BR')->translatedFormat('F');
        $filename = $mesInicio === $mesFim && $anoInicio === $anoFim
            ? "prestacao-contas-{$mesInicioNome}-{$anoInicio}.pdf"
            : "prestacao-contas-{$mesInicioNome}-{$anoInicio}-a-{$mesFimNome}-{$anoFim}.pdf";

        return response()->streamDownload(
            fn () => print($output),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Gera PDF para um único mês (retorna Response com stream).
     */
    public function gerar(int $mes, int $ano): Response
    {
        $tempFile = $this->gerarPdfUnicoMesTemp($mes, $ano);
        $mesNome = Carbon::create()->month($mes)->locale('pt_BR')->translatedFormat('F');
        $filename = "prestacao-contas-{$mesNome}-{$ano}.pdf";

        try {
            return response()->streamDownload(
                fn () => print(file_get_contents($tempFile)),
                $filename,
                ['Content-Type' => 'application/pdf']
            );
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Gera PDF para um único mês e retorna o path do arquivo temporário.
     * O caller deve deletar o arquivo após o uso.
     */
    private function gerarPdfUnicoMesTemp(int $mes, int $ano): string
    {
        $inicio = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $fim = Carbon::createFromDate($ano, $mes, 1)->endOfMonth();

        $entradas = Lancamento::with('segmentos')
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->whereDate('data', '>=', $inicio)
            ->whereDate('data', '<=', $fim)
            ->orderBy('data')
            ->get();

        $saidas = Lancamento::query()
            ->where('tipo', TipoLancamentoEnum::Saida)
            ->whereDate('data', '>=', $inicio)
            ->whereDate('data', '<=', $fim)
            ->orderBy('data')
            ->get();

        $saidasAfetamSaldo = $saidas->filter(fn ($l) => $l->categoria !== CategoriaLancamentoEnum::Reembolso);
        $reembolsos = $saidas->filter(fn ($l) => $l->categoria === CategoriaLancamentoEnum::Reembolso);

        $totalEntradas = $this->saldoService->totalEntradasPeriodo($inicio, $fim);
        $totalSaidas = $this->saldoService->totalSaidasPeriodo($inicio, $fim);
        $totalReembolsos = $this->saldoService->totalReembolsosPeriodo($inicio, $fim);
        $saldoAnterior = $this->saldoService->saldoAnterior($mes, $ano);
        $saldoFinal = $saldoAnterior + $totalEntradas - $totalSaidas;

        $mesNome = Carbon::create()->month($mes)->locale('pt_BR')->translatedFormat('F');
        $titulo = "RESUMO DE " . strtoupper($mesNome) . "/{$ano} - Pastoral de Rua PJC";

        $data = [
            'titulo' => $titulo,
            'entradas' => $entradas,
            'saidasAfetamSaldo' => $saidasAfetamSaldo,
            'reembolsos' => $reembolsos,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'totalReembolsos' => $totalReembolsos,
            'saldoAnterior' => $saldoAnterior,
            'saldoFinal' => $saldoFinal,
        ];

        $domPdf = Pdf::loadView('pdf.prestacao-contas-mensal', $data)
            ->setPaper('a4', 'portrait');

        $lancamentosComPdf = $entradas->concat($saidas)
            ->filter(fn (Lancamento $l) => $l->anexo_path && strtolower(pathinfo($l->anexo_path, PATHINFO_EXTENSION)) === 'pdf')
            ->sortBy('data')
            ->values();

        $tempMain = tempnam(sys_get_temp_dir(), 'prestacao_');
        $domPdf->save($tempMain);

        if ($lancamentosComPdf->isEmpty()) {
            return $tempMain;
        }

        try {
            $fpdi = new Fpdi;
            $pageCount = $fpdi->setSourceFile($tempMain);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $fpdi->importPage($i);
                $fpdi->AddPage();
                $fpdi->useTemplate($tplId);
            }

            foreach ($lancamentosComPdf as $lancamento) {
                $anexoPath = Storage::disk('local')->path($lancamento->anexo_path);
                if (! file_exists($anexoPath)) {
                    continue;
                }
                try {
                    $anexoPageCount = $fpdi->setSourceFile($anexoPath);
                    for ($i = 1; $i <= $anexoPageCount; $i++) {
                        $tplId = $fpdi->importPage($i);
                        $fpdi->AddPage();
                        $fpdi->useTemplate($tplId);
                    }
                } catch (\Throwable) {
                    continue;
                }
            }

            $tempFinal = tempnam(sys_get_temp_dir(), 'prestacao_');
            file_put_contents($tempFinal, $fpdi->Output('S'));
            @unlink($tempMain);

            return $tempFinal;
        } catch (\Throwable $e) {
            @unlink($tempMain);
            throw $e;
        }
    }
}
