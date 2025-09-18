<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\DrugsMeds;
use App\UnitofMes;
use App\DrugsMedsSubcat;

class DrugsMedsCtrl extends Controller
{

    public function index(Request $req)
    {
        $keyword = $req->keyword;
        $data = DrugsMeds::select('ref_drugsmeds.*','ref_drugsubcat.subcat_name','ref_unitofmes.unit_name')
        ->leftjoin('ref_drugsubcat','ref_drugsmeds.subcat_id','=','ref_drugsubcat.id')
        ->leftjoin('ref_unitofmes','ref_drugsmeds.unitofmes_id','=','ref_unitofmes.id')
        ->where('ref_drugsmeds.isvoid',1)
        ->where(function($q) use ($keyword){
        $q->where('ref_drugsmeds.drugcode',"like","%$keyword%")
          ->orwhere('ref_drugsmeds.drugdescription',"like","%$keyword%");
          })
        ->orderby('ref_drugsmeds.id','desc')
        ->paginate(10);

        return view('superadmin.drugsmeds',[
            'data' => $data
        ]);
    }

    public function drugsmedsBody(Request $req)
    {
        if($req->id)
        {
            $data = DrugsMeds::find($req->id);
        }
        else{
            $data = array();
        }
        
        return view('superadmin.drugsmeds_body',[
        'data' => $data
        ]);
    }

    public function drugsmedsOptions(Request $req)
    {
     $user_id = Session::get('auth')->id;
        $data = array(
            'drugcode' => $req->drugcode,
            'subcat_id' => $req->subcat_id,
            'drugdescription' => $req->drugdescription,
            'unitofmes_id' => $req->unitofmes_id,
            'isgeneral' => $req->isgeneral,
            'isvoid' => $req->void,
            'encoded_by' => $user_id,
        );
        $match = array('id' => $req->id);

        $form = DrugsMeds::UpdateOrCreate($match,$data);

        if($form->wasRecentlyCreated)
        {
        Session::put("drugmeds_add",true);
         }
         else
         {
            DrugsMeds::find($req->id)->update([
                'modify_by' => $user_id
            ]);
            Session::put("drugmeds_update",true);
         }

         return Redirect::back();
     }

     public function drugsmedsDelete(Request $req)
     {
        DrugsMeds::find($req->id)->update([
            'isvoid' => 0
        ]);
     }

    public function unitofmesIndex(Request $req)
    {
        $keyword = $req->keyword;
        $data = UnitofMes::where('isactive',1)
          ->where(function($q) use ($keyword){
            $q->where('unit_name',"like","%$keyword%")
                ->orwhere('unit_code',"like","%$keyword%");
               
            })
        ->paginate(10);
        
       return view('superadmin.unitofmes',[
        'data' => $data,
        'keyword' => $keyword
       ]);
    }

    public function unitofmesBody(Request $req)
    {
        if($req->id)
        {
            $data = UnitofMes::find($req->id);
        }
        else{
            $data = array();
        }
        
        return view('superadmin.unitofmes_body',[
            'data' => $data
        ]);
    }

    public function unitofmesOptions(Request $req)
    {
        $data = $req->all();
        $match = array('id' => $req->id);

        $form = UnitofMes::UpdateOrCreate($match,$data);
        if($form->wasRecentlyCreated)
        {
            Session::put("unitodmes_add",true);
        }else{
            Session::put("unitodmes_update",true);
        }
    
        return Redirect::back();
    }

    public function unitofmesDelete(Request $req)
    {
        UnitofMes::find($req->id)->update([
            'isactive' => 0
        ]);
    }

    public function subcatIndex(Request $req)
    {
        $keyword = $req->keyword;
        $data = DrugsMedsSubcat::where('isactive',1)
          ->where(function($q) use ($keyword){
          $q->where('subcat_name',"like","%$keyword%")
              ->orwhere('subcat_code',"like","%$keyword%");
             
          })
      ->paginate(10);

        return view('superadmin.drugsubcat',[
            'data' => $data
        ]);
    }

    public function subcatBody(Request $req)
    {
        if($req->id)
        {
            $data = DrugsMedsSubcat::find($req->id);
        }
        else{
            $data = array();
        }

        return view('superadmin.drugsubcat_body',[
            'data' => $data
        ]);
    }

    public function subcatOptions(Request $req)
    {
        $data = $req->all();
        $match = array('id' => $req->id);

        $form = DrugsMedsSubcat::UpdateOrCreate($match,$data);
        if($form->wasRecentlyCreated)
        {
            Session::put("unitodmes_add",true);
        }else{
            Session::put("unitodmes_update",true);
        }
    
        return Redirect::back();
    }

    public function subcatDelete(Request $req)
    {
        DrugsMedsSubcat::find($req->id)->update([
            'isactive' => 0
        ]);
    }
}
