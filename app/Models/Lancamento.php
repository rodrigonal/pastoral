<?php

namespace App\Models;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lancamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'data',
        'tipo',
        'categoria',
        'valor',
        'descricao',
        'observacao',
        'anexo_path',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'tipo' => TipoLancamentoEnum::class,
            'categoria' => CategoriaLancamentoEnum::class,
            'valor' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function segmentos(): BelongsToMany
    {
        return $this->belongsToMany(Segmento::class, 'lancamento_segmento')->withTimestamps();
    }
}
