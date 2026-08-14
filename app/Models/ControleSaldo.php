<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControleSaldo extends Model
{
    protected $table = 'controle_saldos';

    protected $fillable = [
        'saldo_em_maos',
    ];

    protected function casts(): array
    {
        return [
            'saldo_em_maos' => 'decimal:2',
        ];
    }

    /**
     * Registro único de controle (id 1): saldo em mãos informado pela tesouraria.
     */
    public static function registro(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['saldo_em_maos' => 0]
        );
    }
}
