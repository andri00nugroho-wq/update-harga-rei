<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GoogleSheetsService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SHEETS_API_URL =
        'https://sheets.googleapis.com/v4/spreadsheets';

    private const SCOPE =
        'https://www.googleapis.com/auth/spreadsheets.readonly';

    private ?string $accessToken = null;

    private int $tokenExpiresAt = 0;

    private array $credentials = [];

    /*
    |--------------------------------------------------------------------------
    | Kredensial Service Account
    |--------------------------------------------------------------------------
    */

    private function credentials(): array
    {
        if ($this->credentials !== []) {
            return $this->credentials;
        }

        $path = config(
            'services.google_sheets.credentials',
            env('GOOGLE_SERVICE_ACCOUNT_JSON', '')
        );

        if (!is_string($path) || trim($path) === '') {
            throw new RuntimeException(
                'Lokasi file Google Service Account belum dikonfigurasi. ' .
                'Periksa GOOGLE_SERVICE_ACCOUNT_JSON di file .env.'
            );
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                'File Google Service Account tidak ditemukan atau tidak dapat dibaca: ' .
                $path
            );
        }

        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException(
                'Gagal membaca file Google Service Account.'
            );
        }

        try {
            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Format JSON Google Service Account tidak valid.',
                0,
                $e
            );
        }

        if (
            !is_array($data) ||
            ($data['type'] ?? '') !== 'service_account' ||
            empty($data['client_email']) ||
            empty($data['private_key'])
        ) {
            throw new RuntimeException(
                'File kredensial harus berupa JSON Service Account yang memiliki ' .
                'client_email dan private_key.'
            );
        }

        $this->credentials = $data;

        return $this->credentials;
    }

    /*
    |--------------------------------------------------------------------------
    | Membuat Access Token OAuth
    |--------------------------------------------------------------------------
    */

    private function getAccessToken(): string
    {
        if (
            $this->accessToken !== null &&
            $this->tokenExpiresAt > time() + 60
        ) {
            return $this->accessToken;
        }

        $credentials = $this->credentials();

        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $base64UrlEncode = static function (string $value): string {
            return rtrim(
                strtr(base64_encode($value), '+/', '-_'),
                '='
            );
        };

        $unsignedToken =
            $base64UrlEncode(
                json_encode($header, JSON_THROW_ON_ERROR)
            ) .
            '.' .
            $base64UrlEncode(
                json_encode($claims, JSON_THROW_ON_ERROR)
            );

        $privateKey = openssl_pkey_get_private(
            $credentials['private_key']
        );

        if ($privateKey === false) {
            throw new RuntimeException(
                'Private key Service Account tidak valid. Periksa file JSON.'
            );
        }

        $signature = '';

        try {
            $signed = openssl_sign(
                $unsignedToken,
                $signature,
                $privateKey,
                OPENSSL_ALGO_SHA256
            );
        } finally {
            openssl_free_key($privateKey);
        }

        if (!$signed) {
            throw new RuntimeException(
                'Gagal menandatangani JWT Google Service Account.'
            );
        }

        $jwt = $unsignedToken . '.' . $base64UrlEncode($signature);

        $response = Http::asForm()
            ->timeout(30)
            ->post(self::TOKEN_URL, [
                'grant_type' =>
                    'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (!$response->successful()) {
            Log::error('Google OAuth token gagal', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException(
                'Gagal mendapatkan token Google OAuth. HTTP ' .
                $response->status() . ': ' .
                $this->responseMessage($response)
            );
        }

        $result = $response->json();

        if (
            !is_array($result) ||
            empty($result['access_token'])
        ) {
            throw new RuntimeException(
                'Respons Google OAuth tidak berisi access_token.'
            );
        }

        $this->accessToken = $result['access_token'];

        $this->tokenExpiresAt = $now +
            (int) ($result['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Google Sheets
    |--------------------------------------------------------------------------
    */

    private function request(
        string $url,
        array $query = []
    ): array {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->timeout(60)
            ->retry(2, 500, throw: false)
            ->get($url, $query);

        if (!$response->successful()) {
            $status = $response->status();
            $message = $this->responseMessage($response);

            Log::error('Google Sheets API request gagal', [
                'status' => $status,
                'url' => $url,
                'response' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException(
                'Gagal mengambil data Google Sheets: HTTP ' .
                $status . ': ' . $message
            );
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new RuntimeException(
                'Respons Google Sheets bukan JSON yang valid.'
            );
        }

        return $data;
    }

    private function responseMessage($response): string
    {
        $data = $response->json();

        if (is_array($data)) {
            $message =
                data_get($data, 'error.message') ??
                data_get($data, 'error_description');

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        $body = trim((string) $response->body());

        return $body !== '' ? $body : 'Tidak ada detail error.';
    }

    /*
    |--------------------------------------------------------------------------
    | Resolusi GID Menjadi Nama Tab
    |--------------------------------------------------------------------------
    */

    private function resolveSheetName(
        string $spreadsheetId,
        string|int $gid
    ): string {
        $metadata = $this->request(
            self::SHEETS_API_URL . '/' .
            rawurlencode($spreadsheetId),
            [
                'fields' => 'sheets(properties(sheetId,title))',
            ]
        );

        $sheets = $metadata['sheets'] ?? [];

        foreach ($sheets as $sheet) {
            $properties = $sheet['properties'] ?? [];

            if (
                isset($properties['sheetId']) &&
                (string) $properties['sheetId'] === (string) $gid
            ) {
                $title = $properties['title'] ?? '';

                if ($title !== '') {
                    return $title;
                }
            }
        }

        throw new RuntimeException(
            'Tab Google Sheets dengan GID ' . $gid .
            ' tidak ditemukan pada spreadsheet ' . $spreadsheetId . '.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Membaca Values
    |--------------------------------------------------------------------------
    */

    private function getValues(
        string $spreadsheetId,
        string $range,
        string $valueRenderOption = 'FORMATTED_VALUE'
    ): array {
        $data = $this->request(
            self::SHEETS_API_URL . '/' .
            rawurlencode($spreadsheetId) .
            '/values/' . rawurlencode($range),
            [
                'valueRenderOption' => $valueRenderOption,
                'dateTimeRenderOption' => 'SERIAL_NUMBER',
            ]
        );

        return $data['values'] ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | Metadata Baris dan Kolom Tersembunyi
    |--------------------------------------------------------------------------
    */

    private function getHiddenMeta(
        string $spreadsheetId,
        string|int $gid
    ): array {
        $data = $this->request(
            self::SHEETS_API_URL . '/' .
            rawurlencode($spreadsheetId),
            [
                'includeGridData' => 'true',
                'ranges' => "'".str_replace("'", "''",
                    $this->resolveSheetName($spreadsheetId, $gid)
                )."'!A1:Z1000",
                'fields' =>
                    'sheets(properties(sheetId,title),' .
                    'data(startRow,startColumn,' .
                    'rowMetadata(hiddenByUser,hiddenByFilter),' .
                    'columnMetadata(hiddenByUser)))',
            ]
        );

        $hiddenRows = [];
        $hiddenColumns = [];

        foreach (($data['sheets'] ?? []) as $sheet) {
            $properties = $sheet['properties'] ?? [];

            if (
                !isset($properties['sheetId']) ||
                (string) $properties['sheetId'] !== (string) $gid
            ) {
                continue;
            }

            foreach (($sheet['data'] ?? []) as $grid) {
                $startRow = (int) ($grid['startRow'] ?? 0);
                $startColumn = (int) ($grid['startColumn'] ?? 0);

                foreach (($grid['rowMetadata'] ?? []) as $i => $row) {
                    if (
                        !empty($row['hiddenByUser']) ||
                        !empty($row['hiddenByFilter'])
                    ) {
                        $hiddenRows[] = $startRow + $i;
                    }
                }

                foreach (
                    ($grid['columnMetadata'] ?? []) as $i => $column
                ) {
                    if (!empty($column['hiddenByUser'])) {
                        $hiddenColumns[] = $startColumn + $i;
                    }
                }
            }
        }

        return [
            'rows' => array_values(array_unique($hiddenRows)),
            'columns' => array_values(array_unique($hiddenColumns)),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Normalisasi Header
    |--------------------------------------------------------------------------
    */

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = trim($header);

        $header = mb_strtolower($header, 'UTF-8');

        $header = str_replace(
            [' ', '-', '/', '\\', '.', '(', ')', ':'],
            '_',
            $header
        );

        $header = preg_replace('/_+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    /*
    |--------------------------------------------------------------------------
    | Normalisasi Tanggal
    |--------------------------------------------------------------------------
    */

    private function formatDate(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return $value;
        }

        // Format tanggal Indonesia: dd/mm/yyyy atau dd-mm-yyyy.
        if (
            preg_match(
                '/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/',
                $value,
                $matches
            )
        ) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return sprintf(
                    '%02d/%02d/%04d',
                    $day,
                    $month,
                    $year
                );
            }
        }

        // Format ISO: yyyy-mm-dd.
        if (
            preg_match(
                '/^(\d{4})-(\d{1,2})-(\d{1,2})$/',
                $value,
                $matches
            )
        ) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return sprintf(
                    '%02d/%02d/%04d',
                    $day,
                    $month,
                    $year
                );
            }
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Membaca Data Berdasarkan Spreadsheet ID dan GID
    |--------------------------------------------------------------------------
    |
    | Metode ini dapat dipanggil controller untuk sumber Jawa,
    | Sumatera, Kalimantan, maupun spreadsheet lain.
    |
    */

    public function getPricesBySpreadsheet(
        string $spreadsheetId,
        string|int $gid
    ): array {
        $spreadsheetId = trim($spreadsheetId);
        $gid = trim((string) $gid);

        if ($spreadsheetId === '') {
            throw new RuntimeException(
                'Spreadsheet ID tidak boleh kosong.'
            );
        }

        if ($gid === '') {
            throw new RuntimeException(
                'GID tab Google Sheets tidak boleh kosong.'
            );
        }

        try {
            $sheetName = $this->resolveSheetName(
                $spreadsheetId,
                $gid
            );

            $range = "'" .
                str_replace("'", "''", $sheetName) .
                "'!A1:Z1000";

            $formattedRows = $this->getValues(
                $spreadsheetId,
                $range,
                'FORMATTED_VALUE'
            );

            $rawRows = $this->getValues(
                $spreadsheetId,
                $range,
                'UNFORMATTED_VALUE'
            );

            $hiddenMeta = $this->getHiddenMeta(
                $spreadsheetId,
                $gid
            );

            if (empty($formattedRows)) {
                return [];
            }

            $headers = array_map(
                fn ($header) => $this->normalizeHeader(
                    (string) $header
                ),
                $formattedRows[0]
            );

            $rawHeaders = array_map(
                fn ($header) => $this->normalizeHeader(
                    (string) $header
                ),
                $rawRows[0] ?? $formattedRows[0]
            );

            $headers = array_map(
                static fn ($header, $index) =>
                    $header !== '' ? $header : 'kolom_' . ($index + 1),
                $headers,
                array_keys($headers)
            );

            $result = [];

            for ($i = 1, $count = count($formattedRows); $i < $count; $i++) {
                // Nomor baris berbasis indeks spreadsheet, mulai dari 1.
                $sheetRowNumber = $i + 1;

                // Metadata hidden row menggunakan indeks berbasis nol.
                if (in_array($i, $hiddenMeta['rows'], true)) {
                    continue;
                }

                $formattedRow = $formattedRows[$i] ?? [];
                $rawRow = $rawRows[$i] ?? [];

                $hasValue = false;

                foreach ($formattedRow as $cell) {
                    if (trim((string) $cell) !== '') {
                        $hasValue = true;
                        break;
                    }
                }

                if (!$hasValue) {
                    continue;
                }

                $record = [];

                foreach ($headers as $columnIndex => $header) {
                    if (in_array(
                        $columnIndex,
                        $hiddenMeta['columns'],
                        true
                    )) {
                        continue;
                    }

                    $formattedValue = $formattedRow[$columnIndex] ?? null;
                    $rawValue = $rawRow[$columnIndex] ?? $formattedValue;

                    $record[$header] = $this->formatDate($formattedValue);

                    // Pertahankan nilai numerik mentah untuk pemrosesan harga.
                    $record[$header . '_raw'] = $rawValue;
                }

                $record['_row'] = $sheetRowNumber;

                $result[] = $record;
            }

            return $result;
        } catch (Throwable $e) {
            Log::error('Google Sheets: gagal membaca data harga', [
                'spreadsheet_id' => $spreadsheetId,
                'gid' => $gid,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Membaca Spreadsheet Default
    |--------------------------------------------------------------------------
    |
    | Dipertahankan untuk kompatibilitas pemanggil lama.
    | Untuk sinkronisasi regional, gunakan getPricesBySpreadsheet()
    | dengan ID dan GID yang sudah dipilih oleh controller.
    |
    */

    public function getPrices(): array
    {
        $spreadsheetId = (string) config(
            'services.google_sheets.spreadsheet_id',
            ''
        );

        $gid = (string) config(
            'services.google_sheets.sheet_gid',
            '0'
        );

        if (trim($spreadsheetId) === '') {
            throw new RuntimeException(
                'Spreadsheet ID default belum dikonfigurasi.'
            );
        }

        return $this->getPricesBySpreadsheet(
            $spreadsheetId,
            $gid
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Debug Sheet
    |--------------------------------------------------------------------------
    */

    public function debugSheet(
        string $spreadsheetId,
        string|int $gid
    ): array {
        $sheetName = $this->resolveSheetName(
            $spreadsheetId,
            $gid
        );

        $range = "'" .
            str_replace("'", "''", $sheetName) .
            "'!A1:Z1000";

        $formattedRows = $this->getValues(
            $spreadsheetId,
            $range,
            'FORMATTED_VALUE'
        );

        $rawRows = $this->getValues(
            $spreadsheetId,
            $range,
            'UNFORMATTED_VALUE'
        );

        return [
            'spreadsheet_id' => $spreadsheetId,
            'gid' => (string) $gid,
            'sheet_name' => $sheetName,
            'formatted_rows_count' => count($formattedRows),
            'raw_rows_count' => count($rawRows),
            'headers' => $formattedRows[0] ?? [],
            'sample_rows' => array_slice($formattedRows, 0, 5),
            'sample_raw_rows' => array_slice($rawRows, 0, 5),
        ];
    }
}
