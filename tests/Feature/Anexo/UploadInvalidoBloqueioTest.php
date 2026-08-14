<?php

use App\Actions\Lancamento\CreateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->benfeitor = Benfeitor::factory()->create();
});

it('bloqueia upload de arquivo invalido', function () {
    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $action = app(CreateLancamentoAction::class);

    $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 100,
        'descricao' => 'Teste com anexo invalido',
        'benfeitor_id' => $this->benfeitor->id,
        'anexo' => $file,
    ], $this->user->id);
})->throws(ValidationException::class);
