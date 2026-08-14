<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pastoral extends Model
{
    use HasFactory;

    protected $table = 'pastorais';

    protected $fillable = [
        'data',
        'titulo',
        'agradecimento',
        'frase_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'frase_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imagens(): HasMany
    {
        return $this->hasMany(PastoralImagem::class)->orderBy('ordem')->orderBy('id');
    }

    public function tituloExibicao(): string
    {
        if (filled($this->titulo)) {
            return $this->titulo;
        }

        return 'Pastoral de Rua · '.$this->data->locale('pt_BR')->translatedFormat('F \d\e Y');
    }
}
