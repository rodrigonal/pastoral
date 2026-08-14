<?php

namespace App\Services;

use App\Models\Pastoral;
use App\Models\PastoralImagem;
use App\Support\FrasesPastorais;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PastoralArteGenerator
{
    private const LARGURA = 1080;

    private const ALTURA = 1920;

    /**
     * @param  Collection<int, PastoralImagem>  $fotos
     */
    public function gerar(Pastoral $pastoral, Collection $fotos, string $agradecimento, ?array $frase): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('A extensão GD do PHP é necessária para gerar a arte.');
        }

        $im = imagecreatetruecolor(self::LARGURA, self::ALTURA);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        $fundo = imagecolorallocate($im, 74, 46, 34);
        $creme = imagecolorallocate($im, 247, 240, 230);
        $ouro = imagecolorallocate($im, 212, 184, 150);
        $fotoFundo = imagecolorallocate($im, 58, 36, 26);

        imagefilledrectangle($im, 0, 0, self::LARGURA, self::ALTURA, $fundo);

        $sans = $this->fonte('DejaVuSans.ttf');
        $sansBold = $this->fonte('DejaVuSans-Bold.ttf');
        $serif = $this->fonte('DejaVuSerif.ttf');
        $serifBold = $this->fonte('DejaVuSerif-Bold.ttf');

        $y = 70;
        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            $y = $this->desenharLogo($im, $logo, $y, 140) + 28;
        }

        $this->textoCentro($im, 'FRATERNIDADE O CAMINHO  ·  PJC', $sans, 18, $ouro, $y);
        $y += 52;
        $this->textoCentro($im, 'Pastoral de Rua', $serifBold, 48, $creme, $y);
        $y += 56;

        $mes = $pastoral->data->locale('pt_BR')->translatedFormat('F \d\e Y');
        $data = $pastoral->data->format('d/m/Y');
        $this->textoCentro($im, mb_convert_case($mes, MB_CASE_TITLE, 'UTF-8').'  ·  '.$data, $sans, 22, $ouro, $y);

        $y += 48;
        $y = $this->desenharGrade($im, $fotos->take(4)->values(), $y, $fotoFundo);

        $y += 56;
        if ($frase) {
            $linhas = $this->quebrar('“'.$frase['texto'].'”', $serif, 28, self::LARGURA - 140);
            foreach ($linhas as $linha) {
                $this->textoCentro($im, $linha, $serif, 28, $creme, $y);
                $y += 42;
            }
            $y += 18;
            $this->textoCentro($im, mb_strtoupper($frase['autor']), $sans, 16, $ouro, $y);
            $y += 56;
        }

        $linhasAgradecimento = $this->quebrar($agradecimento !== '' ? $agradecimento : FrasesPastorais::agradecimentoPadrao(), $sans, 22, self::LARGURA - 140);
        foreach ($linhasAgradecimento as $linha) {
            $this->textoCentro($im, $linha, $sans, 22, $creme, $y);
            $y += 34;
        }

        $this->textoCentro($im, 'OBRIGADO POR CAMINHAR CONOSCO', $sansBold, 16, $ouro, self::ALTURA - 70);

        ob_start();
        imagepng($im, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png;
    }

    /**
     * @param  Collection<int, PastoralImagem>  $fotos
     */
    private function desenharGrade(\GdImage $im, Collection $fotos, int $yInicio, int $fotoFundo): int
    {
        $margem = 56;
        $gap = 16;
        $area = self::LARGURA - ($margem * 2);
        $lado = (int) floor(($area - $gap) / 2);

        for ($i = 0; $i < 4; $i++) {
            $col = $i % 2;
            $row = intdiv($i, 2);
            $x = $margem + ($col * ($lado + $gap));
            $y = $yInicio + ($row * ($lado + $gap));

            imagefilledrectangle($im, $x, $y, $x + $lado, $y + $lado, $fotoFundo);

            $foto = $fotos->get($i);
            if (! $foto instanceof PastoralImagem) {
                continue;
            }

            $path = Storage::disk('public')->path($foto->path);
            $src = $this->carregar($path);
            if ($src === null) {
                continue;
            }

            $quadrado = $this->cropQuadrado($src);
            imagedestroy($src);
            imagecopyresampled($im, $quadrado, $x, $y, 0, 0, $lado, $lado, imagesx($quadrado), imagesy($quadrado));
            imagedestroy($quadrado);
        }

        return $yInicio + ($lado * 2) + $gap;
    }

    private function cropQuadrado(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $lado = min($w, $h);
        $sx = (int) (($w - $lado) / 2);
        $sy = (int) (($h - $lado) / 2);

        $dst = imagecreatetruecolor($lado, $lado);
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $lado, $lado, $lado, $lado);

        return $dst;
    }

    private function carregar(string $path): ?\GdImage
    {
        if (! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        $previous = set_error_handler(static fn () => true);
        try {
            $img = match ($info[2]) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($path),
                IMAGETYPE_PNG => imagecreatefrompng($path),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
                default => false,
            };
        } finally {
            restore_error_handler();
            if ($previous !== null) {
                set_error_handler($previous);
            }
        }

        return $img instanceof \GdImage ? $img : null;
    }

    private function desenharLogo(\GdImage $im, string $path, int $y, int $alturaMax): int
    {
        $logo = $this->carregar($path);
        if ($logo === null) {
            return $y;
        }

        $lw = imagesx($logo);
        $lh = imagesy($logo);
        $altura = min($alturaMax, $lh);
        $largura = (int) round($lw * ($altura / $lh));
        $x = (int) ((self::LARGURA - $largura) / 2);

        imagecopyresampled($im, $logo, $x, $y, 0, 0, $largura, $altura, $lw, $lh);
        imagedestroy($logo);

        return $y + $altura;
    }

    /**
     * @return list<string>
     */
    private function quebrar(string $texto, string $fonte, int $tamanho, int $larguraMax): array
    {
        $palavras = preg_split('/\s+/u', trim($texto)) ?: [];
        $linhas = [];
        $atual = '';

        foreach ($palavras as $palavra) {
            $teste = $atual === '' ? $palavra : $atual.' '.$palavra;
            if ($this->largura($teste, $fonte, $tamanho) > $larguraMax && $atual !== '') {
                $linhas[] = $atual;
                $atual = $palavra;
            } else {
                $atual = $teste;
            }
        }

        if ($atual !== '') {
            $linhas[] = $atual;
        }

        return $linhas;
    }

    private function textoCentro(\GdImage $im, string $texto, string $fonte, int $tamanho, int $cor, int $y): void
    {
        $largura = $this->largura($texto, $fonte, $tamanho);
        $x = (int) ((self::LARGURA - $largura) / 2);
        imagettftext($im, $tamanho, 0, $x, $y, $cor, $fonte, $texto);
    }

    private function largura(string $texto, string $fonte, int $tamanho): int
    {
        $box = imagettfbbox($tamanho, 0, $fonte, $texto);

        return abs($box[2] - $box[0]);
    }

    private function fonte(string $arquivo): string
    {
        $path = resource_path('fonts/'.$arquivo);
        if (! is_file($path)) {
            throw new RuntimeException("Fonte não encontrada: {$arquivo}");
        }

        return $path;
    }
}
