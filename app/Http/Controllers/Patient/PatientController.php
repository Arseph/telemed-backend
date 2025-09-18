<?php

namespace App\Http\Controllers\Patient;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Facility;
use App\Barangay;
use App\Patient;
use App\User;
use App\Countries;
use App\Region;
use App\MunicipalCity;
use App\Province;
use App\PendingMeeting;
use Carbon\Carbon;
use App\ClinicalHistory;
use App\CovidAssessment;
use App\CovidScreening;
use App\DiagnosisAssessment;
use File;
use App\PlanManagement;
use App\DemoProfile;
use App\PhysicalExam;
use App\Meeting;
use App\Teleconsult;
class PatientController extends Controller
{
     public function __construct()
    {
        if(!$login = Session::get('auth')){
            $this->middleware('auth');
        }
    }
    
    public function patientList(Request $request)
    {
        $user = Session::get('auth');

        $municity =  Facility::select(
            "facilities.*",
            "prov.prov_name as province",
            "prov.prov_psgc as p_id",
            "mun.muni_name as muncity",
            "mun.muni_psgc as m_id",
            "bar.brg_name as barangay",
            "bar.brg_psgc as b_id",
        ) ->leftJoin("provinces as prov","prov.prov_psgc","=","facilities.prov_psgc")
         ->leftJoin("municipal_cities as mun","mun.muni_psgc","=","facilities.muni_psgc")
         ->leftJoin("barangays as bar","bar.brg_psgc","=","facilities.brgy_psgc")
         ->where('facilities.id',$user->facility_id)
        ->get();
        if($request->view_all == 'view_all')
            $keyword = '';
        else{
            if(Session::get("keyword")){
                if(!empty($request->keyword) && Session::get("keyword") != $request->keyword)
                    $keyword = $request->keyword;
                else
                    $keyword = Session::get("keyword");
            } else {
                $keyword = $request->keyword;
            }
        }
        Session::put('keyword',$keyword);
        $patients = Patient::all()->where('doctor_id', $user->id);
        $data = Patient::select(
            "patients.*",
            "bar.brg_name as barangay",
            "user.email as email",
            "user.username as username",
        ) ->leftJoin("barangays as bar","bar.brg_psgc","=","patients.brgy")
        ->leftJoin("users as user","user.id","=","patients.account_id")
        ->where(function($q) use ($keyword){
            $q->where('patients.fname',"like","%$keyword%")
                ->orwhere('patients.lname',"like","%$keyword%")
                ->orwhere('patients.mname',"like","%$keyword%");
               
            })
            ->orderby('patients.lname','asc')
            ->paginate(30);
        $users = User::all();
        return view('doctors.patient',[
            'data' => $data,
            'municity' => $municity,
            'patients' => $patients,
            'users' => $users
        ]);
    }

    public function patientUpdate(Request $req)
    {
       
        $user = Session::get('auth');

        $municity =  Facility::select(
            "facilities.*",
            "prov.prov_name as province",
            "prov.prov_psgc as p_id",
            "mun.muni_name as muncity",
            "mun.muni_psgc as m_id",
            "bar.brg_name as barangay",
            "bar.brg_psgc as b_id",
        ) ->leftJoin("provinces as prov","prov.prov_psgc","=","facilities.prov_psgc")
         ->leftJoin("municipal_cities as mun","mun.muni_psgc","=","facilities.muni_psgc")
         ->leftJoin("barangays as bar","bar.brg_psgc","=","facilities.brgy_psgc")
         ->where('facilities.id',$user->facility_id)
        ->get();

        return view('doctors.patient_body',[
            'municity' => $municity
        ]);
    }

    public function getBaranggays($muncity_id)
    {
        $brgy = Barangay::where('muni_psgc',$muncity_id)
        ->orderBy('brg_name','asc')
        ->get();
        return $brgy;
    }

