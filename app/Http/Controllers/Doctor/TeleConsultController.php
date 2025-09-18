<?php

namespace App\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\User;
use App\Patient;
use App\Meeting;
use Carbon\Carbon;
use App\PendingMeeting;
use App\Facility;
use App\TeleCategory;
use App\Countries;
use App\LabRequest;
use App\DoctorOrder;
use App\ZoomToken;
use App\DocOrderLabReq;
use Redirect;
use App\Doc_Type;
class TeleConsultController extends Controller
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
        return view('doctors.teleconsult',[
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

    public function validateDateTime(Request $req) {
        $user = Session::get('auth');
    	$date = Carbon::parse($req->date)->format('Y-m-d');
    	$time = $req->time ? Carbon::parse($req->time)->format('H:i:s') : '';
        $doctor_id = $req->doctor_id ? $req->doctor_id : $user->id;
    	$endtime = Carbon::parse($time)
		            ->addMinutes($req->duration)
		            ->format('H:i:s');
    	// $meetings = Meeting::whereDate('date_meeting','=', $date)
    	// 					->whereTime('from_time', '<=', $time)
    	// 					->whereTime('to_time', '>=', $time)
    	// 					->orWhereTime('from_time', '<=', $endtime)
    	// 					->whereTime('to_time', '>=', $endtime)
    	// 					->orWhereTime('from_time', '>=', $time)
    	// 					->whereTime('to_time', '<=', $time)
    	// 					->orWhereTime('from_time', '>=', $endtime)
    	// 					->whereTime('to_time', '<=', $endtime)
    	// 					->count();
		$meetings = Meeting::whereDate('date_meeting','=', $date)->where(function($q) use($doctor_id, $user) {
                $q->where('doctor_id', $doctor_id)
                ->orWhere('doctor_id', $user->id);
                })->get();
		$count = 1;
        if($date === Carbon::now()->format('Y-m-d') && $time <= Carbon::now()->addMinutes('180')->format('H:i:s') && $time) {
            return 'Not valid';
        }
		foreach ($meetings as $meet) {
			if(($time >= $meet->from_time && $time <= $meet->to_time) || ($endtime >= $meet->from_time && $endtime <= $meet->to_time) || ($meet->from_time >= $time && $meet->to_time <= $endtime) || ($meet->from_time >= $time && $meet->to_time <= $endtime)) {
				
				return $meet->count();
			}
		}
    }

    public function meetingInfo(Request $req) {
    	$meeting = Meeting::select(
    		"meetings.*",
    		"pat.*",
    		"meetings.id as meetID",
    	)->leftJoin("patients as pat","pat.id","=","meetings.patient_id")
         ->where('meetings.id',$req->meet_id)
        ->first();

    	return json_encode($meeting);
    }

    public function indexCall($id) {
        $user = Session::get('auth');
    	$meetings = Meeting::select(
    		"meetings.*",
            "pat.id as PATID",
    		"meetings.id as meetID"
    	)->leftJoin("patients as pat","pat.id","=","meetings.patient_id")
         ->where('meetings.id',$id)
        ->first();
        $api_key = env('ZOOM_API_KEY');
        $api_secret = env('ZOOM_API_SECRET');
        $meeting_number = $meetings->meeting_id;
        $password = $meetings->password;
        $role = $meetings->doctor_id == $user->id ? 1 : 0;
        $username = $user->fname.' '.$user->mname.' '.$user->lname;
        //Set the timezone to UTC
        date_default_timezone_set("UTC");

        $time = time() * 1000 - 30000;//time in milliseconds (or close enough)
        
        $data = base64_encode($api_key . $meeting_number . $time . $role);
        
        $hash = hash_hmac('sha256', $data, $api_secret, true);
        
        $_sig = $api_key . "." . $meeting_number . "." . $time . "." . $role . "." . base64_encode($hash);
        $signature = rtrim(strtr(base64_encode($_sig), '+/', '-_'), '=');

        $patient = Patient::find($meetings->PATID);
        $case_no = mt_rand(100000000, 999999999);
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
        return view('doctors.teleCall',[
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
            'signature'=>$signature,
            'api_key'=>$api_key,
            'meetnum'=>$meeting_number,
            'passw'=>$password,
            'username'=>$username
        ]);
    }

    public function storeToken(Request $req) {
        $envKey = 'WEBEX_API';
        $envValue = $req->webextoken;
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        $oldValue = env($envKey);

        $str = str_replace("{$envKey}={$oldValue}", "{$envKey}={$envValue}\n", $str);

        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
    }

    public function getPendingMeeting($id) {
        $pend_meet = PendingMeeting::find($id);
        $encoded = $pend_meet->encoded->facility;
        $patient = $pend_meet->patient;
        return response()->json($pend_meet);
    }

    public function acceptDeclineMeeting($id, Request $req) {
        $user = Session::get('auth');
        $meet = PendingMeeting::find($id);
        $action = $req->action;
        $date = date('Y-m-d', strtotime($meet->datefrom));
        $time = date('H:i:s', strtotime($meet->time));
        $endtime = Carbon::parse($time)
                            ->addMinutes($meet->duration)
                            ->format('H:i:s');
        $start = $date.'T'.$time;
        $duration = $meet->duration;
        $password = str_random(6);
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        if($action == 'Accept') {
            $db = ZoomToken::where('user_id',$user->id)->first();
            $arr_token = json_decode($db->provider_value);
            $accessToken = $arr_token->access_token;
            $response = $client->request('POST', '/v2/users/me/meetings', [
                "headers" => [
                    "Authorization" => "Bearer $accessToken"
                ],
                'json' => [
                    "topic" => $meet->title,
                    "type" => 2,
                    "start_time" => $start,
                    "duration" => $duration,
                    "password" => $password
                ],
            ]);
      
            $data = json_decode($response->getBody(), true);
            $create_data = array(
                'user_id' => $meet->user_id,
                'doctor_id' => $meet->doctor_id,
                'patient_id' => $meet->patient_id,
                'date_meeting' => $date,
                'from_time' => $time,
                'to_time' => $endtime,
                'meeting_id' => $data['id'],
                'title' => $data['topic'],
                'password' => $data['password'],
                'web_link' => $data['join_url'],
            );
            $create_meeting = Meeting::create($create_data);
        }
        $meet_id = $action == 'Accept' ? $create_meeting->id : '0';
        $data = array(
            'status' => $action,
            'meet_id' => $meet_id
        );
        $meet->update($data); 
        if($action == 'Accept') {
            Session::put("action_made","Successfully Accept Teleconsultation.");
        } else {
            Session::put("delete_action","Successfully Declined Teleconsultation.");
        }
    }

    public function schedTeleStore(Request $req) {
        $date = date('Y-m-d', strtotime($req->date_from));
        $req->request->add([
            'status' => 'Pending',
            'datefrom' => $date
        ]);
        if($req->meeting_id) {
            PendingMeeting::find($req->meeting_id)->update($req->except('meeting_id', 'facility_id', 'date_from'));
        } else {
            PendingMeeting::create($req->except('meeting_id', 'facility_id', 'date_from'));
        }
        Session::put("action_made","Please wait for the confirmation of doctor.");
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

    public function zoomMeeting(Request $req) {
        $api_key = '51JAnl6LT5eDa9b2oX9gpA';
        $api_secret = 'oBESX7AoVyMbNbjwT3PeJe05qxW2ZOP23Yj9';
        $meeting_number = '76688557339';
        $password = 'p95w03';
        $role = 1;
        //Set the timezone to UTC
        date_default_timezone_set("UTC");

        $time = time() * 1000 - 30000;//time in milliseconds (or close enough)
        
        $data = base64_encode($api_key . $meeting_number . $time . $role);
        
        $hash = hash_hmac('sha256', $data, $api_secret, true);
        
        $_sig = $api_key . "." . $meeting_number . "." . $time . "." . $role . "." . base64_encode($hash);
        
        //return signature, url safe base64 encoded
        $signature = rtrim(strtr(base64_encode($_sig), '+/', '-_'), '=');
        return response()->json([
            'signature'=>$signature,
            'api_key'=>$api_key,
            'meetnum'=>$meeting_number,
            'passw'=>$password
        ]);
    }

    public function zoomToken(Request $req) {
        $user_id = Session::get('auth')->id;
        $client_id = env('ZOOM_CLIENT_ID');
        $client_secret = env('ZOOM_CLIENT_SECRET');
        $direct_url = env('ZOOM_REDIRECT_URL');
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
  
        $response = $client->request('POST', '/oauth/token', [
            "headers" => [
                "Authorization" => "Basic ". base64_encode($client_id.':'.$client_secret)
            ],
            'form_params' => [
                "grant_type" => "authorization_code",
                "code" => $req->code,
                "redirect_uri" => $direct_url
            ],
        ]);

        $token = json_decode($response->getBody()->getContents(), true);
        $data = array('user_id' => $user_id,'provider' => 'zoom', 'provider_value' => json_encode($token) );
        $zoomtoken = ZoomToken::where('user_id',$user_id)->first() ?  ZoomToken::where('user_id',$user_id)->first()->update($data) : ZoomToken::create($data);
        echo "Your access token Successfully Refresh. You can close this tab now.";
    }

    public function refreshToken(Request $req) {
        $user_id = Session::get('auth')->id;
        $zoomtoken = ZoomToken::where('user_id',$user_id)->first();
        return json_encode($zoomtoken);
        
    }

    public function thankYouPage(Request $req) {
        return view('thankyou');
    }

    public function calendarMeetings(Request $req) {
        $user = Session::get('auth');
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
        $data = $data->where(function($q) use($user){
            $q->where("meetings.doctor_id","=", $user->id)
            ->orWhere("meetings.user_id", "=", $user->id);
            })->orderBy('meetings.date_meeting', 'asc')
            ->get();
        $data_req = PendingMeeting::select(
            "pending_meetings.*",
            "pending_meetings.id as meetID",
            "pending_meetings.created_at as reqDate",
            "pat.lname as patLname",
            "pat.fname as patFname",
            "pat.mname as patMname",
        )->leftJoin("patients as pat", "pending_meetings.patient_id", "=", "pat.id");
        $data_req = $data_req->where("pending_meetings.doctor_id","=", $user->id)
                ->where('status', 'Pending')
                ->orderBy('pending_meetings.id', 'desc')
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
        foreach ($data_req as $value) {
            $time = Carbon::parse($value->time)->format('H:i:s');
            $endtime = Carbon::parse($time)
                    ->addMinutes($value->duration)
                    ->format('H:i:s');
            $values = array(
                'id' => $value->meetID,
                'title' => $value->title,
                'start' => $value->datefrom.'T'.$time,
                'end' => $value->date_meeting.'T'.$endtime,
                'allow' => 'request'
            );
            array_push($result, $values);
        }
        return json_encode($result);
    }
}
