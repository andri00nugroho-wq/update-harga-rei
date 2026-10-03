<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\View\View;
use Throwable;

class PriceController extends Controller
{
    private array $sheetConfig = [
        'jawa' => [
            'title' => 'Jawa / Nasional',
            'subtitle' => 'Harga emas wilayah Jawa dan Nasional',
            'sheet' => 'Harga Emas Raja Emas - Ujian Fullstack',
            'type' => 'gold',
        ],

        'kalimantan' => [
            'title' => 'Kalimantan / Sulawesi',
            'subtitle' => 'Harga emas wilayah Kalimantan dan Sulawesi',
            'sheet' => 'Harga Emas Raja Emas Kalimantan,Sulawesi',
            'type' => 'gold',
        ],

        'sumatera' => [
            'title' => 'Sumatera / Bali / Lombok',
            'subtitle' => 'Harga emas wilayah Sumatera, Bali dan Lombok',
            'sheet' => 'Harga Emas Raja Emas Sumatera,Bali,Lombok',
            'type' => 'gold',
        ],

        'logam-mulia' => [
            'title' => 'Logam Mulia',
            'subtitle' => 'Harga, buyback dan gramasi Logam Mulia',
            'sheet' => 'Daftar Harga Logam Mulia Raja Emas Indonesia',
            'type' => 'logam_mulia',
        ],
    ];

    public function jawa(GoogleSheetsService $googleSheets): View
    {
        return $this->show('jawa', $googleSheets);
    }

    public function kalimantan(GoogleSheetsService $googleSheets): View
    {
        return $this->show('kalimantan', $googleSheets);
    }

    public function sumatera(GoogleSheetsService $googleSheets): View
    {
        return $this->show('sumatera', $googleSheets);
    }

    public function logamMulia(GoogleSheetsService $googleSheets): View
    {
        return $this->show('logam-mulia', $googleSheets);
    }

    private function show(
        string $key,
        GoogleSheetsService $googleSheets
    ): View {
        $config = $this->sheetConfig[$key];

        $prices = [];
        $error = null;

        try {
            $prices = $googleSheets->getPrices($config['sheet']);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return view('prices.index', [
            'pageKey' => $key,
            'title' => $config['title'],
            'subtitle' => $config['subtitle'],
            'sheetName' => $config['sheet'],
            'type' => $config['type'],
            'prices' => $prices,
            'error' => $error,
        ]);
    }
}