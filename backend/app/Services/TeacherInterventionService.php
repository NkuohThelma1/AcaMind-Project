<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Topic;
use App\Models\TopicMastery;
use App\Models\User;
use Illuminate\Support\Collection;

class TeacherInterventionService
{
    private const WEAK_MASTERY_THRESHOLD = 50;

    private const INACTIVITY_DAYS_THRESHOLD = 14;

    private const MAX_WEAK_TOPICS_LISTED = 3;

    private const MIN_STUDENTS_FOR_CLASS_WEAK_TOPIC = 2;

    /**
     * Turn the raw mastery/engagement numbers a teacher already sees into concrete,
     * explainable next actions - rule-based (no black box) so a teacher can see
     * exactly why a student or topic was flagged and what to do about it.
     */
    public function recommendationsForClass(SchoolClass $class): array
    {
        $studentIds = $class->classStudents()->pluck('student_id');
        $topics = Topic::where('level_id', $class->level_id)->orderBy('order')->get();

        $masteryByStudent = TopicMastery::whereIn('user_id', $studentIds)
            ->whereIn('topic_id', $topics->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $studentInterventions = User::whereIn('id', $studentIds)->get()
            ->map(fn (User $student) => $this->interventionForStudent($student, $masteryByStudent->get($student->id, collect()), $topics))
            ->filter()
            ->values()
            ->all();

        $topicInterventions = $this->classWideWeakTopics($topics, $masteryByStudent);

        return [
            'student_interventions' => $studentInterventions,
            'topic_interventions' => $topicInterventions,
        ];
    }

    private function interventionForStudent(User $student, Collection $masteryRows, Collection $topics): ?array
    {
        $avgMastery = $masteryRows->isEmpty() ? null : $masteryRows->avg('mastery_score');
        $inactiveDays = $student->last_activity_date
            ? (int) floor((now()->getTimestamp() - $student->last_activity_date->getTimestamp()) / 86400)
            : null;

        $reasons = [];
        $actions = [];

        if ($avgMastery !== null && $avgMastery < self::WEAK_MASTERY_THRESHOLD) {
            $weakTopicNames = $masteryRows
                ->sortBy('mastery_score')
                ->take(self::MAX_WEAK_TOPICS_LISTED)
                ->map(fn (TopicMastery $row) => $topics->firstWhere('id', $row->topic_id)?->name)
                ->filter()
                ->values();

            $reasons[] = 'Average topic mastery is '.round($avgMastery, 1).'%, below the 50% pass threshold.';
            $actions[] = $weakTopicNames->isNotEmpty()
                ? 'Assign targeted practice quizzes on: '.$weakTopicNames->implode(', ').'.'
                : 'Assign targeted practice quizzes on the topics covered so far.';
        }

        if ($inactiveDays === null) {
            $reasons[] = 'Has not started any quiz activity yet.';
            $actions[] = 'Encourage the student to take a diagnostic topic quiz to begin tracking progress.';
        } elseif ($inactiveDays > self::INACTIVITY_DAYS_THRESHOLD) {
            $reasons[] = "No quiz activity in {$inactiveDays} day(s).";
            $actions[] = 'Reach out directly (or via the parent contact) to re-engage the student.';
        }

        if (empty($reasons)) {
            return null;
        }

        return [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'average_mastery' => $avgMastery !== null ? round($avgMastery, 1) : null,
            'inactive_days' => $inactiveDays,
            'reasons' => $reasons,
            'recommended_actions' => $actions,
        ];
    }

    private function classWideWeakTopics(Collection $topics, Collection $masteryByStudent): array
    {
        $masteryRowsByTopic = $masteryByStudent->flatten(1)->groupBy('topic_id');

        return $topics
            ->map(function (Topic $topic) use ($masteryRowsByTopic) {
                $rows = $masteryRowsByTopic->get($topic->id, collect());

                if ($rows->count() < self::MIN_STUDENTS_FOR_CLASS_WEAK_TOPIC) {
                    return null;
                }

                $avgMastery = $rows->avg('mastery_score');

                if ($avgMastery >= self::WEAK_MASTERY_THRESHOLD) {
                    return null;
                }

                return [
                    'topic_id' => $topic->id,
                    'topic_name' => $topic->name,
                    'average_mastery' => round($avgMastery, 1),
                    'students_affected' => $rows->count(),
                    'recommended_action' => "Consider a whole-class review session or reteaching \"{$topic->name}\" - "
                        .$rows->count().' student(s) are averaging below 50% mastery on this topic.',
                ];
            })
            ->filter()
            ->sortBy('average_mastery')
            ->values()
            ->all();
    }
}
