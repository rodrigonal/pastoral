<?php

namespace App\Services;

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

    public function gerar(int $mes, int $ano): \Barryvdh\DomPDF\PDF|Response
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

        $totalEntradas = $this->saldoService->totalEntradasPeriodo($inicio, $fim);
        $totalSaidas = $this->saldoService->totalSaidasPeriodo($inicio, $fim);
        $saldoAnterior = $this->saldoService->saldoAnterior($mes, $ano);
        $saldoFinal = $saldoAnterior + $totalEntradas - $totalSaidas;

        $mesNome = Carbon::create()->month($mes)->locale('pt_BR')->translatedFormat('F');
        $titulo = "RESUMO DE " . strtoupper($mesNome) . "/{$ano} - Pastoral de Rua PJC";

        $data = [
            'titulo' => $titulo,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'saldoAnterior' => $saldoAnterior,
            'saldoFinal' => $saldoFinal,
        ];

        $domPdf = Pdf::loadView('pdf.prestacao-contas-mensal', $data)
            ->setPaper('a4', 'portrait');

        $lancamentosComPdf = $entradas->concat($saidas)
            ->filter(fn (Lancamento $l) => $l->anexo_path && strtolower(pathinfo($l->anexo_path, PATHINFO_EXTENSION)) === 'pdf')
            ->sortBy('data')
            ->values();

        if ($lancamentosComPdf->isEmpty()) {
            return $domPdf;
        }

        $tempMain = tempnam(sys_get_temp_dir(), 'prestacao_');
        $domPdf->save($tempMain);

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

            $output = $fpdi->Output('S');
        } finally {
            @unlink($tempMain);
        }

        $filename = "prestacao-contas-{$mesNome}-{$ano}.pdf";

        return response()->streamDownload(
            fn () => print($output),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
