<?php

namespace App\Http\Controllers\Tele;

use App\Events\AcDecReq;
use App\Helpers\PusherHelper;
use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\Doc_Type;
use App\Models\DocCategory;
use App\Models\DocOrderLabReq;
use App\Models\DoctorOrder;
use App\Models\Facility;
use App\Models\LabRequest;
use App\Models\Meeting;
use App\Models\MunicipalCity;
use App\Models\Patient;
use App\Models\PendingMeeting;
use App\Models\Prescription;
use App\Models\Teleconsult;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

use App\Models\DemoProfile;
use App\Models\ClinicalHistory;
use App\Models\PhysicalExam;
use App\Models\CovidScreening;
use App\Models\CovidAssessment;
use App\Models\diagnosisAssessment;
use App\Models\PlanManagement;



class TeleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $keyword = $request->view_all ? '' : $request->date_range;
        $data = Teleconsult::select(
            'teleconsults.*',
            'teleconsults.id as meetID',
            'teleconsults.user_id as Creator',
            'teleconsults.doctor_id as RequestTo',
            'pat.lname as patLname',
            'pat.fname as patFname',
            'pat.mname as patMname',
            'pat.dob as dob',
            'pat.sex as sex',
            'pat.civil_status as civil_status',
            'pat.id as PatID',
        )->leftJoin('patients as pat', 'teleconsults.patient_id', '=', 'pat.id');
        if ($keyword) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[1]));
            $data = $data
                ->where(function ($q) use ($date_start, $date_end) {
                    $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
                });
        }
        $activeid = $user->patient ? $user->patient->id : $user->id;
        $data = $data->where(function ($q) use ($activeid) {
            $q->where('teleconsults.doctor_id', '=', $activeid)
                ->orWhere('teleconsults.user_id', '=', $activeid)
                ->orWhere('teleconsults.patient_id', '=', $activeid);
        })->whereDate('teleconsults.date_meeting', '=', Carbon::now()->toDateString())
            ->orderBy('teleconsults.date_meeting', 'asc')
            ->get();
        $data->load('encoded.facility');
        $patients = Patient::select(
            'patients.*',
            'bar.brg_name as barangay',
            'user.email as email',
            'user.username as username',
        )->leftJoin('barangays as bar', 'bar.brg_psgc', '=', 'patients.brgy')
            ->leftJoin('users as user', 'user.id', '=', 'patients.account_id')
            ->where('patients.doctor_id', $user->id)
            ->orderby('patients.lname', 'asc')
            ->get();

        $keyword_past = $request->view_all_past ? '' : $request->date_range_past;
        $data_past = Teleconsult::select(
            'teleconsults.*',
            'teleconsults.id as meetID',
            'teleconsults.user_id as Creator',
            'teleconsults.doctor_id as RequestTo',
            'pat.lname as patLname',
            'pat.fname as patFname',
            'pat.mname as patMname',
            'pat.id as PatID',
        )->leftJoin('patients as pat', 'teleconsults.patient_id', '=', 'pat.id');
        if ($keyword_past) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range_past)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range_past)[1]));
            $data_past = $data_past
                ->where(function ($q) use ($date_start, $date_end) {
                    $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
                });
        }
        $data_past = $data_past->where(function ($q) use ($user) {
            $q->where('teleconsults.doctor_id', '=', $user->id)
                ->orWhere('teleconsults.user_id', '=', $user->id);
        })->whereDate('teleconsults.date_meeting', '<', Carbon::now()->toDateString())
            ->orderBy('teleconsults.date_meeting', 'desc')
            ->get();
        $data_past->load('encoded.facility');
        $keyword_req = $request->view_all_req ? '' : $request->date_range_req;
        $data_req = PendingMeeting::select(
            'pending_meetings.*',
            'pending_meetings.id as meetID',
            'pending_meetings.created_at as reqDate',
            'pat.lname as patLname',
            'pat.fname as patFname',
            'pat.mname as patMname',
        )->leftJoin('patients as pat', 'pending_meetings.patient_id', '=', 'pat.id');
        if ($keyword_req) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range_req)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range_req)[1]));
            $data_req = $data_req
                ->where(function ($q) use ($date_start, $date_end) {
                    $q->whereDate('pending_meetings.datefrom', '>=', $date_start);
                    $q->whereDate('pending_meetings.datefrom', '<=', $date_end);
                });
        }
        $status_req = $request->view_all_req ? '' : ($request->status_req ? $request->status_req : 'Pending');
        $active_tab = $request->active_tab ? $request->active_tab : 'request';
        if ($status_req) {
            $data_req = $data_req->where(function ($q) use ($status_req) {
                $q->where('pending_meetings.status', $status_req);
            });
        }
        $data_req = $data_req->where('pending_meetings.doctor_id', '=', $user->id)
            ->orderBy('pending_meetings.id', 'desc')
            ->get();
        $data_req->load('facility', 'patient');
        $data_my_req = PendingMeeting::select(
            'pending_meetings.*',
            'pending_meetings.id as meetID',
            'pending_meetings.created_at as reqDate',
            'pat.lname as patLname',
            'pat.fname as patFname',
            'pat.mname as patMname',
        )->leftJoin('patients as pat', 'pending_meetings.patient_id', '=', 'pat.id')
            ->where('pending_meetings.user_id', '=', $user->id)
            ->orderBy('pending_meetings.id', 'desc')
            ->get();
        $data_my_req->load('facility', 'doctor');
        $facilities = Facility::orderBy('facilityname', 'asc')->get();
        $count_req = PendingMeeting::select(
            'pending_meetings.*',
            'pending_meetings.id as meetID',
            'pending_meetings.created_at as reqDate',
        )->leftJoin('patients as pat', 'pending_meetings.patient_id', '=', 'pat.id')
            ->where('pending_meetings.status', 'Pending')
            ->where('pending_meetings.doctor_id', '=', $user->id)->count();
        $telecat = DocCategory::orderBy('category_name', 'asc')->get();
        $labreq = LabRequest::where('req_type', 'LAB')->orderby('description', 'asc')->get();
        $imaging = LabRequest::where('req_type', 'RAD')->orderby('description', 'asc')->get();
        $docorder = DoctorOrder::where('doctorid', $user->id)->get();
        $doc_type = Doc_Type::where('isactive', '1')->orderBy('doc_name', 'asc')->get();

        $data_up = Teleconsult::select(
            'teleconsults.*',
            'teleconsults.id as meetID',
            'teleconsults.user_id as Creator',
            'teleconsults.doctor_id as RequestTo',
            'pat.lname as patLname',
            'pat.fname as patFname',
            'pat.mname as patMname',
            'pat.dob as dob',
            'pat.sex as sex',
            'pat.civil_status as civil_status',
            'pat.id as PatID',
        )->leftJoin('patients as pat', 'teleconsults.patient_id', '=', 'pat.id');
        if ($keyword) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[1]));
            $data_up = $data_up
                ->where(function ($q) use ($date_start, $date_end) {
                    $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
                });
        }
        $activeid = $user->patient ? $user->patient->id : $user->id;
        $data_up = $data_up->where(function ($q) use ($activeid) {
            $q->where('teleconsults.doctor_id', '=', $activeid)
                ->orWhere('teleconsults.user_id', '=', $activeid)
                ->orWhere('teleconsults.patient_id', '=', $activeid);
        })->whereDate('teleconsults.date_meeting', '>=', Carbon::now()->toDateString())
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
            'doc_type' => $doc_type,
            'upcome' => count($data),
        ]);
    }

    public function schedTeleStore(Request $req)
    {
        $docid = $req->doctor_id;
        $user = Auth::user();
        $user_id = $user->id;
        $facility = $user->facility->facilityname;
        // PusherHelper::trigger('my-channel.'.$docid, 'my-event.'.$docid, [
        //     'title' => 'New Teleconsultation Request',
        //     'subtitle' => $facility,
        //     'time' => Carbon::now(),
        //     'isSeen' => false,
        // ]);
        $req->request->add([
            'user_id' => $user_id,
            'status' => 'Pending',
        ]);
        if ($req->meeting_id) {
            $meet = PendingMeeting::find($req->meeting_id)->update($req->except('meeting_id'));
        } else {
            $meet = PendingMeeting::create($req->except('meeting_id'));
        }
    }

    public function indexCall($id)
    {
        $user = Session::get('auth');
        $decid = Crypt::decrypt($id);
        $meetings = Teleconsult::select(
            'teleconsults.*',
            'pat.id as PATID',
            'teleconsults.id as meetID'
        )->leftJoin('patients as pat', 'pat.id', '=', 'teleconsults.patient_id')
            ->where('teleconsults.id', $decid)
            ->first();
        $title = $meetings->title;
        $emailname = Crypt::decrypt($meetings->doctor->email);
        $password = $meetings->password;
        $role = $meetings->doctor_id == $user->id ? 1 : 0;
        $username = $user->fname.' '.$user->mname.' '.$user->lname;
        //Set the timezone to UTC
        date_default_timezone_set('UTC');

        $time = time() * 1000 - 30000; //time in milliseconds (or close enough)
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
        if ($patient->covidscreen) {
            $date_departure = $patient->covidscreen->date_departure ? date('m/d/Y', strtotime($patient->covidscreen->date_departure)) : '';
            $date_arrival_ph = $patient->covidscreen->date_arrival_ph ? date('m/d/Y', strtotime($patient->covidscreen->date_arrival_ph)) : '';
            $date_contact_known_covid_case = $patient->covidscreen->date_contact_known_covid_case ? date('m/d/Y', strtotime($patient->covidscreen->date_contact_known_covid_case)) : '';
            $acco_date_last_expose = $patient->covidscreen->acco_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->acco_date_last_expose)) : '';
            $food_es_date_last_expose = $patient->covidscreen->food_es_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->food_es_date_last_expose)) : '';
            $store_date_last_expose = $patient->covidscreen->store_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->store_date_last_expose)) : '';
            $fac_date_last_expose = $patient->covidscreen->fac_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->fac_date_last_expose)) : '';
            $event_date_last_expose = $patient->covidscreen->event_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->event_date_last_expose)) : '';
            $wp_date_last_expose = $patient->covidscreen->wp_date_last_expose ? date('m/d/Y', strtotime($patient->covidscreen->wp_date_last_expose)) : '';
            $list_name_occasion = $patient->covidscreen->list_name_occasion ? explode('|', $patient->covidscreen->list_name_occasion) : [];
        }
        if ($patient->covidassess) {
            $days_14_date_onset_illness = $patient->covidassess->days_14_date_onset_illness ? date('m/d/Y', strtotime($patient->covidassess->days_14_date_onset_illness)) : '';
            $referral_date = $patient->covidassess->referral_date ? date('m/d/Y', strtotime($patient->covidassess->referral_date)) : '';
            $xray_date = $patient->covidassess->xray_date ? date('m/d/Y', strtotime($patient->covidassess->xray_date)) : '';
            $date_collected = $patient->covidassess->date_collected ? date('m/d/Y', strtotime($patient->covidassess->date_collected)) : '';
            $date_sent_ritm = $patient->covidassess->date_sent_ritm ? date('m/d/Y', strtotime($patient->covidassess->date_sent_ritm)) : '';
            $date_received_ritm = $patient->covidassess->date_received_ritm ? date('m/d/Y', strtotime($patient->covidassess->date_received_ritm)) : '';
            $scrum = $patient->covidassess->scrum ? explode('|', $patient->covidassess->scrum) : [];
            $oro_naso_swab = $patient->covidassess->oro_naso_swab ? explode('|', $patient->covidassess->oro_naso_swab) : [];
            $spe_others = $patient->covidassess->spe_others ? explode('|', $patient->covidassess->spe_others) : [];
            $outcome_date_discharge = $patient->covidassess->outcome_date_discharge ? date('m/d/Y', strtotime($patient->covidassess->outcome_date_discharge)) : '';
        }
        if ($patient->phyexam) {
            $conjunctiva = $patient->phyexam->conjunctiva;
            $neck = $patient->phyexam->neck;
            $breast = $patient->phyexam->breast;
            $thorax = $patient->phyexam->thorax;
            $abdomen = $patient->phyexam->abdomen;
            $genitals = $patient->phyexam->genitals;
            $extremities = $patient->phyexam->extremities;
        }
        if ($patient->clinical) {
            $date_referral = $patient->clinical->date_referral ? date('m/d/Y', strtotime($patient->clinical->date_referral)) : '';
        }
        $municity = MunicipalCity::all();
        $prescription = Prescription::orderBy('presc_code', 'asc')->get();

        return view('teleconsult.teleCall', [
            'nationality' => $nationality,
            'municity' => $municity,
            'meeting' => $meetings,
            'case_no' => $case_no,
            'patient' => $patient,
            'facility' => $facility,
            'countries' => $countries,
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
            'passw' => $password,
            'username' => $username,
            'role' => $role,
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
            'password' => $password,
        ]);
    }

    public function validateDateTime(Request $req)
    {
        $user = Session::get('auth');
        $date = Carbon::parse($req->date)->format('Y-m-d');
        $time = $req->time ? Carbon::parse($req->time)->format('H:i:s') : '';
        $doctor_id = $req->doctor_id ? $req->doctor_id : $user->id;
        $endtime = Carbon::parse($time)
            ->addMinutes($req->duration)
            ->format('H:i:s');
        $meetings = Teleconsult::whereDate('date_meeting', '=', $date)->where(function ($q) use ($doctor_id, $user) {
            $q->where('doctor_id', $doctor_id)
                ->orWhere('doctor_id', $user->id);
        })->get();
        $count = 1;
        if ($date === Carbon::now()->format('Y-m-d') && $time <= Carbon::now()->addMinutes('180')->format('H:i:s') && $time) {
            return 'Not valid';
        } else {
            foreach ($meetings as $meet) {
                if (($time >= $meet->from_time && $time <= $meet->to_time) || ($endtime >= $meet->from_time && $endtime <= $meet->to_time) || ($meet->from_time >= $time && $meet->to_time <= $endtime) || ($meet->from_time >= $time && $meet->to_time <= $endtime)) {
                    return $meet->count();
                }
            }
        }
    }

    // public function adminMeetingInfo(Request $req)
    // {
    //     $meeting = Meeting::select(
    //         'meetings.*',
    //         'pat.*',
    //         'meetings.id as meetID',
    //         'user.fname as docfname',
    //         'user.mname as docmname',
    //         'user.lname as doclname',
    //     )->leftJoin('patients as pat', 'pat.id', '=', 'meetings.patient_id')
    //         ->leftJoin('users as user', 'user.id', '=', 'pat.doctor_id')
    //         ->where('meetings.id', $req->meet_id)
    //         ->first();

    //     return json_encode($meeting);
    // }

        public function adminMeetingInfo(Request $req)
    {
        $meeting = Meeting::select(
            'teleconsults.*',
            'pat.*',
            'teleconsults.id as meetID',
            'user.fname as docfname',
            'user.mname as docmname',
            'user.lname as doclname',
        )->leftJoin('patients as pat', 'pat.id', '=', 'teleconsults.patient_id')
            ->leftJoin('users as user', 'user.id', '=', 'pat.doctor_id')
            ->where('teleconsults.id', $req->meet_id)
            ->first();

        return json_encode($meeting);
    }

    public function meetingInfo(Request $req)
    {
        $meeting = Teleconsult::select(
            'brgyp.brg_name as pbrgyname',
            'munp.muni_name as pmuniname',
            'provp.prov_name as pprov',
            'brgy.brg_name as brgyname',
            'mun.muni_name as muniname',
            'reg.reg_desc as regname',
            'prov.prov_name as provname',
            'user.fname as docfname',
            'user.mname as docmname',
            'user.lname as doclname',
            'teleconsults.*',
            'pat.*',
            'pat.id as patID',
            'teleconsults.id as meetID',
            'd.case_no as caseNO',
            'd.id as demographic_id',
            'ch.id as clinical_id',
            'pe.*',
            'pe.id as phy_id',
            'cs.id as covidscreen_id',
            'csa.id as covidassess_id',
            'das.id as diagassess_id',
            'fac.facilityname as FacName'
        )->leftJoin('patients as pat', 'pat.id', '=', 'teleconsults.patient_id')
            ->leftJoin('facilities as fac', 'fac.id', '=', 'pat.facility_id')
            ->leftJoin('tele_demographic_profile as d', 'd.meeting_id', '=', 'teleconsults.id')
            ->leftJoin('tele_clinical_histories as ch', 'ch.meeting_id', '=', 'teleconsults.id')
            ->leftJoin('tele_physical_exams as pe', 'pe.meeting_id', '=', 'teleconsults.id')
            ->leftJoin('tele_covid19_screening as cs', 'cs.meeting_id', '=', 'teleconsults.id')
            ->leftJoin('tele_covid19_clinical_assessment as csa', 'csa.meeting_id', '=', 'teleconsults.id')
            ->leftJoin('tele_diagnosis_assessment as das', 'das.meeting_id', '=', 'teleconsults.id')
            ->leftJoin('users as user', 'user.id', '=', 'pat.doctor_id')
            ->leftJoin('regions as reg', 'reg.reg_psgc', '=', 'fac.reg_psgc')
            ->leftJoin('provinces as prov','prov.prov_psgc','=', 'fac.prov_psgc')
            ->leftJoin('municipal_cities as mun','mun.muni_psgc','=', 'fac.muni_psgc')
            ->leftJoin('barangays as brgy','brgy.brg_psgc','=', 'fac.brgy_psgc')
            //patient full address
            ->leftJoin('provinces as provp','provp.prov_psgc','=', 'pat.province')
            ->leftJoin('municipal_cities as munp','munp.muni_psgc','=', 'pat.muncity')
            ->leftJoin('barangays as brgyp','brgyp.brg_psgc','=', 'pat.brgy')
            ->where('teleconsults.id', $req->meet_id)
            ->first();

        return json_encode($meeting);
    }

    // Get Demographic Profile by meeting_id
    public function getDP($meeting_id)
    {
        try {
            \Log::info("Fetching Demographic Profile for meeting_id: {$meeting_id}");

            $dp = \App\Models\DemoProfile::where('meeting_id', $meeting_id)->first();

            if (!$dp) {
                return response()->json([
                    'message' => 'No demographic profile found for this meeting.',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Demographic profile retrieved successfully.',
                'data' => $dp,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('DP Fetch Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to fetch demographic profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store / Update Demographic Profile
    public function storeDP(Request $request)
    {
        try {
            \Log::info('Incoming DP Request:', $request->all());

            // ✅ Convert empty strings to null to avoid validation issues
            $data = collect($request->all())->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // ✅ Validate request data
            $validated = validator($data, [
                'meeting_id'             => 'required|integer',
                'name_physician'         => 'required|string|max:255',
                'address_health'         => 'nullable|string|max:255',
                'tele_partner_platform'  => 'nullable|string|max:255',
                'prior_tele_proper'      => 'required|integer',
                'is_patient_accompanied' => 'required|integer',
                'case_no'                => 'required|integer',
                'name_of_companion'      => 'nullable|string|max:255',
                'relationship'           => 'nullable|string|max:255',
                'phone_no'               => 'nullable|string|max:255',
            ])->validate();

            // ✅ Try to find an existing record first
            $existingDP = \App\Models\DemoProfile::where('meeting_id', $validated['meeting_id'])->first();

            if ($existingDP) {
                // ✅ Update the existing demographic profile
                $existingDP->update($validated);

                return response()->json([
                    'message' => 'Demographic profile updated successfully.',
                    'data' => $existingDP,
                    'status' => 'updated',
                ], 200);
            }

            // ✅ Otherwise, create a new one
            $demoProfile = \App\Models\DemoProfile::create($validated);

            return response()->json([
                'message' => 'Demographic profile saved successfully.',
                'data' => $demoProfile,
                'status' => 'created',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('DP Save/Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save demographic profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ Get Clinical History by meeting_id
    public function getCH($meeting_id)
    {
        try {
            \Log::info("Fetching Clinical History for meeting_id: {$meeting_id}");

            $ch = \App\Models\ClinicalHistory::where('meeting_id', $meeting_id)->first();

            if (!$ch) {
                return response()->json([
                    'message' => 'No clinical history found for this meeting.',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Clinical history retrieved successfully.',
                'data' => $ch,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('CH Fetch Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to fetch clinical history.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store / Update clinical history
    public function storeCH(Request $request)
    {
        try {
            \Log::info('Incoming CH Request:', $request->all());

            // ✅ Convert empty strings to null to avoid validation issues
            $data = collect($request->all())->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // ✅ Validate request data
            $validated = validator($data, [
                'meeting_id'                   => 'required|integer',
                'reason_consult'               => 'required|string|max:255',
                'date_onset_illness'           => 'required|date',
                'facility_id'                  => 'required|integer',
                'date_referral'                => 'nullable|date',
                'known_medical_history'        => 'required|string|max:255',
                'current_medication'           => 'required|string|max:255',
                'blood_type'                   => 'required|string|max:10',
                'clinical_status_time_consult' => 'required|string|max:50',
                'specific_findings'            => 'required|string|max:50',
            ])->validate();

            // ✅ Try to find an existing record first
            $existingCH = \App\Models\ClinicalHistory::where('meeting_id', $validated['meeting_id'])->first();

            if ($existingCH) {
                // ✅ Update the existing demographic profile
                $existingCH->update($validated);

                return response()->json([
                    'message' => 'Demographic profile updated successfully.',
                    'data' => $existingCH,
                    'status' => 'updated',
                ], 200);
            }

            // ✅ Otherwise, create a new one
            $clinicalHistory = \App\Models\PhysicalExam::create($validated);

            return response()->json([
                'message' => 'Clinical history saved successfully.',
                'data' => $clinicalHistory,
                'status' => 'created',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('CH Save/Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save clinical history.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ Get Physical Exam by meeting_id
    public function getPE($meeting_id)
    {
        try {
            \Log::info("Fetching Physical Exam for meeting_id: {$meeting_id}");

            $pe = \App\Models\PhysicalExam::where('meeting_id', $meeting_id)->first();

            if (!$pe) {
                return response()->json([
                    'message' => 'No Physical Exam found for this meeting.',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Physical Exam retrieved successfully.',
                'data' => $pe,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('PE Fetch Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to fetch physical exam.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store / Update physical exam
    public function storePE(Request $request)
    {
        try {
            \Log::info('Incoming PE Request:', $request->all());

            // ✅ Convert empty strings to null to avoid validation issues
            $data = collect($request->all())->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // ✅ Validate request data
            $validated = validator($data, [
                'meeting_id'         => 'required|integer',
                'head'               => 'required|string|max:100',
                'conjunctiva'        => 'required|string|max:100',
                'con_remarks'        => 'nullable|string|max:255',
                'neck'               => 'required|string|max:100',
                'chest'              => 'required|string|max:100',
                'breast'             => 'required|string|max:100',
                'breast_remarks'     => 'nullable|string|max:255',
                'thorax'             => 'required|string|max:100',
                'thorax_remarks'     => 'nullable|string|max:255',
                'abdomen'            => 'required|string|max:100',
                'abdomen_remarks'    => 'nullable|string|max:255',
                'genitals'           => 'required|string|max:100',
                'genital_remarks'    => 'nullable|string|max:255',
                'extremities'        => 'required|string|max:100',
                'extremities_remarks'=> 'nullable|string|max:255',
                'others'             => 'nullable|string|max:255',
                'waist_circumference'=> 'nullable|string|max:255',
            ])->validate();


            // ✅ Try to find an existing record first
            $existingPE = \App\Models\PhysicalExam::where('meeting_id', $validated['meeting_id'])->first();

            if ($existingPE) {
                // ✅ Update the existing demographic profile
                $existingPE->update($validated);

                return response()->json([
                    'message' => 'Demographic profile updated successfully.',
                    'data' => $existingPE,
                    'status' => 'updated',
                ], 200);
            }

            // ✅ Otherwise, create a new one
            $physicalExam = \App\Models\PhysicalExam::create($validated);

            return response()->json([
                'message' => 'Physical exam saved successfully.',
                'data' => $physicalExam,
                'status' => 'created',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('PE Save/Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save physical exam.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //fetch facility list
    public function getFacilities()
    {
        try {
            $facilities = Facility::orderBy('facilityname', 'asc')
                ->get(['id', 'facilityname']);

            return response()->json([
                'status' => 'success',
                'data' => $facilities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // Get Covid-19 Screening by meeting_id
    public function getCV($meeting_id)
    {
        try {
            \Log::info("Fetching Covid-19 Screening for meeting_id: {$meeting_id}");

            $cv = \App\Models\CovidScreening::where('meeting_id', $meeting_id)->first();

            if (!$cv) {
                return response()->json([
                    'message' => 'No Covid-19 Screening found for this meeting.',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Covid-19 Screening retrieved successfully.',
                'data' => $cv,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('DP Fetch Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to fetch Covid-19 Screening.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store / Update covid 19-screening 
    public function storeCV(Request $request)
    {
        try {
            \Log::info('Incoming CV Request:', $request->all());

            // ✅ Convert empty strings to null to avoid validation issues
            $data = collect($request->all())->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // ✅ Validate request data
            $validated = validator($data, [
                'meeting_id'                     => 'required|integer',
                'employers_name'                 => 'nullable|string|max:255',
                'place_of_work'                  => 'nullable|string|max:255',
                'house_bldg_name'                => 'nullable|string|max:255',
                'street'                         => 'nullable|string|max:255',
                'municipal'                      => 'nullable|string|max:255',
                'province'                       => 'nullable|string|max:255',
                'country_id'                     => 'nullable|integer',
                'office_phone_no'                => 'nullable|string|max:50',
                'cellphone_no'                   => 'nullable|string|max:20',
                'history_travel_country_symptoms'=> 'nullable|integer',
                'port_of_exit'                   => 'nullable|string|max:255',
                'airline_sea_vessel'             => 'nullable|string|max:255',
                'flight_vessel_no'               => 'nullable|string|max:255',
                'date_departure'                 => 'nullable|date',
                'date_arrival_ph'                => 'nullable|date',
                'known_covid_case'               => 'nullable|integer',
                'date_contact_known_covid_case ' => 'nullable|date',
                'accomodation'                   => 'nullable|integer',
                'acco_specify_type'              => 'nullable|string|max:255',
                'acco_address'                   => 'nullable|string|max:255',
                'acco_date_last_expose'          => 'nullable|date',
                'acco_name'                      => 'nullable|string|max:255',
                'acco_name_type'                 => 'nullable|integer',
                'food_establishment'             => 'nullable|integer',
                'food_es_specify_type'           => 'nullable|string|max:255',
                'food_es_address'                => 'nullable|string|max:255',
                'food_es_date_last_expose'       => 'nullable|date',
                'food_es_name'                   => 'nullable|string|max:255',
                'food_es_name_type'              => 'nullable|integer',
                'store'                          => 'nullable|string|max:255',
                'store_specify_type'             => 'nullable|string|max:255',
                'store_address'                  => 'nullable|string|max:255',
                'store_date_last_expose'         => 'nullable|date',
                'store_name'                     => 'nullable|string|max:255',
                'store_name_type'                => 'nullable|integer',
                'facility'                       => 'nullable|integer',
                'fac_specify_type'               => 'nullable|string|max:255',
                'fac_address'                    => 'nullable|string|max:255',
                'fac_date_last_expose'           => 'nullable|date',
                'fac_name'                       => 'nullable|string|max:255',
                'fac_name_type'                  => 'nullable|integer',
                'fac_significant_other'          => 'nullable|string|max:255',
                'event'                          => 'nullable|integer',
                'event_specify_type'             => 'nullable|string|max:255',
                'event_date_last_expose'         => 'nullable|date',
                'event_place'                    => 'nullable|string|max:255',
                'workplace'                      => 'nullable|integer',
                'wp_company_name'                => 'nullable|string|max:255',
                'wp_date_last_expose'            => 'nullable|date',
                'wp_address'                     => 'nullable|string|max:255',
                'list_name_occasion'             => 'nullable|string|max:255',
            ])->validate();

            // ✅ Try to find an existing record first
            $existingCV = \App\Models\CovidScreening::where('meeting_id', $validated['meeting_id'])->first();

            if ($existingCV) {
                // ✅ Update the existing Covid-19 Screening
                $existingCV->update($validated);

                return response()->json([
                    'message' => 'Covid-19 Screening updated successfully.',
                    'data' => $existingCV,
                    'status' => 'updated',
                ], 200);
            }

            // ✅ Otherwise, create a new one
            $covidScreening = \App\Models\CovidScreening::create($validated);

            return response()->json([
                'message' => 'Covid-19 Screening saved successfully.',
                'data' => $covidScreening,
                'status' => 'created',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('CV Save/Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save Covid-19 Screening.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //fetch country list
    public function getCountries()
    {
        try {
            $countries = Countries::orderBy('en_short_name', 'asc')
                ->get(['num_code', 'en_short_name']);

            return response()->json([
                'status' => 'success',
                'data' => $countries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // Get Covid-19 clinical assessment
    public function getCA($meeting_id)
    {
        try {
            \Log::info("Fetching Clinical Assessment for meeting_id: {$meeting_id}");

            $ca = \App\Models\CovidAssessment::where('meeting_id', $meeting_id)->first();

            if (!$ca) {
                return response()->json([
                    'message' => 'No Clinical Assessment found for this meeting.',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Clinical Assessment retrieved successfully.',
                'data' => $ca,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('DP Fetch Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to fetch Clinical Assessment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store / Update clinical assessment
    public function storeCA(Request $request)
    {
        try {
            \Log::info('Incoming CA Request:', $request->all());

            // ✅ Convert empty strings to null to avoid validation issues
            $data = collect($request->all())->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // ✅ Validate request data
            $validated = validator($data, [
                'meeting_id'                 => 'required|integer',
                'days_14_prior_expose'       => 'required|integer',
                'anytime_during_expose'      => 'required|integer',
                'days_14_date_onset_illness' => 'nullable|date',
                'name_facility'              => 'nullable|string|max:255',
                'referral_date'              => 'nullable|date',
                'place_quarantine'           => 'nullable|integer',
                'quarantine_facility'        => 'nullable|string|max:255',
                'fever'                      => 'nullable|integer',
                'cough'                      => 'nullable|integer',
                'colds'                      => 'nullable|integer',
                'sore_throat'                => 'nullable|integer',
                'diarrhea'                   => 'nullable|integer',
                'short_breathing'            => 'nullable|integer',
                'other_symptoms'             => 'nullable|string|max:255',
                'history_illness'            => 'nullable|integer',
                'history_specify'            => 'nullable|string|max:255',
                'xray'                       => 'nullable|integer',
                'xray_date'                  => 'nullable|date',
                'pregnant'                   => 'nullable|integer',
                'lmp'                        => 'nullable|string|max:255',
                'cxr_result'                 => 'nullable|integer',
                'radiologic_findings'        => 'nullable|string|max:255',
                'specimen_collected'         => 'nullable|string|max:255',
                'date_collected'             => 'nullable|date',
                'date_sent_ritm'             => 'nullable|date',
                'date_received_ritm'         => 'nullable|date',
                'virus_isolation_result'     => 'nullable|string|max:255',
                'rt_pcr_result'              => 'nullable|string|max:255',
                'scrum'                      => 'nullable|string|max:255',
                'oro_naso_swab'              => 'nullable|string|max:255',
                'spe_others'                 => 'nullable|string|max:255',
                'classification'             => 'nullable|integer',
                'outcome_date_discharge'     => 'nullable|date',
                'outcome_condition_discharge'=> 'nullable|integer',
            ])->validate();

            // ✅ Try to find an existing record first
            $existingCA = \App\Models\CovidAssessment::where('meeting_id', $validated['meeting_id'])->first();

            if ($existingCA) {
                // ✅ Update the existing Covid-19 Screening
                $existingCA->update($validated);

                return response()->json([
                    'message' => 'Covid-19 Screening updated successfully.',
                    'data' => $existingCA,
                    'status' => 'updated',
                ], 200);
            }

            // ✅ Otherwise, create a new one
            $covidAssessment = \App\Models\CovidAssessment::create($validated);

            return response()->json([
                'message' => 'Covid-19 Assessment saved successfully.',
                'data' => $covidAssessment,
                'status' => 'created',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('DP Save/Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save Covid-19 Screening.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Get diagnosis assessment
    public function getDA($meeting_id)
    {
        try {
            \Log::info("Fetching Clinical Assessment for meeting_id: {$meeting_id}");

            $da = \App\Models\DiagnosisAssessment::where('meeting_id', $meeting_id)->first();

            if (!$da) {
                return response()->json([
                    'message' => 'No Clinical Assessment found for this meeting.',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Clinical Assessment retrieved successfully.',
                'data' => $da,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('DP Fetch Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to fetch Clinical Assessment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store / Update diagnosis assessment
    public function storeDA(Request $request)
    {
        try {
            \Log::info('Incoming DA Request:', $request->all());

            // ✅ Convert empty strings to null to avoid validation issues
            $data = collect($request->all())->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // ✅ Validate request data
            $validated = validator($data, [
                'meeting_id'                 => 'required|integer',
                'patient_id'                 => 'required|integer',
                'summary_assess'             => 'required|string|max:255',
                'diagnosis'                  => 'required|string|max:255',
                'clinical_classification'    => 'required|integer',
                'if_covid'                   => 'nullable|integer',
            ])->validate();

            // ✅ Try to find an existing record first
            $existingDA = \App\Models\DiagnosisAssessment::where('meeting_id', $validated['meeting_id'])->first();

            if ($existingDA) {
                // ✅ Update the existing Covid-19 Screening
                $existingDA->update($validated);

                return response()->json([
                    'message' => 'Covid-19 Screening updated successfully.',
                    'data' => $existingDA,
                    'status' => 'updated',
                ], 200);
            }

            // ✅ Otherwise, create a new one
            $diagnosisAssessment = \App\Models\DiagnosisAssessment::create($validated);

            return response()->json([
                'message' => 'Covid-19 Assessment saved successfully.',
                'data' => $diagnosisAssessment,
                'status' => 'created',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('DP Save/Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save Covid-19 Screening.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Get plan of management
    public function getPM($meeting_id)
    {
        try {
            \Log::info("Fetching Clinical Assessment for meeting_id: {$meeting_id}");

            $pm = \App\Models\PlanManagement::where('meeting_id', $meeting_id)->first();

            if (!$pm) {
                return response()->json([
                    'message' => 'No Clinical Assessment found for this meeting.',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Clinical Assessment retrieved successfully.',
                'data' => $pm,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('DP Fetch Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to fetch Clinical Assessment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store / Update plan of management
    public function storePM(Request $request)
    {
        try {
            \Log::info('Incoming PM Request:', $request->all());

            // ✅ Convert empty strings to null to avoid validation issues
            $data = collect($request->all())->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // ✅ Validate request data
            $validated = validator($data, [
                'meeting_id'                 => 'required|integer',
                'plan_management'            => 'required|string|max:255',
                'prescription'               => 'required|string|max:255',
                'referral'                   => 'required|string|max:255',
                'disposition'                => 'required|string|max:255',
                'name_physician'             => 'required|string|max:255',
                // 'signature'               => 'required|string|max:255',
                'license_no'                 => 'required|string|max:50',
                'prof_tax_receipt'           => 'required|string|max:255',
            ])->validate();

            // ✅ Try to find an existing record first
            $existingPM = \App\Models\PlanManagement::where('meeting_id', $validated['meeting_id'])->first();

            if ($existingPM) {
                // ✅ Update the existing Covid-19 Screening
                $existingPM->update($validated);

                return response()->json([
                    'message' => 'Covid-19 Screening updated successfully.',
                    'data' => $existingPM,
                    'status' => 'updated',
                ], 200);
            }

            // ✅ Otherwise, create a new one
            $planManagement = \App\Models\PlanManagement::create($validated);

            return response()->json([
                'message' => 'Covid-19 Assessment saved successfully.',
                'data' => $planManagement,
                'status' => 'created',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('DP Save/Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save Covid-19 Screening.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    //prescription list
    public function prescriptionList(Request $request)
    {
        try {
            $keyword = $request->keyword ?? '';

            \Log::info('➡️ Entered prescriptionList()', ['keyword' => $keyword]);

            $data = Prescription::with('drugmed') // include relation for drug name
                ->where(function($q) use ($keyword) {
                    $q->where('presc_code', 'like', "%$keyword%")
                    ->orWhere('drug_id', 'like', "%$keyword%")
                    ->orWhere('type_of_medicine', 'like', "%$keyword%");
                })
                // ->where('void', 1)
                ->orderBy('presc_code', 'asc')
                ->get()
                ->map(function ($presc) {
                    return [
                        'presc_code'    => $presc->presc_code,
                        'type_of_medicine' => $presc->type_med(),   // ✅ from model
                        'drugcode' => optional($presc->drugmed)->drugcode,
                        'frequency'     => $presc->freq(),       // ✅ from model
                        'dose_regimen'  => $presc->dose_reg(),   // ✅ from model
                        'total_qty'      => $presc->total_qty,
                    ];
                });

            \Log::info('✅ PrescriptionList result count', ['count' => $data->count()]);

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('❌ Error in prescriptionList(): '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }







    // public function meetingInfo(Request $req)
    // {
    //     $meeting = Teleconsult::select(
    //         'teleconsults.*',
    //         'pat.*',
    //         'teleconsults.id as meetID',
    //         'd.case_no as caseNO',
    //         'd.id as demographic_id',
    //         'ch.id as clinical_id',
    //         'pe.id as phy_id',
    //         'cs.id as covidscreen_id',
    //         'csa.id as covidassess_id',
    //         'das.id as diagassess_id',
    //         'fac.facilityname as FacName'
    //     )->leftJoin('patients as pat', 'pat.id', '=', 'teleconsults.patient_id')
    //         ->leftJoin('facilities as fac', 'fac.id', '=', 'pat.facility_id')
    //         ->leftJoin('tele_demographic_profile as d', 'd.meeting_id', '=', 'teleconsults.id')
    //         ->leftJoin('tele_clinical_histories as ch', 'ch.meeting_id', '=', 'teleconsults.id')
    //         ->leftJoin('tele_physical_exams as pe', 'pe.meeting_id', '=', 'teleconsults.id')
    //         ->leftJoin('tele_covid19_screening as cs', 'cs.meeting_id', '=', 'teleconsults.id')
    //         ->leftJoin('tele_covid19_clinical_assessment as csa', 'csa.meeting_id', '=', 'teleconsults.id')
    //         ->leftJoin('tele_diagnosis_assessment as das', 'das.meeting_id', '=', 'teleconsults.id')
    //         ->where('teleconsults.id', $req->meet_id)
    //         ->first();
            
    //         👇 Add this line to inspect what you get
    //         dd($meeting);

    //     if ($meeting->phyexam) {
    //         $conjunctiva = $meeting->phyexam->conjunctiva;
    //         $neck = $meeting->phyexam->neck;
    //         $breast = $meeting->phyexam->breast;
    //         $thorax = $meeting->phyexam->thorax;
    //         $abdomen = $meeting->phyexam->abdomen;
    //         $genitals = $meeting->phyexam->genitals;
    //         $extremities = $meeting->phyexam->extremities;
    //     }

    //     return json_encode($meeting);
    // }

    public function getPendingMeeting($id)
    {
        $pend_meet = PendingMeeting::find($id);
        $encoded = $pend_meet->encoded->facility;
        $patient = $pend_meet->patient;
        $patname = \Crypt::decrypt($patient->fname).' '.\Crypt::decrypt($patient->mname).' '.\Crypt::decrypt($patient->lname);

        return response()->json([
            'pend_meet' => $pend_meet,
            'patname' => $patname,
        ]);
    }

    public function acceptDeclineMeeting($id, Request $req)
    {
        $user = Auth::user();
        $userfac = $user->facility?->facilityname;
        $meet = PendingMeeting::find($id);
        $action = $req->action;
        $date = date('Y-m-d', strtotime($req->date_from));
        $time = date('H:i:s', strtotime($req->time));
        $endtime = Carbon::parse($time)
            ->addMinutes((int) $req->duration)
            ->format('H:i:s');
        $patient = $meet->patient->lname.', '.$meet->patient->fname.' '.$meet->patient->mname;
        if ($action == 'Accept') {
            $create_data = [
                'user_id' => $meet->user_id,
                'doctor_id' => $meet->doctor_id,
                'patient_id' => $meet->patient_id,
                'date_meeting' => $date,
                'from_time' => $time,
                'to_time' => $endtime,
                'title' => $meet->title,
                'password' => 'doh'.Str::random(5),
                'is_started' => 0,
            ];
            $create_meeting = Teleconsult::create($create_data);
            // PusherHelper::trigger('my-channel.'.$meet->user_id, 'my-event.'.$meet->user_id, [
            //     'title' => 'New Teleconsultation Accepted',
            //     'subtitle' => $userfac,
            //     'time' => Carbon::now(),
            //     'isSeen' => false,
            // ]);
        }
        $meet_id = $action == 'Accept' ? $create_meeting->id : null;
        $data = [
            'status' => $action,
            'meet_id' => $meet_id,
        ];
        $meet->update($data);
        if ($action == 'Accept') {
            event(new AcDecReq($user, $create_meeting, $action, $userfac));
            $to_name = $meet->encoded->fname.' '.$meet->encoded->mname.' '.$meet->encoded->lname;
            $to_email = $meet->encoded->email;
            $doctor = 'Dr. '.$meet->doctor->fname.' '.$meet->doctor->mname.' '.$meet->doctor->lname;
            $from_fac = $meet->doctor->facility->facilityname;
            $em = [
                'to_name' => $to_name,
                'patient' => $patient,
                'doctor' => $doctor,
                'from_fac' => $from_fac,
                'date' => $date.' '.$time,
                'complaint' => $meet->title,

            ];
            // Mail::send('teleconsult.email.email_accept', $em, function($message) use ($to_name, $to_email) {
            // $message->to($to_email, $to_name)
            // ->subject('Schedule for Teleconsultation');
            // $message->from('aronjbra20@gmail.com','DOH XII TELEMEDICINE');
            // });
        } else {
            event(new AcDecReq($user, $meet, $action, $userfac));
            Session::put('delete_action', 'Successfully Declined Teleconsultation.');
        }
    }

    public function getDocOrder(Request $req)
    {
        $docorder = DoctorOrder::find($req->docorderid);
        $labreq = $docorder ? $docorder->labreq : '';

        return response()->json([
            'docorder' => $docorder,
            'labreq' => $labreq,
        ]);
    }

    public function labreqStore(Request $req)
    {
        $user = Session::get('auth');
        $fac_id = Session::get('auth')->facility->id;
        $files = $req->file('file');
        $pat_id = $req->doctororder_patient_id;
        if ($req->hasFile('file')) {
            foreach ($files as $file) {
                $name = str_replace(' ', '', $file->getClientOriginalName());
                $file->move(public_path('labrequest').'/'.$fac_id.'/'.$pat_id, $name);
                $path = 'labrequest/'.$fac_id.'/'.$pat_id.'/'.$name;
                $data = [
                    'docorderid' => $req->doctororder_id,
                    'doctypeid' => $req->doc_type,
                    'description' => $req->description,
                    'filepath' => $path,
                    'filename' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'extensionname' => pathinfo($name, PATHINFO_EXTENSION),
                    'uploadedby' => $user->id,
                ];
                DocOrderLabReq::create($data);
            }
        }
        Session::put('action_made', 'Successfully Add Lab Request.');

    }

    public function thankYouPage(Request $req)
    {
        return view('thankyou');
    }

    public function calendarMeetings(Request $req)
    {
        $user = Session::get('auth');
        $data = Teleconsult::select(
            'teleconsults.*',
            'teleconsults.id as meetID',
            'teleconsults.user_id as Creator',
            'teleconsults.doctor_id as RequestTo',
            'pat.lname as patLname',
            'pat.fname as patFname',
            'pat.mname as patMname',
            'pat.id as PatID',
            'users.facility_id as facid'
        )->leftJoin('patients as pat', 'teleconsults.patient_id', '=', 'pat.id')
            ->leftJoin('users as users', 'teleconsults.doctor_id', '=', 'users.id')
            ->leftJoin('users as use', 'teleconsults.user_id', '=', 'users.id');
        $data = $data->where(function ($q) use ($user) {
            $q->where('teleconsults.doctor_id', '=', $user->id)
                ->orWhere('teleconsults.user_id', '=', $user->id)
                ->orWhere('teleconsults.user_id', '=', $user->id)
                ->orWhere('users.facility_id', '=', $user->facility_id)
                ->orWhere('use.facility_id', '=', $user->facility_id);
        })->orderBy('teleconsults.date_meeting', 'asc')
            ->get();
        $result = [];
        $join = '';
        foreach ($data as $value) {
            if ($value->RequestTo == $user->id) {
                $join = 'no';
            } elseif ($value->Creator == $user->id) {
                $join = 'yes';
            }
            $values = [
                'id' => $value->id,
                'title' => $value->title,
                'start' => $value->date_meeting.'T'.$value->from_time,
                'end' => $value->date_meeting.'T'.$value->to_time,
                'allow' => $join,
            ];
            array_push($result, $values);
        }

        return json_encode($result);
    }

    public function getDoctorsFacility(Request $req)
    {
        $user_id = Auth::user()->id;
        $doctors = User::where('facility_id', $req->fac_id)
            ->where('doc_cat_id', $req->cat_id)
            ->where('level', 'doctor')
            ->where('id', '!=', $user_id)
            ->orderBy('lname', 'asc')->get();

        return json_encode($doctors);
    }

    public function teleconsultDetails($id)
    {
        $decid = Crypt::decrypt($id);
        $meeting = Meeting::find($decid);

        return view('teleconsult.teledetails', [
            'meeting' => $meeting,
        ]);
    }

    public function mycalendarMeetings(Request $req)
    {
        $user = Auth::user();
        $data = Teleconsult::select(
            'teleconsults.*',
            'teleconsults.id as meetID',
            'teleconsults.user_id as Creator',
            'teleconsults.doctor_id as RequestTo',
            'pat.lname as patLname',
            'pat.fname as patFname',
            'pat.mname as patMname',
            'pat.id as PatID',
        )->leftJoin('patients as pat', 'teleconsults.patient_id', '=', 'pat.id');
        $data = $data->where(function ($q) use ($user) {
            $q->where('teleconsults.doctor_id', '=', $user->id)
                ->orWhere('teleconsults.user_id', '=', $user->id);
        })->orderBy('teleconsults.date_meeting', 'asc')
            ->get();
        $result = [];
        $join = '';
        foreach ($data as $value) {
            if ($value->RequestTo == $user->id) {
                $join = 'no';
            } elseif ($value->Creator == $user->id) {
                $join = 'yes';
            }
            $values = [
                'id' => $value->id,
                'title' => $value->title,
                'start' => $value->date_meeting.'T'.$value->from_time,
                'end' => $value->date_meeting.'T'.$value->to_time,
                'allow' => $join,
                'facility' => $value->doctor->facility->facilityname,
            ];
            array_push($result, $values);
        }

        return json_encode($result);
    }

    public function getPrescription(Request $req)
    {
        $prescription = Meeting::find($req->id)->planmanage ? Meeting::find($req->id)->planmanage->prescription : [];
        $finalpres = [];
        if ($prescription) {
            $arrpres = explode(',', $prescription);
            foreach ($arrpres as $value) {
                $pres = Prescription::where('presc_code', $value)->first();
                array_push($finalpres, $pres);
            }

            return view('teleconsult.prescription', [
                'prescription' => $finalpres,
            ]);
        } else {
            return 'No prescription found.';
        }
    }

    public function declineTele($id, Request $req)
    {
        $meet = PendingMeeting::find($id);
        $data = [
            'status' => 'Declined',
            'remarks' => $req->decline_message,
        ];
        $meet->update($data);
        Session::put('delete_action', 'Successfully Declined Teleconsultation.');
    }

    public function enterConsult(Request $req)
    {
        $tel = Teleconsult::find($req->consult_id);

        return response()->json($tel);
    }

    public function startConsult(Request $req)
    {
        $user = Auth::user();
        $userfac = $user->facility?->facilityname;
        $tel = Teleconsult::find($req->consult_id);
        $start_time = now();
        if ($tel) {
            $create_data = [
                'is_started' => 1,
                'start_time' => $start_time,
            ];
            if ($tel->user_id != $user->id) {
                // PusherHelper::trigger('my-channel.'.$tel->user_id, 'my-event.'.$tel->user_id, [
                //     'title' => 'Teleconsultation - '.$tel->title.' Started!',
                //     'subtitle' => $userfac,
                //     'time' => Carbon::now(),
                //     'isSeen' => false,
                // ]);
                if (! $tel->start_time) {
                    $tel = $tel->update($create_data);
                }
            }
            $tel = Teleconsult::find($req->consult_id);

            return response()->json($tel);
        }
    }

    public function stopConsult(Request $request)
    {
        $tel = Teleconsult::find($request->consult_id);
        if ($tel) {
            $request->validate([
                'consult_id' => 'required|integer',
                'video' => 'required|file|mimes:webm,mp4,mov,avi|max:51200', // 50MB limit
            ]);

            $file = $request->file('video');

            // Option 1: Save to storage
            $path = $file->store('consult_videos/'.$tel->id, 'public');

            // Option 2: Save as blob in DB (not recommended for large files)
            // $binary = file_get_contents($file->getRealPath());
            // DB::table('consults')->where('id', $request->consult_id)->update(['video' => $binary]);
            $finish_time = now();
            $create_data = [
                'is_finished' => 1,
                'finish_time' => $finish_time,
            ];
            $tel = $tel->update($create_data);

            return response()->json([
                'message' => 'Video saved successfully',
                'path' => $path,
            ]);
        }

    }
}
