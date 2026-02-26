<?php

namespace Database\Seeders;

use App\Models\Segmento;
use Illuminate\Database\Seeder;

class SegmentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $segmentos = [
            ['nome' => 'Freis', 'ordem' => 1],
            ['nome' => 'Sede Sóbrios', 'ordem' => 2],
            ['nome' => 'JC (Juventude Caminho)', 'ordem' => 3],
            ['nome' => 'Comunicação', 'ordem' => 4],
            ['nome' => 'Segmento São José', 'ordem' => 5],
            ['nome' => 'Leigos', 'ordem' => 6],
            ['nome' => 'Intercessão', 'ordem' => 7],
            ['nome' => 'Cura', 'ordem' => 8],
            ['nome' => 'Juventude', 'ordem' => 9],
        ];

        foreach ($segmentos as $item) {
            Segmento::create($item);
        }
    }
}
