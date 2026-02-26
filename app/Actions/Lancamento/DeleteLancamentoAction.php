<?php

namespace App\Actions\Lancamento;

use App\Models\Lancamento;
use Illuminate\Support\Facades\Storage;

class DeleteLancamentoAction
{
    public function execute(Lancamento $lancamento): void
    {
        if ($lancamento->anexo_path) {
            Storage::disk('local')->delete($lancamento->anexo_path);
        }

        $lancamento->delete();
    }
}
