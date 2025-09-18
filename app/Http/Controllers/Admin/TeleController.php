<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\User;
use App\Patient;
use App\Meeting;
use App\Facility;
use App\PendingMeeting;
use Carbon\Carbon;
use App\TeleCategory;
use App\LabRequest;
use App\DoctorOrder;
use App\ZoomToken;
use App\DocOrderLabReq;
use Redirect;
use App\Doc_Type;
class TeleController extends Controller
{
	public function __construct()
    {
        if(!$login = Session::get('auth')){
            $this->middleware('auth');
        }
    }
    
    public function index(Request $request) {
    	$user = Session::get('auth');
        $keyword = $request->view_all ? '' : $request->date_range;
        $data = Meeting::select(
            "meetings.*",
            "meetings.id as meetID",
            "meetings.user_id as Creator",
            "meetings.doctor_id as RequestTo",
            "pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
            "pat.id as PatID",
        )->leftJoin("patients as pat", "meetings.patient_id", "=", "pat.id");
        if($keyword){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0]));
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1]));
            $data = $data
                ->where(function($q) use($date_start, $date_end) {
                $q->whereBetween('meetings.date_meeting', [$date_start, $date_end]);
            });
        }
        $data = $data->where(function($q) use($user){
            $q->where("meetings.doctor_id","=", $user->id)
            ->orWhere("meetings.user_id", "=", $user->id);
            })->whereDate("meetings.date_meeting", ">=", Carbon::now()->toDateString())
                ->orderBy('meetings.date_meeting', 'asc')
                ->paginate(20);
        $patients =  Patient::select(
            "patients.*",
            "user.email as email"
        ) ->leftJoin("users as user","patients.account_id","=","user.id")
         ->where('patients.facility_id',$user->facility_id)
        ->get();

        $keyword_past = $request->view_all_past ? '' : $request->date_range_past;
        $data_past = Meeting::select(
            "meetings.*",
            "meetings.id as meetID",
            "pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
        )->leftJoin("patients as pat", "meetings.patient_id", "=", "pat.id");
        if($keyword_past){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range_past)[0]));
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range_past)[1]));
            $data_past = $data_past
                ->where(function($q) use($date_start, $date_end) {
                $q->whereBetween('meetings.date_meeting', [$date_start, $date_end]);
            });
        }
        $data_past = $data_past->where("meetings.doctor_id","=", $user->id)
                ->whereDate("meetings.date_meeting", "<", Carbon::now()->toDateString())
                ->orderBy('meetings.date_meeting', 'desc')
                ->paginate(20);

        $keyword_req = $request->view_all_req ? '' : $request->date_range_req;
        $data_req = PendingMeeting::select(
            "pending_meetings.*",
            "pending_meetings.id as meetID",
            "pending_meetings.created_at as reqDate",
            "pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
        )->leftJoin("patients as pat", "pending_meetings.patient_id", "=", "pat.id");
        if($keyword_req){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range_req)[0]));
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range_req)[1]));
            $data_req = $data_req
                ->where(function($q) use($date_start, $date_end) {
                $q->whereDate('pending_meetings.datefrom', '>=', $date_start);
                $q->whereDate('pending_meetings.datefrom', '<=', $date_end);
            });
        }
        $status_req = $request->view_all_req ? '' : $request->status_req;
        $active_tab = $request->active_tab ? $request->active_tab : 'upcoming';
        if($status_req) {
            $data_req = $data_req->where(function($q) use($status_req) {
                $q->where('pending_meetings.status', $status_req);
            });
        }
        $data_req = $data_req->where("pending_meetings.doctor_id","=", $user->id)
                ->orderBy('pending_meetings.id', 'desc')
                ->paginate(20);

        $data_my_req = PendingMeeting::select(
            "pending_meetings.*",
            "pending_meetings.id as meetID",
            "pending_meetings.created_at as reqDate",
            "pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
        )->leftJoin("patients as pat", "pending_meetings.patient_id", "=", "pat.id")
        ->where("pending_meetings.user_id","=", $user->id)
                ->orderBy('pending_meetings.id', 'desc')
                ->paginate(10);
        $facilities = Facility::where('id','!=', $user->facility_id)->orderBy('facilityname', 'asc')->get();
        $count_req = PendingMeeting::select(
            "pending_meetings.*",
            "pending_meetings.id as meetID",
            "pending_meetings.created_at as reqDate",
        )->leftJoin("patients as pat", "pending_meetings.patient_id", "=", "pat.id")
        ->where('pending_meetings.status', 'Pending')
        ->where("pending_meetings.doctor_id","=", $user->id)->count();
        $telecat = TeleCategory::orderBy('category_name', 'asc')->get();
        $labreq = LabRequest::where('req_type', 'LAB')->orderby('description', 'asc')->get();
        $imaging = LabRequest::where('req_type', 'RAD')->orderby('description', 'asc')->get();
        $docorder = DoctorOrder::where('doctorid', $user->id)->get();
        $zoomtoken = ZoomToken::where('user_id',$user->id)->first() ?
                        ZoomToken::where('user_id',$user->id)->first()->updated_at
                        : 'none';
        $doc_type = Doc_Type::where('isactive', '1')->orderBy('doc_name', 'asc')->get();
        return view('admin.teleconsult',[
            'patients' => $patients,
            'search' => $keyword,
            'data' => $data,
            'pastmeetings' => $data_past,
            'search_past' => $keyword_past,
            'facilities' => $facilities,
            'search_req' => $keyword_req,
            'data_req' => $data_req,
            'status_req' => $status_req,
            'active_tab' => $active_tab,
            'data_my_req' => $data_my_req,
            'active_user' => $user,
            'pending' => $count_req,
            'telecat' => $telecat,
            'labreq' => $labreq,
            'imaging' => $imaging,
            'docorder' => $docorder,
            'zoomtoken'=> $zoomtoken,
            'doc_type'=> $doc_type
        ]);
    }

    public function meetingInfo(Request $req) {
    	$meeting = Meeting::select(
    		"meetings.*",
    		"pat.*",
    		"meetings.id as meetID",
    		"user.fname as docfname",
    		"user.mname as docmname",
    		"user.lname as doclname",
    	)->leftJoin("patients as pat","pat.id","=","meetings.patient_id")
    	->leftJoin("users as user", "user.id", "=", "pat.doctor_id")
         ->where('meetings.id',$req->meet_id)
        ->first();

    	return json_encode($meeting);
    }

    public function indexCall($id) {
    	$meetings = Meeting::select(
    		"meetings.*",
    		"pat.*",
    		"meetings.id as meetID",
    		"user.fname as docfname",
    		"user.mname as docmname",
    		"user.lname as doclname",
    	)->leftJoin("patients as pat","pat.id","=","meetings.patient_id")
    	->leftJoin("users as user", "user.id", "=", "pat.doctor_id")
         ->where('meetings.id',$id)
        ->first();
        $case_no = mt_rand(100000000, 999999999);

        return view('admin.teleCall',[
        	'meeting' => $meetings,
            'case_no' => $case_no
        ]);
    }

    public function getDoctorsFacility(Request $req) {
        $user_id = Session::get('auth')->id;
        $doctors = User::where('facility_id',$req->fac_id)
                        ->where('level', 'doctor')
                        ->where('id', '!=', $user_id)
                        ->orderBy('lname', 'asc')->get();
        return json_encode($doctors);
    }
}
