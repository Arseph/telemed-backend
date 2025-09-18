<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Audit;

class AuditTrailCtrl extends Controller
{
    public function index(Request $req)
    {
        if($req->daterange)
        {
            $str = $req->daterange;
            $temp1 = explode('-',$str);
            $temp2 = array_slice($temp1, 0, 1);
            $temp3 = array_slice($temp1, 1, 1);
        }
        else
        { 
            $end_date = date('m/d/Y'.' 12:59:59');
            $start_date = date('m/d/Y'.' 12:00:00', strtotime ( '-2 month')) ;
            $str = $start_date.' - '.$end_date;

            $temp1 = explode('-',$str);
            $temp2 = array_slice($temp1, 0, 1);
            $temp3 = array_slice($temp1, 1, 1);
        }
       
       
        $tmp = implode(',', $temp2);
        $startdate = date('Y-m-d'.' 12:00:00',strtotime($tmp));
        // $startdate = date("Y-m-d", strtotime ( '-2 month' , strtotime ( $tmp ) )) ;

        $tmp = implode(',', $temp3);
        $enddate = date('Y-m-d'.' 23:59:00',strtotime($tmp));

        $data = Audit::select('audits.*','users.fname','users.lname')
        ->leftjoin('users','audits.user_id','=','users.id')
        ->whereBetween('audits.created_at', [$startdate, $enddate])
        ->paginate(15);

        return view('superadmin.audit2',[
            'data' => $data,
            'daterange' => $str
        ]);
    }
}
