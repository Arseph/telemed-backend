<?php

namespace App\Http\Controllers\Doctor;

use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
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
use App\Meeting;
use Carbon\Carbon;
use App\PendingMeeting;
use App\LabRequest;
use App\DoctorOrder;
use App\Diagnosis;
use App\MedicalHistory;
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
        $municity =  MunicipalCity::all();
        $province = Province::where('reg_psgc', '120000000')->get();
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
        $data = Patient::select(
            "patients.*",
            "bar.brg_name as barangay",
            "user.email as email",
            "user.username as username",
        ) ->leftJoin("barangays as bar","bar.brg_psgc","=","patients.brgy")
        ->leftJoin("users as user","user.id","=","patients.account_id")
        ->where('patients.doctor_id', $user->id)
        ->orWhere('patients.facility_id', $user->facility_id)
        ->where(function($q) use ($keyword){
            $q->where('patients.fname',"like","%$keyword%")
                ->orwhere('patients.lname',"like","%$keyword%")
                ->orwhere('patients.mname',"like","%$keyword%");
               
            })
            ->orderby('patients.lname','asc')
            ->paginate(30);

        $patients = Patient::select(
            "patients.*",
            "bar.brg_name as barangay",
            "user.email as email",
            "user.username as username",
        ) ->leftJoin("barangays as bar","bar.brg_psgc","=","patients.brgy")
        ->leftJoin("users as user","user.id","=","patients.account_id")
        ->where('patients.doctor_id', $user->id)->get();
        $users = User::where('facility_id', $user->facility_id)
                      ->where('level', 'doctor')->get();
        $nationality = Countries::orderBy('nationality', 'asc')->get();
        $region = Region::all();
        $nationality_def = Countries::where('num_code', '608')->first();
        return view('doctors.patient',[
            'data' => $data,
            'municity' => $municity,
            'patients' => $patients,
            'users' => $users,
            'nationality' => $nationality,
            'nationality_def' => $nationality_def,
            'region' => $region,
            'user' => $user,
            'province' => $province
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
        $accept = $req->is_accepted ? $req->is_accepted : 0;
        $doctor_id = $req->doctor_id ? $req->doctor_id : $user->id;
        $province = Facility::select(
            "facilities.*",
            "prov.prov_psgc as p_id",
        ) ->leftJoin("provinces as prov","prov.prov_psgc","=","facilities.prov_psgc")
        ->where('facilities.id',$user->facility_id)
        ->first();
        $subcat = Patient::find($req->patient_id);
        $unique_id = $req->lname.mt_rand(1000000, 9999999);
        $data = array(
            'unique_id' => $unique_id,
            'doctor_id' => $doctor_id,
            'facility_id' => $user->facility_id,
            'phic_status' => $req->phic_status,
            'phic_id' => \Crypt::encrypt($req->phic_id),
            'fname' => \Crypt::encrypt($req->fname),
            'mname' => \Crypt::encrypt($req->mname),
            'lname' => \Crypt::encrypt($req->lname),
            'occupation' => $req->occupation,
            'monthly_income' => $req->monthly_income,
            'nationality_id' => $req->nationality_id,
            'id_type' => \Crypt::encrypt($req->id_type),
            'id_type_no' => \Crypt::encrypt($req->id_type_no),
            'contact' => \Crypt::encrypt($req->contact),
            'dob' => $req->dob,
            'sex' => \Crypt::encrypt($req->sex),
            'civil_status' => \Crypt::encrypt($req->civil_status),
            'region' => $req->region,
            'house_no' => \Crypt::encrypt($req->house_no),
            'street' => \Crypt::encrypt($req->street),
            'muncity' => $req->muncity,
            'province' => $province->p_id,
            'brgy' => $req->brgy,
            'address' => \Crypt::encrypt($req->address),
            'tsekap_patient' => 0,
            'is_accepted' => $accept,
            'religion' => \Crypt::encrypt($req->religion),
            'edu_attain' => $req->edu_attain
        );
        if($req->patient_id){
            Session::put("action_made","Successfully updated Patient");
            $patient = Patient::find($req->patient_id);
            $patient->update($data);
            if($req->email && $req->username && $req->password) {
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
                $account = User::find($patient->account_id);
                if($account) {
                    $account->update($data);
                } else {
                    $account = User::create($data);
                    Patient::find($account->id)->update([
                        'account_id' => $account->id
                    ]);
                }
            }
        }
        else{
            Session::put("action_made","Successfully added new Patient");
            $patient = Patient::create($data);
            if($req->email && $req->username && $req->password) {
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
                $account = User::find($patient->account_id);
                if($account) {
                    $account->update($data);
                } else {
                    $account = User::create($data);
                    Patient::find($patient->id)->update([
                        'account_id' => $account->id
                    ]);
                }
            }
        }
    }

    public function deletePatient($id) {
        $patient = Patient::find($id);
        $account = User::find($patient->account_id);
        if($patient) {
            $patient->delete();
        }
        if($account) {
            $account->delete();
        }
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

    public function patientConsultInfo($id) {
        $info = Patient::find($id)->meeting;
        return json_encode($info);
    }

    public function patientInformation($id) {
        try {
            $facilities = Facility::orderBy('facilityname', 'asc')->get();
            $decid = Crypt::decrypt($id);
            $patient = Patient::find($decid);
            $nationality = Countries::orderBy('nationality', 'asc')->get();
            $municity =  MunicipalCity::all();
            $diagnosis = Diagnosis::orderBy('diagcode', 'asc')->paginate(5);
            return view('doctors.patientinfo',[
                'patient' => $patient,
                'nationality' => $nationality,
                'municity' => $municity,
                'diagnosis' => $diagnosis
            ]);
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    public function teleDetails(Request $req) {
        $meetings = Meeting::select(
            "teleconsults.*",
            "teleconsults.id as meetID",
            "d.case_no as caseNO",
        )->leftJoin("tele_demographic_profile as d","d.meeting_id","=","teleconsults.id")
         ->where('teleconsults.id',$req->meet_id)
        ->first();
        $patient = Meeting::find($req->meet_id);
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
        if($patient->clinical) {
            $date_referral = $patient->clinical->date_referral ? date('m/d/Y', strtotime($patient->clinical->date_referral)) : '';
        }
        $labreq = LabRequest::where('req_type', 'LAB')->orderby('description', 'asc')->get();
        $imaging = LabRequest::where('req_type', 'RAD')->orderby('description', 'asc')->get();
        $docorder = DoctorOrder::find($req->docorderid);
        $labrequest = $docorder ? $docorder->labrequestcodes : [];
        $imgrequest = $docorder ? $docorder->imagingrequestcodes : [];
        switch ($req->view) {
            case 'demographic':
                return view('forms.demographic',[
                    'meeting' => $meetings,
                    'patient'=> $patient
                ]);
                break;
            case 'clinical':
                return view('forms.clinical',[
                    'patient'=> $patient,
                    'facility' => $facility,
                    'date_referral' => $date_referral
                ]);
                break;
            case 'covid':
                return view('forms.'.$req->view,[
                    'patient'=> $patient,
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
                    'outcome_date_discharge' => $outcome_date_discharge
                ]);
                break;
            case 'diagnosis':
                return view('forms.'.$req->view,[
                    'patient'=> $patient
                ]);
                break;
            case 'plan':
                return view('forms.'.$req->view,[
                    'patient'=> $patient
                ]);
                break;
            case 'docorder':
            if($docorder) {
                return view('doctors.tabs.docorder',[
                    'patient'=> $patient,
                    'labreq' => $labreq,
                    'imaging' => $imaging,
                    'labrequest' => $labrequest,
                    'imgrequest' => $imgrequest,
                    'docorder' => $docorder
                ]);
                break;
            } else {
                return 'No doctor\'s order found.' ;
            }
        }
    }

    public function medHisStore(Request $req) {
        if(!$req->id) {
            MedicalHistory::create($req->all());
            Session::put("action_made","Successfully created medical history");
        } else {
            MedicalHistory::find($req->id)->update($req->all());
            Session::put("action_made","Successfully updated medical history");
        }
    }

    public function medHisData(Request $req) {
        $medhis = MedicalHistory::find($req->id);
        $diagnosis = $medhis->icd;
        return response()->json([
            'medhis'=>$medhis,
            'diag'=>$diagnosis
        ]);
    }

    public function getPatientEref(Request $req) {
        $facility_id = Session::get('auth')->facility_id;
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'http://222.127.126.38/doh/referral/api/getpatient', [
        'form_params' => [
            'fname' => $req->fname,
            'mname' => $req->mname,
            'lname' => $req->lname,
            'dob' => $req->dob,
            'facility_id' => $facility_id,
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        return $res;
    }

}
