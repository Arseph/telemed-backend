<?php

namespace App\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Issue;
use App\Facility;

class IssueConcernCtrl extends Controller
{
    public function index(Request $req)
    {
        $user = Session::get('auth');

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

        $data = Issue::select('issue.*','meetings.*')
        ->leftjoin('meetings','issue.meet_id','=','meetings.id')
        ->where('meetings.doctor_id',$user->id)
        ->orwhere('meetings.user_id',$user->id)
        ->where('issue.void',1) 
        ->whereBetween('issue.created_at', [$startdate, $enddate])
        ->groupby('issue.meet_id')
        ->paginate(15);

        // $data = Issue::with(['meeting' => function($q)use($user) {
        //     $q->where("meetings.doctor_id",$user->id)
        //     ->orWhere("meetings.user_id",$user->id)
        //     ->orWhere("meetings.patient_id",$user->id);
        // }])
        // ->where('void',1) 
        // ->groupby('meet_id')
        // ->paginate(15);

        return view('doctors.issue',[
            'data' => $data,
            'daterange' => $str,
            'pastmeetings' => []
        ]);
    }

    public function IssueAndConcern($meet_id,$issue_from)
    {
        $facility = Facility::find($issue_from);
        $data = Issue::where("meet_id","=",$meet_id)->orderBy("id","asc")->get();

        return view('doctors.convo_issue',[
            'data' => $data,
            'facility' => $facility
        ]);
    }

    public function issueSubmit(Request $request)
    {
        $issue = $request->get('issue');
        $meeting_id = $request->get('meeting_id');
        $data  = array(
            "meet_id" => $meeting_id,
            "issue" => $issue,
            "status" => 'outgoing',
            "void" => 1
        );
        
        Issue::create($data);

        $facility = Facility::find(Session::get("auth")->facility_id);
        return view("doctors.issue_append",[
            "facility_name" => $facility->facilityname,
            "meeting_id" => $meeting_id,
            "issue" => $issue
        ]);
    }
}
