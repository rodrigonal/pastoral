<?php

namespace App\Models;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LancamentoLegadoCs extends Model
{
    protected $table = 'lancamentos_legado_cs';

    protected $fillable = [
        'original_lancamento_id',
        'data',
        'tipo',
        'categoria',
        'valor',
        'descricao',
        'observacao',
        'user_id',
        'benfeitor_id',
        'classificado',
        'historico_bancario',
        'documento',
        'original_created_at',
        'original_updated_at',
        'arquivado_em',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'tipo' => TipoLancamentoEnum::class,
            'categoria' => CategoriaLancamentoEnum::class,
            'valor' => 'decimal:2',
            'classificado' => 'boolean',
            'original_created_at' => 'datetime',
            'original_updated_at' => 'datetime',
            'arquivado_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function benfeitor(): BelongsTo
    {
        return $this->belongsTo(Benfeitor::class);
    }
}
