<?php

namespace App\Services;

use App\Models\Teleconsult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeleFormsStoreFetchService
{
    private const REQUESTER = 'requester';
    private const ACCEPTER  = 'accepter';

    /**
     * What the logged-in user may do with this meeting's forms, worked out on the
     * server (Teleconsult::accessFor). Never from the request body: the client can
     * send anything. Returns ['role' => requester|accepter, 'can_edit' => bool], or
     * null when the user has no access at all.
     */
    private function resolveAccess(Request $request, int $meetingId): ?array
    {
        return Teleconsult::find($meetingId)?->accessFor($request->user()?->id);
    }

    private function otherRole(string $role): string
    {
        return $role === self::REQUESTER ? self::ACCEPTER : self::REQUESTER;
    }

    // Save / store / update / create
    public function storeTeleform(Request $request, string $modelClass, string $resourceName)
    {
        try {
            Log::info("Incoming {$resourceName} Request:", $request->all());

            // Convert empty strings to null
            $data = collect($request->all())->map(fn($v) => $v === '' ? null : $v)->toArray();

            // Validate meeting_id exists
            if (!isset($data['meeting_id'])) {
                return response()->json([
                    'message' => "{$resourceName} requires meeting_id",
                ], 422);
            }

            // The role comes from the meeting, not from the client.
            $access = $this->resolveAccess($request, (int) $data['meeting_id']);
            if (!$access) {
                return response()->json([
                    'message' => "You do not have access to this consultation.",
                ], 403);
            }
            if (!$access['can_edit']) {
                return response()->json([
                    'message' => "This consultation is read only for you.",
                ], 403);
            }
            $role = $access['role'];

            // Ignore any role or owner the client tried to send.
            unset($data['form_role'], $data['user_id']);

            // One row per meeting per role: each user only ever writes their own form.
            $record = $modelClass::updateOrCreate(
                ['meeting_id' => $data['meeting_id'], 'form_role' => $role],
                $data + ['user_id' => $request->user()->id]
            );

            $status = $record->wasRecentlyCreated ? 'created' : 'updated';

            return response()->json([
                'message' => "{$resourceName} saved successfully.",
                'data' => $record,
                'status' => $status,
            ], $status === 'created' ? 201 : 200);

        } catch (\Exception $e) {
            Log::error("{$resourceName} Save/Update Error:", ['error' => $e->getMessage()]);

            return response()->json([
                'message' => "Failed to save {$resourceName}.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Fetch
    //
    // 'data'  = the caller's own form (same shape as before, so existing
    //           frontend code that reads response.data.data keeps working)
    // 'other' = the requester's form, for the read-only panel. Only the accepting
    //           doctor gets it. The requester (RHU) only ever receives its own form.
    // A doctor who is not on the call reads the consultation as the accepter would
    // see it, with 'can_edit' false.
    public function getTeleform(string $modelClass, int $meeting_id, string $resourceName)
    {
        try {
            Log::info("Fetching {$resourceName} for meeting_id: {$meeting_id}");

            $access = $this->resolveAccess(request(), $meeting_id);
            if (!$access) {
                return response()->json([
                    'message' => "You do not have access to this consultation.",
                    'data' => null,
                ], 403);
            }
            $role = $access['role'];

            $otherRole = $this->otherRole($role);

            $mine  = $modelClass::where('meeting_id', $meeting_id)->forRole($role)->first();
            $other = $role === self::ACCEPTER
                ? $modelClass::where('meeting_id', $meeting_id)->forRole($otherRole)->first()
                : null;

            return response()->json([
                'message' => $mine
                    ? "{$resourceName} retrieved successfully."
                    : "No {$resourceName} found for this meeting.",
                'data' => $mine,
                'other' => $other,
                'my_role' => $role,
                'other_role' => $otherRole,
                'can_edit' => $access['can_edit'],
            ], 200);

        } catch (\Exception $e) {
            Log::error("{$resourceName} Fetch Error:", ['error' => $e->getMessage()]);

            return response()->json([
                'message' => "Failed to fetch {$resourceName}.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
