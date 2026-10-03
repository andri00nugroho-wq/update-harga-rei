<?php

namespace App\Http\Controllers;

use App\Services\CanvaService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

class CanvaAutofillController extends Controller
{
    public function sync(
        Request $request,
        CanvaService $canva,
        GoogleSheetsService $sheets
    ): JsonResponse {
        $token = $request->session()->get(
            'canva_token.access_token'
        );

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Canva belum terhubung.',
            ], 401);
        }

        /*
         * Design Canva yang digunakan untuk ujian.
         */
        $designId = 'DAHWukifqHg';

        try {
            /*
             * 1. Ambil data Google Sheets
             */
            $prices = $sheets->getPrices();

            if (empty($prices)) {
                throw new \RuntimeException(
                    'Google Sheets tidak memiliki data harga.'
                );
            }

            /*
             * 2. Cek dataset Canva
             */
            $dataset = $canva->getDesignDataset(
                $token,
                $designId
            );

            /*
             * 3. Cari field sheet
             */
            $sheetField = $canva->findSheetField(
                $dataset
            );

            /*
             * Kalau kosong, jangan pura-pura berhasil.
             */
            if (!$sheetField) {
                return response()->json([
                    'success' => false,
                    'code' => 'NO_AUTOFILL_SHEET_FIELD',
                    'message' =>
                        'Template Canva belum memiliki field Autofill bertipe sheet.',
                    'design_id' => $designId,
                    'dataset' => $dataset,
                    'total_data' => count($prices),
                ], 422);
            }

            /*
             * 4. Buat Autofill Job
             */
            $job = $canva->createAutofillFromDesign(
                $token,
                $designId,
                $sheetField,
                $prices
            );

            $jobId = $job['job']['id'] ?? null;

            if (!$jobId) {
                throw new \RuntimeException(
                    'Canva tidak mengembalikan Autofill Job ID.'
                );
            }

            /*
             * 5. Polling beberapa kali.
             */
            $result = null;

            for ($i = 0; $i < 10; $i++) {
                sleep(2);

                $result = $canva->getAutofillJob(
                    $token,
                    $jobId
                );

                $status = $result['job']['status'] ?? null;

                if ($status === 'success') {
                    break;
                }

                if ($status === 'failed') {
                    break;
                }
            }

            $status = $result['job']['status'] ?? null;

            if ($status !== 'success') {
                return response()->json([
                    'success' => false,
                    'code' => 'CANVA_AUTOFILL_NOT_SUCCESS',
                    'message' =>
                        'Canva Autofill belum berhasil.',
                    'job' => $result,
                ], 500);
            }

            /*
             * 6. Ambil URL hasil Canva.
             */
            $design = $result['job']['result']['design'] ?? [];

            $editUrl =
                $design['urls']['edit_url'] ??
                null;

            $viewUrl =
                $design['urls']['view_url'] ??
                null;

            return response()->json([
                'success' => true,
                'message' =>
                    count($prices) .
                    ' data harga berhasil disinkronkan ke Canva.',
                'total_data' => count($prices),
                'job_id' => $jobId,
                'design' => [
                    'edit_url' => $editUrl,
                    'view_url' => $viewUrl,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}