    public function storePatient(Request $req) {
        $user = Session::get('auth');
        $province = Facility::select(
            "facilities.*",
            "prov.prov_psgc as p_id",
        ) ->leftJoin("provinces as prov","prov.prov_psgc","=","facilities.prov_psgc")
        ->where('facilities.id',$user->facility_id)
        ->first();
        $subcat = Patient::find($req->patient_id);
        $unique_id = $req->fname.' '.$req->mname.' '.$req->lname.mt_rand(1000000, 9999999);
        $data = array(
            'unique_id' => $unique_id,
            'doctor_id' => $user->id,
            'facility_id' => $user->facility_id,
            'phic_status' => $req->phic_status,
            'phic_id' => $req->phic_id,
            'fname' => $req->fname,
            'mname' => $req->mname,
            'lname' => $req->lname,
            'contact' => $req->contact,
            'dob' => $req->dob,
            'sex' => $req->sex,
            'civil_status' => $req->civil_status,
            'muncity' => $req->muncity,
            'province' => $province->p_id,
            'brgy' => $req->brgy,
            'address' => $req->address,
            'tsekap_patient' => 0
        );
        if(!$req->patient_id){
            Session::put("action_made","Successfully added new Patient");
            Patient::create($data);
        }
        else{
            Session::put("action_made","Successfully updated Patient");
            Patient::find($req->patient_id)->update($data);
        }
    }

    public function deletePatient($id) {
        $patient = Patient::find($id);
        $patient->delete();
        Session::put("delete_action","Successfully delete Patient");
    }

    public function createPatientAcc(Request $req) {
        $user = Session::get('auth');
        $data = array(
            'fname' => $req->fname,
            'mname' => $req->mname,
            'lname' => $req->lname,
            'level' => 'patient',
            'facility_id' => $user->facility_id,
            'status' => 'active',
            'contact' => $req->contact,
            'email' => $req->email,
            'username' => $req->username,
            'password' => bcrypt($req->password)
        );
        Session::put("action_made","Successfully created account");
        $user = User::create($data);
        $accountID = $user->id;
        Patient::find($req->account_id)->update([
            'account_id' => $accountID
        ]);
    }
    public function clinical($id) {
        $patient = Patient::find($id);
        $facility = Facility::orderBy('facilityname', 'asc')->get();
        $date_referral = '';
        $date_onset_illness = '';
        if($patient->clinical) {
            $date_referral = date('m/d/Y', strtotime($patient->clinical->date_referral));
            $date_onset_illness = date('m/d/Y', strtotime($patient->clinical->date_onset_illness));
        }
        return view('patients.clinical',[
            'patient' => $patient,
            'facility' => $facility,
            'date_referral' => $date_referral,
            'date_onset_illness' => $date_onset_illness
        ]);
    }
    public function clinicalStore(Request $req) {
       $date_illness = date('Y-m-d', strtotime($req->date_onset_illness));
       $date_referral = date('Y-m-d', strtotime($req->date_referral));
       $data = $req->all();
       $data['date_onset_illness'] = $date_illness;
       $data['date_referral'] = $date_referral;
       $tel = ClinicalHistory::where('meeting_id', $req->meeting_id)->first();
       if($req->id || $tel) {
        $id = $req->id ? $req->id : $tel->id;
        $data['id'] = $id;
        ClinicalHistory::find($id)->update($data);
       } else {
        ClinicalHistory::create($data);
       }
    }
    public function covid($id) {
        $patient = Patient::find($id);
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
        return view('patients.covid',[
            'patient' => $patient,
            'countries' => $countries,
            'date_departure' => $date_departure,
            'date_arrival_ph' => $date_arrival_ph,
            'date_contact' => $date_contact_known_covid_case,
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
            'outcome_date_discharge' => $outcome_date_discharge
        ]);
    }

