<?php

use App\Actions\Lancamento\CreateLancamentoAction;
use App\Actions\Lancamento\UpdateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->benfeitor = Benfeitor::factory()->create();
});

it('salva anexo valido no lancamento', function () {
    $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

    $action = app(CreateLancamentoAction::class);
    $lancamento = $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 100,
        'descricao' => 'Teste com anexo',
        'benfeitor_id' => $this->benfeitor->id,
        'anexo' => $file,
    ], $this->user->id);

    expect($lancamento->anexos)->toHaveCount(1);
    Storage::disk('local')->assertExists($lancamento->anexos->first()->path);
});

it('salva varios anexos no mesmo lancamento', function () {
    $pdf = UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf');
    $imagem = UploadedFile::fake()->image('comprovante.jpg');

    $lancamento = app(CreateLancamentoAction::class)->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Saida->value,
        'categoria' => CategoriaLancamentoEnum::Compra->value,
        'valor' => 50,
        'descricao' => 'Compra com dois comprovantes',
        'anexos' => [$pdf, $imagem],
    ], $this->user->id);

    expect($lancamento->anexos)->toHaveCount(2);

    $extra = UploadedFile::fake()->create('recibo.pdf', 80, 'application/pdf');

    $lancamento = app(UpdateLancamentoAction::class)->execute($lancamento, [
        'data' => $lancamento->data->format('Y-m-d'),
        'tipo' => $lancamento->tipo->value,
        'categoria' => $lancamento->categoria->value,
        'valor' => $lancamento->valor,
        'descricao' => $lancamento->descricao,
        'anexos' => [$extra],
    ]);

    expect($lancamento->anexos)->toHaveCount(3);
});
