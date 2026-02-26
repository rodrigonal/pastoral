<?php

namespace App\Http\Controllers;

use App\Services\PrestacaoContasPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PrestacaoContasController extends Controller
{
    public function download(Request $request): HttpResponse
    {
        $request->validate([
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
            'ano' => ['required', 'integer', 'min:2020', 'max:2030'],
        ]);

        $mes = (int) $request->input('mes');
        $ano = (int) $request->input('ano');
        $mesNome = Carbon::create()->month($mes)->locale('pt_BR')->translatedFormat('F');

        $result = app(PrestacaoContasPdfService::class)->gerar($mes, $ano);

        return $result instanceof HttpResponse
            ? $result
            : $result->stream("prestacao-contas-{$mesNome}-{$ano}.pdf");
    }
}
