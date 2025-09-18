<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Doc_Type;

class DocumentCtrl extends Controller
{
    public function index(Request $req)
    {
        $keyword = $req->keyword;
        $data = Doc_Type::where('isactive',1)
        ->where(function($q) use ($keyword){
            $q->where('doc_name',"like","%$keyword%");
            })
        ->paginate(10);

        return view('superadmin.doc_type',[
            'data' => $data
        ]);
    }

    public function doctypeBody(Request $req)
    {
        if($req->id)
        {
            $data = Doc_Type::find($req->id);
        }
        else{
            $data = array();
        }
        return view('superadmin.doctype_body',[
            'data' => $data
        ]);
    }
    
    public function doctypeOptions(Request $req)
    {
        $data = $req->all();
        $match = array('id' => $req->id);

        $form = Doc_Type::UpdateOrCreate($match,$data);
        if($form->wasRecentlyCreated)
        {
            Session::put("docytpe_add",true);
        }else{
            Session::put("doctype_update",true);
        }
    
        return Redirect::back();
    }

    public function doctypeDelete(Request $req)
    {
        Doc_Type::find($req->id)->update([
            'isactive' => 0
        ]);
    }
}
