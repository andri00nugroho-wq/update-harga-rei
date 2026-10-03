<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsService
{
    public function getPrices(?string $sheetName = null): array
    {
        $apiKey = config('services.google_sheets.api_key');
        $spreadsheetId = config('services.google_sheets.spreadsheet_id');
        $sheetName = $sheetName ?: config('services.google_sheets.sheet_name');

        if (!$apiKey) {
            throw new RuntimeException('GOOGLE_API_KEY belum diatur di .env');
        }

        if (!$spreadsheetId) {
            throw new RuntimeException('GOOGLE_SHEETS_ID belum diatur di .env');
        }

        if (!$sheetName) {
            throw new RuntimeException('Nama Google Sheet belum diatur.');
        }

        $range = $sheetName . '!A1:Z100';

        $url = 'https://sheets.googleapis.com/v4/spreadsheets/'
            . $spreadsheetId
            . '/values/'
            . rawurlencode($range);

        $response = Http::timeout(15)->get($url, [
            'key' => $apiKey,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Google Sheets API gagal: ' . $response->body()
            );
        }

        $values = $response->json('values', []);

        if (empty($values)) {
            return [];
        }

        $headers = array_map(
            fn ($header) => strtolower(trim((string) $header)),
            $values[0]
        );

        $result = [];

        foreach (array_slice($values, 1) as $row) {
            $row = array_pad($row, count($headers), '');

            $item = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $item[$header] = trim((string) ($row[$index] ?? ''));
            }

            $hasData = count(
                array_filter(
                    $item,
                    fn ($value) => $value !== ''
                )
            ) > 0;

            if (!$hasData) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }
}