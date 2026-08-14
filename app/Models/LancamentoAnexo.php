<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LancamentoAnexo extends Model
{
    protected $fillable = [
        'lancamento_id',
        'path',
        'nome_original',
        'ordem',
    ];

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class);
    }

    public function extensao(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function ehImagem(): bool
    {
        return in_array($this->extensao(), ['jpg', 'jpeg', 'png', 'gif'], true);
    }

    public function ehPdf(): bool
    {
        return $this->extensao() === 'pdf';
    }

    public function nome(): string
    {
        return $this->nome_original ?: basename($this->path);
    }

    public function caminhoAbsoluto(): string
    {
        return Storage::disk('local')->path($this->path);
    }

    public function mimeType(): string
    {
        return match ($this->extensao()) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }

    public function excluirArquivo(): void
    {
        Storage::disk('local')->delete($this->path);
        $this->delete();
    }
}
