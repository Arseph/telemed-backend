<?php

namespace App\Http\Controllers\Tele;

use Illuminate\Http\Request;
use App\Helpers\PusherHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Patient;
use App\Models\Meeting;
use Carbon\Carbon;
use App\Models\PendingMeeting;
use App\Models\Facility;
use App\Models\DocCategory;
use App\Models\Countries;
use App\Models\LabRequest;
use App\Models\DoctorOrder;
use App\Models\ZoomToken;
use App\Models\DocOrderLabReq;
use Redirect;
use App\Models\Doc_Type;
use App\Models\MunicipalCity;
use App\Events\ReqTele;
use App\Events\AcDecReq;
use App\Events\MyEvent;
use App\Models\Prescription;
use App\Models\Teleconsult;
use Auth;
class TeleController extends Controller
{
    public function index(Request $request) {
    	$user = Auth::user();
    	$keyword = $request->view_all ? '' : $request->date_range;
        $data = Teleconsult::select(
        	"teleconsults.*",
        	"teleconsults.id as meetID",
            "teleconsults.user_id as Creator",
            "teleconsults.doctor_id as RequestTo",
        	"pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
            "pat.dob as dob",
            "pat.sex as sex",
            "pat.civil_status as civil_status",
            "pat.id as PatID",
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id");
        if($keyword){
        	$date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0]));
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1]));
            $data = $data
                ->where(function($q) use($date_start, $date_end) {
                $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
            });
        }
        $activeid = $user->patient ? $user->patient->id : $user->id; 
        $data = $data->where(function($q) use($user, $activeid){
            $q->where("teleconsults.doctor_id","=", $activeid)
            ->orWhere("teleconsults.user_id", "=", $activeid)
            ->orWhere("teleconsults.patient_id", "=", $activeid);
            })->whereDate("teleconsults.date_meeting", "=", Carbon::now()->toDateString())
        		->orderBy('teleconsults.date_meeting', 'asc')
        		->get();
        $data->load('encoded.facility');
    	$patients =  Patient::select(
            "patients.*",
            "bar.brg_name as barangay",
            "user.email as email",
            "user.username as username",
        ) ->leftJoin("barangays as bar","bar.brg_psgc","=","patients.brgy")
        ->leftJoin("users as user","user.id","=","patients.account_id")
        ->where('patients.doctor_id', $user->id)
        ->orderby('patients.lname','asc')
        ->get();

        $keyword_past = $request->view_all_past ? '' : $request->date_range_past;
        $data_past = Teleconsult::select(
        	"teleconsults.*",
        	"teleconsults.id as meetID",
            "teleconsults.user_id as Creator",
            "teleconsults.doctor_id as RequestTo",
        	"pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
            "pat.id as PatID",
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id");
        if($keyword_past){
        	$date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range_past)[0]));
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range_past)[1]));
            $data_past = $data_past
                ->where(function($q) use($date_start, $date_end) {
                $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
            });
        }
        $data_past = $data_past->where(function($q) use($user){
            $q->where("teleconsults.doctor_id","=", $user->id)
            ->orWhere("teleconsults.user_id", "=", $user->id);
            })->whereDate("teleconsults.date_meeting", "<", Carbon::now()->toDateString())
        		->orderBy('teleconsults.date_meeting', 'desc')
        		->get();
        $data_past->load('encoded.facility');
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
        $status_req = $request->view_all_req ? '' : ($request->status_req ? $request->status_req: 'Pending');
        $active_tab = $request->active_tab ? $request->active_tab : 'request';
        if($status_req) {
            $data_req = $data_req->where(function($q) use($status_req) {
                $q->where('pending_meetings.status', $status_req);
            });
        }
        $data_req = $data_req->where("pending_meetings.doctor_id","=", $user->id)
                ->orderBy('pending_meetings.id', 'desc')
                ->get();
        $data_req->load('facility','patient');
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
                ->get();
        $data_my_req->load('facility','doctor');
        $facilities = Facility::orderBy('facilityname', 'asc')->get();
        $count_req = PendingMeeting::select(
            "pending_meetings.*",
            "pending_meetings.id as meetID",
            "pending_meetings.created_at as reqDate",
        )->leftJoin("patients as pat", "pending_meetings.patient_id", "=", "pat.id")
        ->where('pending_meetings.status', 'Pending')
        ->where("pending_meetings.doctor_id","=", $user->id)->count();
        $telecat = DocCategory::orderBy('category_name', 'asc')->get();
        $labreq = LabRequest::where('req_type', 'LAB')->orderby('description', 'asc')->get();
        $imaging = LabRequest::where('req_type', 'RAD')->orderby('description', 'asc')->get();
        $docorder = DoctorOrder::where('doctorid', $user->id)->get();
        $doc_type = Doc_Type::where('isactive', '1')->orderBy('doc_name', 'asc')->get();

        $data_up = Teleconsult::select(
        	"teleconsults.*",
        	"teleconsults.id as meetID",
            "teleconsults.user_id as Creator",
            "teleconsults.doctor_id as RequestTo",
        	"pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
            "pat.dob as dob",
            "pat.sex as sex",
            "pat.civil_status as civil_status",
            "pat.id as PatID",
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id");
        if($keyword){
        	$date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0]));
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1]));
            $data_up = $data_up
                ->where(function($q) use($date_start, $date_end) {
                $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
            });
        }
        $activeid = $user->patient ? $user->patient->id : $user->id; 
        $data_up = $data_up->where(function($q) use($user, $activeid){
            $q->where("teleconsults.doctor_id","=", $activeid)
            ->orWhere("teleconsults.user_id", "=", $activeid)
            ->orWhere("teleconsults.patient_id", "=", $activeid);
            })->whereDate("teleconsults.date_meeting", ">=", Carbon::now()->toDateString())
        		->orderBy('teleconsults.date_meeting', 'asc')
        		->get();
        return response()->json([
            'patients' => $patients,
            'search' => $keyword,
            'data' => $data,
            'data_up' => $data_up,
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
            'doc_type'=> $doc_type,
            'upcome' => count($data)
        ]);
    }

    public function schedTeleStore(Request $req) {
        $docid = $req->doctor_id;
        $user = Auth::user();
        $user_id = $user->id;
        $facility = $user->facility->facilityname;
        PusherHelper::trigger('my-channel.'.$docid, 'my-event.'.$docid,[
            'title' => 'New Teleconsultation Request',
            'subtitle' => $facility,
            'time' => Carbon::now(),
            'isSeen' => false,
        ]);
        $req->request->add([
            'user_id' => $user_id,
            'status' => 'Pending',
        ]);
        if($req->meeting_id) {
            $meet = PendingMeeting::find($req->meeting_id)->update($req->except('meeting_id'));
        } else {
            $meet = PendingMeeting::create($req->except('meeting_id'));
        }
    }

    public function indexCall($id) {
        $user = Session::get('auth');
        $decid = Crypt::decrypt($id);
    	$meetings = Teleconsult::select(
    		"teleconsults.*",
            "pat.id as PATID",
    		"teleconsults.id as meetID"
    	)->leftJoin("patients as pat","pat.id","=","teleconsults.patient_id")
         ->where('teleconsults.id',$decid)
        ->first();
        $title = $meetings->title;
        $emailname = Crypt::decrypt($meetings->doctor->email);
        $password = $meetings->password;
        $role = $meetings->doctor_id == $user->id ? 1 : 0;
        $username = $user->fname.' '.$user->mname.' '.$user->lname;
        //Set the timezone to UTC
        date_default_timezone_set("UTC");

        $time = time() * 1000 - 30000;//time in milliseconds (or close enough)
        $nationality = Countries::orderBy('nationality', 'asc')->get();
        $patient = Teleconsult::find($decid);
        $case_no = $patient->demoprof ? $patient->demoprof->case_no : sprintf('%09d', $patient->id);
        $facility = Facility::orderBy('facilityname', 'asc')->get();
        $countries = Countries::orderBy('en_short_name', 'asc')->get();
        $date_departure = '';
        $date_arrival_ph = '';
        $date_contact_known_covid_case = '';
        $acco_date_last_expose = '';
        $food_es_date_last_expose = '';
        $store_date_last_expose = '';
        $fac_date_last_expose = '';
        $event_date_last_expose = '';
        $wp_date_last_expose = '';
        $list_name_occasion = [];
        $days_14_date_onset_illness = '';
        $referral_date = '';
        $xray_date = '';
        $date_collected = '';
        $date_sent_ritm = '';
        $date_received_ritm = '';
        $scrum = [];
        $oro_naso_swab = [];
        $spe_others = [];
        $outcome_date_discharge = '';
        $conjunctiva = '';
        $neck = '';
        $breast = '';
        $thorax = '';
        $abdomen = '';
        $genitals = '';
        $extremities = '';
        $date_referral = '';
        if($patient->covidscreen) {
            $date_departure = $patient->covidscreen->date_departure ? date('m/d/Y', strtotime($patient->covidscreen->date_departure)) : '';
            $date_arrival_ph = $patient->covidscreen->date_arrival_ph ? date('m/d/Y', strtotime($patient->covidscreen->date_arrival_ph)) : '';
            $date_contact_known_covid_case = $patient->covidscreen->date_contact_known_covid_case ? date('m/d/Y', strtotime($patient->covidscreen->date_contact_known_covid_case)) : '';
            $acco_date_last_expose = $patient->covidscreen->acco_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->acco_date_last_expose)) : '';
            $food_es_date_last_expose = $patient->covidscreen->food_es_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->food_es_date_last_expose)) : '';
            $store_date_last_expose = $patient->covidscreen->store_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->store_date_last_expose)) : '';
            $fac_date_last_expose = $patient->covidscreen->fac_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->fac_date_last_expose)) : '';
            $event_date_last_expose = $patient->covidscreen->event_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->event_date_last_expose)) : '';
            $wp_date_last_expose = $patient->covidscreen->wp_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->wp_date_last_expose)) : '';
            $list_name_occasion = $patient->covidscreen->list_name_occasion ? explode("|",$patient->covidscreen->list_name_occasion) : [];
        }
        if($patient->covidassess) {
            $days_14_date_onset_illness = $patient->covidassess->days_14_date_onset_illness ? date('m/d/Y', strtotime($patient->covidassess->days_14_date_onset_illness)) : '';
            $referral_date = $patient->covidassess->referral_date ? date('m/d/Y', strtotime($patient->covidassess->referral_date)) : '';
            $xray_date = $patient->covidassess->xray_date ? date('m/d/Y', strtotime($patient->covidassess->xray_date)) : '';
            $date_collected = $patient->covidassess->date_collected ? date('m/d/Y', strtotime($patient->covidassess->date_collected)) : '';
            $date_sent_ritm = $patient->covidassess->date_sent_ritm ? date('m/d/Y', strtotime($patient->covidassess->date_sent_ritm)) : '';
            $date_received_ritm = $patient->covidassess->date_received_ritm ? date('m/d/Y', strtotime($patient->covidassess->date_received_ritm)) : '';
            $scrum = $patient->covidassess->scrum ? explode("|",$patient->covidassess->scrum) : [];
            $oro_naso_swab = $patient->covidassess->oro_naso_swab ? explode("|",$patient->covidassess->oro_naso_swab) : [];
            $spe_others = $patient->covidassess->spe_others ? explode("|",$patient->covidassess->spe_others) : [];
            $outcome_date_discharge = $patient->covidassess->outcome_date_discharge ? date('m/d/Y', strtotime($patient->covidassess->outcome_date_discharge)) : '';
        }
        if($patient->phyexam) {
            $conjunctiva = $patient->phyexam->conjunctiva;
            $neck = $patient->phyexam->neck;
            $breast = $patient->phyexam->breast;
            $thorax = $patient->phyexam->thorax;
            $abdomen = $patient->phyexam->abdomen;
            $genitals = $patient->phyexam->genitals;
            $extremities = $patient->phyexam->extremities;
        }
        if($patient->clinical) {
            $date_referral = $patient->clinical->date_referral ? date('m/d/Y', strtotime($patient->clinical->date_referral)) : '';
        }
        $municity =  MunicipalCity::all();
        $prescription = Prescription::orderBy('presc_code', 'asc')->get();
        return view('teleconsult.teleCall',[
            'nationality' => $nationality,
            'municity' => $municity,
        	'meeting' => $meetings,
            'case_no' => $case_no,
            'patient' => $patient,
            'facility' => $facility,
            'countries' =>$countries,
            'date_departure' => $date_departure,
            'date_arrival_ph' => $date_arrival_ph,
            'date_contact_known_covid_case' => $date_contact_known_covid_case,
            'acco_date_last_expose' => $acco_date_last_expose,
            'food_es_date_last_expose' => $food_es_date_last_expose,
            'store_date_last_expose' => $store_date_last_expose,
            'fac_date_last_expose' => $fac_date_last_expose,
            'event_date_last_expose' => $event_date_last_expose,
            'wp_date_last_expose' => $wp_date_last_expose,
            'list_name_occasion' => $list_name_occasion,
            'days_14_date_onset_illness' => $days_14_date_onset_illness,
            'referral_date' => $referral_date,
            'xray_date' => $xray_date,
            'date_collected' => $date_collected,
            'date_sent_ritm' => $date_sent_ritm,
            'date_received_ritm' => $date_received_ritm,
            'scrum' => $scrum,
            'oro_naso_swab' => $oro_naso_swab,
            'spe_others' => $spe_others,
            'outcome_date_discharge' => $outcome_date_discharge,
            'passw'=>$password,
            'username'=>$username,
            'role'=> $role,
            'conjunctiva' => $conjunctiva,
            'neck' => $neck,
            'breast' => $breast,
            'thorax' => $thorax,
            'abdomen' => $abdomen,
            'genitals' => $genitals,
            'extremities' => $extremities,
            'prescription' => $prescription,
            'date_referral' => $date_referral,
            'title' => $title,
            'emailname' => $emailname,
            'password' =>$password
        ]);
    }

    public function validateDateTime(Request $req) {
        $user = Session::get('auth');
    	$date = Carbon::parse($req->date)->format('Y-m-d');
    	$time = $req->time ? Carbon::parse($req->time)->format('H:i:s') : '';
        $doctor_id = $req->doctor_id ? $req->doctor_id : $user->id;
    	$endtime = Carbon::parse($time)
		            ->addMinutes($req->duration)
		            ->format('H:i:s');
		$meetings = Teleconsult::whereDate('date_meeting','=', $date)->where(function($q) use($doctor_id, $user) {
                $q->where('doctor_id', $doctor_id)
                ->orWhere('doctor_id', $user->id);
                })->get();
		$count = 1;
        if($date === Carbon::now()->format('Y-m-d') && $time <= Carbon::now()->addMinutes('180')->format('H:i:s') && $time) {
            return 'Not valid';
        } else {
    		foreach ($meetings as $meet) {
    			if(($time >= $meet->from_time && $time <= $meet->to_time) || ($endtime >= $meet->from_time && $endtime <= $meet->to_time) || ($meet->from_time >= $time && $meet->to_time <= $endtime) || ($meet->from_time >= $time && $meet->to_time <= $endtime)) {
    				return $meet->count();
    			}
    		}
        }
    }

    public function adminMeetingInfo(Request $req) {
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

    public function meetingInfo(Request $req) {
    	$meeting = Teleconsult::select(
    		"teleconsults.*",
    		"pat.*",
    		"teleconsults.id as meetID",
            "d.case_no as caseNO",
            "d.id as demographic_id",
            "ch.id as clinical_id",
            "pe.id as phy_id",
            "cs.id as covidscreen_id",
            "csa.id as covidassess_id",
            "das.id as diagassess_id",
            "fac.facilityname as FacName"
    	)->leftJoin("patients as pat","pat.id","=","teleconsults.patient_id")
        ->leftJoin("facilities as fac","fac.id","=","pat.facility_id")
        ->leftJoin("tele_demographic_profile as d","d.meeting_id","=","teleconsults.id")
        ->leftJoin("tele_clinical_histories as ch","ch.meeting_id","=","teleconsults.id")
        ->leftJoin("tele_physical_exams as pe","pe.meeting_id","=","teleconsults.id")
        ->leftJoin("tele_covid19_screening as cs","cs.meeting_id","=","teleconsults.id")
        ->leftJoin("tele_covid19_clinical_assessment as csa","csa.meeting_id","=","teleconsults.id")
        ->leftJoin("tele_diagnosis_assessment as das","das.meeting_id","=","teleconsults.id")
         ->where('teleconsults.id',$req->meet_id)
        ->first();
        if($meeting->phyexam) {
            $conjunctiva = $meeting->phyexam->conjunctiva;
            $neck = $meeting->phyexam->neck;
            $breast = $meeting->phyexam->breast;
            $thorax = $meeting->phyexam->thorax;
            $abdomen = $meeting->phyexam->abdomen;
            $genitals = $meeting->phyexam->genitals;
            $extremities = $meeting->phyexam->extremities;
        }

    	return json_encode($meeting);
    }

    public function getPendingMeeting($id) {
        $pend_meet = PendingMeeting::find($id);
        $encoded = $pend_meet->encoded->facility;
        $patient = $pend_meet->patient;
        $patname = \Crypt::decrypt($patient->fname).' '.\Crypt::decrypt($patient->mname).' '.\Crypt::decrypt($patient->lname);
        return response()->json([
            'pend_meet' => $pend_meet,
            'patname' => $patname
        ]);
    }

    public function acceptDeclineMeeting($id, Request $req) {
        $user = Auth::user();
        $userfac = $user->facility?->facilityname;
        $meet = PendingMeeting::find($id);
        $action = $req->action;
        $date = date('Y-m-d', strtotime($req->date_from));
        $time = date('H:i:s', strtotime($req->time));
        $endtime = Carbon::parse($time)
                            ->addMinutes($req->duration)
                            ->format('H:i:s');
        $duration = $req->duration;
        $patient = $meet->patient->lname.', '.$meet->patient->fname.' '.$meet->patient->mname;
        if($action == 'Accept') {
            $create_data = array(
                'user_id' => $meet->user_id,
                'doctor_id' => $meet->doctor_id,
                'patient_id' => $meet->patient_id,
                'date_meeting' => $date,
                'from_time' => $time,
                'to_time' => $endtime,
                'title' => $meet->title,
                'password' => 'doh'.Str::random(5),
                'is_started' => 0
            );
            $create_meeting = Teleconsult::create($create_data);
            PusherHelper::trigger('my-channel.'.$meet->user_id, 'my-event.'.$meet->user_id,[
                'title' => 'New Teleconsultation Accepted',
                'subtitle' => $userfac,
                'time' => Carbon::now(),
                'isSeen' => false,
            ]);
        }
        $meet_id = $action == 'Accept' ? $create_meeting->id : null;
        $data = array(
            'status' => $action,
            'meet_id' => $meet_id
        );
        $meet->update($data); 
        if($action == 'Accept') {
            event(new AcDecReq($user, $create_meeting, $action, $userfac));
            $to_name = $meet->encoded->fname.' '.$meet->encoded->mname.' '.$meet->encoded->lname;
            $to_email =$meet->encoded->email;
            $doctor = 'Dr. '.$meet->doctor->fname.' '.$meet->doctor->mname.' '.$meet->doctor->lname;
            $from_fac = $meet->doctor->facility->facilityname;
            $em = array(
                'to_name'=> $to_name,
                'patient' => $patient,
                'doctor' => $doctor,
                'from_fac' => $from_fac,
                'date' => $date.' '.$time,
                'complaint' => $meet->title

            );
            // Mail::send('teleconsult.email.email_accept', $em, function($message) use ($to_name, $to_email) {
            // $message->to($to_email, $to_name)
            // ->subject('Schedule for Teleconsultation');
            // $message->from('aronjbra20@gmail.com','DOH XII TELEMEDICINE');
            // });
        } else {
            event(new AcDecReq($user, $meet, $action, $userfac));
            Session::put("delete_action","Successfully Declined Teleconsultation.");
        }
    }

    public function getDocOrder(Request $req) {
        $docorder = DoctorOrder::find($req->docorderid);
        $labreq = $docorder ? $docorder->labreq : '';
        return response()->json([
            'docorder'=>$docorder,
            'labreq'=>$labreq
        ]);
    }

    public function labreqStore(Request $req) {
        $user = Session::get('auth');
        $fac_id = Session::get('auth')->facility->id;
        $files = $req->file('file');
        $pat_id = $req->doctororder_patient_id;
        if($req->hasFile('file'))
        {
            foreach ($files as $file) {
                $name = str_replace(' ', '', $file->getClientOriginalName());
                $file->move(public_path('labrequest').'/'.$fac_id.'/'.$pat_id,$name);
                $path = 'labrequest/'.$fac_id.'/'.$pat_id.'/'.$name;
                $data = array(
                    'docorderid' => $req->doctororder_id,
                    'doctypeid' => $req->doc_type,
                    'description' =>$req->description,
                    'filepath' => $path,
                    'filename'=> pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'extensionname'=>pathinfo($name, PATHINFO_EXTENSION),
                    'uploadedby'=> $user->id
                );
                DocOrderLabReq::create($data);
            }
        }
        Session::put("action_made","Successfully Add Lab Request.");

    }

    public function thankYouPage(Request $req) {
        return view('thankyou');
    }

    public function calendarMeetings(Request $req) {
        $user = Session::get('auth');
        $data = Teleconsult::select(
            "teleconsults.*",
            "teleconsults.id as meetID",
            "teleconsults.user_id as Creator",
            "teleconsults.doctor_id as RequestTo",
            "pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
            "pat.id as PatID",
            "users.facility_id as facid"
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id")
        ->leftJoin("users as users", "teleconsults.doctor_id", "=", "users.id")
        ->leftJoin("users as use", "teleconsults.user_id", "=", "users.id");
        $data = $data->where(function($q) use($user){
            $q->where("teleconsults.doctor_id","=", $user->id)
            ->orWhere("teleconsults.user_id", "=", $user->id)
            ->orWhere("teleconsults.user_id", "=", $user->id)
            ->orWhere("users.facility_id", "=", $user->facility_id)
            ->orWhere("use.facility_id", "=", $user->facility_id);
            })->orderBy('teleconsults.date_meeting', 'asc')
            ->get();
        $result = [];
        $join = '';
        foreach ($data as $value) {
            if($value->RequestTo == $user->id) {
              $join = 'no';
            } else if($value->Creator == $user->id) {
              $join = 'yes';
            }
            $values = array(
                'id' => $value->id,
                'title' => $value->title,
                'start' => $value->date_meeting.'T'.$value->from_time,
                'end' => $value->date_meeting.'T'.$value->to_time,
                'allow' => $join
            );
            array_push($result, $values);
        }
        return json_encode($result);
    }

    public function getDoctorsFacility(Request $req) {
        $user_id = Auth::user()->id;
        $doctors = User::where('facility_id',$req->fac_id)
                        ->where('doc_cat_id', $req->cat_id)
                        ->where('level', 'doctor')
                        ->where('id', '!=', $user_id)
                        ->orderBy('lname', 'asc')->get();
        return json_encode($doctors);
    }

    public function teleconsultDetails($id) {
        $decid = Crypt::decrypt($id);
        $meeting = Meeting::find($decid);
        return view('teleconsult.teledetails',[
            'meeting' => $meeting
        ]);
    }

    public function mycalendarMeetings(Request $req) {
        $user = Auth::user();
        $data = Teleconsult::select(
            "teleconsults.*",
            "teleconsults.id as meetID",
            "teleconsults.user_id as Creator",
            "teleconsults.doctor_id as RequestTo",
            "pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
            "pat.id as PatID",
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id");
        $data = $data->where(function($q) use($user){
            $q->where("teleconsults.doctor_id","=", $user->id)
            ->orWhere("teleconsults.user_id", "=", $user->id);
            })->orderBy('teleconsults.date_meeting', 'asc')
            ->get();
        $result = [];
        $join = '';
        foreach ($data as $value) {
            if($value->RequestTo == $user->id) {
              $join = 'no';
            } else if($value->Creator == $user->id) {
              $join = 'yes';
            }
            $values = array(
                'id' => $value->id,
                'title' => $value->title,
                'start' => $value->date_meeting.'T'.$value->from_time,
                'end' => $value->date_meeting.'T'.$value->to_time,
                'allow' => $join,
                'facility' => $value->doctor->facility->facilityname,
            );
            array_push($result, $values);
        }
        return json_encode($result);
    }

    public function getPrescription(Request $req) {
        $prescription = Meeting::find($req->id)->planmanage ? Meeting::find($req->id)->planmanage->prescription : [];
        $finalpres = [];
        if($prescription) {
            $arrpres = explode(",", $prescription);
            foreach ($arrpres as $value) {
                $pres = Prescription::where('presc_code', $value)->first();
                array_push($finalpres, $pres);
            }
            return view('teleconsult.prescription',[
                'prescription' => $finalpres
            ]);
        } else {
            return 'No prescription found.';
        }
    }

    public function declineTele($id, Request $req) {
        $meet = PendingMeeting::find($id);
        $data = array(
            'status' => 'Declined',
            'remarks' => $req->decline_message
        );
        $meet->update($data);
        Session::put("delete_action","Successfully Declined Teleconsultation.");
    }
}
