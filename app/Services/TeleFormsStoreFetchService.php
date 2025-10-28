<?php

namespace App\Services;

class TeleFormsStoreFetchService
{
    public function saveOrUpdate(string $modelClass, array $data, string $uniqueField = 'meeting_id')
    {
        $record = $modelClass::where($uniqueField, $data[$uniqueField] ?? null)->first();

        if ($record) {
            $record->update($data);
            return ['record' => $record, 'status' => 'updated'];
        }

        $record = $modelClass::create($data);
        return ['record' => $record, 'status' => 'created'];
    }

    // Fetch by meeting_id
    public function getByMeetingId(string $modelClass, $meeting_id, string $label = 'Resource')
    {
        $record = $modelClass::where('meeting_id', $meeting_id)->first();

        if (!$record) {
            return response()->json([
                'message' => "No {$label} found for this meeting.",
                'data' => null
            ], 200);
        }

        return response()->json([
            'message' => "{$label} retrieved successfully.",
            'data' => $record
        ], 200);
    }
}

