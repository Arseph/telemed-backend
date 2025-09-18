<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dtr;
use App\Models\DtrAm;
use App\Models\DtrPm;
use Rats\Zkteco\Lib\ZKTeco;
use Carbon\Carbon;
use Auth;
use App\Models\ZkPunch;
use App\Models\ZkEmp;
use Barryvdh\DomPDF\Facade\Pdf;
class DtrController extends Controller
{
    private function users($dtr, $id = null) {
        $users = User::join('hris_main', 'users.emp_id', '=', 'hris_main.id')
        ->select('users.id','users.email', 'hris_main.first_name','hris_main.sur_name','hris_main.middle_name',
        'hris_main.picture_link as pic',
        'positions.position as pos',
            'divisions.description as off',
            'sections.station as sec',
        )->leftJoin('hr_infos as hr_infos', 'hris_main.id','=','hr_infos.emp_id')
        ->leftJoin('positions as positions','hr_infos.position_id','=','positions.id')
        ->leftJoin('sections as sections','hr_infos.section_id','=','sections.id')
        ->leftJoin('divisions as divisions','hr_infos.division_id','=','divisions.id')
        ->where('users.is_dtr',$dtr, 0)
        ->when($id !== null, function ($query) use ($id) {
            $query->where('users.id', $id);
        })->orderBy('hris_main.sur_name', 'asc');
        $users = $id ? $users->first() : $users->get();
        return response()->json($users);
    }
    public function employees() {
        $users = $this->users('=',null);
        return $users;
    }
    public function dtr() {
        $users = $this->users('>',null);
        return $users;
    }
    public function register(Request $req) {
        $userId = $req->input('id');
        $userName = $req->input('name');
        $role = $req->input('role') == 'Employee' ? 0 : 14;
        $user = User::find($userId);
        if($user) {
            if($user->is_dtr != 1) {
                $zk = new ZKTeco(env('ZK_IP'));
                if ($zk->connect()) {
                    $zk->setUser($userId, $userId, $userName, '', $role);
                    $user->update([
                        'is_dtr' => 1
                    ]);
                    return response()->json(['message' => 'Successfully added employee'], 200);
                } else {
                    return response()->json(['message' => 'Unable to connect to ZKTeco device.'], 500);
                }
            }
        }
    }
    public function getEmployee($id) {
        $users = $this->users('=', $id);
        return $users;
    }
    public function dtrView(Request $req) {
        $dtr = $this->dtrQu($req->id, $req->month, $req->year,$req->name);
        return $dtr;
    }
    public function generateDtr(Request $req) {
        $dtr = $this->dtrQu($req->id, $req->month, $req->year,$req->name);
        $data = $dtr->getData();
        $pdf = PDF::loadView('pdf.dtr', [
            'data' => $data
        ])->setPaper('a4', 'portrait');
        return $pdf->download('dtr.pdf', ['Content-Type' => 'application/pdf']);
    }
    private function dtrQu($userId, $month, $year, $name) {
        $month = (int)$month;
        $year = (int)$year;
        $userId = (int)$userId ?? (int)Auth::user()->id;
        $dtr = ZkEmp::where('emp_pin',$userId)->first();
        $dtr_id = ZkEmp::where('emp_pin',$userId)->first();
        $attendance = [];
        $totalHours = 0;
        $regdays = 0;
        $sat = 0;
        if($dtr) {
            $dtr = $dtr->dtr;
            $nodays = Carbon::createFromDate($year, $month)->daysInMonth;
            $start = Carbon::createFromDate($year, $month, 1);
            $total = 0;
            $end = $start->copy()->endOfMonth();
            for ($date = $start; $date->lte($end); $date->addDay()) {
                if (!$date->isWeekend()) {
                    $total++;
                }
            }
            for ($day = 1; $day <= $nodays; $day++) {
                $currentDate = Carbon::create($year, $month, $day);
                if ($currentDate->isWeekday() && !$currentDate->isSaturday()) {
                    $regdays++;
                } elseif ($currentDate->isSaturday()) {
                    $sat++;
                }
                $attendance[$day] = [
                    'date' => Carbon::create($year, $month, $day)->day,
                    'in_am' => '',
                    'out_am' => '',
                    'in_pm' => '',
                    'out_pm' => '',
                    'total_hours' => 0,
                ];
            }
            foreach($dtr as $data) {
                $punches = $data->where('employee_id',$dtr_id->id)
                                ->whereYear('punch_time', $year)
                                ->whereMonth('punch_time', $month)
                                ->orderBy('punch_time', 'asc')
                                ->get();
                
                foreach ($punches as $d) {
                    $date = Carbon::parse($d->punch_time);
                    $isAm = $date->hour < 12;
                    $isPm = $date->hour >= 12;
                    $day = $date->day;
            
                    if(!$attendance[$day]['in_am']) {
                        if($isAm) {
                            $attendance[$day]['in_am'] = Carbon::parse($d->punch_time)->format('h:i A');
                        }
                    }
                    else if(!$attendance[$day]['out_am']) {
                        if($attendance[$day]['in_am'] != Carbon::parse($d->punch_time)->format('h:i A')) {
                            $attendance[$day]['out_am'] = Carbon::parse($d->punch_time)->format('h:i A');
                        }
                    }
                    if(!$attendance[$day]['in_pm']) {
                        if($isPm) {
                            if($attendance[$day]['out_am'] != Carbon::parse($d->punch_time)->format('h:i A')) {
                                $attendance[$day]['in_pm'] = Carbon::parse($d->punch_time)->format('h:i A');
                            }
                        }
                    }
                    else if(!$attendance[$day]['out_pm']) {
                        if($isPm) {
                            if($attendance[$day]['out_am'] != Carbon::parse($d->punch_time)->format('h:i A')){
                                if($attendance[$day]['in_pm'] != Carbon::parse($d->punch_time)->format('h:i A')) {
                                    $attendance[$day]['out_pm'] = Carbon::parse($d->punch_time)->format('h:i A');
                                }
                            }
                        }
                    }
                }
            }
            foreach ($attendance as $day => $data) {
                $inAm = $data['in_am'] ? Carbon::parse($data['in_am']) : null;
                $outAm = $data['out_am'] ? Carbon::parse($data['out_am']) : null;
                $inPm = $data['in_pm'] ? Carbon::parse($data['in_pm']) : null;
                $outPm = $data['out_pm'] ? Carbon::parse($data['out_pm']) : null;
    
                $morningHours = 0;
                $afternoonHours = 0;
    
                if ($inAm && $outAm) {
                    $morningHours = $inAm->diffInMinutes($outAm) / 60;
                }
    
                if ($inPm && $outPm) {
                    $afternoonHours = $inPm->diffInMinutes($outPm) / 60;
                }
    
                $dailyHours = $morningHours + $afternoonHours;
                $attendance[$day]['total_hours'] = $dailyHours !== 0 ? round($dailyHours, 2) : $dailyHours;
    
                $totalHours += $dailyHours !== 0 ? round($dailyHours, 2) : $dailyHours;
            }
        }

        return response()->json([
            'attendance' => $attendance,
            'total_rendered_hours' => $totalHours !== 0 ? round($totalHours, 2) : $totalHours,
            'regdays' => $regdays,
            'sat' => $sat,
            'name' => $name,
            'monthno' => $month,
            'month' => Carbon::create()->month($month)->format('F'),
            'year'=> $year,
        ]);
    }
}
