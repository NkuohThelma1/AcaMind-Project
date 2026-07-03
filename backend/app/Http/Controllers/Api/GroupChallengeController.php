<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PeerGroup\StoreGroupChallengeRequest;
use App\Models\GroupChallenge;
use App\Models\PeerGroup;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupChallengeController extends Controller
{
    public function index(Request $request, PeerGroup $peerGroup): JsonResponse
    {
        app(PeerGroupController::class)->ensureMember($request, $peerGroup);

        return response()->json($peerGroup->challenges()->with('topic')->latest('starts_at')->get());
    }

    public function store(StoreGroupChallengeRequest $request, PeerGroup $peerGroup): JsonResponse
    {
        app(PeerGroupController::class)->ensureMember($request, $peerGroup);

        $challenge = $peerGroup->challenges()->create([
            'topic_id' => $request->input('topic_id'),
            'starts_at' => $request->date('starts_at'),
            'ends_at' => $request->date('ends_at'),
            'question_count' => $request->integer('question_count', 10),
            'status' => 'active',
        ]);

        return response()->json($challenge, 201);
    }

    public function submit(Request $request, GroupChallenge $challenge): JsonResponse
    {
        app(PeerGroupController::class)->ensureMember($request, $challenge->peerGroup);

        $request->validate(['quiz_attempt_id' => ['required', 'integer', 'exists:quiz_attempts,id']]);

        $attempt = QuizAttempt::findOrFail($request->integer('quiz_attempt_id'));

        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_if($attempt->status !== 'completed', 422, 'Attempt must be completed before it can be submitted.');

        if ($challenge->topic_id && $attempt->topic_id !== $challenge->topic_id) {
            abort(422, 'This attempt is for a different topic than the challenge.');
        }

        $participant = $challenge->participants()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'quiz_attempt_id' => $attempt->id,
                'score_percent' => $attempt->score_percent,
                'completed_at' => now(),
            ]
        );

        return response()->json($participant);
    }

    public function leaderboard(Request $request, GroupChallenge $challenge): JsonResponse
    {
        app(PeerGroupController::class)->ensureMember($request, $challenge->peerGroup);

        $leaderboard = $challenge->participants()
            ->with('user:id,name')
            ->whereNotNull('score_percent')
            ->orderByDesc('score_percent')
            ->get()
            ->values()
            ->map(fn ($p, $rank) => [
                'rank' => $rank + 1,
                'student_name' => $p->user->name,
                'score_percent' => $p->score_percent,
                'completed_at' => $p->completed_at,
            ]);

        return response()->json($leaderboard);
    }
}
