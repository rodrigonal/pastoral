<?php

namespace App\Http\Controllers;

use App\Models\LancamentoAnexo;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LancamentoAnexoController extends Controller
{
    public function download(LancamentoAnexo $anexo): StreamedResponse
    {
        $path = $anexo->caminhoAbsoluto();

        if (! file_exists($path)) {
            abort(404);
        }

        $filename = $anexo->nome();
        $disposition = (request()->boolean('inline') && $anexo->ehImagem()) ? 'inline' : 'attachment';

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
        }, $filename, [
            'Content-Type' => $anexo->mimeType(),
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }
}
