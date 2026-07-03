<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\StoreReactivationRequestRequest;
use App\Mail\TeacherReactivationRequestMail;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{
    /**
     * Documents are stored on the private 'local' disk (not the public disk
     * used for learning resources) since a national ID/CV is personal data -
     * only accessible later via an admin-gated, authenticated download route.
     */
    private function storeVerificationDocuments(RegisterRequest $request, User $user): void
    {
        $documents = [
            'national_id' => 'national_id_path',
            'degree_certificate' => 'degree_certificate_path',
            'teaching_qualification' => 'teaching_qualification_path',
            'cv' => 'cv_path',
        ];

        $paths = [];

        foreach ($documents as $field => $column) {
            if ($request->hasFile($field)) {
                $paths[$column] = $request->file($field)->store("teacher-applications/{$user->id}", 'local');
            }
        }

        if ($paths !== []) {
            $user->update($paths);
        }
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => $request->string('password'),
                'role' => $request->string('role'),
                'phone' => $request->input('phone'),
                'level_id' => $request->input('level_id'),
            ]);

            if ($request->string('role')->toString() === 'teacher_pending') {
                $this->storeVerificationDocuments($request, $user);
            }

            $freePlan = SubscriptionPlan::where('code', 'free')->first();

            if ($freePlan) {
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'started_at' => now(),
                ]);
            }

            return $user;
        });

        $token = $user->createToken('acamind')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Auth::validate(['email' => $request->string('email'), 'password' => $request->string('password')])) {
            throw new AuthenticationException('Invalid credentials.');
        }

        if ($user->isDeactivated()) {
            throw new HttpException(403, 'Your account has been deactivated due to inactivity. Please submit a reactivation request.');
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('acamind')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Public (no auth) - the whole point is that a deactivated teacher who is
     * locked out of the app can still reach the admin without logging in.
     */
    public function storeReactivationRequest(StoreReactivationRequestRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))
            ->where('role', 'teacher_verified')
            ->whereNotNull('deactivated_at')
            ->first();

        abort_unless($user !== null, 404, 'No deactivated teacher account was found with that email.');

        $adminEmail = config('services.admin.notification_email');

        if ($adminEmail) {
            Mail::to($adminEmail)->send(
                new TeacherReactivationRequestMail($user->name, $user->email, $request->string('message')->toString())
            );
        }

        return response()->json(['message' => 'Your reactivation request has been sent to the admin team.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
