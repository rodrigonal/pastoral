<?php

namespace App\Support;

class FrasesPastorais
{
    /**
     * @return list<array{id: int, texto: string, autor: string, tema: string}>
     */
    public static function todas(): array
    {
        return config('frases-pastorais', []);
    }

    /**
     * @return array{id: int, texto: string, autor: string, tema: string}|null
     */
    public static function find(int $id): ?array
    {
        foreach (self::todas() as $frase) {
            if ((int) $frase['id'] === $id) {
                return $frase;
            }
        }

        return null;
    }

    /**
     * @return array{id: int, texto: string, autor: string, tema: string}
     */
    public static function aleatoria(): array
    {
        $frases = self::todas();

        return $frases[array_rand($frases)];
    }

    public static function agradecimentoPadrao(): string
    {
        return 'Obrigado, benfeitores e irmãos da comunidade. Cada gesto de partilha chega como abraço de Cristo a quem vive nas ruas.';
    }
}
