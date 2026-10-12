<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CanvaService
{
    private const AUTHORIZE_URL =
        'https://www.canva.com/api/oauth/authorize';

    private const TOKEN_URL =
        'https://api.canva.com/rest/v1/oauth/token';

    private const API_URL =
        'https://api.canva.com/rest/v1';

    /**
     * =========================================================
     * GET AUTHORIZATION URL
     * =========================================================
     */
    public function getAuthorizationUrl(
        string $state,
        string $codeChallenge
    ): string {
        $query = http_build_query([
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',

            'scope' => implode(' ', [
                'design:content:read',
                'design:content:write',
                'design:meta:read',
            ]),

            'response_type' => 'code',

            'client_id' => config(
                'services.canva.client_id'
            ),

            'state' => $state,

            'redirect_uri' => config(
                'services.canva.redirect_uri'
            ),
        ]);

        return self::AUTHORIZE_URL . '?' . $query;
    }

    /**
     * =========================================================
     * EXCHANGE OAUTH CODE
     * =========================================================
     */
    public function exchangeCode(
        string $code,
        string $codeVerifier
    ): array {
        $response = Http::asForm()
            ->withBasicAuth(
                (string) config(
                    'services.canva.client_id'
                ),
                (string) config(
                    'services.canva.client_secret'
                )
            )
            ->post(
                self::TOKEN_URL,
                [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'code_verifier' => $codeVerifier,
                    'redirect_uri' => config(
                        'services.canva.redirect_uri'
                    ),
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Canva OAuth gagal: '
                . $response->body()
            );
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException(
                'Response OAuth Canva tidak valid.'
            );
        }

        return $json;
    }

    /**
     * =========================================================
     * GET DESIGN DATASET
     * =========================================================
     */
    public function getDesignDataset(
        string $accessToken,
        string $designId
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(
                self::API_URL
                . '/designs/'
                . rawurlencode($designId)
                . '/dataset'
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil dataset Canva: '
                . $response->body()
            );
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException(
                'Response dataset Canva tidak valid.'
            );
        }

        return $json;
    }

    /**
     * =========================================================
     * GET DESIGN
     * =========================================================
     */
    public function getDesign(
        string $accessToken,
        string $designId
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(
                self::API_URL
                . '/designs/'
                . rawurlencode($designId)
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil detail Canva Design: '
                . $response->body()
            );
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException(
                'Response detail Canva tidak valid.'
            );
        }

        return $json;
    }

    /**
     * =========================================================
     * CREATE AUTOFILL
     * =========================================================
     *
     * Membuat DESIGN BARU dari design sumber Canva.
     */
    public function createAutofill(
        string $accessToken,
        string $designId,
        array $data,
        ?string $title = null
    ): array {
        $payload = [
            'type' => 'create_from_design',

            'design_id' => $designId,

            'data' => $data,
        ];

        if (
            $title !== null
            && trim($title) !== ''
        ) {
            $payload['title'] = trim($title);
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post(
                self::API_URL . '/autofills',
                $payload
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Canva Autofill gagal. HTTP '
                . $response->status()
                . ': '
                . $response->body()
            );
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException(
                'Response Canva Autofill tidak valid.'
            );
        }

        return $json;
    }

    /**
     * =========================================================
     * GET AUTOFILL JOB
     * =========================================================
     */
    public function getAutofillJob(
        string $accessToken,
        string $jobId
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(
                self::API_URL
                . '/autofills/'
                . rawurlencode($jobId)
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil status Autofill Canva. HTTP '
                . $response->status()
                . ': '
                . $response->body()
            );
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException(
                'Response status Autofill Canva tidak valid.'
            );
        }

        return $json;
    }

    /**
     * =========================================================
     * WAIT FOR AUTOFILL
     * =========================================================
     */
    public function waitForAutofill(
        string $accessToken,
        string $jobId,
        int $maxAttempts = 30,
        int $delayMilliseconds = 700
    ): array {
        for (
            $attempt = 1;
            $attempt <= $maxAttempts;
            $attempt++
        ) {
            $result = $this->getAutofillJob(
                $accessToken,
                $jobId
            );

            $job = $result['job'] ?? [];

            if (!is_array($job)) {
                throw new RuntimeException(
                    'Response job Canva tidak valid.'
                );
            }

            $status = (string) (
                $job['status'] ?? ''
            );

            /**
             * AUTOFILL SELESAI
             */
            if ($status === 'success') {
                return $result;
            }

            /**
             * AUTOFILL GAGAL
             */
            if ($status === 'failed') {
                $error = $job['error'] ?? [];

                $errorMessage = is_array($error)
                    ? (
                        $error['message']
                        ?? 'Autofill Canva gagal.'
                    )
                    : 'Autofill Canva gagal.';

                throw new RuntimeException(
                    (string) $errorMessage
                );
            }

            /**
             * MASIH BERJALAN
             */
            if (
                $status === 'in_progress'
                || $status === 'queued'
            ) {
                usleep(
                    $delayMilliseconds * 1000
                );

                continue;
            }

            throw new RuntimeException(
                'Status Autofill Canva tidak dikenal: '
                . $status
            );
        }

        throw new RuntimeException(
            'Autofill Canva belum selesai setelah '
            . $maxAttempts
            . ' percobaan.'
        );
    }
}