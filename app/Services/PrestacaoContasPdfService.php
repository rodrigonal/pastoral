<?php

namespace App\Services;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Support\ExtratoBancarioCatalogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;

class PrestacaoContasPdfService
{
    public function __construct(
        private readonly SaldoService $saldoService
    ) {}

    /**
     * Gera PDF para um período de vários meses.
     *
     * @param  'mensal'|'resumo'  $formato  mensal = cada mês separado no mesmo PDF; resumo = todos os lançamentos juntos, saldo final único
     */
    public function gerarPeriodo(int $mesInicio, int $anoInicio, int $mesFim, int $anoFim, string $formato = 'mensal'): Response
    {
        $dataInicio = Carbon::createFromDate($anoInicio, $mesInicio, 1);
        $dataFim = Carbon::createFromDate($anoFim, $mesFim, 1)->endOfMonth();
        if ($dataInicio->gt($dataFim)) {
            return response()->json(['message' => 'Período inválido: data inicial deve ser anterior à final.'], 422);
        }

        if ($formato === 'resumo') {
            return $this->gerarPeriodoResumo($dataInicio, $dataFim, $mesInicio, $anoInicio, $mesFim, $anoFim);
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

            $this->anexarPdfs($fpdi, ExtratoBancarioCatalogo::noPeriodo($dataInicio, $dataFim)->pluck('caminho')->all());

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
     * Gera PDF resumo consolidado: todos os lançamentos do período juntos, saldo final único.
     */
    private function gerarPeriodoResumo(Carbon $inicio, Carbon $fim, int $mesInicio, int $anoInicio, int $mesFim, int $anoFim): Response
    {
        $entradas = Lancamento::with(['benfeitor', 'anexos'])
            ->contaAtual()
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->whereDate('data', '>=', $inicio)
            ->whereDate('data', '<=', $fim)
            ->orderBy('data')
            ->get();

        $saidas = Lancamento::query()
            ->with('anexos')
            ->contaAtual()
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
        $saldoAnterior = $this->saldoService->saldoAcumulado($inicio->copy()->subDay());
        $saldoFinal = $saldoAnterior + $totalEntradas - $totalSaidas;

        $mesInicioNome = Carbon::create()->month($mesInicio)->locale('pt_BR')->translatedFormat('F');
        $mesFimNome = Carbon::create()->month($mesFim)->locale('pt_BR')->translatedFormat('F');
        $titulo = "RESUMO DE " . strtoupper($mesInicioNome) . "/{$anoInicio} A " . strtoupper($mesFimNome) . "/{$anoFim} - Pastoral de Rua PJC";

        $data = [
            'titulo' => $titulo,
            'logoBase64' => $this->getLogoBase64(),
            'entradas' => $entradas,
            'saidasAfetamSaldo' => $saidasAfetamSaldo,
            'reembolsos' => $reembolsos,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'totalReembolsos' => $totalReembolsos,
            'saldoAnterior' => $saldoAnterior,
            'saldoFinal' => $saldoFinal,
            'extratos' => ExtratoBancarioCatalogo::noPeriodo($inicio, $fim),
        ];

        $domPdf = Pdf::loadView('pdf.prestacao-contas-resumo', $data)
            ->setPaper('a4', 'portrait');

        $lancamentosComPdf = $entradas->concat($saidas)->sortBy('data')->values();

        $tempMain = tempnam(sys_get_temp_dir(), 'prestacao_');
        $domPdf->save($tempMain);

        $anexos = array_merge(
            $this->caminhosAnexosPdf($lancamentosComPdf),
            ExtratoBancarioCatalogo::noPeriodo($inicio, $fim)->pluck('caminho')->all()
        );

        $tempFinal = $this->concatenarAnexos($tempMain, $anexos);
        $output = file_get_contents($tempFinal);
        @unlink($tempFinal);

        $filename = "prestacao-contas-resumo-{$mesInicioNome}-{$anoInicio}-a-{$mesFimNome}-{$anoFim}.pdf";

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
        $inicio = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();
        $tempFile = $this->gerarPdfUnicoMesTemp($mes, $ano);
        $tempFile = $this->concatenarAnexos(
            $tempFile,
            ExtratoBancarioCatalogo::noPeriodo($inicio, $fim)->pluck('caminho')->all()
        );
        $mesNome = Carbon::create()->month($mes)->locale('pt_BR')->translatedFormat('F');
        $filename = "prestacao-contas-{$mesNome}-{$ano}.pdf";

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return response()->streamDownload(
            fn () => print($content),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Gera PDF para um único mês e retorna o path do arquivo temporário.
     * O caller deve deletar o arquivo após o uso.
     */
    private function gerarPdfUnicoMesTemp(int $mes, int $ano): string
    {
        $inicio = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $fim = Carbon::createFromDate($ano, $mes, 1)->endOfMonth();

        $entradas = Lancamento::with(['benfeitor', 'anexos'])
            ->contaAtual()
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->whereDate('data', '>=', $inicio)
            ->whereDate('data', '<=', $fim)
            ->orderBy('data')
            ->get();

        $saidas = Lancamento::query()
            ->with('anexos')
            ->contaAtual()
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
            'logoBase64' => $this->getLogoBase64(),
            'entradas' => $entradas,
            'saidasAfetamSaldo' => $saidasAfetamSaldo,
            'reembolsos' => $reembolsos,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'totalReembolsos' => $totalReembolsos,
            'saldoAnterior' => $saldoAnterior,
            'saldoFinal' => $saldoFinal,
            'extratos' => ExtratoBancarioCatalogo::noPeriodo($inicio, $fim),
        ];

        $domPdf = Pdf::loadView('pdf.prestacao-contas-mensal', $data)
            ->setPaper('a4', 'portrait');

        $lancamentosDoMes = $entradas->concat($saidas)->sortBy('data')->values();

        $tempMain = tempnam(sys_get_temp_dir(), 'prestacao_');
        $domPdf->save($tempMain);

        return $this->concatenarAnexos($tempMain, $this->caminhosAnexosPdf($lancamentosDoMes));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Lancamento>|iterable<Lancamento>  $lancamentos
     * @return list<string>
     */
    private function caminhosAnexosPdf(iterable $lancamentos): array
    {
        return collect($lancamentos)
            ->flatMap(fn (Lancamento $lancamento) => $lancamento->anexos)
            ->filter(fn ($anexo) => $anexo->ehPdf())
            ->map(fn ($anexo) => $anexo->caminhoAbsoluto())
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $caminhos
     */
    private function concatenarAnexos(string $tempMain, array $caminhos): string
    {
        $caminhos = array_values(array_filter(
            $caminhos,
            fn ($caminho) => is_string($caminho) && is_file($caminho)
        ));

        if ($caminhos === []) {
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

            $this->anexarPdfs($fpdi, $caminhos);

            $tempFinal = tempnam(sys_get_temp_dir(), 'prestacao_');
            file_put_contents($tempFinal, $fpdi->Output('S'));
            @unlink($tempMain);

            return $tempFinal;
        } catch (\Throwable) {
            return $tempMain;
        }
    }

    /**
     * @param  list<string>  $caminhos
     */
    private function anexarPdfs(Fpdi $fpdi, array $caminhos): void
    {
        foreach ($caminhos as $caminho) {
            if (! is_string($caminho) || ! is_file($caminho)) {
                continue;
            }

            try {
                $pageCount = $fpdi->setSourceFile($caminho);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $fpdi->importPage($i);
                    $fpdi->AddPage();
                    $fpdi->useTemplate($tplId);
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }

    private function getLogoBase64(): ?string
    {
        $path = public_path('images/logo.png');
        if (! File::exists($path)) {
            return null;
        }
        $content = File::get($path);

        return 'data:image/png;base64,' . base64_encode($content);
    }
}
