<?php

namespace App\Services;

use App\Contracts\AiServiceInterface;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\TopicMastery;
use App\Models\User;

class AiCoachService
{
    public function __construct(
        private readonly AiServiceInterface $aiService,
    ) {
    }

    public function reply(User $user, AiConversation $conversation, string $userMessage): AiMessage
    {
        $conversation->messages()->create(['role' => 'user', 'content' => $userMessage]);

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        $result = $this->aiService->reply($this->buildSystemPrompt($user), $history);

        $content = $result->success
            ? $result->content
            : 'Sorry, I ran into a problem answering that: '.$result->errorMessage;

        return $conversation->messages()->create(['role' => 'assistant', 'content' => $content]);
    }

    /**
     * Grounds the assistant as a Biology GCE tutor and gives it the student's
     * current weak topics, so its answers connect back to what they actually
     * need help with rather than being generic.
     */
    private function buildSystemPrompt(User $user): string
    {
        $levelName = $user->level?->name ?? 'GCE';

        $weakTopics = TopicMastery::where('user_id', $user->id)
            ->where('mastery_score', '<', 50)
            ->with('topic:id,name')
            ->orderBy('mastery_score')
            ->take(5)
            ->get()
            ->map(fn (TopicMastery $m) => $m->topic?->name)
            ->filter()
            ->implode(', ');

        $prompt = "You are AcaMind's AI Academic Coach: a friendly, encouraging Biology tutor for Cameroon {$levelName} "
            .'GCE students. Explain concepts clearly and simply, tailored to the Cameroon GCE Biology syllabus. '
            .'Keep answers concise and exam-focused, using short paragraphs or bullet points where helpful.';

        if ($weakTopics !== '') {
            $prompt .= " This student is currently weak in: {$weakTopics}. Where relevant, relate your answers back to these topics.";
        }

        $prompt .= ' If asked something unrelated to Biology or study skills, gently redirect the conversation back to academics.';

        return $prompt;
    }
}
