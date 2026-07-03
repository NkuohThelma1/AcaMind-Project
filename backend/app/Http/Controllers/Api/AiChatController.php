<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiChat\SendMessageRequest;
use App\Models\AiConversation;
use App\Services\AiCoachService;
use App\Services\SubscriptionGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        private readonly AiCoachService $aiCoach,
        private readonly SubscriptionGateService $subscriptionGate,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()->aiConversations()
            ->withCount('messages')
            ->latest('updated_at')
            ->get();

        return response()->json($conversations);
    }

    public function store(Request $request): JsonResponse
    {
        $conversation = $request->user()->aiConversations()->create([
            'title' => 'New conversation',
        ]);

        return response()->json($conversation, 201);
    }

    public function show(Request $request, AiConversation $aiConversation): JsonResponse
    {
        abort_unless($aiConversation->user_id === $request->user()->id, 403);

        return response()->json($aiConversation->load('messages'));
    }

    public function sendMessage(SendMessageRequest $request, AiConversation $aiConversation): JsonResponse
    {
        abort_unless($aiConversation->user_id === $request->user()->id, 403);

        abort_unless(
            $this->subscriptionGate->allows($request->user(), 'ai_coach_messages_per_day'),
            403,
            "You've reached your plan's daily AI coach message limit. Please upgrade to continue."
        );

        $userMessage = $request->string('message')->toString();

        if ($aiConversation->title === 'New conversation') {
            $aiConversation->update(['title' => str($userMessage)->limit(60)->toString()]);
        }

        $assistantMessage = $this->aiCoach->reply($request->user(), $aiConversation, $userMessage);
        $this->subscriptionGate->recordUsage($request->user(), 'ai_coach_messages_per_day');

        $aiConversation->touch();

        return response()->json($assistantMessage);
    }
}
