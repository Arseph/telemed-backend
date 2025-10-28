<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeleFormsStoreFetchService
{
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

            // Save or update record
            $record = $modelClass::updateOrCreate(
                ['meeting_id' => $data['meeting_id']],
                $data
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
    public function getTeleform(string $modelClass, int $meeting_id, string $resourceName)
    {
        try {
            Log::info("Fetching {$resourceName} for meeting_id: {$meeting_id}");

            $record = $modelClass::where('meeting_id', $meeting_id)->first();

            if (!$record) {
                return response()->json([
                    'message' => "No {$resourceName} found for this meeting.",
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => "{$resourceName} retrieved successfully.",
                'data' => $record,
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
