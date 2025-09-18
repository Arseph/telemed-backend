<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rats\Zkteco\Lib\ZKTeco;
use App\Models\ZkPunch;
use App\Models\ZkEmp;
use Carbon\Carbon;
use Auth;
class SampleCtrl extends Controller
{
    public function index() {
        $year = 2024;
        $month = 10;
        $dtr = ZkEmp::where('emp_pin',$userId)
        ->dtr
        ->whereYear('punch_time',$year)
        ->whereMonth('punch_time',$month)
        ->orderBy('punch_time','asc')
        ->get();
        $nodays = Carbon::createFromDate($year, $month)->daysInMonth;
        $start = Carbon::createFromDate($year, $month, 1);
        $total = 0;
        $end = $start->copy()->endOfMonth();
        $attendance = [];
        for ($date = $start; $date->lte($end); $date->addDay()) {
            if (!$date->isWeekend()) {
                $total++;
            }
        }
        for ($day = 1; $day <= $nodays; $day++) {
            $attendance[$day] = [
                'date' => Carbon::create($year, $month, $day)->toDateString(),
                'in_am' => '',
                'out_am' => '',
                'in_pm' => '',
                'out_pm' => '',
            ];
        }
        foreach($dtr as $d) {
            $date = Carbon::parse($d->punch_time);
            $isAm = Carbon::parse($d->punch_time)->hour < 12;
            $isPm = Carbon::parse($d->punch_time)->hour >= 12;
            $day = $date->day;
            if(!$attendance[$day]['in_am']) {
                if($isAm) {
                    $attendance[$day]['in_am'] = $d->punch_time;
                }
            }
            else if(!$attendance[$day]['out_am']) {
                $attendance[$day]['out_am'] = $d->punch_time;
            }
            if(!$attendance[$day]['in_pm']) {
                if($isPm) {
                    $attendance[$day]['in_pm'] = $d->punch_time;
                }
            }
            else if(!$attendance[$day]['out_pm']) {
                $attendance[$day]['out_pm'] = $d->punch_time;
            }

        } 
        return response()->json($attendance);
    }
    public function attendance(Request $req) {
        $faceDescriptor = $req->input('faceDescriptor');
        $user = Auth::user();
        $storedDescriptor = json_decode($user->face_id, true);
        if ($this->compareDescriptors($storedDescriptor, $faceDescriptor)) {
            return response()->json(['message' => 'Successfully Attend', 'time_in' => $user->time_out]);
        } else {
            return response()->json(['message' => 'Face does not match! Please Try Again'], 401);
        }
    }
    private function compareDescriptors($stored, $current)
    {
        // Example of comparing face descriptors
        $distance = 0;
        for ($i = 0; $i < count($stored); $i++) {
            $distance += pow($stored[$i] - $current[$i], 2);
        }
        $distance = sqrt($distance);
        
        // If the distance is small enough, it's considered the same face
        return $distance < 0.6;
    }
}
