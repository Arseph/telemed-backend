<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\LabRequest;

class LabRequestCtrl extends Controller
{
    public function index(Request $req)
    {
        $keyword = $req->keyword;
        $data = LabRequest::select('ref_labrequest.*','ref_unitofmes.unit_name')
        ->leftjoin('ref_unitofmes','ref_labrequest.uom_id','=','ref_unitofmes.id')
        ->where('ref_labrequest.isactive',1)
        ->where(function($q) use ($keyword){
        $q->where('ref_labrequest.req_code',"like","%$keyword%")
          ->orwhere('ref_labrequest.description',"like","%$keyword%");
          })
        ->orderby('ref_labrequest.id','desc')
        ->paginate(10);

        return view('superadmin.labrequest',[
            'data' => $data
        ]);
    }

    public function labrequestBody(Request $req)
    {
        if($req->id)
        {
            $data = LabRequest::find($req->id);
        }
        else{
            $data = array();
        }
        
        return view('superadmin.labrequest_body',[
        'data' => $data
        ]);
    }

    public function labrequestOptions(Request $req)
    {
        $user_id = Session::get('auth')->id;
        $code = LabRequest::where('req_code',$req->req_code)->first();
        $desc = LabRequest::where('description',$req->description)->first();
        $uom = LabRequest::where('uom_id',$req->uom_id)->first();

        if(isset($desc))
        {   
            Session::put('desc_valid',true);
            return Redirect::back();
        }

        if(isset($code))
        {
            $type = preg_replace('/[0-9]+/', '', $req->req_code);
            $str = ltrim($req->req_code, '0');
            $str1 = ltrim($str, 'RAD'); 
            $str2 = ltrim($str1, 'LAB'); 
            $add = $str2 + 1;
            $inc = str_pad($add, 5, '0', STR_PAD_LEFT);

            if($type == 'LAB')
            {
                $req_code = 'LAB'.$inc;
            }
            else if($type == 'RAD')
            {
                $req_code = 'RAD'.$inc;
            }
        }
        else
        {
            $req_code = $req->req_code;
        }

        $data = array(
            'req_code' => $req_code,
            'req_type' => $req->req_type,
            'description' => $req->description,
            'uom_id' => $req->uom_id,
            'isgeneral' => $req->isgeneral,
            'isactive' => $req->isactive,
            'encoded_by' => $user_id,
        );
        $match = array('id' => $req->id);

        $form = LabRequest::UpdateOrCreate($match,$data);

        if($form->wasRecentlyCreated)
        {
         Session::put("labrequest_add",true);
         }
         else
         {
            LabRequest::find($req->id)->update([
                'modify_by' => $user_id
            ]);
            Session::put("labrequest_update",true);
         }

         return Redirect::back();
     }

     public function labrequestDelete(Request $req)
     {
        LabRequest::find($req->id)->update([
            'isactive' => 0
        ]);
     }
}
