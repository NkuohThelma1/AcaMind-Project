<?php

namespace App\Services\Ai;

use App\Contracts\AiChatResult;
use App\Contracts\AiServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Fallback used when no AI provider API key is configured. Returns a canned,
 * clearly-labelled response so the chat feature stays usable end-to-end in
 * development or during a demo without any external dependency.
 */
class LogAiDriver implements AiServiceInterface
{
    public function reply(string $systemPrompt, array $history): AiChatResult
    {
        Log::channel('single')->info('AI chat (log driver - no provider configured)', [
            'system_prompt' => $systemPrompt,
            'history' => $history,
        ]);

        $lastUserMessage = collect($history)->last(fn (array $m) => $m['role'] === 'user')['content'] ?? '';

        return new AiChatResult(
            success: true,
            content: "[No AI provider configured yet] I received your message: \"{$lastUserMessage}\". "
                .'Once a GEMINI_API_KEY (or another provider) is set in the backend .env, I\'ll respond with real AI-generated guidance.',
        );
    }
}
