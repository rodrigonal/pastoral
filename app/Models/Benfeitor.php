<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Benfeitor extends Model
{
    use HasFactory;

    protected $table = 'benfeitores';

    protected $fillable = [
        'nome',
        'membro_id',
        'ativo',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function membro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'membro_id');
    }
}
