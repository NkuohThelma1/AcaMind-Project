<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Topic;
use App\Models\TopicMastery;
use App\Models\User;
use App\Services\TeacherInterventionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherAnalyticsController extends Controller
{
    public function __construct(
        private readonly TeacherInterventionService $teacherInterventions,
    ) {
    }

    public function analytics(Request $request, SchoolClass $class): JsonResponse
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);

        $studentIds = $class->classStudents()->pluck('student_id');

        $topics = Topic::where('level_id', $class->level_id)->orderBy('order')->get();

        $masteryByTopic = TopicMastery::whereIn('user_id', $studentIds)
            ->whereIn('topic_id', $topics->pluck('id'))
            ->get()
            ->groupBy('topic_id');

        $heatmap = $topics->map(function (Topic $topic) use ($masteryByTopic) {
            $rows = $masteryByTopic->get($topic->id, collect());

            return [
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'average_mastery' => $rows->isEmpty() ? null : round($rows->avg('mastery_score'), 1),
                'students_with_data' => $rows->count(),
            ];
        });

        $engagement = $class->classStudents()
            ->with('student:id,name,last_activity_date')
            ->get()
            ->map(function ($enrollment) {
                $student = $enrollment->student;

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'quiz_attempts_completed' => $student->quizAttempts()->where('status', 'completed')->count(),
                    'last_activity_date' => $student->last_activity_date,
                ];
            });

        $interventions = $this->teacherInterventions->recommendationsForClass($class);

        return response()->json([
            'class' => ['id' => $class->id, 'name' => $class->name, 'student_count' => $studentIds->count()],
            'topic_mastery_heatmap' => $heatmap,
            'engagement' => $engagement,
            'student_interventions' => $interventions['student_interventions'],
            'topic_interventions' => $interventions['topic_interventions'],
        ]);
    }

    public function studentDetail(Request $request, SchoolClass $class, User $student): JsonResponse
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);
        abort_unless($class->classStudents()->where('student_id', $student->id)->exists(), 404);

        return response()->json([
            'student' => $student->only(['id', 'name', 'email', 'total_points', 'current_streak_days']),
            'mastery' => $student->topicMastery()->with('topic:id,name')->orderBy('mastery_score')->get(),
            'recent_attempts' => $student->quizAttempts()->where('status', 'completed')->latest('completed_at')->take(10)->get(),
            'active_study_plan' => $student->studyPlans()->where('level_id', $class->level_id)->where('status', 'active')->with('items.topic')->first(),
        ]);
    }
}
