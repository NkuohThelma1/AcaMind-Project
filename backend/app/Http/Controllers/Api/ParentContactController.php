<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentContact\StoreParentContactRequest;
use App\Models\ParentContact;
use App\Services\ProgressReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->parentContacts);
    }

    public function store(StoreParentContactRequest $request): JsonResponse
    {
        $contact = $request->user()->parentContacts()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'phone_number' => $request->input('phone_number'),
            'relationship' => $request->input('relationship'),
            'locale' => $request->input('locale', 'en'),
            'is_active' => true,
        ]);

        return response()->json($contact, 201);
    }

    public function destroy(Request $request, ParentContact $parentContact): JsonResponse
    {
        abort_unless($parentContact->student_id === $request->user()->id, 403);

        $parentContact->update(['is_active' => false]);

        return response()->json(['message' => 'Parent contact removed.']);
    }

    public function sendReportNow(Request $request, ParentContact $parentContact, ProgressReportService $progressReportService): JsonResponse
    {
        abort_unless($parentContact->student_id === $request->user()->id, 403);

        $sent = $progressReportService->sendReportFor($parentContact);

        return response()->json([
            'message' => $sent ? 'Progress report sent.' : 'Could not send report — check mail configuration.',
            'sent' => $sent,
        ], $sent ? 200 : 422);
    }
}
