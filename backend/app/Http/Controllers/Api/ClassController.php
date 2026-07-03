<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\JoinClassRequest;
use App\Http\Requests\Classes\StoreClassRequest;
use App\Http\Requests\GroupMessage\StoreGroupMessageRequest;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $classes = $request->user()->classesTaught()
            ->withCount('classStudents')
            ->with('level')
            ->get();

        return response()->json($classes);
    }

    /**
     * Classes a student has joined - the student-facing counterpart to
     * index(), which only lists classes a teacher owns.
     */
    public function mine(Request $request): JsonResponse
    {
        $classes = $request->user()->classEnrollments()
            ->with(['schoolClass.level', 'schoolClass.teacher:id,name'])
            ->get()
            ->pluck('schoolClass');

        return response()->json($classes);
    }

    public function store(StoreClassRequest $request): JsonResponse
    {
        $class = SchoolClass::create([
            'teacher_id' => $request->user()->id,
            'level_id' => $request->integer('level_id'),
            'name' => $request->string('name'),
            'join_code' => $this->generateUniqueJoinCode(),
        ]);

        return response()->json($class, 201);
    }

    public function show(Request $request, SchoolClass $class): JsonResponse
    {
        $this->ensureTeacherOrEnrolled($request, $class);

        return response()->json(
            $class->load(['level', 'teacher:id,name', 'classStudents.student:id,name,email'])
        );
    }

    public function join(JoinClassRequest $request): JsonResponse
    {
        $class = SchoolClass::where('join_code', $request->string('join_code'))->firstOrFail();

        $enrollment = $class->classStudents()->firstOrCreate(
            ['student_id' => $request->user()->id],
            ['joined_at' => now()]
        );

        return response()->json($enrollment->load('schoolClass'), 201);
    }

    public function messages(Request $request, SchoolClass $class): JsonResponse
    {
        $this->ensureTeacherOrEnrolled($request, $class);

        return response()->json($class->messages()->with('user:id,name')->get());
    }

    public function sendMessage(StoreGroupMessageRequest $request, SchoolClass $class): JsonResponse
    {
        $this->ensureTeacherOrEnrolled($request, $class);

        $message = $class->messages()->create([
            'user_id' => $request->user()->id,
            'content' => $request->string('content'),
        ]);

        return response()->json($message->load('user:id,name'), 201);
    }

    private function ensureTeacherOrEnrolled(Request $request, SchoolClass $class): void
    {
        $user = $request->user();
        $isTeacher = $class->teacher_id === $user->id;
        $isEnrolled = ! $isTeacher && $class->classStudents()->where('student_id', $user->id)->exists();

        abort_unless($isTeacher || $isEnrolled, 403, 'You do not have access to this class.');
    }

    private function generateUniqueJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (SchoolClass::where('join_code', $code)->exists());

        return $code;
    }
}
