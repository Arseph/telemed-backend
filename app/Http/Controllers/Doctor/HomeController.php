<?php

namespace App\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\PendingMeeting;
use App\Patient;
use App\User;
use App\Teleconsult;
class HomeController extends Controller
{
    public function __construct()
    {
        if(!$login = Session::get('auth')){
            $this->middleware('auth');
        }
    } 

    public function index()
    {
        $user = Session::get('auth');
        $totaltele = Teleconsult::select(
            "teleconsults.*",
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id")
        ->count();
        $upcoming = Teleconsult::select(
            "teleconsults.*",
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id")
        ->whereDate("teleconsults.date_meeting", ">=", Carbon::now()->toDateString())
        ->count();
        $requested = PendingMeeting::select(
        "pending_meetings.*",
        )->leftJoin("patients as pat", "pending_meetings.patient_id", "=", "pat.id")
        ->leftJoin("users as use", "pending_meetings.user_id", "=", "use.id")
        ->where('pending_meetings.status', 'Pending')
        ->count();
        $success = Teleconsult::select(
            "teleconsults.*",
        )->leftJoin("patients as pat", "teleconsults.patient_id", "=", "pat.id")
        ->whereDate("teleconsults.date_meeting", "<=", Carbon::now()->toDateString())
        ->count();
        return view('doctors.home',[
            'upcoming' => $upcoming,
            'totaltele' => $totaltele,
            'requested' => $requested,
            'success' => $success
        ]);
    }
    function chart(){
        $data = array(
            'data1' => self::_requested(),
            'data2' => self::_completed()
        );
        echo json_encode($data);
    }

    function _requested(){
        $id = Session::get('auth')->id;
        for($i=1; $i<=12; $i++){
            $new = str_pad($i, 2, '0', STR_PAD_LEFT);
            $current = '01.'.$new.'.'.date('Y');
            $data['months'][] = date('M/y',strtotime($current));
            $startdate = date('Y-m-d',strtotime($current));
            $end = '01.'.($new+1).'.'.date('Y');
            if($new==12){
                $end = '12/31/'.date('Y');
            }
            $enddate = date('Y-m-d',strtotime($end));
            $count = PendingMeeting::where("user_id","=", $id)
                        ->where('created_at','>=',$startdate)
                        ->where('created_at','<=',$enddate)
                        ->count();
            $data['count'][] = $count;
        }
        return $data;
    }

    function _completed(){
        $id = Session::get('auth')->id;
        for($i=1; $i<=12; $i++){
            $new = str_pad($i, 2, '0', STR_PAD_LEFT);
            $current = '01.'.$new.'.'.date('Y');
            $data['months'][] = date('M/y',strtotime($current));
            $startdate = date('Y-m-d',strtotime($current));
            $end = '01.'.($new+1).'.'.date('Y');
            if($new==12){
                $end = '12/31/'.date('Y');
            }
            $enddate = date('Y-m-d',strtotime($end));
            $count = Teleconsult::where(function($q) use($id){
            $q->where("teleconsults.doctor_id","=", $id)
            ->orWhere("teleconsults.user_id", "=", $id);
                })->where('date_meeting','>=',$startdate)
                ->where('date_meeting','<=',$enddate)
                ->count();
            $data['count'][] = $count;
        }
        return $data;
    }
}
