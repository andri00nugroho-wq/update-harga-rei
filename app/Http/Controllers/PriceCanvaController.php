<?php

namespace App\Http\Controllers;

use App\Services\CanvaAutofillService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PriceCanvaController extends Controller
{
    /**
     * Dry run: tampilkan persis data yang AKAN dikirim ke Canva (tanpa memanggil Canva).
     * Gunakan ini untuk membandingkan dengan Google Sheets.
     */
    public function preview(string $region, GoogleSheetsService $sheets): JsonResponse
    {
        try {
            $prepared = $this->prepare($region, $sheets);

            return response()->json([
                'region' => $region,
                'gid' => $prepared['gid'],
                'fields' => $prepared['fields'],
                'rows_used' => $prepared['rows'],
            ]);
        } catch (Throwable $e) {
            return $this->fail($e, $region);
        }
    }

    /**
     * Baca sheet -> pilih harga terbaru per karat -> autofill Canva.
     */
    public function autofill(
        string $region,
        GoogleSheetsService $sheets,
        CanvaAutofillService $canva
    ): JsonResponse {
        try {
            $prepared = $this->prepare($region, $sheets);
            $config = $prepared['config'];

            Log::info('CANVA AUTOFILL PAYLOAD', [
                'region' => $region,
                'gid' => $prepared['gid'],
                'fields' => $prepared['fields'],
            ]);

            $templateId = trim((string) ($config['brand_template_id'] ?? ''));

            if ($templateId === '') {
                throw new RuntimeException(
                    "Brand template untuk wilayah '{$region}' belum diatur di .env."
                );
            }

            $title = 'Harga Emas ' . ($config['label'] ?? $region) .
                ' - ' . ($prepared['fields'][config('canva.field_names.date', 'tanggal')] ?? '');

            $result = $canva->autofill($templateId, $prepared['fields'], $title);

            return response()->json([
                'region' => $region,
                'status' => $result['status'],
                'job_id' => $result['job_id'],
                'design' => $result['design'],
                'fields_sent' => $result['fields_sent'],
                'fields_not_in_template' => $result['fields_not_in_template'],
                'template_fields_not_filled' => $result['template_fields_not_filled'],
            ]);
        } catch (Throwable $e) {
            return $this->fail($e, $region);
        }
    }

    /**
     * Cek status job autofill yang belum selesai.
     */
    public function job(string $jobId, CanvaAutofillService $canva): JsonResponse
    {
        try {
            $job = $canva->getJob($jobId);

            return response()->json([
                'job_id' => $jobId,
                'status' => $job['status'] ?? 'unknown',
                'design' => $job['result']['design'] ?? null,
                'error' => $job['error'] ?? null,
            ]);
        } catch (Throwable $e) {
            return $this->fail($e, 'job');
        }
    }

    /* ---------------------------------------------------------------
     |  Internal
     * ------------------------------------------------------------- */

    private function prepare(string $region, GoogleSheetsService $sheets): array
    {
        $config = config("canva.regions.{$region}");

        if (!is_array($config)) {
            throw new RuntimeException(
                "Wilayah '{$region}' tidak dikenal. Pilihan: " .
                implode(', ', array_keys(config('canva.regions', [])))
            );
        }

        $spreadsheetId = trim((string) config('services.google_sheets.spreadsheet_id', ''));
        $gid = trim((string) ($config['gid'] ?? ''));

        if ($spreadsheetId === '' || $gid === '') {
            throw new RuntimeException(
                'Spreadsheet ID atau GID wilayah belum diatur.'
            );
        }

        $rows = $sheets->getPricesBySpreadsheet($spreadsheetId, $gid);

        if (empty($rows)) {
            throw new RuntimeException("Tab wilayah '{$region}' (GID {$gid}) kosong.");
        }

        $cols = config('canva.columns');

        foreach ($cols as $key => $column) {
            if (!array_key_exists($column, $rows[0])) {
                throw new RuntimeException(
                    "Kolom '{$column}' ({$key}) tidak ditemukan di tab '{$region}'. " .
                    'Kolom yang ada: ' . implode(', ', array_keys($rows[0]))
                );
            }
        }

        $latest = $this->latestPerKarat($rows, $cols['karat'], $cols['price']);

        if (empty($latest)) {
            throw new RuntimeException('Tidak ada baris dengan karat dan harga terisi.');
        }

        $latest = array_slice($latest, 0, (int) config('canva.max_rows', 10));

        return [
            'config' => $config,
            'gid' => $gid,
            'rows' => $latest,
            'fields' => $this->buildFields($latest, $cols, $config),
        ];
    }

    /**
     * Ambil satu baris per karat: yang tanggalnya paling baru
     * (bila tanggal sama, baris paling bawah di sheet).
     * Ini mencegah baris riwayat lama ikut terkirim.
     */
    private function latestPerKarat(array $rows, string $karatCol, string $priceCol): array
    {
        $best = [];

        foreach ($rows as $row) {
            $karat = trim((string) ($row[$karatCol] ?? ''));
            $price = trim((string) ($row[$priceCol] ?? ''));

            if ($karat === '' || $price === '') {
                continue;
            }

            $key = mb_strtolower($karat, 'UTF-8');
            $rank = ($row['_tanggal_iso'] ?? '0000-00-00') . '|' .
                str_pad((string) ($row['_row'] ?? 0), 8, '0', STR_PAD_LEFT);

            if (!isset($best[$key]) || strcmp($rank, $best[$key]['_rank']) > 0) {
                $row['_rank'] = $rank;
                $best[$key] = $row;
            }
        }

        $list = array_values($best);

        // Karat terbesar di atas (24, 23, 22, ...).
        usort($list, function ($a, $b) use ($karatCol) {
            $na = (int) preg_replace('/\D/', '', (string) $a[$karatCol]);
            $nb = (int) preg_replace('/\D/', '', (string) $b[$karatCol]);

            return $nb <=> $na;
        });

        return $list;
    }

    /**
     * Susun payload: tanggal, wilayah, karat_1, harga_1, karat_2, harga_2, ...
     * Nama field bisa diubah di config/canva.php (field_names).
     */
    private function buildFields(array $rows, array $cols, array $config): array
    {
        $names = config('canva.field_names');
        $fields = [];

        // Tanggal diambil dari baris dengan rank tertinggi (paling baru).
        $newest = collect($rows)->sortByDesc('_rank')->first();
        $fields[$names['date']] = (string) ($newest[$cols['date']] ?? '');
        $fields[$names['region']] = (string) ($config['label'] ?? '');

        foreach ($rows as $i => $row) {
            $n = $i + 1;
            $fields[sprintf($names['karat'], $n)] = (string) $row[$cols['karat']];
            $fields[sprintf($names['price'], $n)] = (string) $row[$cols['price']];
        }

        return $fields;
    }

    private function fail(Throwable $e, string $context): JsonResponse
    {
        Log::error('CANVA PRICE ERROR', [
            'context' => $context,
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}