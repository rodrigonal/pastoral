<?php

namespace App\Http\Controllers;

use App\Models\Lancamento;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LancamentoAnexoController extends Controller
{
    public function download(Lancamento $lancamento): StreamedResponse
    {
        if (! $lancamento->anexo_path) {
            abort(404);
        }

        $path = Storage::disk('local')->path($lancamento->anexo_path);

        if (! file_exists($path)) {
            abort(404);
        }

        $filename = basename($lancamento->anexo_path);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeTypes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);

        $disposition = (request()->boolean('inline') && $isImage) ? 'inline' : 'attachment';

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
        }, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }
}
