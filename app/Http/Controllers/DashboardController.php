<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    private const JAWA_SPREADSHEET =
        '1vJDRh0PXDARQaQyZwSNtBgqmz3jDdV_Iqx5ZKPkRnO8';

    private const REGION_SPREADSHEET =
        '1v2dWRjif4JfkK4QiZrF6-6IhPUACu-VTvQDlg87kf5Y';

    private const JAWA_GID = '0';
    private const KALIMANTAN_GID = '1716259668';
    private const SUMATERA_GID = '491816101';

    public function index(
        GoogleSheetsService $googleSheets
    ): View {
        $logamMuliaSpreadsheet = trim((string) config(
            'services.google_sheets.spreadsheet_id',
            env('GOOGLE_SHEETS_SPREADSHEET_ID', '')
        ));

        $logamMuliaSheet = trim((string) config(
            'services.google_sheets.sheet',
            env(
                'GOOGLE_SHEETS_SHEET',
                'Daftar Harga Logam Mulia Raja Emas Indonesia'
            )
        ));

        $sheets = [
            'jawa' => [
                'title' => 'Jawa / Nasional',
                'sheet_name' => 'Jawa / Nasional',
                'prices' => [],
                'error' => null,
            ],

            'kalimantan' => [
                'title' => 'Kalimantan / Sulawesi',
                'sheet_name' => 'Kalimantan / Sulawesi',
                'prices' => [],
                'error' => null,
            ],

            'sumatera' => [
                'title' => 'Sumatera / Bali / Lombok',
                'sheet_name' => 'Sumatera / Bali / Lombok',
                'prices' => [],
                'error' => null,
            ],

            'logam_mulia' => [
                'title' => 'Logam Mulia',
                'sheet_name' => $logamMuliaSheet,
                'prices' => [],
                'error' => null,
            ],
        ];

        $sources = [
            'jawa' => [
                self::JAWA_SPREADSHEET,
                self::JAWA_GID,
            ],

            'kalimantan' => [
                self::REGION_SPREADSHEET,
                self::KALIMANTAN_GID,
            ],

            'sumatera' => [
                self::REGION_SPREADSHEET,
                self::SUMATERA_GID,
            ],

            'logam_mulia' => [
                $logamMuliaSpreadsheet,
                $logamMuliaSheet,
            ],
        ];

        foreach ($sources as $key => [$spreadsheetId, $sheet]) {
            try {
                $sheets[$key]['prices'] =
                    $googleSheets->getPricesBySpreadsheet(
                        $spreadsheetId,
                        $sheet
                    );
            } catch (Throwable $e) {
                $sheets[$key]['error'] = $e->getMessage();
            }
        }

        $totalRows = collect($sheets)->sum(
            fn (array $sheet) => count($sheet['prices'])
        );

        return view('dashboard', [
            'sheets' => $sheets,
            'prices' => $sheets['jawa']['prices'],
            'error' => $sheets['jawa']['error'],
            'totalRows' => $totalRows,
        ]);
    }
}

