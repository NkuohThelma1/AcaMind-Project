<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveTeacherRequest;
use App\Http\Requests\Admin\RejectTeacherRequest;
use App\Mail\TeacherApplicationAcceptedMail;
use App\Mail\TeacherApplicationRejectedMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherVerificationController extends Controller
{
    private const DOCUMENT_TYPES = ['national_id', 'degree_certificate', 'teaching_qualification', 'cv'];

    public function index(): JsonResponse
    {
        $teachers = User::where('role', 'teacher_pending')->orderBy('created_at')->get();

        $teachers->each(function (User $teacher) {
            foreach (self::DOCUMENT_TYPES as $type) {
                $teacher->setAttribute('has_'.$type, $teacher->verificationDocumentPath($type) !== null);
            }
        });

        return response()->json($teachers);
    }

    /**
     * Grants teacher access immediately, as before, and additionally emails
     * an interview invite with the admin-supplied meet link. Real interview
     * scheduling (calendar booking, gating access on interview outcome) is
     * intentionally out of scope for now - see the acceptance email.
     */
    public function approve(ApproveTeacherRequest $request, User $user): JsonResponse
    {
        abort_unless($user->role === 'teacher_pending', 422, 'User is not pending teacher verification.');

        $user->update(['role' => 'teacher_verified']);

        Mail::to($user->email)->send(
            new TeacherApplicationAcceptedMail(
                $user->name,
                $request->string('meet_link')->toString(),
                Carbon::parse($request->string('interview_at')->toString())
            )
        );

        return response()->json($user);
    }

    /**
     * Rejected applicants are deleted outright rather than left as inactive
     * accounts - the role is granted on qualifications/documents, so a
     * rejection means "fix your documents and re-apply", not "wait in limbo".
     */
    public function reject(RejectTeacherRequest $request, User $user): JsonResponse
    {
        abort_unless($user->role === 'teacher_pending', 422, 'User is not pending teacher verification.');

        $name = $user->name;
        $email = $user->email;
        $reason = $request->input('reason');

        DB::transaction(function () use ($user) {
            foreach (self::DOCUMENT_TYPES as $type) {
                if ($path = $user->verificationDocumentPath($type)) {
                    Storage::disk('local')->delete($path);
                }
            }

            $user->subscriptions()->delete();
            $user->delete();
        });

        Mail::to($email)->send(new TeacherApplicationRejectedMail($name, $reason));

        return response()->json(['message' => 'Application rejected and account removed.']);
    }

    /**
     * Streams a teacher applicant's verification document. Only reachable by
     * admins (see the route group) - the document was stored on the private
     * disk specifically so it's never exposed via a public URL.
     */
    public function downloadDocument(User $user, string $type): StreamedResponse
    {
        abort_unless(in_array($type, self::DOCUMENT_TYPES, true), 404);

        $path = $user->verificationDocumentPath($type);

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404, 'Document not found.');

        return Storage::disk('local')->download($path, $type.'-'.$user->id.'.'.pathinfo($path, PATHINFO_EXTENSION));
    }

    public function deactivated(): JsonResponse
    {
        return response()->json(
            User::where('role', 'teacher_verified')
                ->whereNotNull('deactivated_at')
                ->orderBy('deactivated_at')
                ->get()
        );
    }

    public function reactivate(Request $request, User $user): JsonResponse
    {
        abort_unless($user->isDeactivated(), 422, 'This account is not deactivated.');

        $user->update(['deactivated_at' => null, 'last_login_at' => now()]);

        return response()->json($user);
    }
}
