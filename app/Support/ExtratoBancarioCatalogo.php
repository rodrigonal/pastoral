<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExtratoBancarioCatalogo
{
    public static function diretorio(): string
    {
        return database_path('seeders/data/extratos');
    }

    /**
     * @return list<array{arquivo: string, inicio: string, fim: string, titulo: string, emitido_em: string, seeder: string|null, caminho: string}>
     */
    public static function todos(): array
    {
        $catalogo = require self::diretorio().DIRECTORY_SEPARATOR.'catalogo.php';
        $itens = [];

        foreach ($catalogo as $item) {
            $caminho = self::diretorio().DIRECTORY_SEPARATOR.$item['arquivo'];
            $itens[] = [
                ...$item,
                'seeder' => $item['seeder'] ?? null,
                'caminho' => $caminho,
                'existe' => is_file($caminho),
            ];
        }

        return $itens;
    }

    /**
     * Extratos cujo período coincide com o intervalo da prestação.
     *
     * @return Collection<int, array{arquivo: string, inicio: string, fim: string, titulo: string, emitido_em: string, seeder: string|null, caminho: string, existe: bool}>
     */
    public static function noPeriodo(Carbon $inicio, Carbon $fim): Collection
    {
        $inicio = $inicio->copy()->startOfDay();
        $fim = $fim->copy()->endOfDay();

        return collect(self::todos())->filter(function (array $item) use ($inicio, $fim) {
            if (! ($item['existe'] ?? false)) {
                return false;
            }

            $de = Carbon::parse($item['inicio'])->startOfDay();
            $ate = Carbon::parse($item['fim'])->endOfDay();

            return $de->lte($fim) && $ate->gte($inicio);
        })->values();
    }
}
