<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaperSubmission\StorePaperSubmissionRequest;
use App\Models\PaperSubmission;
use App\Services\SubscriptionGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaperSubmissionController extends Controller
{
    public function __construct(private readonly SubscriptionGateService $subscriptionGate)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->paperSubmissions()->latest()->get());
    }

    public function store(StorePaperSubmissionRequest $request): JsonResponse
    {
        abort_unless(
            $this->subscriptionGate->allows($request->user(), 'paper_marking'),
            403,
            'Paper marking is a Premium+ feature. Please upgrade to submit papers for marking.'
        );

        $submission = $request->user()->paperSubmissions()->create([
            'topic_id' => $request->input('topic_id'),
            'question_reference' => $request->input('question_reference'),
            'submitted_text' => $request->input('submitted_text'),
            'file_path' => $request->input('file_path'),
            'status' => 'pending_manual_review',
        ]);

        return response()->json($submission, 201);
    }

    public function grade(Request $request, PaperSubmission $paperSubmission): JsonResponse
    {
        $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback_text' => ['required', 'string'],
        ]);

        $paperSubmission->update([
            'status' => 'graded',
            'graded_by' => $request->user()->id,
            'score' => $request->input('score'),
            'feedback_text' => $request->input('feedback_text'),
            'graded_at' => now(),
        ]);

        return response()->json($paperSubmission);
    }
}
