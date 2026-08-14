<?php

namespace App\Actions\Lancamento;

use App\Models\Lancamento;

class UpdateLancamentoAction
{
    public function execute(Lancamento $lancamento, array $data): Lancamento
    {
        $createAction = app(CreateLancamentoAction::class);

        $merged = array_merge([
            'data' => $lancamento->data->format('Y-m-d'),
            'tipo' => $lancamento->tipo->value,
            'categoria' => $lancamento->categoria->value,
            'valor' => $lancamento->valor,
            'descricao' => $lancamento->descricao,
            'observacao' => $lancamento->observacao,
            'benfeitor_id' => $lancamento->benfeitor_id,
            'classificado' => $lancamento->classificado,
            'is_historico' => $lancamento->is_historico,
            'historico_bancario' => $lancamento->historico_bancario,
            'documento' => $lancamento->documento,
        ], $data);

        $createAction->validate($merged);

        $lancamento->update([
            'data' => $data['data'],
            'tipo' => $data['tipo'],
            'categoria' => $data['categoria'],
            'valor' => $data['valor'],
            'descricao' => $data['descricao'],
            'observacao' => $data['observacao'] ?? null,
            'benfeitor_id' => $createAction->resolveBenfeitorId($merged, $lancamento->user_id),
            'classificado' => array_key_exists('classificado', $data)
                ? (bool) $data['classificado']
                : $lancamento->classificado,
        ]);

        $lancamento->anexarArquivos($createAction->arquivosDeAnexo($data));

        return $lancamento->fresh(['anexos']);
    }
}
