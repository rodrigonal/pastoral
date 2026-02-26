<?php

namespace App\Actions\Lancamento;

use App\Models\Lancamento;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateLancamentoAction
{
    public function execute(Lancamento $lancamento, array $data): Lancamento
    {
        $merged = array_merge([
            'data' => $lancamento->data->format('Y-m-d'),
            'tipo' => $lancamento->tipo->value,
            'categoria' => $lancamento->categoria->value,
            'valor' => $lancamento->valor,
            'descricao' => $lancamento->descricao,
            'observacao' => $lancamento->observacao,
            'anexo_path' => $lancamento->anexo_path,
            'segmento_ids' => $lancamento->segmentos->pluck('id')->toArray(),
        ], $data);
        app(CreateLancamentoAction::class)->validate($merged);

        $lancamento->update([
            'data' => $data['data'],
            'tipo' => $data['tipo'],
            'categoria' => $data['categoria'],
            'valor' => $data['valor'],
            'descricao' => $data['descricao'],
            'observacao' => $data['observacao'] ?? null,
            'anexo_path' => $data['anexo_path'] ?? $lancamento->anexo_path,
        ]);

        $segmentoIds = $data['segmento_ids'] ?? [];
        $lancamento->segmentos()->sync(is_array($segmentoIds) ? $segmentoIds : []);

        return $lancamento->fresh();
    }
}
