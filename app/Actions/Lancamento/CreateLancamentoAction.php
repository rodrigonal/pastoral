<?php

namespace App\Actions\Lancamento;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\Lancamento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateLancamentoAction
{
    public function execute(array $data, int $userId): Lancamento
    {
        $this->validate($data);

        $classificado = array_key_exists('classificado', $data)
            ? (bool) $data['classificado']
            : true;

        $lancamento = Lancamento::create([
            'data' => $data['data'],
            'tipo' => $data['tipo'],
            'categoria' => $data['categoria'],
            'valor' => $data['valor'],
            'descricao' => $data['descricao'],
            'observacao' => $data['observacao'] ?? null,
            'user_id' => $userId,
            'benfeitor_id' => $this->resolveBenfeitorId($data, $userId),
            'classificado' => $classificado,
            'is_historico' => (bool) ($data['is_historico'] ?? false),
            'historico_bancario' => $data['historico_bancario'] ?? null,
            'documento' => $data['documento'] ?? null,
        ]);

        $lancamento->anexarArquivos($this->arquivosDeAnexo($data));

        return $lancamento->load('anexos');
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
            'anexos' => ['nullable', 'array', 'max:10'],
            'anexos.*' => ['file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
            'benfeitor_id' => ['nullable', 'exists:benfeitores,id'],
            'novo_benfeitor_nome' => ['nullable', 'string', 'max:255'],
            'membro_id' => ['nullable', 'exists:users,id'],
            'classificado' => ['nullable', 'boolean'],
            'is_historico' => ['nullable', 'boolean'],
            'historico_bancario' => ['nullable', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:50'],
        ];

        $validator = Validator::make($data, $rules);

        $validator->after(function ($validator) use ($data) {
            $categoria = $data['categoria'] ?? null;
            $classificado = array_key_exists('classificado', $data)
                ? (bool) $data['classificado']
                : true;

            if ($categoria === CategoriaLancamentoEnum::Arrecadacao->value) {
                if (($data['tipo'] ?? '') !== TipoLancamentoEnum::Entrada->value) {
                    $validator->errors()->add('tipo', 'Arrecadação deve ser tipo entrada.');
                }

                if ($classificado) {
                    $benfeitorId = $data['benfeitor_id'] ?? null;
                    $novoNome = trim((string) ($data['novo_benfeitor_nome'] ?? ''));
                    if (empty($benfeitorId) && $novoNome === '') {
                        $validator->errors()->add('benfeitor_id', 'Informe o benfeitor da arrecadação.');
                    }
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

    /**
     * @param  array<string, mixed>  $data
     * @return list<UploadedFile>
     */
    public function arquivosDeAnexo(array $data): array
    {
        $arquivos = [];

        if (isset($data['anexo']) && $data['anexo'] instanceof UploadedFile) {
            $arquivos[] = $data['anexo'];
        }

        if (isset($data['anexos']) && is_array($data['anexos'])) {
            foreach ($data['anexos'] as $arquivo) {
                if ($arquivo instanceof UploadedFile) {
                    $arquivos[] = $arquivo;
                }
            }
        }

        return $arquivos;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resolveBenfeitorId(array $data, ?int $userId = null): ?int
    {
        if (! empty($data['benfeitor_id'])) {
            return (int) $data['benfeitor_id'];
        }

        $nome = trim((string) ($data['novo_benfeitor_nome'] ?? ''));
        if ($nome === '') {
            return null;
        }

        $membroId = $data['membro_id'] ?? $userId;

        return Benfeitor::firstOrCreate(
            ['nome' => $nome],
            ['ativo' => true, 'membro_id' => $membroId]
        )->id;
    }
}
