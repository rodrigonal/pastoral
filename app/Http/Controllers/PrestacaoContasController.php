<?php

namespace App\Http\Controllers;

use App\Services\PrestacaoContasPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PrestacaoContasController extends Controller
{
    public function download(Request $request): HttpResponse
    {
        $periodo = $request->filled('mes_inicio');

        if ($periodo) {
            $request->validate([
                'mes_inicio' => ['required', 'integer', 'min:1', 'max:12'],
                'ano_inicio' => ['required', 'integer', 'min:2020', 'max:2030'],
                'mes_fim' => ['required', 'integer', 'min:1', 'max:12'],
                'ano_fim' => ['required', 'integer', 'min:2020', 'max:2030'],
                'formato_periodo' => ['nullable', 'string', 'in:mensal,resumo'],
            ]);

            $mesInicio = (int) $request->input('mes_inicio');
            $anoInicio = (int) $request->input('ano_inicio');
            $mesFim = (int) $request->input('mes_fim');
            $anoFim = (int) $request->input('ano_fim');
            $formato = $request->input('formato_periodo', 'mensal');

            return app(PrestacaoContasPdfService::class)->gerarPeriodo($mesInicio, $anoInicio, $mesFim, $anoFim, $formato);
        }

        $request->validate([
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
            'ano' => ['required', 'integer', 'min:2020', 'max:2030'],
        ]);

        $mes = (int) $request->input('mes');
        $ano = (int) $request->input('ano');

        return app(PrestacaoContasPdfService::class)->gerar($mes, $ano);
    }
}
