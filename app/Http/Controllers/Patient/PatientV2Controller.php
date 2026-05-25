<?php

namespace App\Http\Controllers\Patient;

use App\Models\PatientV2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Barangay;
use App\Models\Patient;
use App\Models\User;
use App\Models\Countries;
use App\Models\Region;
use App\Models\MunicipalCity;
use App\Models\Province;
use App\Models\PendingMeeting;
use Carbon\Carbon;
use App\Models\ClinicalHistory;
use App\Models\CovidAssessment;
use App\Models\CovidScreening;
use App\Models\DiagnosisAssessment;
use File;
use App\Models\PlanManagement;
use App\Models\DemoProfile;
use App\Models\PhysicalExam;
use App\Models\Meeting;
use App\Models\Teleconsult;

class PatientV2Controller extends Controller
{
    /**
     * Eager-load relationships reused across methods.
     */
    private array $with = ['account', 'meeting', 'barangay', 'allmeetings.doctor.doccat'];

    /**
     * Display a paginated list of patients.
     */
    public function index(Request $request)
    {
        $query = PatientV2::query()->with($this->with)->orderBy('id', 'desc');

        // Search filter — uses CAST because pat_fname/pat_lname are varbinary
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(pat_fname AS CHAR) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('CAST(pat_lname AS CHAR) LIKE ?', ["%{$search}%"])
                    ->orWhere('pat_temp_id', 'like', "%{$search}%");
            });
        }

        $patients = $query->paginate(20);

        // Append barangay name directly onto each patient item
        $patients->getCollection()->transform(function ($p) {
            $p->brg_name = $p->barangay->brg_name ?? null;
            return $p;
        });

        return response()->json($patients);
    }

    /**
     * Display a single patient with full relationships.
     */
    public function show($id)
    {
        try {
            $patient = PatientV2::with($this->with)->find($id);

            if (!$patient) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Patient not found',
                    ],
                    404,
                );
            }

            // Append barangay name for consistency with index()
            $patient->brg_name = $patient->barangay->brg_name ?? null;

            return response()->json([
                'status' => 'success',
                'data' => $patient,
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching patient {$id}: " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to fetch patient profile',
                ],
                500,
            );
        }
    }

    /**
     * Create or update a patient (upsert).
     */
    public function storeOrUpdate(Request $request)
    {
        // Validate required NOT NULL fields that have no DB default
        $request->validate([
            'regcode' => 'required|string|max:2',
            'provcode' => 'required|string|max:4',
            'citycode' => 'required|string|max:6',
            'bgycode' => 'required|string|max:9',
            'pat_fname' => 'required',
            'pat_lname' => 'required',
            'pat_mname' => 'required',
            'sex_code' => 'required',
            'pat_birthDate' => 'required|date',
            'pat_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $patientId = $request->input('id');

        if ($patientId) {
            $patient = PatientV2::find($patientId) ?? new PatientV2();
        } else {
            $patient = new PatientV2();
        }

        // Handle profile image upload
        if ($request->hasFile('pat_image') && $request->file('pat_image')->isValid()) {
            $destinationPath = public_path('images/profilepictures');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Delete old image if it exists
            if ($patient->pat_image && file_exists(public_path($patient->pat_image))) {
                @unlink(public_path($patient->pat_image));
            }

            $filename = time() . '_' . uniqid() . '.' . $request->file('pat_image')->getClientOriginalExtension();
            $relativePath = 'images/profilepictures/' . $filename;

            $request->file('pat_image')->move($destinationPath, $filename);

            $patient->pat_image = $relativePath;
        }

        // Fill all other fields — exclude pat_image (handled above) and
        // any eager-loaded relationships/appended fields the Vue sends back
        $patient->fill($request->except(['pat_image', 'account', 'meeting', 'allmeetings', 'barangay', 'brg_name']));

        // System-managed fields
        $patient->userid = auth()->id() ?? null;
        $patient->date_updated = now()->toDateString();
        $patient->time_updated = now()->toTimeString();

        // Apply safe defaults for NOT NULL columns that have no DB default
        // so inserts don't fail when the frontend omits these optional flags
        $patient->fsNumber = $patient->fsNumber ?? '';
        $patient->phic_member = $patient->phic_member ?? '0';
        $patient->uploaded = $patient->uploaded ?? '0';
        $patient->validated = $patient->validated ?? '0';
        $patient->phic_stat = $patient->phic_stat ?? '0';
        $patient->PCB_nhts = $patient->PCB_nhts ?? '0';

        // Set entered timestamps only on create
        if (!$patient->exists) {
            $patient->date_entered = now()->toDateString();
            $patient->time_entered = now()->toTimeString();
        }

        $patient->save();

        return response()->json([
            'status' => true,
            'message' => 'Patient successfully saved.',
            'data' => $patient,
        ]);
    }
}
