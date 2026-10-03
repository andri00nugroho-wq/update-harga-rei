<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(GoogleSheetsService $googleSheets): View
    {
        $sheets = [
            'jawa' => [
                'title' => 'Jawa / Nasional',
                'sheet_name' => 'Harga Emas Raja Emas - Ujian Fullstack',
                'prices' => [],
                'error' => null,
            ],

            'kalimantan' => [
                'title' => 'Kalimantan / Sulawesi',
                'sheet_name' => 'Harga Emas Raja Emas Kalimantan,Sulawesi',
                'prices' => [],
                'error' => null,
            ],

            'sumatera' => [
                'title' => 'Sumatera / Bali / Lombok',
                'sheet_name' => 'Harga Emas Raja Emas Sumatera,Bali,Lombok',
                'prices' => [],
                'error' => null,
            ],

            'logam_mulia' => [
                'title' => 'Logam Mulia',
                'sheet_name' => 'Daftar Harga Logam Mulia Raja Emas Indonesia',
                'prices' => [],
                'error' => null,
            ],
        ];

        foreach ($sheets as $key => &$sheet) {
            try {
                $sheet['prices'] = $googleSheets->getPrices(
                    $sheet['sheet_name']
                );
            } catch (Throwable $e) {
                $sheet['error'] = $e->getMessage();
            }
        }

        unset($sheet);

        $totalRows = collect($sheets)
            ->sum(fn ($sheet) => count($sheet['prices']));

        return view('dashboard', [
            'sheets' => $sheets,

            'prices' => $sheets['jawa']['prices'],

            'error' => $sheets['jawa']['error'],

            'totalRows' => $totalRows,
        ]);
    }
}