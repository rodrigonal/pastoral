<?php

namespace App\Actions\Lancamento;

use App\Models\Lancamento;

class DeleteLancamentoAction
{
    public function execute(Lancamento $lancamento): void
    {
        $lancamento->load('anexos');

        foreach ($lancamento->anexos as $anexo) {
            $anexo->excluirArquivo();
        }

        $lancamento->delete();
    }
}
