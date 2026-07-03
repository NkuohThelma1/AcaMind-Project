<?php

namespace App\Contracts;

/**
 * Swap point for the AI academic coach's underlying model provider. The
 * default (Gemini, free tier) can be replaced with Claude, OpenAI, or any
 * other provider by adding a new driver here and rebinding it in
 * AppServiceProvider - nothing else in the app depends on which provider is
 * active.
 */
interface AiServiceInterface
{
    /**
     * @param  string  $systemPrompt  Grounding instructions (tutor persona, student context).
     * @param  array<int, array{role: string, content: string}>  $history  Prior turns, oldest first. role is 'user' or 'assistant'.
     */
    public function reply(string $systemPrompt, array $history): AiChatResult;
}
