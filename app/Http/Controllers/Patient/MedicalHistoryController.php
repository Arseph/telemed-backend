<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\DiagMainCat;
use App\Models\DiagSubCat;
use App\Models\Diagnosis;
use App\Models\MedicalHistory;
use App\Models\MedicalHistorySnapshot;
use App\Models\PatientProblem;
use App\Services\MedicalHistorySnapshotService;
use Illuminate\Http\Request;

/**
 * Medical history for a patient.
 *
 * One row per patient, keyed on patient_id. The table carries no meeting_id, and
 * per-encounter diagnoses already live in tele_diagnosis_assessment (see
 * TeleController::storeDA), so this is the patient's standing history rather than a
 * log of visits.
 *
 * Replaces DoctorPatientController::medHisStore / medHisData, which are dead code:
 * they import App\MedicalHistory and App\Diagnosis, neither of which exists since
 * the models moved to App\Models, and they return Session flash data rather than
 * JSON.
 */
class MedicalHistoryController extends Controller
{
    /** Columns the client is allowed to write. Anything else is ignored. */
    private const FILLABLE = [
        'history_present_illness',
        'present_med_fam_soc',
        'icd10',
        'date_diagnosis',
        'time_diagnosis',
        'past_med_history',
        'past_specify',
        'past_surg_his_op',
        'date_surgical',
        'fam_history',
        'fam_specify',
        'smoking',
        'alcohol',
        'illicit_drug',
        'oral_agents',
        'hyper_med',
    ];

    /**
     * The patient's medical history, plus the ICD-10 row it points at so the client
     * can show the code and description without a second lookup.
     *
     * Returns data: null rather than 404 when there is nothing yet — an empty history
     * is a normal state for a patient, not an error.
     */
    public function show($patientId)
    {
        $record = MedicalHistory::where('patient_id', $patientId)->first();

        return response()->json([
            'status' => 'success',
            'data' => $record,
            'diagnosis' => $record?->icd10
                ? Diagnosis::find($record->icd10, ['id', 'diagcode', 'diagdesc'])
                : null,
        ]);
    }

    /**
     * Create or update the patient's history.
     *
     * Only the keys actually present in the request are written, so a caller that
     * sends one field cannot blank the rest — the same partial-update rule the
     * teleconsultation forms follow.
     */
    public function store(Request $request, MedicalHistorySnapshotService $snapshots)
    {
        $validated = $request->validate([
            'patient_id' => 'required|integer',
            'icd10' => 'nullable|exists:diagnosis,id',
            'date_diagnosis' => 'nullable|date',
            'time_diagnosis' => 'nullable',
            'date_surgical' => 'nullable|date',
            'smoking' => 'nullable|boolean',
            'alcohol' => 'nullable|boolean',
            'illicit_drug' => 'nullable|boolean',
            'oral_agents' => 'nullable|boolean',
            'hyper_med' => 'nullable|boolean',
        ]);

        $data = collect($request->only(self::FILLABLE))
            ->map(fn ($v) => $v === '' ? null : $v)
            ->toArray();

        $record = MedicalHistory::updateOrCreate(
            ['patient_id' => $validated['patient_id']],
            $data
        );

        // meeting_id ties the change to a consultation when the save came from
        // inside one; edits from the patient profile leave it null.
        $snapshots->capture($validated['patient_id'], MedicalHistorySnapshot::REASON_HISTORY, $request->input('meeting_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Medical history saved.',
            'data' => $record->fresh(),
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * ICD-10 lookup for the diagnosis field.
     *
     * The diagnosis table holds ~22,800 rows, far too many to hand to a client-side
     * autocomplete, so this searches server-side and caps the result set. Matching on
     * code and description both, since clinicians search either way.
     */
    public function searchDiagnosis(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $query = Diagnosis::query()
            ->where('void', 0)
            ->select(['id', 'diagcode', 'diagdesc']);

        // Optional narrowing by ICD-10 chapter / block. The hierarchy is:
        //   diagnosis_main_categories.diagcat  (26)  -> diagnosis.diagmaincat
        //   diagnosis_sub_categories.diagsubcat (266) -> diagnosis.diagcategory
        if ($request->filled('maincat')) {
            $query->where('diagmaincat', $request->query('maincat'));
        }

        if ($request->filled('category')) {
            $query->where('diagcategory', $request->query('category'));
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('diagcode', 'like', $term . '%')
                    ->orWhere('diagdesc', 'like', '%' . $term . '%');
            });
        }

        return response()->json([
            'status' => 'success',
            // diagcode first so exact-code searches land at the top.
            'data' => $query->orderBy('diagcode')->limit(30)->get(),
        ]);
    }
/** The 26 ICD-10 chapters, for narrowing the diagnosis search. */
    public function mainCategories()
    {
        return response()->json([
            'status' => 'success',
            'data' => DiagMainCat::orderBy('diagcat')->get(['diagcat', 'catdesc']),
        ]);
    }

    /**
     * Blocks within a chapter. Without a chapter this would return all 266, which is
     * more than anyone scrolls, so the chapter is required.
     */
    public function subCategories(Request $request)
    {
        $mainCat = $request->query('maincat');

        if (!$mainCat) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        return response()->json([
            'status' => 'success',
            'data' => DiagSubCat::where('diagmcat', $mainCat)
                ->orderBy('diagsubcat')
                ->get(['diagsubcat', 'diagscatdesc']),
        ]);
    }

    // ---- Problem list -------------------------------------------------------

    /**
     * The patient's conditions, active first, then most recent onset.
     *
     * The ICD-10 row is eager-loaded so the client can render the code and
     * description without a lookup per problem.
     */
    public function problems($patientId)
    {
        $problems = PatientProblem::with('diagnosis:id,diagcode,diagdesc')
            ->where('patient_id', $patientId)
            ->orderByRaw("status = 'resolved'")
            ->orderByDesc('onset_date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $problems,
        ]);
    }

