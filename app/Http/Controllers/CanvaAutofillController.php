<?php

namespace App\Http\Controllers;

use App\Services\CanvaService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use RuntimeException;

class CanvaAutofillController extends Controller
{
    /**
     * GET /canva/dataset
     *
     * Mengambil dataset Autofill dari Canva.
     */
    public function dataset(
        Request $request,
        CanvaService $canva
    ): JsonResponse {
        $token = $request->session()->get(
            'canva_token.access_token'
        );

        if (!$token) {
            return response()->json([
                'success' => false,
                'code' => 'CANVA_NOT_CONNECTED',
                'message' => 'Canva belum terhubung. Silakan hubungkan Canva terlebih dahulu.',
            ], 401);
        }

        $designId = config('services.canva.design_id');

        if (empty($designId)) {
            return response()->json([
                'success' => false,
                'code' => 'CANVA_DESIGN_ID_EMPTY',
                'message' => 'CANVA_DESIGN_ID belum diatur di file .env.',
            ], 422);
        }

        try {
            $response = $canva->getDesignDataset(
                $token,
                $designId
            );

            $dataset = $response['dataset'] ?? [];

            if (!is_array($dataset)) {
                $dataset = [];
            }

            $fields = [];

            foreach ($dataset as $name => $definition) {
                $fields[] = [
                    'name' => (string) $name,
                    'type' => is_array($definition)
                        ? ($definition['type'] ?? null)
                        : null,
                    'definition' => $definition,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Dataset Autofill berhasil diambil dari Canva API.',
                'design_id' => $designId,
                'total_fields' => count($fields),
                'fields' => $fields,
                'dataset' => $dataset,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'code' => 'CANVA_DATASET_ERROR',
                'message' => $e->getMessage(),
                'design_id' => $designId,
            ], 500);
        }
    }

    /**
     * GET/POST /canva/sync
     *
     * Google Sheets -> Canva Autofill.
     */
    public function sync(
        Request $request,
        CanvaService $canva,
        GoogleSheetsService $sheets
    ): JsonResponse {
        /*
         * 1. VALIDASI TOKEN CANVA
         */
        $token = $request->session()->get(
            'canva_token.access_token'
        );

        if (!$token) {
            return response()->json([
                'success' => false,
                'code' => 'CANVA_NOT_CONNECTED',
                'message' => 'Canva belum terhubung. Silakan hubungkan Canva terlebih dahulu.',
            ], 401);
        }

        /*
         * 2. VALIDASI DESIGN ID
         */
        $designId = config('services.canva.design_id');

        if (empty($designId)) {
            return response()->json([
                'success' => false,
                'code' => 'CANVA_DESIGN_ID_EMPTY',
                'message' => 'CANVA_DESIGN_ID belum diatur di file .env.',
            ], 422);
        }

        try {
            /*
             * 3. AMBIL KONFIGURASI GOOGLE SHEETS
             */
            $spreadsheetId = trim((string) config(
                'services.google_sheets.spreadsheet_id',
                ''
            ));

            $sheetGid = trim((string) config(
                'services.google_sheets.sheet_gid',
                '0'
            ));

            if ($spreadsheetId === '') {
                throw new RuntimeException(
                    'GOOGLE_SHEETS_ID belum diatur di file .env.'
                );
            }

            if ($sheetGid === '') {
                $sheetGid = '0';
            }

            /*
             * 4. AMBIL DATA HARGA
             *
             * Jangan gunakan getPrices() karena metode tersebut
             * memang melempar RuntimeException.
             */
            $prices = $sheets->getPricesBySpreadsheet(
                $spreadsheetId,
                $sheetGid
            );

            if (!is_array($prices) || count($prices) === 0) {
                throw new RuntimeException(
                    'Google Sheets tidak memiliki data harga. Periksa Spreadsheet ID, GID, nama tab, dan isi datanya.'
                );
            }

            /*
             * 5. AMBIL DATASET CANVA
             */
            $datasetResponse = $canva->getDesignDataset(
                $token,
                $designId
            );

            if (!is_array($datasetResponse)) {
                throw new RuntimeException(
                    'Respons dataset Canva tidak valid.'
                );
            }

            /*
             * Pertahankan format respons yang digunakan
             * oleh CanvaService pada implementasi saat ini.
             */
            $sheetField = $canva->findSheetField(
                $datasetResponse
            );

            if (!$sheetField) {
                return response()->json([
                    'success' => false,
                    'code' => 'NO_AUTOFILL_SHEET_FIELD',
                    'message' => 'Field Autofill bertipe sheet tidak ditemukan. Periksa konfigurasi Autofill pada desain Canva.',
                    'design_id' => $designId,
                    'total_data' => count($prices),
                    'dataset' => $datasetResponse,
                ], 422);
            }

            /*
             * 6. BUAT AUTOFILL JOB
             */
            $jobResponse = $canva->createAutofillFromDesign(
                $token,
                $designId,
                $sheetField,
                $prices
            );

            $jobId = $jobResponse['job']['id'] ?? null;

            if (!$jobId) {
                throw new RuntimeException(
                    'Canva tidak mengembalikan Job ID. Periksa respons createAutofillFromDesign().'
                );
            }

            /*
             * 7. POLLING STATUS JOB
             *
             * Maksimal 10 kali, jeda 2 detik.
             */
            $result = null;
            $status = null;

            for ($i = 0; $i < 10; $i++) {
                sleep(2);

                $result = $canva->getAutofillJob(
                    $token,
                    $jobId
                );

                $status = $result['job']['status'] ?? null;

                if ($status === 'success' || $status === 'failed') {
                    break;
                }
            }

            /*
             * 8. VALIDASI HASIL JOB
             */
            if ($status !== 'success') {
                return response()->json([
                    'success' => false,
                    'code' => 'CANVA_AUTOFILL_NOT_SUCCESS',
                    'message' => $status === 'failed'
                        ? 'Canva gagal memproses Autofill Job.'
                        : 'Autofill belum selesai setelah menunggu maksimal 20 detik.',
                    'job_id' => $jobId,
                    'status' => $status,
                    'job' => $result,
                ], 500);
            }

            /*
             * 9. AMBIL URL DESAIN HASIL AUTOFILL
             */
            $design = $result['job']['result']['design'] ?? [];

            $editUrl = $design['urls']['edit_url'] ?? null;
            $viewUrl = $design['urls']['view_url'] ?? null;

            /*
             * 10. RESPONS SUKSES
             */
            return response()->json([
                'success' => true,
                'message' => count($prices)
                    . ' baris data berhasil diproses oleh Canva.',
                'spreadsheet_id' => $spreadsheetId,
                'sheet_gid' => $sheetGid,
                'design_id' => $designId,
                'total_data' => count($prices),
                'job_id' => $jobId,
                'status' => $status,
                'design' => [
                    'edit_url' => $editUrl,
                    'view_url' => $viewUrl,
                ],
                'job' => $result,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'code' => 'CANVA_SYNC_ERROR',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}