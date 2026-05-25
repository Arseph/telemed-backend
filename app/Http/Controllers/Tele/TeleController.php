<?php

namespace App\Http\Controllers\Tele;

use App\Events\AcDecReq;
use App\Helpers\PusherHelper;
use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\Region;
use App\Models\Province;
use App\Models\Barangay;
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
use Illuminate\Support\Facades\DB;

use App\Services\TeleFormsStoreFetchService;
use App\Models\DemoProfile;
use App\Models\ClinicalHistory;
use App\Models\PhysicalExam;
use App\Models\CovidScreening;
use App\Models\CovidAssessment;
use App\Models\diagnosisAssessment;
use App\Models\PlanManagement;

use App\Models\PatientV2;

class TeleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $keyword = $request->view_all ? '' : $request->date_range;
        $data = Teleconsult::select('teleconsults.*', 'teleconsults.id as meetID', 'teleconsults.user_id as Creator', 'teleconsults.doctor_id as RequestTo', 'pat.pat_lname as patLname', 'pat.pat_fname as patFname', 'pat.pat_mname as patMname', 'pat.pat_birthDate as dob', 'pat.sex_code as sex', 'pat.civil_stat_code as civil_status', 'pat.id as PatID')->leftJoin('tbl_master_patient as pat', 'teleconsults.patient_id', '=', 'pat.id');
        if ($keyword) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[1]));
            $data = $data->where(function ($q) use ($date_start, $date_end) {
                $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
            });
        }
        $activeid = $user->patient ? $user->patient->id : $user->id;
        $data = $data
            ->where(function ($q) use ($activeid) {
                $q->where('teleconsults.doctor_id', '=', $activeid)->orWhere('teleconsults.user_id', '=', $activeid)->orWhere('teleconsults.patient_id', '=', $activeid);
            })
            ->whereDate('teleconsults.date_meeting', '=', Carbon::now()->toDateString())
            ->orderBy('teleconsults.date_meeting', 'asc')
            ->get();
        $data->load('encoded.facility');
        $patients = PatientV2::select(
            'tbl_master_patient.*',
            'bar.brg_name as barangay',
            // 'user.email as email',
            // 'user.username as username',
        )
            ->leftJoin('barangays as bar', DB::raw('CAST(bar.brg_psgc AS CHAR)'), '=', 'tbl_master_patient.bgycode')
            // ->leftJoin('users as user', 'user.id', '=', 'tbl_master_patient.account_id')
            // ->where('tbl_master_patient.userid', $user->id)
            ->orderby('tbl_master_patient.pat_lname', 'asc')
            ->get();

        $keyword_past = $request->view_all_past ? '' : $request->date_range_past;
        $data_past = Teleconsult::select('teleconsults.*', 'teleconsults.id as meetID', 'teleconsults.user_id as Creator', 'teleconsults.doctor_id as RequestTo', 'pat.pat_lname as patLname', 'pat.pat_fname as patFname', 'pat.pat_mname as patMname', 'pat.id as PatID')->leftJoin('tbl_master_patient as pat', 'teleconsults.patient_id', '=', 'pat.id');
        if ($keyword_past) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range_past)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range_past)[1]));
            $data_past = $data_past->where(function ($q) use ($date_start, $date_end) {
                $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
            });
        }
        $data_past = $data_past
            ->where(function ($q) use ($user) {
                $q->where('teleconsults.doctor_id', '=', $user->id)->orWhere('teleconsults.user_id', '=', $user->id);
            })
            ->whereDate('teleconsults.date_meeting', '<', Carbon::now()->toDateString())
            ->orderBy('teleconsults.date_meeting', 'desc')
            ->get();
        $data_past->load('encoded.facility');
        $keyword_req = $request->view_all_req ? '' : $request->date_range_req;
        $data_req = PendingMeeting::select('pending_meetings.*', 'pending_meetings.id as meetID', 'pending_meetings.created_at as reqDate', 'pat.pat_lname as patLname', 'pat.pat_fname as patFname', 'pat.pat_mname as patMname')->leftJoin('tbl_master_patient as pat', 'pending_meetings.patient_id', '=', 'pat.id');
        if ($keyword_req) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range_req)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range_req)[1]));
            $data_req = $data_req->where(function ($q) use ($date_start, $date_end) {
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
        $data_req = $data_req->where('pending_meetings.doctor_id', '=', $user->id)->orderBy('pending_meetings.id', 'desc')->get();
        $data_req->load('facility', 'patient');
        $data_my_req = PendingMeeting::select('pending_meetings.*', 'pending_meetings.id as meetID', 'pending_meetings.created_at as reqDate', 'pat.pat_lname as patLname', 'pat.pat_fname as patFname', 'pat.pat_mname as patMname')->leftJoin('tbl_master_patient as pat', 'pending_meetings.patient_id', '=', 'pat.id')->where('pending_meetings.user_id', '=', $user->id)->orderBy('pending_meetings.id', 'desc')->get();
        $data_my_req->load('facility', 'doctor');
        $facilities = Facility::orderBy('facilityname', 'asc')->get();
        $count_req = PendingMeeting::select('pending_meetings.*', 'pending_meetings.id as meetID', 'pending_meetings.created_at as reqDate')->leftJoin('tbl_master_patient as pat', 'pending_meetings.patient_id', '=', 'pat.id')->where('pending_meetings.status', 'Pending')->where('pending_meetings.doctor_id', '=', $user->id)->count();
        $telecat = DocCategory::orderBy('category_name', 'asc')->get();
        $labreq = LabRequest::where('req_type', 'LAB')->orderby('description', 'asc')->get();
        $imaging = LabRequest::where('req_type', 'RAD')->orderby('description', 'asc')->get();
        $docorder = DoctorOrder::where('doctorid', $user->id)->get();
        $doc_type = Doc_Type::where('isactive', '1')->orderBy('doc_name', 'asc')->get();

        $data_up = Teleconsult::select('teleconsults.*', 'teleconsults.id as meetID', 'teleconsults.user_id as Creator', 'teleconsults.doctor_id as RequestTo', 'pat.pat_lname as patLname', 'pat.pat_fname as patFname', 'pat.pat_mname as patMname', 'pat.pat_birthDate as dob', 'pat.sex_code as sex', 'pat.civil_stat_code as civil_status', 'pat.id as PatID')->leftJoin('tbl_master_patient as pat', 'teleconsults.patient_id', '=', 'pat.id');
        if ($keyword) {
            $date_start = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[0]));
            $date_end = date('Y-m-d', strtotime(explode(' - ', $request->date_range)[1]));
            $data_up = $data_up->where(function ($q) use ($date_start, $date_end) {
                $q->whereBetween('teleconsults.date_meeting', [$date_start, $date_end]);
            });
        }
        $activeid = $user->patient ? $user->patient->id : $user->id;
        $data_up = $data_up
            ->where(function ($q) use ($activeid) {
                $q->where('teleconsults.doctor_id', '=', $activeid)->orWhere('teleconsults.user_id', '=', $activeid)->orWhere('teleconsults.patient_id', '=', $activeid);
            })
            ->whereDate('teleconsults.date_meeting', '>=', Carbon::now()->toDateString())
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
        $req->validate([
            'doctor_id' => 'required',
        ]);

        $user = Auth::user();

        $data = $req->except('meeting_id');
        $data['user_id'] = $user->id;
        $data['status'] = 'Pending';

        if ($req->meeting_id) {
            $meet = PendingMeeting::find($req->meeting_id);

            if (!$meet) {
                return response()->json(
                    [
                        'message' => 'Meeting not found',
                    ],
                    404,
                );
            }

            $meet->update($data);
        } else {
            $meet = PendingMeeting::create($data);
        }

        // Trigger AFTER successful save
        PusherHelper::trigger('my-channel.' . $req->doctor_id, 'my-event.' . $req->doctor_id, [
            'title' => 'New Teleconsultation Request',
            'subtitle' => optional($user->facility)->facilityname,
            'time' => Carbon::now()->toDateTimeString(),
            'isSeen' => false,
        ]);

        return response()->json([
            'message' => 'Teleconsultation scheduled successfully',
            'data' => $meet,
        ]);
    }

    public function indexCall($id)
    {
        $user = Session::get('auth');
        $decid = Crypt::decrypt($id);
        $meetings = Teleconsult::select('teleconsults.*', 'pat.id as PATID', 'teleconsults.id as meetID')->leftJoin('patients as pat', 'pat.id', '=', 'teleconsults.patient_id')->where('teleconsults.id', $decid)->first();
        $title = $meetings->title;
        $emailname = Crypt::decrypt($meetings->doctor->email);
        $password = $meetings->password;
        $role = $meetings->doctor_id == $user->id ? 1 : 0;
        $username = $user->fname . ' ' . $user->mname . ' ' . $user->lname;
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
        $endtime = Carbon::parse($time)->addMinutes($req->duration)->format('H:i:s');
        $meetings = Teleconsult::whereDate('date_meeting', '=', $date)
            ->where(function ($q) use ($doctor_id, $user) {
                $q->where('doctor_id', $doctor_id)->orWhere('doctor_id', $user->id);
            })
            ->get();
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
        $meeting = Meeting::select('teleconsults.*', 'pat.*', 'teleconsults.id as meetID', 'user.fname as docfname', 'user.mname as docmname', 'user.lname as doclname')->leftJoin('patients as pat', 'pat.id', '=', 'teleconsults.patient_id')->leftJoin('users as user', 'user.id', '=', 'pat.doctor_id')->where('teleconsults.id', $req->meet_id)->first();

        return json_encode($meeting);
    }

    public function meetingInfo(Request $req)
    {
        $meeting = Teleconsult::select(
            //patient
            'brgyp.brg_name as pbrgyname',
            'munp.muni_name as pmuniname',
            'provp.prov_name as pprov',
            //user/doc
            'brgy.brg_name as brgyname',
            'mun.muni_name as muniname',
            'reg.reg_desc as regname',
            'prov.prov_name as provname',
            //doc
            'user.fname as docfname',
            'user.mname as docmname',
            'user.lname as doclname',
            'teleconsults.*',
            'pat.*',
            'pat.id as patID',
            'teleconsults.id as meetID',
            'fac.id as facID',
            'fac.facilityname as FacName',
        )
            ->leftJoin('tbl_master_patient as pat', 'pat.id', '=', 'teleconsults.patient_id')
            ->leftJoin('pending_meetings as pmeet', 'pmeet.meet_id', '=', 'teleconsults.id')
            ->leftJoin('facilities as fac', 'fac.id', '=', 'pmeet.facility_id')
            ->leftJoin('users as user', 'user.id', '=', 'pat.userid')
            ->leftJoin('regions as reg', 'reg.reg_psgc', '=', 'fac.reg_psgc')
            ->leftJoin('provinces as prov', 'prov.prov_psgc', '=', 'fac.prov_psgc')
            ->leftJoin('municipal_cities as mun', 'mun.muni_psgc', '=', 'fac.muni_psgc')
            ->leftJoin('barangays as brgy', 'brgy.brg_psgc', '=', 'fac.brgy_psgc')
            //patient full address
            ->leftJoin('provinces as provp', DB::raw('CAST(provp.prov_code AS CHAR)'), '=', 'pat.provcode')
            ->leftJoin('municipal_cities as munp', DB::raw('CAST(munp.zipcode AS CHAR)'), '=', 'pat.citycode')
            ->leftJoin('barangays as brgyp', 'brgyp.brg_psgc', '=', DB::raw('CAST(pat.bgycode AS UNSIGNED)'))
            ->where('teleconsults.id', $req->meet_id)
            ->first();

        //         //👇 Add this line to inspect what you get
        //                 dd($meeting);

        //             if ($meeting->phyexam) {
        //                 $conjunctiva = $meeting->phyexam->conjunctiva;
        //                 $neck = $meeting->phyexam->neck;
        //                 $breast = $meeting->phyexam->breast;
        //                 $thorax = $meeting->phyexam->thorax;
        //                 $abdomen = $meeting->phyexam->abdomen;
        //                 $genitals = $meeting->phyexam->genitals;
        //                 $extremities = $meeting->phyexam->extremities;
        //             }

        return json_encode($meeting);
    }

    public function meetingInfoV2(Request $request)
    {
        $request->validate([
            'meet_id' => 'required|exists:teleconsults,id',
        ]);

        $meeting = Teleconsult::with(['patient', 'doctor', 'pendmeet', 'encoded', 'docorder', 'clinical', 'covidassess', 'covidscreen', 'diagassess', 'planmanage', 'phyexam', 'demoprof'])->findOrFail($request->meet_id);

        return response()->json([
            'data' => [
                /* ===================== BASIC MEETING ===================== */
                'meetID' => $meeting->id,
                'facID' => $meeting->facID ?? null,
                'case_no' => $meeting->demoprof->case_no ?? $meeting->id,
                'date_meeting' => $meeting->date_meeting,
                'date_meeting' => $meeting->date_meeting,
                'datetimemeet' => $meeting->date_meeting && $meeting->from_time ? $meeting->date_meeting . 'T' . $meeting->from_time : null,

                /* ===================== DOCTOR ===================== */
                'doctor' => [
                    'id' => $meeting->doctor->id ?? null,
                    'fname' => $meeting->doctor->fname ?? null,
                    'mname' => $meeting->doctor->mname ?? null,
                    'lname' => $meeting->doctor->lname ?? null,
                    'address' => $meeting->doctor_address, // accessor
                ],

                /* ===================== PATIENT ===================== */
                'patient' => [
                    'id' => $meeting->patient->id ?? null,
                    'pat_fname' => $meeting->patient->pat_fname ?? null,
                    'pat_mname' => $meeting->patient->pat_mname ?? null,
                    'pat_lname' => $meeting->patient->pat_lname ?? null,
                    'pat_mobile' => $meeting->patient->pat_mobile ?? null,
                    'pat_birthDate' => $meeting->patient->pat_birthDate ?? null,
                    'sex_code' => $meeting->patient->sex_code ?? null,
                    'civil_stat_code' => $meeting->patient->civil_stat_code ?? null,
                    'religion_code' => $meeting->patient->religion_code ?? null,
                    'educattainment' => $meeting->patient->educattainment ?? null,
                    'occupation_sp' => $meeting->patient->occupation_sp ?? null,
                    'monthly_income' => $meeting->patient->monthly_income ?? null,
                    'pat_philhealth' => $meeting->patient->pat_philhealth ?? null,
                    'type_of_membership' => $meeting->patient->type_of_membership ?? null,
                    'address' => $meeting->patient_address, // accessor
                ],

                /* ===================== DEMOGRAPHIC PROFILE ===================== */
                'demographic_profile' => $meeting->demoprof
                    ? [
                        'tele_partner_platform' => $meeting->demoprof->tele_partner_platform,
                        'prior_tele_proper' => $meeting->demoprof->prior_tele_proper,
                        'is_patient_accompanied' => $meeting->demoprof->is_patient_accompanied,
                        'case_no' => $meeting->demoprof->case_no,
                        'name_of_companion' => $meeting->demoprof->name_of_companion,
                        'relationship' => $meeting->demoprof->relationship,
                        'phone_no' => $meeting->demoprof->phone_no,
                    ]
                    : null,

                /* ===================== RELATED CLINICAL DATA ===================== */
                'clinical_history' => $meeting->clinical,
                'covid_assessment' => $meeting->covidassess,
                'covid_screening' => $meeting->covidscreen,
                'diagnosis' => $meeting->diagassess,
                'plan_management' => $meeting->planmanage,
                'physical_exam' => $meeting->phyexam,

                /* ===================== META ===================== */
                'encoded_by' => $meeting->encoded,
                'pending_meeting' => $meeting->pendmeet,
                'doctor_order' => $meeting->docorder,
            ],
        ]);
    }

    //tele forms
    //use custom app service
    public function __construct(TeleFormsStoreFetchService $fetchService)
    {
        $this->fetchService = $fetchService;
    }

    // ------------------ Demographic Profile ------------------
    public function getDP($meeting_id)
    {
        return $this->fetchService->getTeleform(DemoProfile::class, $meeting_id, 'Demographic Profile');
    }

    public function storeDP(Request $request)
    {
        return $this->fetchService->storeTeleform($request, DemoProfile::class, 'Demographic Profile');
    }

    // ------------------ Clinical History ------------------
    public function getCH($meeting_id)
    {
        return $this->fetchService->getTeleform(ClinicalHistory::class, $meeting_id, 'Clinical History');
    }

    public function storeCH(Request $request)
    {
        return $this->fetchService->storeTeleform($request, ClinicalHistory::class, 'Clinical History');
    }

    // ------------------ Physical Exam ------------------
    public function getPE($meeting_id)
    {
        return $this->fetchService->getTeleform(PhysicalExam::class, $meeting_id, 'Physical Exam');
    }

    public function storePE(Request $request)
    {
        return $this->fetchService->storeTeleform($request, PhysicalExam::class, 'Physical Exam');
    }

    // ------------------ Covid-19 Screening ------------------
    public function getCV($meeting_id)
    {
        return $this->fetchService->getTeleform(CovidScreening::class, $meeting_id, 'Covid-19 Screening');
    }

    public function storeCV(Request $request)
    {
        return $this->fetchService->storeTeleform($request, CovidScreening::class, 'Covid-19 Screening');
    }

    // ------------------ Clinical Assessment ------------------
    public function getCA($meeting_id)
    {
        return $this->fetchService->getTeleform(CovidAssessment::class, $meeting_id, 'Clinical Assessment');
    }

    public function storeCA(Request $request)
    {
        return $this->fetchService->storeTeleform($request, CovidAssessment::class, 'Clinical Assessment');
    }

    // ------------------ Diagnosis Assessment ------------------
    public function getDA($meeting_id)
    {
        return $this->fetchService->getTeleform(DiagnosisAssessment::class, $meeting_id, 'Diagnosis Assessment');
    }

    public function storeDA(Request $request)
    {
        return $this->fetchService->storeTeleform($request, DiagnosisAssessment::class, 'Diagnosis Assessment');
    }

    // ------------------ Plan of Management ------------------
    public function getPM($meeting_id)
    {
        return $this->fetchService->getTeleform(PlanManagement::class, $meeting_id, 'Plan of Management');
    }

    public function storePM(Request $request)
    {
        return $this->fetchService->storeTeleform($request, PlanManagement::class, 'Plan of Management');
    }

    // ------------------ Auxiliary ------------------
    public function getFacilities()
    {
        try {
            $facilities = Facility::orderBy('facilityname', 'asc')->get(['id', 'facilityname']);

            return response()->json([
                'status' => 'success',
                'data' => $facilities,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ],
                500,
            );
        }
    }

    public function getCountries()
    {
        try {
            $countries = Countries::orderBy('en_short_name', 'asc')->get(['num_code', 'en_short_name', 'nationality']);

            return response()->json([
                'status' => 'success',
                'data' => $countries,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ],
                500,
            );
        }
    }

    public function getRegions()
    {
        try {
            $regions = Region::orderBy('reg_desc', 'asc')->get(['reg_psgc', 'reg_desc', 'reg_code']);

            return response()->json([
                'status' => 'success',
                'data' => $regions,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ],
                500,
            );
        }
    }

    public function getProvinces()
    {
        try {
            $provinces = Province::orderBy('prov_name')->get(['prov_code', 'prov_name', 'prov_psgc']);
            return response()->json(['status' => 'success', 'data' => $provinces]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    public function getMunicipalCities()
    {
        try {
            $cities = MunicipalCity::orderBy('muni_name')->get(['muni_psgc', 'muni_name', 'zipcode']);
            return response()->json(['status' => 'success', 'data' => $cities]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    public function getBarangays()
    {
        try {
            $barangays = Barangay::orderBy('brg_name')->get(['brg_psgc', 'brg_name']);
            return response()->json(['status' => 'success', 'data' => $barangays]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    //prescription list
    public function prescriptionList(Request $request)
    {
        try {
            $keyword = $request->keyword ?? '';

            \Log::info('➡️ Entered prescriptionList()', ['keyword' => $keyword]);

            $data = Prescription::with('drugmed') // include relation for drug name
                ->where(function ($q) use ($keyword) {
                    $q->where('presc_code', 'like', "%$keyword%")
                        ->orWhere('drug_id', 'like', "%$keyword%")
                        ->orWhere('type_of_medicine', 'like', "%$keyword%");
                })
                // ->where('void', 1)
                ->orderBy('presc_code', 'asc')
                ->get()
                ->map(function ($presc) {
                    return [
                        'presc_code' => $presc->presc_code,
                        'type_of_medicine' => $presc->type_med(), // ✅ from model
                        'drugcode' => optional($presc->drugmed)->drugcode,
                        'frequency' => $presc->freq(), // ✅ from model
                        'dose_regimen' => $presc->dose_reg(), // ✅ from model
                        'total_qty' => $presc->total_qty,
                    ];
                });

            \Log::info('✅ PrescriptionList result count', ['count' => $data->count()]);

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('❌ Error in prescriptionList(): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPendingMeeting($id)
    {
        $pend_meet = PendingMeeting::find($id);
        $encoded = $pend_meet->encoded->facility;
        $patient = $pend_meet->patient;
        $patname = \Crypt::decrypt($patient->fname) . ' ' . \Crypt::decrypt($patient->mname) . ' ' . \Crypt::decrypt($patient->lname);

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
        $endtime = Carbon::parse($time)->addMinutes((int) $req->duration)->format('H:i:s');
        $patient = $meet->patient->lname . ', ' . $meet->patient->fname . ' ' . $meet->patient->mname;
        if ($action == 'Accept') {
            $create_data = [
                'user_id' => $meet->user_id,
                'doctor_id' => $meet->doctor_id,
                'patient_id' => $meet->patient_id,
                'date_meeting' => $date,
                'from_time' => $time,
                'to_time' => $endtime,
                'title' => $meet->title,
                'password' => 'doh' . Str::random(5),
                'is_started' => 0,
            ];
            $create_meeting = Teleconsult::create($create_data);
            PusherHelper::trigger('my-channel.' . $meet->user_id, 'my-event.' . $meet->user_id, [
                'title' => 'New Teleconsultation Accepted',
                'subtitle' => $userfac,
                'time' => Carbon::now(),
                'isSeen' => false,
            ]);
        }
        $meet_id = $action == 'Accept' ? $create_meeting->id : null;
        $data = [
            'status' => $action,
            'meet_id' => $meet_id,
        ];
        $meet->update($data);
        if ($action == 'Accept') {
            event(new AcDecReq($user, $create_meeting, $action, $userfac));
            $to_name = $meet->encoded->fname . ' ' . $meet->encoded->mname . ' ' . $meet->encoded->lname;
            $to_email = $meet->encoded->email;
            $doctor = 'Dr. ' . $meet->doctor->fname . ' ' . $meet->doctor->mname . ' ' . $meet->doctor->lname;
            $from_fac = $meet->doctor->facility->facilityname;
            $em = [
                'to_name' => $to_name,
                'patient' => $patient,
                'doctor' => $doctor,
                'from_fac' => $from_fac,
                'date' => $date . ' ' . $time,
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
                $file->move(public_path('labrequest') . '/' . $fac_id . '/' . $pat_id, $name);
                $path = 'labrequest/' . $fac_id . '/' . $pat_id . '/' . $name;
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
        $data = Teleconsult::select('teleconsults.*', 'teleconsults.id as meetID', 'teleconsults.user_id as Creator', 'teleconsults.doctor_id as RequestTo', 'pat.lname as patLname', 'pat.fname as patFname', 'pat.mname as patMname', 'pat.id as PatID', 'users.facility_id as facid')->leftJoin('patients as pat', 'teleconsults.patient_id', '=', 'pat.id')->leftJoin('users as users', 'teleconsults.doctor_id', '=', 'users.id')->leftJoin('users as use', 'teleconsults.user_id', '=', 'users.id');
        $data = $data
            ->where(function ($q) use ($user) {
                $q->where('teleconsults.doctor_id', '=', $user->id)->orWhere('teleconsults.user_id', '=', $user->id)->orWhere('teleconsults.user_id', '=', $user->id)->orWhere('users.facility_id', '=', $user->facility_id)->orWhere('use.facility_id', '=', $user->facility_id);
            })
            ->orderBy('teleconsults.date_meeting', 'asc')
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
                'start' => $value->date_meeting . 'T' . $value->from_time,
                'end' => $value->date_meeting . 'T' . $value->to_time,
                'allow' => $join,
            ];
            array_push($result, $values);
        }

        return json_encode($result);
    }

    public function getDoctorsFacility(Request $req)
    {
        $user_id = Auth::user()->id;
        $doctors = User::where('facility_id', $req->fac_id)->where('doc_cat_id', $req->cat_id)->where('level', 'doctor')->where('id', '!=', $user_id)->orderBy('lname', 'asc')->get();

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
        $data = Teleconsult::select('teleconsults.*', 'teleconsults.id as meetID', 'teleconsults.user_id as Creator', 'teleconsults.doctor_id as RequestTo', 'pat.lname as patLname', 'pat.fname as patFname', 'pat.mname as patMname', 'pat.id as PatID')->leftJoin('patients as pat', 'teleconsults.patient_id', '=', 'pat.id');
        $data = $data
            ->where(function ($q) use ($user) {
                $q->where('teleconsults.doctor_id', '=', $user->id)->orWhere('teleconsults.user_id', '=', $user->id);
            })
            ->orderBy('teleconsults.date_meeting', 'asc')
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
                'start' => $value->date_meeting . 'T' . $value->from_time,
                'end' => $value->date_meeting . 'T' . $value->to_time,
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
                PusherHelper::trigger('my-channel.' . $tel->user_id, 'my-event.' . $tel->user_id, [
                    'title' => 'Teleconsultation - ' . $tel->title . ' Started!',
                    'subtitle' => $userfac,
                    'time' => Carbon::now(),
                    'isSeen' => false,
                ]);
                if (!$tel->start_time) {
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
            $path = $file->store('consult_videos/' . $tel->id, 'public');

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
