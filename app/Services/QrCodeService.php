<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class QrCodeService
{
    public function generatePng(string $text, int $size = 400, int $margin = 4): string
    {
        $qrCode = Encoder::encode($text, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();

        $totalSize = $matrixSize + ($margin * 2);
        $scale = $size / $totalSize;
        $finalSize = (int) ceil($totalSize * $scale);

        $img = imagecreatetruecolor($finalSize, $finalSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $px = (int) floor(($x + $margin) * $scale);
                    $py = (int) floor(($y + $margin) * $scale);
                    $pw = (int) ceil($scale);
                    $ph = (int) ceil($scale);
                    imagefilledrectangle($img, $px, $py, $px + $pw - 1, $py + $ph - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }
}
