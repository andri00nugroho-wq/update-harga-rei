<?php

namespace App\Http\Controllers;

use App\Services\CanvaService;

use App\Services\GoogleSheetsService;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

use Throwable;

class SyncController extends Controller

{

    private const LM_SPREADSHEET = '1hz_16R3D4WdrdiUbUiSIOXydm0z9uzOeEpTsK2gVj24';
    private const LM_GID = '0';

    private const REGION_SPREADSHEET =

        '1vJDRh0PXDARQaQyZwSNtBgqmz3jDdV_Iqx5ZKPkRnO8';

    private const JAWA_GID = '0';

    private const SUMATERA_GID = '430244690';

    private const KALIMANTAN_GID = '1637683801';

    public function __construct(

        private readonly CanvaService $canva,

        private readonly GoogleSheetsService $sheets

    ) {

    }

    public function sync(Request $request): JsonResponse

    {

        $syncId = (string) Str::uuid();

        $designId = '';

        // Nomor cabang wajib angka saja.
        $nomorCabang = trim((string) $request->input('nomor_cabang', ''));
        if ($nomorCabang === '' || !preg_match('/^\\d+$/', $nomorCabang)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor cabang wajib diisi dengan angka saja.',
                'sync_id' => $syncId,
            ], 422);
        }

        try {

            $accessToken = $this->getSessionAccessToken($request);

            if ($accessToken === '') {

                return response()->json([

                    'success' => false,

                    'message' => 'Token Canva tidak ditemukan. Hubungkan ulang akun Canva.',

                    'sync_id' => $syncId,

                ], 401);

            }

            $designId = trim((string) config(

                'services.canva.design_id',

                env('CANVA_DESIGN_ID', '')

            ));

            if ($designId === '') {

                throw new \RuntimeException(

                    'CANVA_DESIGN_ID belum dikonfigurasi.'

                );

            }

            $response = $this->canva->getDesignDataset(

                $accessToken,

                $designId

            );

            if (!is_array($response)) {

                throw new \RuntimeException(

                    'Respons dataset Canva tidak valid.'

                );

            }

            if (($response['success'] ?? true) === false) {

                throw new \RuntimeException(

                    (string) ($response['message'] ?? 'Gagal mengambil dataset Canva.')

                );

            }

            $dataset = $response['dataset'] ?? $response;

            if (isset($dataset['dataset']) && is_array($dataset['dataset'])) {

                $dataset = $dataset['dataset'];

            }

            if (!is_array($dataset) || $dataset === []) {

                throw new \RuntimeException(

                    'Dataset Canva kosong atau format respons tidak sesuai.'

                );

            }

            
                Log::info('DEBUG CANVA DATASET', [
                'dataset_type' => gettype($dataset),
                'field_names' => array_keys($dataset),
                'dataset' => $dataset,
            ]);


            // Sumber harga LM menggunakan spreadsheet yang ditentukan pengguna.
            $lmSpreadsheetId = self::LM_SPREADSHEET;
            $lmGid = self::LM_GID;

            $sources = [

                'logam_mulia' => $this->sheets->getPricesBySpreadsheet(

                    $lmSpreadsheetId,

                    $lmGid

                ),

                'jawa' => $this->sheets->getPricesBySpreadsheet(

                    self::REGION_SPREADSHEET,

                    self::JAWA_GID

                ),

                'sumatera' => $this->sheets->getPricesBySpreadsheet(

                    self::REGION_SPREADSHEET,

                    self::SUMATERA_GID

                ),

                'kalimantan' => $this->sheets->getPricesBySpreadsheet(

                    self::REGION_SPREADSHEET,

                    self::KALIMANTAN_GID

                ),

            ];

            $sourceCounts = [];

            foreach ($sources as $name => $rows) {

                if (!is_array($rows)) {

                    throw new \RuntimeException(

                        "Data sumber {$name} tidak valid."

                    );

                }

                $sourceCounts[$name] = count($rows);

            }

            $autofillData = [];

            $mappedFields = [];

            $unmatchedFields = [];

            $skippedFields = [];

            $today = now()->locale('id')->translatedFormat('j F Y');

            foreach ($dataset as $fieldName => $definition) {

                $fieldName = $this->safeString($fieldName);

                if ($fieldName === '' || !is_array($definition)) {

                    continue;

                }

                $type = strtolower($this->safeString(

                    $definition['type'] ?? 'text'

                ));

                if ($type !== '' && $type !== 'text') {

                    $skippedFields[$fieldName] = 'Field bukan tipe text.';

                    continue;

                }

                $normalizedField = $this->normalizeColumnName($fieldName);

                // Isi field nomor cabang dari input, jika tersedia di desain Canva.
                if (in_array($normalizedField, ['cabang', 'nomorcabang', 'nomorbranch', 'branchnumber'], true)) {
                    $autofillData[$fieldName] = [
                        'type' => 'text',
                        'text' => $nomorCabang,
                    ];
                    $mappedFields[$fieldName] = [
                        'source' => 'input nomor cabang',
                        'value' => $nomorCabang,
                    ];
                    continue;
                }

                if ($this->isDateField($normalizedField)) {

                    $autofillData[$fieldName] = [

                        'type' => 'text',

                        'text' => $today,

                    ];

                    $mappedFields[$fieldName] = [

                        'source' => 'tanggal sistem',

                        'value' => $today,

                    ];

                    continue;

                }

               
$sourceName = $this->selectSourceName($normalizedField);
$rows = $sources[$sourceName] ?? [];

$match = $this->findPriceForField($fieldName, $rows);

if ($match === null) {
    $normalizedLmField = str_replace(
        ',',
        '.',
        strtolower(trim($fieldName))
    );

    // Harga beli kembali yang kosong dilewati.
    // Jangan menggunakan harga jual sebagai pengganti.
    if (
        preg_match(
            '/^lmb[-_ ]?([0-9]+(?:\.[0-9]+)?)$/',
            $normalizedLmField
        ) === 1
        || $normalizedLmField === 'kitkatb'
    ) {
        $skippedFields[$fieldName] =
            'Harga beli kembali kosong atau tidak tersedia di Google Sheets.';

        continue;
    }

    $unmatchedFields[] = $fieldName;
    continue;
}

$price = trim($this->safeString($match['value'] ?? ''));

if ($price === '') {
    $skippedFields[$fieldName] = 'Nilai sumber kosong.';
    continue;
}

$autofillData[$fieldName] = [
    'type' => 'text',
    'text' => $price,
];

$mappedFields[$fieldName] = [
    'source' => $sourceName,
    'header' => $match['header'] ?? '',
    'row' => $match['row'] ?? null,
    'value' => $price,
];


            }

            if ($autofillData === []) {

                return response()->json([

                    'success' => false,

                    'message' => 'Tidak ada field Canva yang cocok dengan header Google Sheets.',

                    'sync_id' => $syncId,

                    'design_id' => $designId,

                    'source_counts' => $sourceCounts,

                    'sample_headers' => $this->getSampleHeaders($sources),

                    'unmatched_fields' => $unmatchedFields,

                    'skipped_fields' => $skippedFields,

                ], 422);

            }

            $autofillResponse = $this->canva->createAutofill(

                $accessToken,

                $designId,

                $autofillData

            );

            if (!is_array($autofillResponse)) {

                throw new \RuntimeException(

                    'Respons Canva Autofill tidak valid.'

                );

            }

            if (($autofillResponse['success'] ?? true) === false) {

                throw new \RuntimeException(

                    (string) (

                        $autofillResponse['message']

                        ?? $autofillResponse['error']

                        ?? 'Canva gagal memulai Autofill.'

                    )

                );

            }

            $jobId = $this->extractJobId($autofillResponse);

            if ($jobId === '') {

                throw new \RuntimeException(

                    'Canva menerima permintaan tetapi ID job tidak ditemukan. Respons: '

                    . json_encode($autofillResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)

                );

            }

            // Jangan menyatakan sinkronisasi selesai hanya karena job berhasil dibuat.

            // Tunggu sampai Canva menyelesaikan Autofill, atau laporkan status sebenarnya.

            $completedResponse = $this->canva->waitForAutofill(

                $accessToken,

                $jobId,

                30,

                700

            );

            // Normalisasi struktur respons job Canva agar URL selalu mudah

            // diambil oleh frontend melalui properti design_url.

            $completedJob = $completedResponse['job'] ?? $completedResponse;

            if (!is_array($completedJob)) {

                $completedJob = [];

            }

            $jobStatus = strtolower($this->safeString(

                $completedJob['status']

                    ?? $completedResponse['status']

                    ?? ''

            ));

            if (in_array($jobStatus, ['failed', 'failure', 'error'], true)) {

                throw new \RuntimeException(

                    'Canva gagal membuat desain. Detail: '

                    . json_encode($completedResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)

                );

            }

            if (!in_array($jobStatus, ['success', 'succeeded', 'completed', 'complete', 'done'], true)) {

                throw new \RuntimeException(

                    'Job Canva belum selesai. Status saat ini: '

                    . ($jobStatus !== '' ? $jobStatus : 'tidak diketahui')

                );

            }

            $jobResult = $completedJob['result'] ?? $completedResponse['result'] ?? [];

            if (!is_array($jobResult)) {

                $jobResult = [];

            }

            $design = $jobResult['design'] ?? $completedResponse['design'] ?? [];

            if (!is_array($design)) {

                $design = [];

            }

            $designUrls = $design['urls'] ?? [];

            if (!is_array($designUrls)) {

                $designUrls = [];

            }

            $newDesignId = $this->safeString(

                $design['id'] ?? $jobResult['design_id'] ?? $completedResponse['design_id'] ?? ''

            );

            $designUrl = $this->safeString(

                $design['url']

                    ?? $designUrls['edit_url']

                    ?? $design['edit_url']

                    ?? $jobResult['url']

                    ?? $jobResult['edit_url']

                    ?? $completedResponse['design_url']

                    ?? ''

            );

            // Tolak URL selain domain Canva untuk mencegah link tidak tepercaya.

            if ($designUrl !== '' && !preg_match('~^https://(?:www\\.)?canva\\.com/~i', $designUrl)) {

                Log::warning('URL desain Canva memiliki domain yang tidak dikenali.', [

                    'sync_id' => $syncId,

                    'design_url' => $designUrl,

                ]);

                $designUrl = '';

            }

            Log::info('Autofill Google Sheets ke Canva selesai.', [

                'sync_id' => $syncId,

                'source_design_id' => $designId,

                'new_design_id' => $newDesignId,

                'design_url' => $designUrl,

                'job_id' => $jobId,

                'field_count' => count($autofillData),

                'source_counts' => $sourceCounts,

                'mapped_fields' => $mappedFields,

                'unmatched_fields' => $unmatchedFields,

                'canva_job_response' => $completedResponse,

            ]);

            return response()->json([

                'success' => true,

                'message' => $designUrl !== ''

                    ? 'Autofill berhasil. Desain Canva baru siap dibuka.'

                    : 'Autofill berhasil, tetapi URL desain tidak ditemukan pada respons Canva.',

                'sync_id' => $syncId,

                'source_design_id' => $designId,

                'job_id' => $jobId,

                'design_id' => $newDesignId,

                'design_url' => $designUrl !== '' ? $designUrl : null,

                'field_count' => count($autofillData),

                'mapped_fields' => $mappedFields,

                'unmatched_fields' => $unmatchedFields,

                'skipped_fields' => $skippedFields,

                'canva_create_response' => $autofillResponse,

                'canva_completed_response' => $completedResponse,

            ]);

        } catch (Throwable $e) {

            Log::error('Sinkronisasi Google Sheets ke Canva gagal.', [

                'sync_id' => $syncId,

                'message' => $e->getMessage(),

                'file' => $e->getFile(),

                'line' => $e->getLine(),

                'design_id' => $designId,

            ]);

            return response()->json([

                'success' => false,

                'message' => 'Sinkronisasi gagal: ' . $e->getMessage(),

                'sync_id' => $syncId,

            ], 500);

        }

    }

    public function status(Request $request): JsonResponse

    {

        $token = $this->getSessionAccessToken($request);

        if ($token === '') {

            return response()->json([

                'success' => false,

                'message' => 'Token Canva tidak ditemukan. Hubungkan ulang akun Canva.',

            ], 401);

        }

        $jobId = trim($this->safeString(

            $request->input('job_id', $request->input('jobId', ''))

        ));

        if ($jobId === '') {

            return response()->json([

                'success' => false,

                'message' => 'Parameter job_id wajib diisi.',

            ], 422);

        }

        try {

            $result = $this->canva->getAutofillJob($token, $jobId);

            if (!is_array($result)) {

                throw new \RuntimeException(

                    'Respons status job Canva tidak valid.'

                );

            }

            return response()->json([

                'success' => true,

                'job_id' => $jobId,

                'data' => $result,

            ]);

        } catch (Throwable $e) {

            Log::error('Pemeriksaan status Canva gagal.', [

                'job_id' => $jobId,

                'message' => $e->getMessage(),

            ]);

            return response()->json([

                'success' => false,

                'message' => 'Gagal memeriksa status: ' . $e->getMessage(),

            ], 500);

        }

    }

    private function getSessionAccessToken(Request $request): string

    {

        foreach (['canva_token', 'canva_access_token'] as $key) {

            $token = $request->session()->get($key);

            if (is_array($token)) {

                $data = $token['data'] ?? [];

                $token = $token['access_token']

                    ?? $token['token']

                    ?? (is_array($data) ? ($data['access_token'] ?? '') : '');

            }

            if (is_string($token) && trim($token) !== '') {

                return trim($token);

            }

        }

        $nested = $request->session()->get('canva_token.access_token');

        return is_string($nested) ? trim($nested) : '';

    }

    private function selectSourceName(string $field): string

    {

        if ($this->containsAny($field, [

            'kalimantan', 'banjarmasin', 'balikpapan',

            'samarinda', 'pontianak',

        ])) {

            return 'kalimantan';

        }

        if ($this->containsAny($field, [

            'sumatera', 'sumatra', 'medan',

            'palembang', 'pekanbaru', 'lampung', 'aceh',

        ])) {

            return 'sumatera';

        }

        if ($this->containsAny($field, [

            'jawa', 'jakarta', 'bandung', 'semarang',

            'surabaya', 'yogyakarta', 'solo', 'malang',

        ])) {

            return 'jawa';

        }

        return 'logam_mulia';

    }

    private function containsAny(string $value, array $needles): bool

    {

        foreach ($needles as $needle) {

            if (str_contains($value, $needle)) {

                return true;

            }

        }

        return false;

    }

    private function isDateField(string $field): bool

    {

        return in_array(

            $field,

            ['tanggal', 'date', 'updatedate', 'tanggalupdate'],

            true

        );

    }

    /**

     * Normalisasi ringan untuk pencocokan nama field tanpa menghapus tanda

     * khusus seperti * atau keterangan kadar dalam tanda kurung.

     */

    private function strictColumnName(string $value): string

    {

        $value = mb_strtolower(Str::ascii(trim($value)), 'UTF-8');

        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return $value;

    }

    /**

     * Normalisasi cadangan untuk variasi spasi/tanda hubung biasa.

     * Tidak digunakan untuk field bertanda * atau berketerangan kadar.

     */

    private function normalizeColumnName(string $value): string

    {

        $value = strtolower(Str::ascii(trim($value)));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';

    }

    /**

     * Menghasilkan kandidat nama kolom berdasarkan jenis field.

     * Penanda kadar (99,5) dan bintang (*) dipertahankan agar tidak

     * tertukar dengan K24 biasa.

     */

    private function buildFieldAliases(string $fieldName): array

    {

        $raw = mb_strtolower(trim($fieldName), 'UTF-8');

        $aliases = [$raw];

        // Field wilayah: jawa-k11 -> k11, sumatera-15 -> 15.

        foreach (['kalimantan-', 'sumatera-', 'sumatra-', 'jawa-'] as $prefix) {

            if (str_starts_with($raw, $prefix)) {

                $aliases[] = substr($raw, strlen($prefix));

                break;

            }

        }

        // Logam Mulia: lm-250 / lmB-100. Prefix dipertahankan sebagai

        // kandidat utama; gramasi hanya kandidat cadangan bila header memang

        // menggunakan gramasi saja.

        if (preg_match('/^lmb[-_ ](.+)$/i', $raw, $m)) {

            $aliases[] = 'lmb ' . $m[1];

            $aliases[] = $m[1];

        } elseif (preg_match('/^lm[-_ ](.+)$/i', $raw, $m)) {

            $aliases[] = 'lm ' . $m[1];

            $aliases[] = $m[1];

        }

        return array_values(array_unique(array_filter($aliases, fn ($v) => trim($v) !== '')));

    }

    /**

     * Cari header dengan prioritas:

     * 1) nama field persis;

     * 2) alias yang eksplisit (misalnya jawa-k11 -> k11);

     * 3) variasi tanda hubung/spasi untuk field tanpa penanda khusus.

     * Jika ada lebih dari satu header dengan kecocokan terbaik, jangan

     * menebak karena bisa mengirim harga produk lain ke Canva.

     *

     * Setelah kolom dipilih, ambil nilai terakhir yang tidak kosong dari

     * kolom tersebut saja dan sertakan nomor baris sumber untuk audit.

     */

    private function findPriceForField(string $fieldName, array $rows): ?array

    {

        if (trim($fieldName) === '' || $rows === []) {

            return null;

        }

        // Field LM memakai Product / Weight (g) / Sell price / Buyback price.
        $lmMatch = $this->findPriceFromLmTable($fieldName, $rows);
        if ($lmMatch !== null) {
            return $lmMatch;
        }

        // Format tabel vertikal: kolom KARAT berisi K24\*, K24 (99.5%), K11,

        // sedangkan kolom HARGA/gr berisi harga. Cari baris produk langsung;

        // jangan membaca harga dari baris terakhir seperti format tabel horizontal.

        $verticalMatch = $this->findPriceFromVerticalTable($fieldName, $rows);

        if ($verticalMatch !== null) {

            return $verticalMatch;

        }

        $aliases = $this->buildFieldAliases($fieldName);

        $strictAliases = array_values(array_unique(array_map(

            fn (string $alias): string => $this->strictColumnName($alias),

            $aliases

        )));

        $specialField = str_contains($fieldName, '*')

            || preg_match('/\([^)]*\)/', $fieldName) === 1;

        $headers = [];

        foreach ($rows as $row) {

            if (!is_array($row)) {

                continue;

            }

            foreach ($row as $header => $value) {

                if (!is_string($header) || $header === '_row') {

                    continue;

                }

                $headers[$header] = true;

            }

        }

        // Skor lebih kecil berarti kecocokan lebih kuat.

        $scores = [];

        $fieldStrict = $this->strictColumnName($fieldName);

        foreach (array_keys($headers) as $header) {

            $headerStrict = $this->strictColumnName($header);

            if ($headerStrict === $fieldStrict) {

                $scores[$header] = 0;

                continue;

            }

            foreach ($strictAliases as $index => $alias) {

                if ($headerStrict === $alias) {

                    $scores[$header] = min($scores[$header] ?? PHP_INT_MAX, 10 + $index);

                }

            }

            // Fuzzy normalisasi hanya untuk field biasa. Jangan hapus tanda

            // \* atau kadar karena itu membedakan produk.

            if (!$specialField) {

                $headerNormalized = $this->normalizeColumnName($header);

                foreach ($aliases as $index => $alias) {

                    if ($headerNormalized !== '' && $headerNormalized === $this->normalizeColumnName($alias)) {

                        $scores[$header] = min($scores[$header] ?? PHP_INT_MAX, 30 + $index);

                    }

                }

            }

        }

        if ($scores === []) {

            return null;

        }

        asort($scores, SORT_NUMERIC);

        $bestScore = reset($scores);

        $bestHeaders = array_keys(array_filter(

            $scores,

            fn ($score) => $score === $bestScore

        ));

        if (count($bestHeaders) !== 1) {

            Log::warning('Pemetaan harga Canva ambigu; field dilewati.', [

                'field' => $fieldName,

                'candidate_headers' => $bestHeaders,

            ]);

            return null;

        }

        $chosenHeader = $bestHeaders[0];

        foreach (array_reverse($rows) as $row) {

            if (!is_array($row)) {

                continue;

            }

            $candidate = trim($this->safeString($row[$chosenHeader] ?? ''));

            if ($candidate === '') {

                continue;

            }

            return [

                'value' => $candidate,

                'header' => $chosenHeader,

                'row' => isset($row['_row']) ? (int) $row['_row'] : null,

            ];

        }

        return null;

    }

    /**

     * Mencocokkan field Canva ke tabel vertikal dengan kolom KARAT/PRODUK

     * dan HARGA/gr/HARGA. Mendukung K24*, K24 (99,5%) dan K11.

     */

    /**
     * Map field LM Canva ke tabel Product / Weight (g) / Sell price / Buyback price.
     * Harga buyback yang kosong tidak pernah diganti dengan harga jual.
     */
    private function findPriceFromLmTable(string $fieldName, array $rows): ?array
    {
        $field = mb_strtolower(trim($fieldName), 'UTF-8');
        $field = str_replace(',', '.', $field);
        $isBuyback = false;
        $weight = null;
        $isKitkat = false;

        if (preg_match('/^lmb[-_ ]?([0-9]+(?:\.[0-9]+)?)$/', $field, $m)) {
            $isBuyback = true;
            $weight = (float) $m[1];
        } elseif (preg_match('/^lm[-_ ]?([0-9]+(?:\.[0-9]+)?)$/', $field, $m)) {
            $weight = (float) $m[1];
        } elseif ($field === 'kitkatb') {
            $isBuyback = true;
            $isKitkat = true;
        } elseif ($field === 'kitkat') {
            $isKitkat = true;
        } else {
            return null;
        }

        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row)) {
                continue;
            }
            $columns = [];
            foreach ($row as $header => $value) {
                if (!is_string($header) || $header === '_row') {
                    continue;
                }
                $columns[$this->normalizeColumnName($header)] = $header;
            }

            $productHeader = $columns['product'] ?? $columns['produk'] ?? null;
            $weightHeader = $columns['weightg'] ?? $columns['weight'] ?? $columns['beratg'] ?? $columns['berat'] ?? null;
            $sellHeader = $columns['sellprice'] ?? $columns['hargajual'] ?? $columns['sell'] ?? null;
            $buybackHeader = $columns['buybackprice'] ?? $columns['hargabelikembali'] ?? $columns['buyback'] ?? null;
            if ($productHeader === null || $weightHeader === null) {
                continue;
            }

            $product = mb_strtolower(trim($this->safeString($row[$productHeader] ?? '')), 'UTF-8');
            $rowWeightRaw = str_replace(',', '.', trim($this->safeString($row[$weightHeader] ?? '')));
            if ($rowWeightRaw === '' || !is_numeric($rowWeightRaw)) {
                continue;
            }
            $rowWeight = (float) $rowWeightRaw;

            if ($isKitkat) {
                if (!str_contains($product, 'kitkat')) {
                    continue;
                }
            } else {
                if (!str_contains($product, 'lm') || abs($rowWeight - (float) $weight) > 0.00001) {
                    continue;
                }
            }

            $priceHeader = $isBuyback ? $buybackHeader : $sellHeader;
            if ($priceHeader === null) {
                return null;
            }
            $price = trim($this->safeString($row[$priceHeader] ?? ''));
            if ($price === '') {
                return null;
            }
            return [
                'value' => $price,
                'header' => $priceHeader,
                'row' => isset($row['_row']) ? (int) $row['_row'] : ($rowIndex + 2),
            ];
        }
        return null;
    }

    private function findPriceFromVerticalTable(string $fieldName, array $rows): ?array

    {

        $field = mb_strtolower(trim($fieldName), 'UTF-8');

        // Hanya proses field wilayah, bukan field Logam Mulia.

        if (!preg_match('/^(?:jawa|sumatera|sumatra|kalimantan)[-_ ](.+)$/u', $field, $m)) {

            return null;

        }

        $productToken = trim($m[1]);

        $target = $this->canonicalProductKey($productToken);

        if ($target === '') {

            return null;

        }

        foreach ($rows as $row) {

            if (!is_array($row)) {

                continue;

            }

            $keyHeader = null;

            $priceHeader = null;

            foreach (array_keys($row) as $header) {

                if (!is_string($header) || $header === '_row') {

                    continue;

                }

                $headerKey = $this->normalizeColumnName($header);

                if (in_array($headerKey, ['karat', 'produk', 'kode', 'jenis', 'gramasi', 'berat'], true)) {

                    $keyHeader ??= $header;

                }

                if (in_array($headerKey, ['hargagr', 'harga', 'price', 'hargaemas', 'hargapergram'], true)) {

                    $priceHeader ??= $header;

                }

            }

            if ($keyHeader === null || $priceHeader === null) {

                continue;

            }

            $rowKey = $this->canonicalProductKey($this->safeString($row[$keyHeader] ?? ''));

            if ($rowKey === '' || $rowKey !== $target) {

                continue;

            }

            $price = trim($this->safeString($row[$priceHeader] ?? ''));

            if ($price === '') {

                Log::warning('Baris produk ditemukan tetapi harga kosong.', [

                    'field' => $fieldName,

                    'key_header' => $keyHeader,

                    'price_header' => $priceHeader,

                    'row' => $row['_row'] ?? null,

                ]);

                return null;

            }

            return [

                'value' => $price,

                'header' => $priceHeader . ' (produk: ' . $keyHeader . '=' . $this->safeString($row[$keyHeader] ?? '') . ')',

                'row' => isset($row['_row']) ? (int) $row['_row'] : null,

            ];

        }

        return null;

    }

    /** Normalisasi label produk tanpa menyamakan K24\* dengan K24 (99,5%). */

    private function canonicalProductKey(string $value): string

    {

        $value = mb_strtolower(Str::ascii(trim($value)), 'UTF-8');

        $value = str_replace(['，', '％'], [',', '%'], $value);

        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        $value = str_replace(',', '.', $value);

        // Field Canva jawa-k24(99,5) berarti K24 (99.5%) di sheet.

        if (preg_match('/^k?24\s*\(?\s*99[.,]5\s*%?\s*\)?$/', $value)) {

            return 'k24-99.5';

        }

        if (preg_match('/^k?24\*$/', $value)) {

            return 'k24*';

        }

        if (preg_match('/^k?(\d{1,2})$/', $value, $m)) {

            return 'k' . (int) $m[1];

        }

        // Untuk gramasi, pertahankan penanda lm/lmb agar tidak tertukar.

        if (preg_match('/^(lmb|lm)[-_ ]?([0-9]+(?:\.[0-9]+)?)$/', $value, $m)) {

            return $m[1] . '-' . $m[2];

        }

        return preg_replace('/[^a-z0-9.\*-]/', '', $value) ?? '';

    }

    private function extractJobId(array $response): string

    {

        $candidates = [

            $response['job']['id'] ?? null,

            $response['job']['job_id'] ?? null,

            $response['job_id'] ?? null,

            $response['id'] ?? null,

            $response['data']['job']['id'] ?? null,

            $response['data']['job_id'] ?? null,

        ];

        foreach ($candidates as $candidate) {

            $value = $this->safeString($candidate);

            if ($value !== '') {

                return $value;

            }

        }

        return '';

    }

    private function getSampleHeaders(array $sources): array

    {

        $result = [];

        foreach ($sources as $sourceName => $rows) {

            foreach ($rows as $row) {

                if (!is_array($row)) {

                    continue;

                }

                $headers = array_filter(

                    array_keys($row),

                    fn ($key): bool => is_string($key) && $key !== '_row'

                );

                $result[$sourceName] = array_values($headers);

                break;

            }

            $result[$sourceName] ??= [];

        }

        return $result;

    }

    private function safeString(mixed $value): string

    {

        if (is_string($value)) {

            return trim($value);

        }

        if (is_int($value) || is_float($value)) {

            return (string) $value;

        }

        if (is_bool($value)) {

            return $value ? '1' : '0';

        }

        if ($value instanceof \Stringable) {

            return trim((string) $value);

        }

        return '';

    }

}
