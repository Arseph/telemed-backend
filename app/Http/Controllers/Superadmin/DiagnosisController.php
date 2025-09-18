<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Diagnosis;
use App\DiagMainCat;
use App\DiagSubCat;

class DiagnosisController extends Controller
{
    public function __construct()
    {
        if(!$login = Session::get('auth')){
            $this->middleware('auth');
        }
    }

    //Start Diagnosis Module
    public function indexDiagnosis(Request $request) {
    	$diagnosis = Diagnosis::all();
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
        $data = Diagnosis::where(function($q) use ($keyword){
            $q->where('diagcode',"like","%$keyword%")
                ->orwhere('diagdesc',"like","%$keyword%");
               
            })
            ->where('void',0)
            ->orderby('diagcode','asc')
            ->paginate(30);
        $maincats = DiagMainCat::all()->where('void', 0);
	    return view('superadmin.diagnosis',[
	        'data' => $data,
	        'maincats' => $maincats,
	        'diagnosis' => $diagnosis
	    ]);
    }

    public function getSubCategory($id) {
    	$subcats = DiagSubCat::where('diagmcat', '=', $id)->orderBy('diagmcat', 'asc')->get();
            return response()->json(['subcats'=>$subcats]);
    }

    public function storeDiagnosis(Request $req) {
    	$diagnosis = Diagnosis::find($req->diagnosis_id);
        $data = array(
            'diagcode' => $req->diagcode,
            'diagdesc' => $req->diagdesc,
            'diagmaincat' => $req->diagmaincat,
            'diagcategory' => $req->diagcategory,
            'diagsubcat' => $req->diagsubcat,
            'diagpriority' => $req->diagpriority,
            'void' => 0
        );
        if(!$req->diagnosis_id){
            Session::put("action_made","Successfully added new diagnosis");
            Diagnosis::create($data);
        }
        else{
            Session::put("action_made","Successfully updated diagnosis");
            Diagnosis::find($req->diagnosis_id)->update($data);
        }
    }

    public function deleteDiagnosis($id) {
    	$diagnosis = Diagnosis::find($id);
        $diagnosis->update([
        	'void' => '1'
        ]);
        Session::put("delete_action","Successfully delete diagnosis");
    }

    public function indexDiagMainCat(Request $request) {
    	$maincats = DiagMainCat::all()->where('void', 0);
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
        $data = DiagMainCat::where(function($q) use ($keyword){
            $q->where('diagcat',"like","%$keyword%")
                ->orwhere('catdesc',"like","%$keyword%");
               
            })
            ->where('void',0)
            ->orderby('diagcat','asc')
            ->paginate(30);
        $maincats = DiagMainCat::all()->where('void', 0);
	    return view('superadmin.diagmaincat',[
	        'data' => $data,
	        'maincats' => $maincats
	    ]);
    }

    public function storeMainCat(Request $req) {
    	$maincat = DiagMainCat::find($req->main_id);
        $data = array(
            'diagcat' => $req->diagcat,
            'catdesc' => $req->catdesc,
            'void' => 0
        );
        if(!$req->main_id){
            Session::put("action_made","Successfully added new Main Category");
            DiagMainCat::create($data);
        }
        else{
            Session::put("action_made","Successfully updated Main Category");
            DiagMainCat::find($req->main_id)->update($data);
        }
    }
    public function deleteMainCat($id) {
    	$maincat = DiagMainCat::find($id);
        $maincat->update([
        	'void' => '1'
        ]);
        Session::put("delete_action","Successfully delete main category");
    }

    public function indexDiagSubCat(Request $request) {
    	$maincats = DiagMainCat::all()->where('void', 0);
    	$subcats = DiagSubCat::all()->where('void', 0);
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
        $data = DiagSubCat::where(function($q) use ($keyword){
            $q->where('diagsubcat',"like","%$keyword%")
                ->orwhere('diagscatdesc',"like","%$keyword%");
               
            })
            ->where('void',0)
            ->orderby('diagsubcat','asc')
            ->paginate(30);
	    return view('superadmin.diagsubcat',[
	        'data' => $data,
	        'maincats' => $maincats,
	        'subcats' => $subcats
	    ]);
    }
    public function storeSubCat(Request $req) {
    	$subcat = DiagSubCat::find($req->sub_id);
        $data = array(
            'diagmcat' => $req->diagmcat,
            'diagsubcat' => $req->diagsubcat,
            'diagscatdesc' => $req->diagscatdesc,
            'void' => 0
        );
        if(!$req->sub_id){
            Session::put("action_made","Successfully added new Sub Category");
            DiagSubCat::create($data);
        }
        else{
            Session::put("action_made","Successfully updated Sub Category");
            DiagSubCat::find($req->sub_id)->update($data);
        }
    }
	public function deleteSubCat($id) {
		$subcat = DiagSubCat::find($id);
        $subcat->update([
        	'void' => '1'
        ]);
        Session::put("delete_action","Successfully delete sub category");
	}
}
