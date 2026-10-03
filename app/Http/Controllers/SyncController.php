<?php

namespace App\Http\Controllers;

use App\Models\SyncLog;
use App\Services\CanvaService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SyncController extends Controller
{
    public function sync(
        Request $request,
        GoogleSheetsService $googleSheets,
        CanvaService $canva
    ): JsonResponse {
        try {
            // 1. Canva token
            $token = $request->session()->get('canva_token');

            if (empty($token)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Canva belum terhubung.',
                ], 401);
            }

            $accessToken = is_array($token)
                ? ($token['access_token'] ?? null)
                : $token;

            if (empty($accessToken)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Access token Canva tidak ditemukan.',
                ], 401);
            }

            // 2. Canva design
            $designId = config('services.canva.design_id');

            if (!$designId) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'CANVA_DESIGN_ID belum diatur.',
                ], 500);
            }

            // 3. Ambil dataset Canva
            $datasetResponse = $canva->getDesignDataset(
                $accessToken,
                $designId
            );

            $dataset = $datasetResponse['dataset'] ?? [];

            if (empty($dataset)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Dataset Canva kosong.',
                ], 422);
            }

            $datasetKeys = array_keys($dataset);

            // 4. Buat index field Canva
            $fieldIndex = [];

            foreach ($datasetKeys as $field) {
                $fieldIndex[$this->normalizeField($field)] = $field;
            }

            // 5. Ambil data Google Sheets
            $jawa = $googleSheets->getPrices(
                'Harga Emas Raja Emas - Ujian Fullstack'
            );

            $kalimantan = $googleSheets->getPrices(
                'Harga Emas Raja Emas Kalimantan,Sulawesi'
            );

            $sumatera = $googleSheets->getPrices(
                'Harga Emas Raja Emas Sumatera,Bali,Lombok'
            );

            $logamMulia = $googleSheets->getPrices(
                'Daftar Harga Logam Mulia Raja Emas Indonesia'
            );

            // 6. Data Autofill
            $autofillData = [];

            $mapped = [
                'jawa' => 0,
                'kalimantan' => 0,
                'sumatera' => 0,
                'logam_mulia' => 0,
                'kitkat' => 0,
            ];

            $skipped = [
                'jawa' => [],
                'kalimantan' => [],
                'sumatera' => [],
                'logam_mulia' => [],
                'kitkat' => [],
            ];

            // 7. Jawa / Nasional
            foreach ($jawa as $row) {
                $karat = $this->cleanKarat(
                    $row['karat'] ?? ''
                );

                $harga = trim(
                    (string) ($row['harga/gr'] ?? '')
                );

                if ($karat === '' || $harga === '') {
                    continue;
                }

                $field = $this->findJawaField(
                    $karat,
                    $fieldIndex
                );

                if (!$field) {
                    $skipped['jawa'][] = $karat;
                    continue;
                }

                $autofillData[$field] = [
                    'type' => 'text',
                    'text' => $harga,
                ];

                $mapped['jawa']++;
            }

            // 8. Kalimantan / Sulawesi
            foreach ($kalimantan as $row) {
                $karat = $this->cleanKarat(
                    $row['karat'] ?? ''
                );

                $harga = trim(
                    (string) ($row['harga/gr'] ?? '')
                );

                if ($karat === '' || $harga === '') {
                    continue;
                }

                $field = $this->findRegionalField(
                    $karat,
                    'kalimantan_',
                    $fieldIndex
                );

                if (!$field) {
                    $skipped['kalimantan'][] = $karat;
                    continue;
                }

                $autofillData[$field] = [
                    'type' => 'text',
                    'text' => $harga,
                ];

                $mapped['kalimantan']++;
            }

            // 9. Sumatera / Bali / Lombok
            foreach ($sumatera as $row) {
                $karat = $this->cleanKarat(
                    $row['karat'] ?? ''
                );

                $harga = trim(
                    (string) ($row['harga/gr'] ?? '')
                );

                if ($karat === '' || $harga === '') {
                    continue;
                }

                $field = $this->findRegionalField(
                    $karat,
                    'sumatera_',
                    $fieldIndex
                );

                if (!$field) {
                    $skipped['sumatera'][] = $karat;
                    continue;
                }

                $autofillData[$field] = [
                    'type' => 'text',
                    'text' => $harga,
                ];

                $mapped['sumatera']++;
            }

            // 10. Logam Mulia
            $inKitkatSection = false;

            foreach ($logamMulia as $row) {
                $gramasi = trim(
                    (string) ($row['gramasi'] ?? '')
                );

                $harga = trim(
                    (string) ($row['harga'] ?? '')
                );

                $buyback = trim(
                    (string) ($row['buyback'] ?? '')
                );

                // Kitkat Gold
                if (strtolower($gramasi) === 'kitkat gold') {
                    $inKitkatSection = true;
                    continue;
                }

                if ($gramasi === '') {
                    continue;
                }

                if ($inKitkatSection) {
                    $kitkatHarga = $this->findField(
                        'kitkat_harga',
                        $fieldIndex
                    );

                    $kitkatBuyback = $this->findField(
                        'kitkat_buyback',
                        $fieldIndex
                    );

                    $kitkatGramasi = $this->findField(
                        'kitkat_gramasi',
                        $fieldIndex
                    );

                    if (
                        $kitkatHarga ||
                        $kitkatBuyback ||
                        $kitkatGramasi
                    ) {
                        if ($kitkatGramasi) {
                            $autofillData[$kitkatGramasi] = [
                                'type' => 'text',
                                'text' => $gramasi,
                            ];
                        }

                        if ($kitkatHarga && $harga !== '') {
                            $autofillData[$kitkatHarga] = [
                                'type' => 'text',
                                'text' => $harga,
                            ];
                        }

                        if (
                            $kitkatBuyback &&
                            $buyback !== ''
                        ) {
                            $autofillData[$kitkatBuyback] = [
                                'type' => 'text',
                                'text' => $buyback,
                            ];
                        }

                        $mapped['kitkat']++;
                    } else {
                        $skipped['kitkat'][] = $gramasi;
                    }

                    continue;
                }

                // Logam Mulia biasa
                $suffix = $this->normalizeGramasi($gramasi);

                if ($suffix === '') {
                    continue;
                }

                $hargaField = $this->findField(
                    'harga_' . $suffix,
                    $fieldIndex
                );

                $buybackField = $this->findField(
                    'buyback_' . $suffix,
                    $fieldIndex
                );

                $gramasiField = $this->findField(
                    'gramasi_' . $suffix,
                    $fieldIndex
                );

                if (
                    !$hargaField &&
                    !$buybackField &&
                    !$gramasiField
                ) {
                    $skipped['logam_mulia'][] = $gramasi;
                    continue;
                }

                if ($gramasiField) {
                    $autofillData[$gramasiField] = [
                        'type' => 'text',
                        'text' => $gramasi,
                    ];
                }

                if (
                    $hargaField &&
                    $harga !== ''
                ) {
                    $autofillData[$hargaField] = [
                        'type' => 'text',
                        'text' => $harga,
                    ];
                }

                if (
                    $buybackField &&
                    $buyback !== ''
                ) {
                    $autofillData[$buybackField] = [
                        'type' => 'text',
                        'text' => $buyback,
                    ];
                }

                $mapped['logam_mulia']++;
            }

            // 11. Validasi
            $mappedCount = count($autofillData);

            if ($mappedCount === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Tidak ada data Google Sheets yang cocok dengan field Canva.',
                    'mapped' => $mapped,
                    'skipped' => $skipped,
                    'dataset_fields' => $datasetKeys,
                ], 422);
            }

            // 12. Canva Autofill
            $autofill = $canva->createAutofill(
                $accessToken,
                $designId,
                $autofillData
            );

            $jobId =
                $autofill['job']['id']
                ?? $autofill['id']
                ?? null;

            if (!$jobId) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Canva tidak mengembalikan Job ID.',
                    'canva_response' => $autofill,
                ], 500);
            }

            // 13. Simpan log
            SyncLog::create([
                'status' => 'processing',
                'total_data' => $mappedCount,
                'job_id' => $jobId,
                'design_id' => $designId,
                'message' => 'Autofill Canva sedang diproses.',
            ]);

            // 14. Response
            return response()->json([
                'status' => 'processing',
                'job_status' => 'in_progress',
                'message' => "Berhasil mengirim {$mappedCount} field harga ke Canva.",
                'job_id' => $jobId,
                'design_id' => $designId,
                'total_data' => $mappedCount,
                'mapped' => $mapped,
                'skipped' => $skipped,
                'fields_sent' => array_keys($autofillData),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // STATUS AUTOFILL
    public function status(
        Request $request,
        string $jobId,
        CanvaService $canva
    ): JsonResponse {
        try {
            $token = $request->session()->get('canva_token');

            if (empty($token)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Canva belum terhubung.',
                ], 401);
            }

            $accessToken = is_array($token)
                ? ($token['access_token'] ?? null)
                : $token;

            if (empty($accessToken)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Access token Canva tidak ditemukan.',
                ], 401);
            }

            $log = SyncLog::where(
                'job_id',
                $jobId
            )->first();

            $job = $canva->getAutofillJob(
                $accessToken,
                $jobId
            );

            $rawStatus = strtolower(
                (string) (
                    $job['job']['status']
                    ?? $job['status']
                    ?? ''
                )
            );

            // Processing
            if (in_array(
                $rawStatus,
                [
                    'pending',
                    'queued',
                    'processing',
                    'in_progress',
                ],
                true
            )) {
                if ($log) {
                    $log->update([
                        'status' => 'processing',
                        'message' => 'Canva Autofill masih diproses.',
                    ]);
                }

                return response()->json([
                    'status' => 'processing',
                    'job_status' => $rawStatus,
                    'message' => 'Canva Autofill masih diproses.',
                    'job_id' => $jobId,
                    'design_id' => $log?->design_id,
                ]);
            }

            // Success
            if (in_array(
                $rawStatus,
                [
                    'success',
                    'completed',
                ],
                true
            )) {
                $resultDesign =
                    $job['job']['result']['design']
                    ?? $job['result']['design']
                    ?? null;

                $resultDesignId =
                    $resultDesign['id']
                    ?? null;

                $editUrl =
                    $resultDesign['urls']['edit_url']
                    ?? $resultDesign['url']
                    ?? null;

                $viewUrl =
                    $resultDesign['urls']['view_url']
                    ?? null;

                $resultUrl =
                    $viewUrl
                    ?? $editUrl;

                if ($log) {
                    $log->update([
                        'status' => 'success',
                        'design_id' =>
                            $resultDesignId
                            ?? $log->design_id,
                        'result_url' => $resultUrl,
                        'edit_url' => $editUrl,
                        'view_url' => $viewUrl,
                        'message' => 'Sinkronisasi Canva berhasil.',
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'job_status' => $rawStatus,
                    'message' => 'Sinkronisasi Canva berhasil.',
                    'job_id' => $jobId,
                    'design_id' =>
                        $resultDesignId
                        ?? $log?->design_id,
                    'result_url' => $resultUrl,
                    'edit_url' => $editUrl,
                    'view_url' => $viewUrl,
                ]);
            }

            // Failed
            $errorMessage =
                $job['job']['error']['message']
                ?? $job['error']['message']
                ?? $job['message']
                ?? 'Canva Autofill gagal.';

            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'message' => $errorMessage,
                ]);
            }

            return response()->json([
                'status' => 'failed',
                'job_status' => $rawStatus,
                'message' => $errorMessage,
                'job_id' => $jobId,
                'design_id' => $log?->design_id,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'job_id' => $jobId,
            ], 500);
        }
    }

    private function findJawaField(
        string $karat,
        array $fieldIndex
    ): ?string {
        $karat = strtoupper(trim($karat));

        if (preg_match('/^K24\s*\*$/i', $karat)) {
            return $this->findField(
                'harga_k24',
                $fieldIndex
            );
        }

        if (
            preg_match(
                '/^K24\s*\(99[.,]5\s*%\)$/i',
                $karat
            )
        ) {
            foreach ([
                'harga_k24_99,5',
                'harga_k24_995',
            ] as $target) {
                $field = $this->findField(
                    $target,
                    $fieldIndex
                );

                if ($field) {
                    return $field;
                }
            }

            return null;
        }

        if (
            preg_match(
                '/^K(6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23)$/i',
                $karat,
                $matches
            )
        ) {
            return $this->findField(
                'harga_k' . $matches[1],
                $fieldIndex
            );
        }

        return null;
    }

    private function findRegionalField(
        string $karat,
        string $prefix,
        array $fieldIndex
    ): ?string {
        $karat = strtoupper(trim($karat));

        $karat = preg_replace(
            '/^(KALIMANTAN_|SUMATERA_|SULAWESI_|BALI_|LOMBOK_)/i',
            '',
            $karat
        );

        if (preg_match('/^K24\s*\*$/i', $karat)) {
            return $this->findField(
                $prefix . '24',
                $fieldIndex
            );
        }

        if (
            preg_match(
                '/^K24\s*\(99[.,]5\s*%\)$/i',
                $karat
            )
        ) {
            foreach ([
                $prefix . '24_99,5',
                $prefix . '24_995',
            ] as $target) {
                $field = $this->findField(
                    $target,
                    $fieldIndex
                );

                if ($field) {
                    return $field;
                }
            }

            return null;
        }

        if (
            preg_match(
                '/^K(6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23)$/i',
                $karat,
                $matches
            )
        ) {
            return $this->findField(
                $prefix . $matches[1],
                $fieldIndex
            );
        }

        return null;
    }

    private function findField(
        string $target,
        array $fieldIndex
    ): ?string {
        $normalizedTarget = $this->normalizeField($target);

        return $fieldIndex[$normalizedTarget]
            ?? null;
    }

    private function cleanKarat($value): string
    {
        $value = trim((string) $value);

        $value = preg_replace(
            '/^(kalimantan|sulawesi|sumatera|bali|lombok)[_\-\s]+/i',
            '',
            $value
        );

        return trim($value);
    }

    private function normalizeGramasi(
        string $value
    ): string {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = str_replace(
            ',',
            '.',
            $value
        );

        $value = preg_replace(
            '/[^0-9.]/',
            '',
            $value
        );

        if ($value === '') {
            return '';
        }

        $number = (float) $value;

        if (floor($number) === $number) {
            return (string) (int) $number;
        }

        return str_replace(
            '.',
            '_',
            rtrim(
                rtrim(
                    number_format(
                        $number,
                        4,
                        '.',
                        ''
                    ),
                    '0'
                ),
                '.'
            )
        );
    }

    private function normalizeField(
        string $value
    ): string {
        $value = strtolower(trim($value));

        $value = str_replace(
            [
                ' ',
                '(',
                ')',
                '%',
                '*',
            ],
            '',
            $value
        );

        $value = str_replace(
            ',',
            '',
            $value
        );

        return $value;
    }
}

