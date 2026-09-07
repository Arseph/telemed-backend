<?php

namespace App\Services;

use App\Models\MedicalHistory;
use App\Models\MedicalHistorySnapshot;
use App\Models\PatientProblem;
use Illuminate\Support\Facades\Log;

/**
 * Captures point-in-time copies of a patient's medical history.
 *
 * Called after the history or the problem list changes. The snapshot holds both, not
 * just the part that changed — someone looking back wants the whole picture as it
 * stood, and reconstructing the other half from a different table's timeline defeats
 * the point of having snapshots at all.
 */
class MedicalHistorySnapshotService
{
    /**
     * The acting user.
     *
     * auth()->id() alone reads the default guard, which is the session-based 'web'
     * one — the SPA authenticates with tokens, so that returns null outside an
     * auth:sanctum request. Both are checked, matching config/audit.php.
     */
    private function currentUserId(): ?int
    {
        foreach (['sanctum', 'web'] as $guard) {
            try {
                if (auth()->guard($guard)->check()) {
                    return auth()->guard($guard)->id();
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return auth()->id();
    }

    /**
     * Take a snapshot of everything currently recorded for this patient.
     *
     * Failures are logged and swallowed on purpose. A snapshot is a secondary record;
     * if writing it fails, the clinical save that triggered it must still stand rather
     * than being rolled back over bookkeeping.
     *
     * @param  int|null  $meetingId  the consultation this happened during, if any
     */
    public function capture(int $patientId, string $reason, ?int $meetingId = null): ?MedicalHistorySnapshot
    {
        try {
            $history = MedicalHistory::where('patient_id', $patientId)->first();

            $problems = PatientProblem::with('diagnosis:id,diagcode,diagdesc')
                ->where('patient_id', $patientId)
                ->orderByRaw("status = 'resolved'")
                ->orderByDesc('onset_date')
                ->get();

            return MedicalHistorySnapshot::create([
                'patient_id' => $patientId,
                'meeting_id' => $meetingId,
                'taken_by' => $this->currentUserId(),
                'reason' => $reason,
                'payload' => [
                    'history' => $history?->toArray(),
                    'problems' => $problems->toArray(),
                ],
                'taken_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Medical history snapshot failed', [
                'patient_id' => $patientId,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