    /** Add a condition, or update one when an id is supplied. */
    public function storeProblem(Request $request, MedicalHistorySnapshotService $snapshots)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:patient_problems,id',
            'patient_id' => 'required|integer',
            'diagnosis_id' => 'nullable|exists:diagnosis,id',
            'problem' => 'nullable|string|max:255',
            'onset_date' => 'nullable|date',
            'status' => 'nullable|in:active,resolved',
            'resolved_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        // A problem needs something to call it by: either an ICD-10 entry or text.
        if (empty($validated['diagnosis_id']) && trim((string) ($validated['problem'] ?? '')) === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Select a diagnosis or describe the problem.',
            ], 422);
        }

        $data = collect($validated)
            ->except('id')
            ->map(fn ($v) => $v === '' ? null : $v)
            ->toArray();

        $data['status'] = $data['status'] ?? PatientProblem::STATUS_ACTIVE;

        // Resolved with no date reads as "resolved at some unknown point"; default it
        // to today so the timeline stays usable. Clearing status clears the date.
        if ($data['status'] === PatientProblem::STATUS_RESOLVED) {
            $data['resolved_date'] = $data['resolved_date'] ?? now()->toDateString();
        } else {
            $data['resolved_date'] = null;
        }

        $problem = isset($validated['id'])
            ? tap(PatientProblem::findOrFail($validated['id']))->update($data)
            : PatientProblem::create($data);

        $snapshots->capture($data['patient_id'], MedicalHistorySnapshot::REASON_PROBLEM, $request->input('meeting_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Problem saved.',
            'data' => $problem->fresh()->load('diagnosis:id,diagcode,diagdesc'),
        ], isset($validated['id']) ? 200 : 201);
    }

    /**
     * Remove a condition.
     *
     * A hard delete on purpose: this is for correcting a mistaken entry. A condition
     * the patient genuinely had and no longer has should be marked resolved instead,
     * which keeps it on the record.
     */
    public function destroyProblem($id, Request $request, MedicalHistorySnapshotService $snapshots)
    {
        $problem = PatientProblem::findOrFail($id);
        $patientId = $problem->patient_id;

        $problem->delete();

        $snapshots->capture($patientId, MedicalHistorySnapshot::REASON_PROBLEM, $request->input('meeting_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Problem removed.',
        ]);
    }

    // ---- History timeline ---------------------------------------------------

    /**
     * The patient's snapshot timeline, newest first.
     *
     * Payloads are omitted here — they are large and the list only needs enough to
     * pick an entry from. Call showSnapshot for the contents.
     */
    public function snapshots(Request $request, $patientId)
    {
        $query = MedicalHistorySnapshot::with('user:id,fname,lname')
            ->where('patient_id', $patientId);

        // "What did we know at that visit" — the reason snapshots carry meeting_id.
        if ($request->filled('meeting_id')) {
            $query->where('meeting_id', $request->query('meeting_id'));
        }

        // id breaks ties: several snapshots can share a taken_at to the second.
        $snapshots = $query->orderByDesc('taken_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'patient_id', 'meeting_id', 'taken_by', 'reason', 'taken_at']);

        return response()->json([
            'status' => 'success',
            'data' => $snapshots,
        ]);
    }

    /** One snapshot, including the stored state. */
    public function showSnapshot($id)
    {
        $snapshot = MedicalHistorySnapshot::with('user:id,fname,lname')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $snapshot,
        ]);
    }
}


