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

    public function requerSegmento(): bool
    {
        return $this === self::Arrecadacao;
    }
}
