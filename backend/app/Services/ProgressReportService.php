<?php

namespace App\Services;

use App\Mail\WeeklyProgressReportMail;
use App\Models\ParentContact;
use App\Models\ProgressReport;
use App\Models\StudyPlan;
use App\Models\TopicMastery;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ProgressReportService
{
    public function __construct(
        private readonly SubscriptionGateService $subscriptionGate,
    ) {
    }

    public function sendWeeklyReports(): int
    {
        $periodEnd = Carbon::now();
        $periodStart = $periodEnd->copy()->subDays(7);
        $sentCount = 0;

        foreach (ParentContact::where('is_active', true)->whereNotNull('email')->with('student')->get() as $parentContact) {
            if (! $this->subscriptionGate->allows($parentContact->student, 'parent_email')) {
                continue;
            }

            if ($this->sendReportFor($parentContact, $periodStart, $periodEnd)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    public function sendReportFor(ParentContact $parentContact, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): bool
    {
        $periodEnd ??= Carbon::now();
        $periodStart ??= $periodEnd->copy()->subDays(7);

        $content = $this->buildReportContent($parentContact->student, $periodStart, $periodEnd);

        $report = ProgressReport::create([
            'student_id' => $parentContact->student_id,
            'parent_id' => $parentContact->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'content' => $content,
            'status' => 'pending',
        ]);

        try {
            Mail::to($parentContact->email)->send(new WeeklyProgressReportMail($report));
            $report->update(['status' => 'sent', 'sent_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            $report->update(['status' => 'failed']);

            return false;
        }
    }

    private function buildReportContent(User $student, Carbon $periodStart, Carbon $periodEnd): string
    {
        $quizzesTaken = $student->quizAttempts()->where('status', 'completed')
            ->whereBetween('completed_at', [$periodStart, $periodEnd])->count();

        $masteryRows = TopicMastery::where('user_id', $student->id)->get();
        $avgMastery = $masteryRows->isEmpty() ? null : round($masteryRows->avg('mastery_score'), 1);
        $weakTopicCount = $masteryRows->filter->isWeak()->count();

        $activePlan = StudyPlan::where('user_id', $student->id)->where('status', 'active')->with('items')->first();
        $planProgress = $activePlan ? $activePlan->items->where('is_completed', true)->count() . '/' . $activePlan->items->count() : null;

        $latestPrediction = $student->gradePredictions()->latest('created_at')->first();

        $lines = [];
        $lines[] = "Quizzes completed: {$quizzesTaken}.";

        if ($avgMastery !== null) {
            $lines[] = "Average topic mastery: {$avgMastery}% ({$weakTopicCount} topic(s) still weak).";
        }

        if ($planProgress !== null) {
            $lines[] = "Study plan progress: {$planProgress} topics covered.";
        }

        if ($latestPrediction) {
            $lines[] = "Latest predicted GCE grade: {$latestPrediction->predicted_grade} ({$latestPrediction->score_percent}%).";
        }

        return implode(' ', $lines);
    }
}
