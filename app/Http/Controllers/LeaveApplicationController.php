<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\LeaveDetails;
use Carbon\Carbon;
use Auth;

class LeaveApplicationController extends Controller
{
    

    public function index(){
        $leaves = LeaveApplication::with('recom_name')->get();
        return response()->json($leaves);
    }

    public function create(){
        return view('leaves.List');
    }
    
    public function store(Request $req){
        $user_id = Auth::user()->id;
        $start_date = Carbon::parse($req->start_date);
        $end_date = Carbon::parse($req->end_date);
        
        $validated = $req->validate([
            'leave_type_id' => 'required|integer', // ID for leave type
            'leave_detail_id' => 'nullable|integer', // Optional leave detail ID
            'leave_remarks' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'inclusive_dates' => 'required|array', // Validate that it's an array
            'inclusive_dates.*' => 'date', // Validate each element as a date
        ]);
        do {
            $leave_number = 'LEAVE-' . rand(100000, 999999);
        } while (LeaveApplication::where('leave_number', $leave_number)->exists());
        $data = [
            'user_id' => $user_id,
            'leave_number' => $leave_number,
            'leave_type_id' => $validated['leave_type_id'],
            'leave_detail_id' => $validated['leave_detail_id'] ?? 0,
            'coc_balance' => 0, // Default value
            'leave_remarks' => $validated['leave_remarks'],
            'start_date' => $start_date,
            'end_date' => $end_date,
            'inclusive_dates' => json_encode($validated['inclusive_dates']), // Convert array to JSON
            'commutation' => 'Requested', // Default value
            'recom_status' => 1, // Default recommendation status
            'recom_remarks' => null, // Default recommendation remarks
            'status_id' => 1, // Default status ID
        ];
        try {
            $leave = LeaveApplication::create($data);
            return response()->json(['success' => true, 'message' => 'Application successfully added.']);
        } catch (Exception $e) {
            if ($e->getCode() == 23000){
                return response()->json(['success' => false, 'message' => 'A leave with this number already exists. Please try again.']);
            }
            return response()->json(['success' => false, 'message' => 'An error occurred. Please contact admin.']);
        }
    }
    
    public function edit($id) {
        $leave = LeaveApplication::find($id);
        if ($leave) {
            return response()->json([
                'success' => true,
                'message' => 'Application successfully retrieved.',
                'leave' => $leave,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], 404);
        }
    }


    public function UpdateLeave(Request $req){
         
        $leave = LeaveApplication::find($req->id);
        $start_date = Carbon::parse($req->start_date);
        $end_date = Carbon::parse($req->end_date);
    
        if ($leave) {
            $inclusive_dates = $req->inclusive_dates; 
           
    
            // Update the leave application
            $leave->update([
                'leave_type_id' => $req->leave_type_id,
                'leave_detail_id' => $req->leave_detail_id,
                'leave_remarks' => $req->leave_remarks,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'inclusive_dates' => json_encode($inclusive_dates), // Encode the inclusive_dates array as JSON
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Application successfully updated',
                'leave' => $leave,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Application not updated. Please contact administrator.',
            ], 404);
        }
    }
    




    
    public function get_leave_type(){
        $data = LeaveType::all();
        return response()->json($data);
    }


    public function get_leave_details(){
        $data = LeaveDetails::all();
        return response()->json($data);
    }

 
    public function getUserleaves(){
        $user_id = Auth::user()->id;
        $leaves = LeaveApplication::with('recom_name')->where('user_id', $user_id)->get();
        if($leaves->isEmpty()){
            return response()->json([
                'success' => false,
                'message' => 'No leaves found for this user.',
            ], 404);

        }

        return response()->json([
            'success' => true,
            'data' => $leaves,
        ]);
    }


 }