    public function covidStore(Request $req) {
        $list_name_occasion = $req->list_name_occa ? implode('|', $req->list_name_occa) : '';
        $req->request->add([
            'list_name_occasion' =>  $list_name_occasion
        ]);
        $data = $req->all();
        $data['date_departure'] = $req->date_departure ? date('Y-m-d', strtotime($req->date_departure)) : null;
        $data['date_arrival_ph'] = $req->date_arrival_ph ? date('Y-m-d', strtotime($req->date_arrival_ph)) : null;
        $data['date_contact_known_covid_case'] = $req->date_contact_known_covid_case ? date('Y-m-d', strtotime($req->date_contact_known_covid_case)) : null;
        $data['acco_date_last_expose'] = $req->acco_date_last_expose ? date('Y-m-d', strtotime($req->acco_date_last_expose)) : null;
        $data['food_es_date_last_expose'] = $req->food_es_date_last_expose ? date('Y-m-d', strtotime($req->food_es_date_last_expose)) : null;
        $data['store_date_last_expose'] = $req->store_date_last_expose ? date('Y-m-d', strtotime($req->store_date_last_expose)) : null;
        $data['fac_date_last_expose'] = $req->fac_date_last_expose ? date('Y-m-d', strtotime($req->fac_date_last_expose)) : null;
        $data['event_date_last_expose'] = $req->event_date_last_expose ? date('Y-m-d', strtotime($req->event_date_last_expose)) : null;
        $data['wp_date_last_expose'] = $req->wp_date_last_expose ? date('Y-m-d', strtotime($req->wp_date_last_expose)) : null;
        $screenid = $req->id;
        unset($data['screen_id']);
        unset($data['list_name_occa']);
        Teleconsult::find($req->meeting_id)->update(["is_started" => 1]);
        $tel = CovidScreening::where('meeting_id', $req->meeting_id)->first();
        if($screenid || $tel) {
            $id = $screenid ? $screenid : $tel->id;
            $data['id'] = $id;
            CovidScreening::find($id)->update($data);
        } else {
            CovidScreening::create($data);
        }
    }
    public function assessStore(Request $req) {
        $scrum = $req->scrumee ? implode('|', $req->scrumee) : '';
        $oro_naso_swab = $req->oro_naso_swabee ? implode('|', $req->oro_naso_swabee) : '';
        $spe_others = $req->spe_othersee ? implode('|', $req->spe_othersee) : '';
        $days_14_date_onset_illness = $req->days_14_date_onset_illness ? date('Y-m-d', strtotime($req->days_14_date_onset_illness)) : null;
        $referral_date = $req->referral_date ? date('Y-m-d', strtotime($req->referral_date)) : null;
        $xray_date = $req->xray_date ? date('Y-m-d', strtotime($req->xray_date)) : null;
        $date_collected = $req->date_collected ? date('Y-m-d', strtotime($req->date_collected)) : null;
        $date_sent_ritm = $req->date_sent_ritm ? date('Y-m-d', strtotime($req->date_sent_ritm)) : null;
        $date_received_ritm = $req->date_received_ritm ? date('Y-m-d', strtotime($req->date_received_ritm)) : null;
        $outcome_date_discharge = $req->outcome_date_discharge ? date('Y-m-d', strtotime($req->outcome_date_discharge)) : null;
        $assessid = $req->assess_id;
        $data = $req->all();
        $data['scrum'] = $scrum;
        $data['oro_naso_swab'] = $oro_naso_swab;
        $data['spe_others'] = $spe_others;
        $data['days_14_date_onset_illness'] = $days_14_date_onset_illness;
        $data['referral_date'] = $referral_date;
        $data['xray_date'] = $xray_date;
        $data['date_collected'] = $date_collected;
        $data['date_sent_ritm'] = $date_sent_ritm;
        $data['date_received_ritm'] = $date_received_ritm;
        $data['outcome_date_discharge'] = $outcome_date_discharge;
        unset($data['assess_id']);
        unset($data['scrumee']);
        unset($data['oro_naso_swabee']);
        unset($data['spe_othersee']);
        Teleconsult::find($req->meeting_id)->update(["is_started" => 1]);
        $tel = CovidAssessment::where('meeting_id', $req->meeting_id)->first();
        if($assessid || $tel) {
            $id = $assessid ? $assessid : $tel->id;
            $data['id'] = $id;
            CovidAssessment::find($id)->update($data);
        } else {
            CovidAssessment::create($data);
        }

    }
    public function diagnosis($id) {
        $patient = Patient::find($id);
        return view('patients.diagnosis',[
            'patient' => $patient
        ]);

    }

