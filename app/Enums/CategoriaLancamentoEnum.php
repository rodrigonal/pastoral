<?php

namespace App\Enums;

enum CategoriaLancamentoEnum: string
{
    case Arrecadacao = 'arrecadacao';
    case Repasse = 'repasse';
    case Compra = 'compra';
    case Reembolso = 'reembolso';
    case Outro = 'outro';

    public function tipoCorrespondente(): TipoLancamentoEnum
    {
        return match ($this) {
            self::Arrecadacao => TipoLancamentoEnum::Entrada,
            self::Repasse, self::Compra, self::Reembolso => TipoLancamentoEnum::Saida,
            self::Outro => throw new \InvalidArgumentException('Categoria Outro requer tipo explícito'),
        };
    }

    public function requerBenfeitor(): bool
    {
        return $this === self::Arrecadacao;
    }

    public function label(): string
    {
        return match ($this) {
            self::Arrecadacao => 'Arrecadação',
            self::Repasse => 'Repasse',
            self::Compra => 'Compra',
            self::Reembolso => 'Reembolso',
            self::Outro => 'Outro',
        };
    }
}
