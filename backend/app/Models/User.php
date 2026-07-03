<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'password', 'role', 'phone', 'level_id',
    'national_id_path', 'degree_certificate_path', 'teaching_qualification_path', 'cv_path',
    'last_login_at', 'deactivated_at',
])]
#[Hidden([
    'password', 'remember_token',
    'national_id_path', 'degree_certificate_path', 'teaching_qualification_path', 'cv_path',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_activity_date' => 'date',
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function isTeacherVerified(): bool
    {
        return $this->role === 'teacher_verified';
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Maps a public document type key to its storage path column, so admin
     * verification endpoints never need to reference raw column names.
     */
    public function verificationDocumentPath(string $type): ?string
    {
        return match ($type) {
            'national_id' => $this->national_id_path,
            'degree_certificate' => $this->degree_certificate_path,
            'teaching_qualification' => $this->teaching_qualification_path,
            'cv' => $this->cv_path,
            default => null,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function topicMastery(): HasMany
    {
        return $this->hasMany(TopicMastery::class);
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function gradePredictions(): HasMany
    {
        return $this->hasMany(GradePrediction::class);
    }

    public function parentContacts(): HasMany
    {
        return $this->hasMany(ParentContact::class, 'student_id');
    }

    public function classesTaught(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function classEnrollments(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'student_id');
    }

    public function pointsLedger(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->latest('started_at')
            ->first();
    }

    public function paperSubmissions(): HasMany
    {
        return $this->hasMany(PaperSubmission::class, 'student_id');
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }
}
