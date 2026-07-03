<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\BulkImportQuestionsRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Question::query()->with('topic:id,name,code')->latest();

        if ($topicId = $request->integer('topic_id')) {
            $query->where('topic_id', $topicId);
        }

        if ($levelId = $request->integer('level_id')) {
            $query->where('level_id', $levelId);
        }

        return response()->json($query->paginate(30));
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $topic = Topic::findOrFail($request->integer('topic_id'));

        $question = Question::create([
            'topic_id' => $topic->id,
            'level_id' => $topic->level_id,
            'type' => 'mcq',
            'stem' => $request->string('stem'),
            'options' => array_filter($request->input('options')),
            'correct_option' => $request->string('correct_option'),
            'difficulty' => $request->integer('difficulty'),
            'explanation' => $request->input('explanation'),
            'marks' => $request->integer('marks', 1),
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);

        return response()->json($question, 201);
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $this->authoriseModification($request, $question);

        $data = $request->only(['stem', 'correct_option', 'difficulty', 'explanation', 'marks', 'is_active']);

        if ($request->has('topic_id')) {
            $topic = Topic::findOrFail($request->integer('topic_id'));
            $data['topic_id'] = $topic->id;
            $data['level_id'] = $topic->level_id;
        }

        if ($request->has('options')) {
            $data['options'] = array_filter($request->input('options'));
        }

        $question->update($data);

        return response()->json($question->fresh());
    }

    public function destroy(Request $request, Question $question): JsonResponse
    {
        $this->authoriseModification($request, $question);

        $question->update(['is_active' => false]);

        return response()->json(['message' => 'Question deactivated.']);
    }

    /**
     * Bulk import questions from a CSV with header row:
     * topic_code,stem,option_a,option_b,option_c,option_d,correct_option,difficulty,explanation
     */
    public function bulkImport(BulkImportQuestionsRequest $request): JsonResponse
    {
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map('trim', fgetcsv($handle) ?: []);

        $created = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) < count($header)) {
                $errors[] = "Row {$rowNumber}: column count does not match header.";
                continue;
            }

            $data = array_combine($header, $row);
            $topic = Topic::where('code', trim($data['topic_code'] ?? ''))->first();

            if (! $topic) {
                $errors[] = "Row {$rowNumber}: unknown topic_code '{$data['topic_code']}'.";
                continue;
            }

            $options = array_filter([
                'A' => trim($data['option_a'] ?? ''),
                'B' => trim($data['option_b'] ?? ''),
                'C' => trim($data['option_c'] ?? ''),
                'D' => trim($data['option_d'] ?? ''),
            ]);

            $correctOption = strtoupper(trim($data['correct_option'] ?? ''));

            if (count($options) < 2 || ! array_key_exists($correctOption, $options)) {
                $errors[] = "Row {$rowNumber}: invalid options or correct_option.";
                continue;
            }

            $difficulty = (int) ($data['difficulty'] ?? 3);

            Question::create([
                'topic_id' => $topic->id,
                'level_id' => $topic->level_id,
                'type' => 'mcq',
                'stem' => trim($data['stem'] ?? ''),
                'options' => $options,
                'correct_option' => $correctOption,
                'difficulty' => max(1, min(5, $difficulty)),
                'explanation' => trim($data['explanation'] ?? '') ?: null,
                'marks' => 1,
                'created_by' => $request->user()->id,
                'is_active' => true,
            ]);

            $created++;
        }

        fclose($handle);

        return response()->json(['created' => $created, 'errors' => $errors]);
    }

    private function authoriseModification(Request $request, Question $question): void
    {
        $user = $request->user();

        abort_if($user->role !== 'admin' && $question->created_by !== $user->id, 403, 'You can only modify questions you created.');
    }
}
