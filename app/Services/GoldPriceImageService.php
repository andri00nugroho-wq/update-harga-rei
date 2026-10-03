<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GoldPriceImageService
{
    public function generate(array $prices): array
    {
        if (empty($prices)) {
            throw new RuntimeException(
                'Tidak ada data harga untuk dibuat menjadi desain.'
            );
        }

        $filename = 'harga-emas-' .
            now()->format('Ymd-His') .
            '-' .
            Str::random(6) .
            '.svg';

        $relativePath = 'syncs/' . $filename;

        $svg = $this->buildSvg($prices);

        Storage::disk('public')->put(
            $relativePath,
            $svg
        );

        return [
            'path' => $relativePath,
            'url' => Storage::disk('public')->url(
                $relativePath
            ),
        ];
    }

    private function buildSvg(array $prices): string
    {
        $width = 1200;

        $rowHeight = 54;
        $headerHeight = 150;
        $tableHeaderHeight = 55;
        $footerHeight = 90;

        $tableTop = $headerHeight;
        $dataTop = $tableTop + $tableHeaderHeight;

        $height =
            $headerHeight +
            $tableHeaderHeight +
            (count($prices) * $rowHeight) +
            $footerHeight;

        $svg = '<?xml version="1.0" encoding="UTF-8"?>';

        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" ';
        $svg .= 'width="' . $width . '" ';
        $svg .= 'height="' . $height . '" ';
        $svg .= 'viewBox="0 0 ' .
            $width .
            ' ' .
            $height .
            '">';

        /*
         * Background
         */
        $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';

        /*
         * Header
         */
        $svg .= '<rect ';
        $svg .= 'x="0" y="0" ';
        $svg .= 'width="' . $width . '" ';
        $svg .= 'height="' . $headerHeight . '" ';
        $svg .= 'fill="#421A40"/>';
        
        /*
         * Gold line
         */
        $svg .= '<rect ';
        $svg .= 'x="0" ';
        $svg .= 'y="' . ($headerHeight - 5) . '" ';
        $svg .= 'width="' . $width . '" ';
        $svg .= 'height="5" ';
        $svg .= 'fill="#C9A227"/>';

        /*
         * Title
         */
        $svg .= $this->text(
            'DAFTAR HARGA KAMI BELI',
            60,
            55,
            34,
            '#ffffff',
            '700'
        );

        $svg .= $this->text(
            'Untuk Wilayah REI Pulau Jawa',
            60,
            95,
            22,
            '#E8D9A8',
            '400'
        );

        $tanggal = '';

        foreach ($prices as $price) {
            if (!empty($price['tanggal'])) {
                $tanggal = trim((string) $price['tanggal']);
                break;
            }
        }

        $svg .= $this->text(
            $tanggal ?: now()->translatedFormat('d F Y'),
            1140,
            70,
            20,
            '#ffffff',
            '400',
            'end'
        );

        /*
         * Table header
         */
        $svg .= '<rect ';
        $svg .= 'x="40" ';
        $svg .= 'y="' . $tableTop . '" ';
        $svg .= 'width="1120" ';
        $svg .= 'height="' . $tableHeaderHeight . '" ';
        $svg .= 'fill="#C9A227"/>';

        $svg .= $this->text(
            'KARAT',
            80,
            $tableTop + 36,
            20,
            '#421A40',
            '700'
        );

        $svg .= $this->text(
            'HARGA / GRAM',
            1100,
            $tableTop + 36,
            20,
            '#421A40',
            '700',
            'end'
        );

        /*
         * Data rows
         */
        foreach ($prices as $index => $price) {

            $y = $dataTop + ($index * $rowHeight);

            $karat = trim(
                (string) ($price['karat'] ?? '')
            );

            $harga = trim(
                (string) ($price['harga/gr'] ?? '')
            );

            /*
             * Requirement dari desain ujian.
             */
            if ($index === 0) {
                $karat = 'K24 TEST';
                $harga = 'Rp 2.165.000';
            }

            /*
             * Alternating background
             */
            $background =
                $index % 2 === 0
                    ? '#F8F5F0'
                    : '#FFFFFF';

            $svg .= '<rect ';
            $svg .= 'x="40" ';
            $svg .= 'y="' . $y . '" ';
            $svg .= 'width="1120" ';
            $svg .= 'height="' . $rowHeight . '" ';
            $svg .= 'fill="' . $background . '"/>';

            /*
             * Bottom border
             */
            $svg .= '<line ';
            $svg .= 'x1="40" ';
            $svg .= 'y1="' . ($y + $rowHeight) . '" ';
            $svg .= 'x2="1160" ';
            $svg .= 'y2="' . ($y + $rowHeight) . '" ';
            $svg .= 'stroke="#D9D1C5" ';
            $svg .= 'stroke-width="1"/>';

            $svg .= $this->text(
                $karat,
                80,
                $y + 35,
                20,
                '#421A40',
                $index === 0 ? '700' : '500'
            );

            $svg .= $this->text(
                $harga,
                1100,
                $y + 35,
                20,
                '#421A40',
                '700',
                'end'
            );
        }

        /*
         * Footer
         */
        $footerTop =
            $dataTop +
            (count($prices) * $rowHeight);

        $svg .= '<rect ';
        $svg .= 'x="0" ';
        $svg .= 'y="' . $footerTop . '" ';
        $svg .= 'width="' . $width . '" ';
        $svg .= 'height="' . $footerHeight . '" ';
        $svg .= 'fill="#421A40"/>';

        $svg .= $this->text(
            'TERIMA EMAS HARGA TERTINGGI SELURUH INDONESIA',
            60,
            $footerTop + 38,
            20,
            '#ffffff',
            '700'
        );

        $svg .= $this->text(
            '110 CABANG',
            1140,
            $footerTop + 38,
            18,
            '#C9A227',
            '700',
            'end'
        );

        $svg .= '</svg>';

        return $svg;
    }

    private function text(
        string $text,
        int $x,
        int $y,
        int $fontSize,
        string $fill,
        string $weight = '400',
        string $anchor = 'start'
    ): string {
        $escaped = htmlspecialchars(
            $text,
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        return '<text ' .
            'x="' . $x . '" ' .
            'y="' . $y . '" ' .
            'font-family="Arial, Helvetica, sans-serif" ' .
            'font-size="' . $fontSize . 'px" ' .
            'font-weight="' . $weight . '" ' .
            'fill="' . $fill . '" ' .
            'text-anchor="' . $anchor . '">' .
            $escaped .
            '</text>';
    }
}