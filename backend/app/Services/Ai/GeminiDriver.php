<?php

namespace App\Services\Ai;

use App\Contracts\AiChatResult;
use App\Contracts\AiServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Default AI provider - Google Gemini, chosen for its free developer tier.
 * Talks to the public Generative Language REST API directly (no SDK
 * dependency) so swapping providers later never requires a package change,
 * only a new class implementing AiServiceInterface.
 */
class GeminiDriver implements AiServiceInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    public function reply(string $systemPrompt, array $history): AiChatResult
    {
        $contents = array_map(
            fn (array $message) => [
                'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message['content']]],
            ],
            $history,
        );

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        try {
            $response = Http::timeout(20)
                ->withHeader('x-goog-api-key', $this->apiKey)
                // Windows' schannel TLS backend can fail to reach the certificate
                // revocation (CRL/OCSP) endpoint on some networks, which otherwise
                // aborts the handshake entirely before any HTTP request is sent.
                ->withOptions(['curl' => [CURLOPT_SSL_OPTIONS => CURLSSLOPT_NO_REVOKE]])
                ->post($url, [
                    'contents' => $contents,
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::channel('single')->warning('Gemini request failed', ['error' => $e->getMessage()]);

            return new AiChatResult(success: false, errorMessage: 'Could not reach the AI provider. Please try again shortly.');
        }

        if ($response->failed()) {
            Log::channel('single')->warning('Gemini returned an error', ['status' => $response->status(), 'body' => $response->body()]);

            return new AiChatResult(success: false, errorMessage: 'The AI provider returned an error. Please try again shortly.');
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! $text) {
            return new AiChatResult(success: false, errorMessage: 'The AI provider returned an empty response.');
        }

        return new AiChatResult(success: true, content: $text);
    }
}
