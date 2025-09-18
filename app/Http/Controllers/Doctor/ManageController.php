<?php

namespace App\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Prescription;
use App\User;
use App\DrugsMeds;
use App\DoctorOrder;
use App\Patient;
use App\LabRequest;
class ManageController extends Controller
{
    public function __construct()
    {
        if(!$login = Session::get('auth')){
            $this->middleware('auth');
        }
    }

    public function prescription(Request $request)
    {
    	$user = Session::get('auth');
    	$lastid = Prescription::max('id') + 1;
    	$pres_code = "RX".$user->id.str_pad($lastid, 7, "0", STR_PAD_LEFT);
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
        $data = Prescription::where(function($q) use ($keyword){
            $q->where('presc_code',"like","%$keyword%")
                ->orwhere('presc_code',"like","%$keyword%")
                ->orwhere('presc_code',"like","%$keyword%");
               
            })
        	->where('void', 1)
            ->orderby('presc_code','asc')
            ->paginate(30);
        $drugmed = DrugsMeds::orderby('drugcode', 'asc')->get();
        $doctors = User::where('level', 'doctor')->orderby('lname', 'asc')->get();
        $prescription = Prescription::with('encoded')->get();
        return view('doctors.prescription',[
            'data' => $data,
            'pres_code' => $pres_code,
            'drugmed' => $drugmed,
            'doctors' => $doctors,
            'prescription' => $prescription,
            'user' => $user
        ]);
    }

    public function prescriptionStore(Request $req) {
    	$user = Session::get('auth');
    	$req->request->add([
            'encodedby' => $user->id,
            'void' => 1
        ]);
        if($req->id) {
        	$req->request->add([
	            'modifyby' => $user->id
	        ]);
            Prescription::find($req->id)->update($req->all());
            Session::put("action_made","Successfully Update Prescription.");
        } else {
            Prescription::create($req->all());
            Session::put("action_made","Successfully Add Prescription.");
        }
    }

    public function prescriptionDelete($id) {
    	$prescription = Prescription::find($id);
        if($prescription) {
            $prescription->update([
            	'void' => 0
            ]);
        }
        Session::put("delete_action","Successfully delete Prescription");
    }

    public function doctorOrder(Request $request) {
        $user = Session::get('auth');
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
        $data = DoctorOrder::where(function($q) use ($keyword){
            $q->where('labrequestcodes',"like","%$keyword%")
                ->orwhere('imagingrequestcodes',"like","%$keyword%")
                ->orwhere('alertdescription',"like","%$keyword%");
               
            })
            ->where('void', 1)
            ->where('doctorid', $user->id)
            ->orderby('id','asc')
            ->paginate(30);
        $patient = Patient::where('doctor_id', $user->id)->orderBy('lname', 'asc')->get();
        $labreq = LabRequest::where('req_type', 'LAB')->orderby('description', 'asc')->get();
        $imaging = LabRequest::where('req_type', 'RAD')->orderby('description', 'asc')->get();
        $docorder = DoctorOrder::where('doctorid', $user->id)->get();
        return view('doctors.docorder',[
            'data' => $data,
            'patients' => $patient,
            'labreq' => $labreq,
            'imaging' => $imaging,
            'user' => $user,
            'docorder' => $docorder
        ]);

    }

    public function doctorOrderStore(Request $req) {
        $docid = Session::get('auth')->id;
        $labreq = $req->labrequestcodes ? implode(',',$req->labrequestcodes) : '';
        $imaging = $req->imagingrequestcodes ? implode(',', $req->imagingrequestcodes) : '';
        $data = array(
            'patientid' => $req->patientid,
            'doctorid' => $docid,
            'labrequestcodes' => $labreq,
            'imagingrequestcodes' => $imaging,
            'alertdescription' => $req->alertdescription,
            'treatmentplan' => $req->treatmentplan,
            'remarks' => $req->remarks,
            'encodedby' => $docid,
            'modifyby' => $req->id ? $docid : null,
            'void' => 1,
            'meet_id' => $req->doctororder_meet_id
        );
        if($req->doctororder_id) {
            DoctorOrder::find($req->doctororder_id)->update($data);
            Session::put("action_made","Successfully Update Doctor Order.");
        } else {
            DoctorOrder::create($data);
            Session::put("action_made","Successfully Add Doctor Order.");
        }
    }

    public function docorderDelete($id) {
        $docorder = DoctorOrder::find($id);
        if($docorder) {
            $docorder->update([
                'void' => 0
            ]);
        }
        Session::put("delete_action","Successfully delete Doctor Order");
    }

    public function doccatcomplete(Request $req) {
        $user = Session::get('auth');
        $doctor = User::find($user->id);
        $doctor->update([
            'doc_cat_id' => $req->tele_cate_id
        ]);
        Session::put('doccat', $req->tele_cate_id);
    }
}