    public function diagnosisStore(Request $req) {
       $tel = DiagnosisAssessment::where('meeting_id', $req->meeting_id)->first();
       if($req->id || $tel) {
        $id = $req->id ? $req->id : $tel->id;
        DiagnosisAssessment::find($id)->update($req->all());
       } else {
        DiagnosisAssessment::create($req->all());
       }

    }
    public function plan($id) {
        $patient = Patient::find($id);
        return view('patients.plan',[
            'patient' => $patient
        ]);
    }

    public function planStore(Request $req) {
        Teleconsult::find($req->meeting_id)->update(["is_started" => 1]);
        $signature = $req->signaturephy ? public_path('signatures').'/'.$req->signaturephy : '';
        $unlink = $signature ? File::delete($signature) : '';
        $sign = $req->signature;
        $sign = str_replace('data:image/png;base64,', '', $sign);
        $sign = str_replace(' ', '+', $sign);
        $signName = 'signature'.str_random(10).'.'.'png';
        $file = File::put(public_path('signatures'). '/' . $signName, base64_decode($sign));
        $data = $req->all();
        $data['signature'] = $signName;
        unset($data['signaturephy']);
        unset($data['patient_id']);
        $tel = PlanManagement::where('meeting_id', $req->meeting_id)->first();
        if($req->id || $tel) {
            $id = $req->id ? $req->id : $tel->id;
            $data['id'] = $id;
            PlanManagement::find($id)->update($data);
        } else {
            PlanManagement::create($data);
        }
    }

    public function demographicStore(Request $req) {
       Teleconsult::find($req->meeting_id)->update(["is_started" => 1]);
       $tel = DemoProfile::where('meeting_id', $req->meeting_id)->first();
       if($req->id || $tel) {
        $data = $req->all();
        $data['id'] = $id;
        DemoProfile::find($req->id)->update($data);
       } else {
        DemoProfile::create($req->all());
       }
    }
    public function phyExamStore(Request $req) {
        Teleconsult::find($req->meeting_id)->update(["is_started" => 1]);
       $tel = PhysicalExam::where('meeting_id', $req->meeting_id)->first();
       if($req->id || $tel) {
        $id = $req->id ? $req->id : $tel->id;
        $data = $req->all();
        $data['id'] = $id;
        PhysicalExam::find($id)->update($data);
       } else {
        PhysicalExam::create($req->all());
       }
    }

    public function clinicalInfo(Request $req) {
        $meeting = Teleconsult::find($req->meet_id);
        $conjunctiva = '';
        $neck = '';
        $breast = '';
        $thorax = '';
        $abdomen = '';
        $genitals = '';
        $extremities = '';
        if($meeting->phyexam) {
            $conjunctiva = $meeting->phyexam->conjunctiva;
            $neck = $meeting->phyexam->neck;
            $breast = $meeting->phyexam->breast;
            $thorax = $meeting->phyexam->thorax;
            $abdomen = $meeting->phyexam->abdomen;
            $genitals = $meeting->phyexam->genitals;
            $extremities = $meeting->phyexam->extremities;
        }

        return response()->json(
            [
                'conjunctiva' => $conjunctiva,
                'neck' => $neck,
                'breast' => $breast,
                'thorax' => $thorax,
                'abdomen' => $abdomen,
                'genitals' => $genitals,
                'extremities' => $extremities
            ]
        );
    }
}
