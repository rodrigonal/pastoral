<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segmento extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'ordem',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function lancamentos(): BelongsToMany
    {
        return $this->belongsToMany(Lancamento::class, 'lancamento_segmento')->withTimestamps();
    }
}
