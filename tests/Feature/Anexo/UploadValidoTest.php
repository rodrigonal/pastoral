<?php

use App\Actions\Lancamento\CreateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Models\Segmento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->segmento = Segmento::factory()->create();
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
        'segmento_ids' => [$this->segmento->id],
        'anexo' => $file,
    ], $this->user->id);

    expect($lancamento->anexo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($lancamento->anexo_path);
});
