<?php

namespace App\Http\Controllers;

use App\Services\CanvaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CanvaController extends Controller
{
    public function __construct(
        private CanvaService $canvaService
    ) {
    }

   
    public function connect(
        Request $request
    ): RedirectResponse {
        $state = Str::random(64);

        $codeVerifier = Str::random(128);

        $codeChallenge = rtrim(
            strtr(
                base64_encode(
                    hash(
                        'sha256',
                        $codeVerifier,
                        true
                    )
                ),
                '+/',
                '-_'
            ),
            '='
        );

        $request->session()->put(
            'canva_oauth_state',
            $state
        );

        $request->session()->put(
            'canva_code_verifier',
            $codeVerifier
        );

        $authorizationUrl =
            $this->canvaService->getAuthorizationUrl(
                $state,
                $codeChallenge
            );

        return redirect()->away(
            $authorizationUrl
        );
    }

  
    public function callback(
        Request $request
    ): RedirectResponse {
        $state = $request->query('state');

        $sessionState =
            $request->session()->pull(
                'canva_oauth_state'
            );

        $codeVerifier =
            $request->session()->pull(
                'canva_code_verifier'
            );

        if (
            !$state ||
            !$sessionState ||
            !hash_equals(
                $sessionState,
                $state
            )
        ) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'OAuth Canva tidak valid.'
                );
        }

        $code = $request->query('code');

        if (!$code) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'Kode OAuth Canva tidak ditemukan.'
                );
        }

        if (!$codeVerifier) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'Code verifier Canva tidak ditemukan.'
                );
        }

        try {
            $token =
                $this->canvaService->exchangeCode(
                    $code,
                    $codeVerifier
                );

            $request->session()->put(
                'canva_token',
                $token
            );

            return redirect()
                ->route('dashboard')
                ->with(
                    'success',
                    'Berhasil. Canva berhasil terhubung.'
                );

        } catch (Throwable $e) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function status(
        Request $request
    ): JsonResponse {
        $token =
            $request->session()->get(
                'canva_token'
            );

        $connected = false;

        if (is_array($token)) {
            $connected =
                !empty(
                    $token['access_token']
                );
        } elseif (is_string($token)) {
            $connected =
                trim($token) !== '';
        }

        return response()->json([
            'connected' => $connected,
        ]);
    }

    /**
     * Ambil dataset Autofill dari design
     * yang ada di config/services.php.
     */
    public function dataset(
        Request $request
    ): JsonResponse {
        $designId =
            config('services.canva.design_id');

        return $this->getDatasetForDesign(
            $request,
            $designId
        );
    }

    /**
     * Ambil dataset Autofill dari Design ID
     * yang diberikan melalui URL.
     *
     * Contoh:
     * /canva/dataset/DAHW22sktik
     */
    public function datasetByDesign(
        Request $request,
        string $designId
    ): JsonResponse {
        return $this->getDatasetForDesign(
            $request,
            $designId
        );
    }

    /**
     * Helper untuk mengambil dataset Canva.
     */
    private function getDatasetForDesign(
        Request $request,
        ?string $designId
    ): JsonResponse {
        $token =
            $request->session()->get(
                'canva_token'
            );

        $accessToken = null;

        if (is_array($token)) {
            $accessToken =
                $token['access_token'] ?? null;
        } elseif (is_string($token)) {
            $accessToken = $token;
        }

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Canva belum terhubung.',
            ], 401);
        }

        if (!$designId) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Design ID Canva belum diatur.',
            ], 400);
        }

        try {
            $dataset =
                $this->canvaService
                    ->getDesignDataset(
                        $accessToken,
                        $designId
                    );

            return response()->json([
                'success' => true,
                'design_id' => $designId,
                'dataset' => $dataset,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'design_id' => $designId,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}