<?php

namespace App\Actions\Lancamento;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateLancamentoAction
{
    public function execute(array $data, int $userId): Lancamento
    {
        $this->validate($data);

        $anexoPath = $data['anexo_path'] ?? null;
        if (isset($data['anexo']) && $data['anexo'] instanceof UploadedFile) {
            $anexoPath = $data['anexo']->store('lancamentos', 'local');
        }

        $lancamento = Lancamento::create([
            'data' => $data['data'],
            'tipo' => $data['tipo'],
            'categoria' => $data['categoria'],
            'valor' => $data['valor'],
            'descricao' => $data['descricao'],
            'observacao' => $data['observacao'] ?? null,
            'anexo_path' => $anexoPath,
            'user_id' => $userId,
        ]);

        $segmentoIds = $data['segmento_ids'] ?? [];
        if (! empty($segmentoIds)) {
            $lancamento->segmentos()->sync($segmentoIds);
        }

        return $lancamento;
    }

    /**
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        $rules = [
            'data' => ['required', 'date'],
            'tipo' => ['required', 'in:entrada,saida'],
            'categoria' => ['required', 'in:arrecadacao,repasse,compra,reembolso,outro'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'descricao' => ['required', 'string', 'max:255'],
            'observacao' => ['nullable', 'string'],
            'anexo' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
            'anexo_path' => ['nullable', 'string', 'max:500'],
            'segmento_ids' => ['nullable', 'array'],
            'segmento_ids.*' => ['exists:segmentos,id'],
        ];

        $validator = Validator::make($data, $rules);

        $validator->after(function ($validator) use ($data) {
            $categoria = $data['categoria'] ?? null;
            if ($categoria === CategoriaLancamentoEnum::Arrecadacao->value) {
                $segmentoIds = $data['segmento_ids'] ?? [];
                if (empty($segmentoIds) || (is_array($segmentoIds) && count(array_filter($segmentoIds)) === 0)) {
                    $validator->errors()->add('segmento_ids', 'Pelo menos um segmento é obrigatório para arrecadação.');
                }
                if (($data['tipo'] ?? '') !== TipoLancamentoEnum::Entrada->value) {
                    $validator->errors()->add('tipo', 'Arrecadação deve ser tipo entrada.');
                }
            }
            if (in_array($categoria, [CategoriaLancamentoEnum::Repasse->value, CategoriaLancamentoEnum::Compra->value, CategoriaLancamentoEnum::Reembolso->value])) {
                if (($data['tipo'] ?? '') !== TipoLancamentoEnum::Saida->value) {
                    $validator->errors()->add('tipo', 'Esta categoria deve ser tipo saída.');
                }
            }
        });

        $validator->validate();
    }
}
