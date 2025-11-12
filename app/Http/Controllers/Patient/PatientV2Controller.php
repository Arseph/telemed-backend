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
     * Display a list of patients.
     */
    public function index(Request $request)
    {
        // ✅ Include 'barangay' relationship
        $query = PatientV2::query()
            ->with(['account', 'meeting', 'barangay'])
            ->orderBy('id', 'desc');

        // ✅ Optional search filter
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pat_fname', 'like', "%{$search}%")
                ->orWhere('pat_lname', 'like', "%{$search}%")
                ->orWhere('pat_temp_id', 'like', "%{$search}%");
            });
        }

        // ✅ Paginate
        $patients = $query->paginate(20);

        // ✅ Map to include barangay name directly
        $patients->getCollection()->transform(function ($p) {
            $p->brg_name = $p->barangay->brg_name ?? null;
            return $p;
        });

        return response()->json($patients);
    }

     public function show($id)
    {
        try {
            $patient = PatientV2::find($id);

            if (!$patient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Patient not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $patient
            ]);

        } catch (\Exception $e) {
            \Log::error("Error fetching patient {$id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch patient profile'
            ], 500);
        }
    }

    /**
     * Store or update a patient record (Upsert).
     */

    public function storeOrUpdate(Request $request)
    {
        $patientId = $request->input('id'); // or master_patient_perm_id if that is your unique key

        if ($patientId) {
            // Try to find existing patient
            $patient = PatientV2::find($patientId);

            if (!$patient) {
                // If not found, create new
                $patient = new PatientV2();
            }
        } else {
            // No ID sent → create new
            $patient = new PatientV2();
        }

        // Fill patient attributes from request
        $patient->fill($request->all());

        // System-managed fields
        $patient->userid = auth()->id() ?? null;
        $patient->date_updated = now()->toDateString();
        $patient->time_updated = now()->toTimeString();

        // Only set entered date/time if new
        if (!$patient->exists) {
            $patient->date_entered = now()->toDateString();
            $patient->time_entered = now()->toTimeString();
        }

        // Save
        $patient->save();

        return response()->json([
            'status' => true,
            'message' => 'Patient successfully saved.',
            'data' => $patient,
        ]);
    }




}

