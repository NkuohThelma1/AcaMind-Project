<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LearningResource\StoreLearningResourceRequest;
use App\Models\LearningResource;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningResourceController extends Controller
{
    /**
     * Approved resources visible to students, optionally filtered by topic - the
     * data source for the resource recommendation engine on the Study Plan page.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LearningResource::where('status', 'approved')->with('topic:id,name,code')->latest();

        if ($topicId = $request->integer('topic_id')) {
            $query->where('topic_id', $topicId);
        }

        if ($levelId = $request->integer('level_id')) {
            $query->where('level_id', $levelId);
        }

        return response()->json($query->get());
    }

    /**
     * A teacher's own uploads, across all review statuses, so they can track
     * what's pending, approved, or rejected.
     */
    public function mine(Request $request): JsonResponse
    {
        $resources = LearningResource::where('created_by', $request->user()->id)
            ->with('topic:id,name,code')
            ->latest()
            ->get();

        return response()->json($resources);
    }

    public function store(StoreLearningResourceRequest $request): JsonResponse
    {
        $topic = Topic::findOrFail($request->integer('topic_id'));

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('learning-resources', 'public');
        }

        $resource = LearningResource::create([
            'topic_id' => $topic->id,
            'level_id' => $topic->level_id,
            'created_by' => $request->user()->id,
            'title' => $request->string('title'),
            'description' => $request->input('description'),
            'type' => $request->string('type'),
            'url' => $request->input('url'),
            'file_path' => $filePath,
            'status' => 'pending',
        ]);

        return response()->json($resource, 201);
    }

    public function destroy(Request $request, LearningResource $learningResource): JsonResponse
    {
        abort_unless($learningResource->created_by === $request->user()->id, 403);

        $learningResource->delete();

        return response()->json(['message' => 'Resource removed.']);
    }
}
