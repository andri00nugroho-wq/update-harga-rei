<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CanvaService
{
    
    public function getAuthorizationUrl(
        string $state,
        string $codeChallenge
    ): string {
        $query = http_build_query([
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 's256',

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

        return 'https://www.canva.com/api/oauth/authorize?'
            . $query;
    }

    
    public function exchangeCode(
        string $code,
        string $codeVerifier
    ): array {
        $response = Http::asForm()
            ->withBasicAuth(
                config('services.canva.client_id'),
                config('services.canva.client_secret')
            )
            ->post(
                'https://api.canva.com/rest/v1/oauth/token',
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

        return $response->json();
    }

    
    public function getDesignDataset(
        string $accessToken,
        string $designId
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(
                'https://api.canva.com/rest/v1/designs/'
                . urlencode($designId)
                . '/dataset'
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil dataset Canva: '
                . $response->body()
            );
        }

        return $response->json();
    }

   
    public function getDesign(
        string $accessToken,
        string $designId
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(
                'https://api.canva.com/rest/v1/designs/'
                . urlencode($designId)
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil detail Canva Design: '
                . $response->body()
            );
        }

        return $response->json();
    }

    public function createAutofill(
        string $accessToken,
        string $designId,
        array $data
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post(
                'https://api.canva.com/rest/v1/autofills',
                [
                    'type' => 'update_design',

                    'design_id' => $designId,

                    'data' => $data,
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Canva Autofill gagal: '
                . $response->body()
            );
        }

        return $response->json();
    }

   
    public function getAutofillJob(
        string $accessToken,
        string $jobId
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(
                'https://api.canva.com/rest/v1/autofills/'
                . urlencode($jobId)
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil status Autofill Canva: '
                . $response->body()
            );
        }

        return $response->json();
    }
}