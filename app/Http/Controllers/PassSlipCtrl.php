<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PassSlip;
use App\Models\EmpMain;
class PassSlipCtrl extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $userId = $user->info->id;
        $id = $user->id;
        $ps = Auth::user()->passslip()->with(['creator.info'])->orderBy('created_at','desc')->get();
        $emp = EmpMain::select(
            'hris_main.*',
            'hr_infos.emp_status as emp_status',
            'hr_infos.division_id as division_id',
            'hr_infos.emp_type as emp_type',
            'hr_infos.section_id as section_id',
            'hr_infos.position_id as position_id',
            'positions.position as Pos',
            'divisions.office as off',
            'sections.office as sec',
        )->leftJoin('hr_infos as hr_infos', 'hris_main.id','=','hr_infos.emp_id')
        ->leftJoin('positions as positions','hr_infos.position_id','=','positions.id')
        ->leftJoin('sections as sections','hr_infos.section_id','=','sections.id')
        ->leftJoin('divisions as divisions','hr_infos.division_id','=','divisions.id')
        ->where('hr_infos.emp_status', 1)
        ->where('hr_infos.emp_type', 1)
        ->orderBy('hris_main.sur_name', 'asc')->get();
        $isInvolved = PassSlip::where('approver_id', $userId)
                ->orWhere('supervisor_id', $userId)
                ->exists();
        $hasActive = PassSlip::where('is_active', 1)
                ->where('user_id', $id)
                ->exists();
        return response()->json([
            'ps' => $ps,
            'emp' => $emp,
            'involved' => $isInvolved,
            'has_active' => $hasActive,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_date' => 'required|date',
            'request_time_out' => 'required|date_format:g:i A',
            'reason' => 'required|string',
            'nature_business' => 'nullable',
            'estimated_arrival' => 'nullable|date_format:g:i A',
            'supervisor_id' => 'required',
            'approver_id' => 'required',
        ]);
        $validated['request_date'] = date('Y-m-d', strtotime($validated['request_date'])); 
        $validated['request_time_out'] = date('H:i:s', strtotime($validated['request_time_out']));
        if ($validated['estimated_arrival']) {
            $validated['estimated_arrival'] = date('H:i:s', strtotime($validated['estimated_arrival']));
        }
        $passSlip = PassSlip::create([
            'user_id' => Auth::id(),
            'request_date' => $validated['request_date'],
            'request_time_out' => $validated['request_time_out'],
            'reason' => $validated['reason'],
            'nature_business' => $validated['nature_business'],
            'estimated_arrival' => $validated['estimated_arrival'],
            'supervisor_id' => $validated['supervisor_id'],
            'approver_id' => $validated['approver_id'],
            'is_active' => true,
            'status' => false,
        ]);
    
        return response()->json([
            'message' => 'Pass Slip successfully created.',
            'data' => $passSlip
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'request_date' => 'required|date',
            'request_time_out' => 'required',
            'reason' => 'required|string',
            'nature_business' => 'nullable',
            'estimated_arrival' => 'nullable',
            'supervisor_id' => 'required',
            'approver_id' => 'required',
        ]);
        $validated['request_date'] = date('Y-m-d', strtotime($validated['request_date'])); 
        $validated['request_time_out'] = date('H:i:s', strtotime($validated['request_time_out']));
        if ($validated['estimated_arrival']) {
            $validated['estimated_arrival'] = date('H:i:s', strtotime($validated['estimated_arrival']));
        }
        $passSlip = PassSlip::findOrFail($id);

        $passSlip->update($validated);
        return response()->json([
            'message' => 'Pass Slip updated successfully.',
            'passSlip' => $passSlip
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $passSlip = PassSlip::find($id);
    
            if (!$passSlip) {
                return response()->json(['message' => 'Pass slip not found.'], 404);
            }
    
            $passSlip->delete();
    
            return response()->json(['message' => 'Pass slip deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong.'], 500);
        }
    }
    public function getApprove() {
        $user = Auth::user();

        if (!$user || !$user->info) {
            return response()->json(['message' => 'User does not have an employee record'], 403);
        }

        $empId = $user->info->id;
        $passSlips = PassSlip::with(['approver', 'supervisor', 'creator.info'])
            ->where('approver_id', $empId)
            ->orWhere('supervisor_id', $empId)
            ->orderBy('created_at','desc')
            ->get();

        return response()->json([
            'pass_slips' => $passSlips,
            'employee_id' => $empId,
        ]);
    }
    public function approve($id)
    {
        $pass = PassSlip::find($id);
        $user = Auth::user();

        if (!$pass) {
            return response()->json(['message' => 'Pass slip not found'], 404);
        }

        if (!$user || !$user->info) {
            return response()->json(['message' => 'User does not have an employee record'], 403);
        }

        $empId = $user->info->id;
        $is_super = $pass->supervisor_id === $empId;
        $is_app = $pass->approver_id === $empId;

        if (!$is_super && !$is_app) {
            return response()->json(['message' => 'Unauthorized: You are not assigned as a Supervisor or Approver'], 403);
        }
        if ($is_super && $is_app) {
            $pass->is_supervisor_approved = true;
            $pass->is_approver_approved = true;
            $pass->status = true;
        } else {
            if ($is_super) {
                $pass->is_supervisor_approved = true;
            }
            if ($is_app) {
                $pass->is_approver_approved = true;
                $pass->status = true;
            }
        }

        $pass->save();

        return response()->json([
            'message' => "Pass slip approved successfully",
            'pass_slip' => $pass
        ]);
    }

    public function generate($id) {
        $ps = PassSlip::find($id);
        if(!$ps) {
            return response()->json(['message' => 'Pass slip not found'], 404);
        }
        $creator_sig = base64_encode($ps->creator?->esign?->signature);
        $supervisor_sig = base64_encode($ps->approver?->user?->esign?->signature);
        $approver_sig = base64_encode($ps->supervisor?->user?->esign?->signature);
        $pdf = Pdf::loadView('pdf.pass-slip', [
            'ps' => $ps,
            'creator_sig' => $creator_sig,
            'supervisor_sig' => $supervisor_sig,
            'approver_sig' => $approver_sig,
        ]);
        return $pdf->download('pass-slip.pdf');
    }

    public function scan($id, Request $req) {
        $pass = PassSlip::find($id);
        if (!$pass) {
            return response()->json(['message' => 'Pass slip not found'], 404);
        }
        $pass->actual_time = now();
        $pass->latitude = $req->latitude;
        $pass->longitude = $req->longitude;
        $pass->save();
    }
    public function scanPassSlip(Request $req) {
        $user = Auth::user();
        $pass = PassSlip::where('is_active',1)->where('user_id',$user->id)->first();
        if (!$pass) {
            return response()->json(['message' => 'Pass slip not found'], 404);
        }
        $pass->actual_time = now();
        $pass->latitude = $req->latitude;
        $pass->longitude = $req->longitude;
        $pass->is_active = false;
        $pass->save();
        return response()->json(['message' => 'Successfully scanned Pass Slip: Actual Time Arrival: '.now()]);
    }


}
