<?php

namespace App\Console\Commands;

use App\Mail\TeacherAccountDeactivatedMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class DeactivateInactiveTeachers extends Command
{
    /**
     * A verified teacher who hasn't logged in for this many days is
     * considered inactive and is automatically deactivated - chosen as a
     * reasonable middle ground (long enough to cover a school holiday,
     * short enough to keep the teacher roster meaningful).
     */
    private const INACTIVITY_DAYS_THRESHOLD = 60;

    protected $signature = 'teachers:deactivate-inactive';

    protected $description = 'Deactivate verified teacher accounts with no login activity for 60+ days';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subDays(self::INACTIVITY_DAYS_THRESHOLD);

        $inactiveTeachers = User::where('role', 'teacher_verified')
            ->whereNull('deactivated_at')
            ->where(function ($query) use ($cutoff) {
                $query->where('last_login_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        $query->whereNull('last_login_at')->where('created_at', '<', $cutoff);
                    });
            })
            ->get();

        foreach ($inactiveTeachers as $teacher) {
            $inactivityDays = $teacher->last_login_at
                ? (int) floor((now()->getTimestamp() - $teacher->last_login_at->getTimestamp()) / 86400)
                : (int) floor((now()->getTimestamp() - $teacher->created_at->getTimestamp()) / 86400);

            $teacher->update(['deactivated_at' => now()]);

            Mail::to($teacher->email)->send(new TeacherAccountDeactivatedMail($teacher->name, $inactivityDays));
        }

        $this->info('Deactivated '.$inactiveTeachers->count().' inactive teacher account(s).');

        return self::SUCCESS;
    }
}
