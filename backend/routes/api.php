<?php

use App\Http\Controllers\Api\Admin\LearningResourceModerationController;
use App\Http\Controllers\Api\Admin\TeacherVerificationController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\GradePredictionController;
use App\Http\Controllers\Api\GroupChallengeController;
use App\Http\Controllers\Api\LearningResourceController;
use App\Http\Controllers\Api\MasteryController;
use App\Http\Controllers\Api\PaperSubmissionController;
use App\Http\Controllers\Api\ParentContactController;
use App\Http\Controllers\Api\PeerGroupController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\StudyPlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TeacherAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/reactivation-request', [AuthController::class, 'storeReactivationRequest']);
Route::get('/subscription-plans', [SubscriptionController::class, 'plans']);
Route::get('/levels', [CatalogController::class, 'levels']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/teachers/pending', [TeacherVerificationController::class, 'index']);
        Route::post('/teachers/{user}/approve', [TeacherVerificationController::class, 'approve']);
        Route::post('/teachers/{user}/reject', [TeacherVerificationController::class, 'reject']);
        Route::get('/teachers/{user}/documents/{type}', [TeacherVerificationController::class, 'downloadDocument']);
        Route::get('/teachers/deactivated', [TeacherVerificationController::class, 'deactivated']);
        Route::post('/teachers/{user}/reactivate', [TeacherVerificationController::class, 'reactivate']);

        Route::get('/learning-resources/pending', [LearningResourceModerationController::class, 'index']);
        Route::post('/learning-resources/{learningResource}/approve', [LearningResourceModerationController::class, 'approve']);
        Route::post('/learning-resources/{learningResource}/reject', [LearningResourceModerationController::class, 'reject']);
    });

    Route::get('/topics', [CatalogController::class, 'topics']);

    Route::get('/quiz-attempts', [QuizController::class, 'index']);
    Route::get('/quiz-attempts/{attempt}', [QuizController::class, 'show']);
    Route::post('/quiz-attempts/{attempt}/answer', [QuizController::class, 'answer']);
    Route::post('/quiz-attempts/{attempt}/abandon', [QuizController::class, 'abandon']);
    Route::post('/quizzes/topic-practice', [QuizController::class, 'startTopicPractice']);
    Route::post('/quizzes/gce-simulation', [QuizController::class, 'startGceSimulation']);

    Route::get('/mastery', [MasteryController::class, 'index']);

    Route::get('/study-plan', [StudyPlanController::class, 'show']);
    Route::get('/study-plan/recommended-resources', [StudyPlanController::class, 'recommendedResources']);
    Route::post('/study-plan/items/{item}/complete', [StudyPlanController::class, 'completeItem']);

    Route::get('/grade-predictions', [GradePredictionController::class, 'index']);

    Route::get('/gamification/summary', [GamificationController::class, 'summary']);

    Route::get('/learning-resources', [LearningResourceController::class, 'index']);

    Route::get('/ai-conversations', [AiChatController::class, 'index']);
    Route::post('/ai-conversations', [AiChatController::class, 'store']);
    Route::get('/ai-conversations/{aiConversation}', [AiChatController::class, 'show']);
    Route::post('/ai-conversations/{aiConversation}/messages', [AiChatController::class, 'sendMessage']);

    Route::get('/parent-contacts', [ParentContactController::class, 'index']);
    Route::post('/parent-contacts', [ParentContactController::class, 'store']);
    Route::delete('/parent-contacts/{parentContact}', [ParentContactController::class, 'destroy']);
    Route::post('/parent-contacts/{parentContact}/send-report', [ParentContactController::class, 'sendReportNow']);

    Route::post('/classes/join', [ClassController::class, 'join'])->middleware('role:student');
    Route::get('/classes/mine', [ClassController::class, 'mine'])->middleware('role:student');
    Route::get('/classes/{class}', [ClassController::class, 'show']);
    Route::get('/classes/{class}/messages', [ClassController::class, 'messages']);
    Route::post('/classes/{class}/messages', [ClassController::class, 'sendMessage']);

    Route::middleware('role:teacher_verified')->group(function () {
        Route::get('/classes', [ClassController::class, 'index']);
        Route::post('/classes', [ClassController::class, 'store']);
        Route::get('/classes/{class}/analytics', [TeacherAnalyticsController::class, 'analytics']);
        Route::get('/classes/{class}/students/{student}', [TeacherAnalyticsController::class, 'studentDetail']);

        Route::get('/learning-resources/mine', [LearningResourceController::class, 'mine']);
        Route::post('/learning-resources', [LearningResourceController::class, 'store']);
        Route::delete('/learning-resources/{learningResource}', [LearningResourceController::class, 'destroy']);
    });

    Route::get('/peer-groups', [PeerGroupController::class, 'index']);
    Route::post('/peer-groups', [PeerGroupController::class, 'store']);
    Route::post('/peer-groups/join', [PeerGroupController::class, 'join']);
    Route::get('/peer-groups/{peerGroup}', [PeerGroupController::class, 'show']);
    Route::get('/peer-groups/{peerGroup}/messages', [PeerGroupController::class, 'messages']);
    Route::post('/peer-groups/{peerGroup}/messages', [PeerGroupController::class, 'sendMessage']);
    Route::get('/peer-groups/{peerGroup}/challenges', [GroupChallengeController::class, 'index']);
    Route::post('/peer-groups/{peerGroup}/challenges', [GroupChallengeController::class, 'store']);

    Route::post('/group-challenges/{challenge}/submit', [GroupChallengeController::class, 'submit']);
    Route::get('/group-challenges/{challenge}/leaderboard', [GroupChallengeController::class, 'leaderboard']);

    Route::get('/subscription', [SubscriptionController::class, 'current']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);

    Route::get('/paper-submissions', [PaperSubmissionController::class, 'index']);
    Route::post('/paper-submissions', [PaperSubmissionController::class, 'store']);
    Route::post('/paper-submissions/{paperSubmission}/grade', [PaperSubmissionController::class, 'grade'])
        ->middleware('role:teacher_verified');

    Route::middleware('role:teacher_verified,admin')->group(function () {
        Route::get('/questions', [QuestionController::class, 'index']);
        Route::post('/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{question}', [QuestionController::class, 'update']);
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);
        Route::post('/questions/bulk-import', [QuestionController::class, 'bulkImport']);
    });
});
