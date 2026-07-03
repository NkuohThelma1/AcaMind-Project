<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupMessage\StoreGroupMessageRequest;
use App\Http\Requests\PeerGroup\StorePeerGroupRequest;
use App\Models\PeerGroup;
use App\Models\PeerGroupMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PeerGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = PeerGroupMember::where('user_id', $request->user()->id)
            ->with('peerGroup')
            ->get()
            ->pluck('peerGroup');

        return response()->json($groups);
    }

    public function store(StorePeerGroupRequest $request): JsonResponse
    {
        $group = PeerGroup::create([
            'name' => $request->string('name'),
            'level_id' => $request->integer('level_id'),
            'join_code' => $this->generateUniqueJoinCode(),
            'created_by' => $request->user()->id,
        ]);

        $group->members()->create(['user_id' => $request->user()->id, 'joined_at' => now()]);

        return response()->json($group, 201);
    }

    public function join(Request $request): JsonResponse
    {
        $request->validate(['join_code' => ['required', 'string', 'exists:peer_groups,join_code']]);

        $group = PeerGroup::where('join_code', $request->string('join_code'))->firstOrFail();

        $membership = $group->members()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['joined_at' => now()]
        );

        return response()->json($membership->load('peerGroup'), 201);
    }

    public function show(Request $request, PeerGroup $peerGroup): JsonResponse
    {
        $this->ensureMember($request, $peerGroup);

        return response()->json($peerGroup->load(['members.user:id,name', 'challenges']));
    }

    public function messages(Request $request, PeerGroup $peerGroup): JsonResponse
    {
        $this->ensureMember($request, $peerGroup);

        return response()->json($peerGroup->messages()->with('user:id,name')->get());
    }

    public function sendMessage(StoreGroupMessageRequest $request, PeerGroup $peerGroup): JsonResponse
    {
        $this->ensureMember($request, $peerGroup);

        $message = $peerGroup->messages()->create([
            'user_id' => $request->user()->id,
            'content' => $request->string('content'),
        ]);

        return response()->json($message->load('user:id,name'), 201);
    }

    public function ensureMember(Request $request, PeerGroup $peerGroup): void
    {
        $isMember = $peerGroup->members()->where('user_id', $request->user()->id)->exists();

        if (! $isMember) {
            throw ValidationException::withMessages(['peer_group' => 'You are not a member of this group.'])
                ->status(403);
        }
    }

    private function generateUniqueJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (PeerGroup::where('join_code', $code)->exists());

        return $code;
    }
}
