<?php

namespace App\Http\Controllers\Dtr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EmpMain;
use Carbon\Carbon;
use App\Models\ZkPunch;
use App\Models\ZkEmp;
use App\Models\EmpTimeLog;
use Auth;
use Illuminate\Support\Facades\Http;
use Rats\Zkteco\Lib\ZKTeco;
class DtrCtrl extends Controller
{
    private function connectBio($userId = null) {
        $devices = [
            ['ip' => '192.168.110.85', 'port' => 4370],
            ['ip' => '192.168.130.72', 'port' => 4370],
            ['ip' => '192.168.120.77', 'port' => 4370],
            ['ip' => '192.168.180.103', 'port' => 4370],
        ];
        $insertData = [];
        foreach ($devices as $device) {
            try {
                $zk = new ZKTeco($device['ip']);
    
                if (!$zk->connect()) {
                    \Log::warning("Failed to connect to device: {$device['ip']}");
                    continue;
                }
    
                $logs = $zk->getAttendance();
                if (empty($logs)) {
                    \Log::info("No attendance logs found for device: {$device['ip']}");
                    continue;
                }
                if ($userId) {
                    $logs = array_filter($logs, fn($log) => $log['id'] == $userId);
                }
                collect($logs)->chunk(100)->each(function ($chunk) use ($device) {
                    $insertData = collect($chunk)->map(function ($log) use ($device) {
                        return [
                            'device_ip'  => $device['ip'],
                            'user_id'    => $log['id'],
                            'timestamp'  => $log['timestamp'],
                        ];
                    })->toArray();
    
                    foreach ($insertData as $data) {
                        EmpTimeLog::firstOrCreate([
                            'device_ip' => $data['device_ip'],
                            'user_id' => $data['user_id'],
                            'timestamp' => $data['timestamp'],
                        ]);
                    }
                });
    
                $zk->disconnect();
            } catch (\Exception $e) {
                \Log::error("Error connecting to device {$device['ip']}: " . $e->getMessage());
            }
        }
    }
    
    public function dtrv2(Request $req) {
        $this->connectBio($req->id);
        return $this->dtrQu($req->id, $req->month, $req->year);
    }
    
    public function index(Request $request, $id)
    {
        $currentMonth = $request->input('month');
        $currentYear = $request->input('year');
        $response = Http::get('http://192.168.1.50:8000/api/employee/dtr/'.$id, [
            'month' => $currentMonth,
            'year' => $currentYear,
        ]);
        return $response->body();
    }
    public function daylog() {
        $id = Auth::user()->id;
        $this->connectBio($id);
        $today = now();
        $timelog = EmpTimeLog::where('user_id', $id)
            ->whereYear('timestamp', $today->year)
            ->whereMonth('timestamp', $today->month)
            ->whereDay('timestamp', $today->day)
            ->orderBy('timestamp', 'asc')
            ->pluck('timestamp')
            ->toArray();
        return response()->json(array_values($timelog));
    }
    private function dtrQu($userId, $month, $year) {
        $month = (int)$month;
        $year = (int)$year;
        $userId = (int)Auth::user()->id;
        $dtr = EmpTimeLog::where('user_id',$userId)->whereYear('timestamp', $year)
        ->whereMonth('timestamp', $month)
        ->orderBy('timestamp', 'asc')
        ->get();
        $attendance = [];
        $totalHours = 0;
        $regdays = 0;
        $sat = 0;
        if($dtr) {
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
            foreach ($dtr as $d) {
                $date = Carbon::parse($d->timestamp);
                $formattedTime = $date->format('h:i A');
                $hour = $date->hour;
                $day = $date->day;

                $isAm = $hour < 12;
                $isNoon = $hour === 12;
                $isPm = $hour >= 12;
                $isLatePm = $hour >= 15; // 3:00 PM or later

                // Ensure array keys exist
                if (!isset($attendance[$day])) {
                    $attendance[$day] = [
                        'in_am' => null,
                        'out_am' => null,
                        'in_pm' => null,
                        'out_pm' => null,
                    ];
                }

                // IN AM
                if (!$attendance[$day]['in_am'] && $isAm) {
                    $attendance[$day]['in_am'] = $formattedTime;
                    continue;
                }

                // OUT AM
                if (!$attendance[$day]['out_am']) {
                    if (!$attendance[$day]['in_am'] && $isNoon) {
                        // No in_am and time is 12:00-12:59 PM → assign out_am
                        $attendance[$day]['out_am'] = $formattedTime;
                        continue;
                    }

                    if ($attendance[$day]['in_am'] && $attendance[$day]['in_am'] != $formattedTime && !$isLatePma) {
                        $attendance[$day]['out_am'] = $formattedTime;
                        continue;
                    }
                }

                // IN PM
                if (!$attendance[$day]['in_pm'] && $isPm && !$isLatePm) {
                    if (
                        $formattedTime !== $attendance[$day]['in_am'] &&
                        $formattedTime !== $attendance[$day]['out_am']
                    ) {
                        $attendance[$day]['in_pm'] = $formattedTime;
                        continue;
                    }
                }

                // OUT PM
                if (!$attendance[$day]['out_pm']) {
                    if (
                        $isPm &&
                        (
                            $isLatePm || // 3:00 PM onwards
                            ($attendance[$day]['in_pm'] && $formattedTime !== $attendance[$day]['in_pm'])
                        ) &&
                        $formattedTime !== $attendance[$day]['in_am'] &&
                        $formattedTime !== $attendance[$day]['out_am']
                    ) {
                        $attendance[$day]['out_pm'] = $formattedTime;
                        continue;
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

        return response()->json(array_values($attendance));
    }
    public function deviceStatus() {
        $devices = [
            ['ip' => '192.168.110.85', 'port' => 4370, 'floor' => '1st Floor'],
            ['ip' => '192.168.120.77', 'port' => 4370, 'floor' => '2nd Floor'],
            ['ip' => '192.168.130.72', 'port' => 4370, 'floor' => '3rd Floor'],
            ['ip' => '192.168.180.103', 'port' => 4370, 'floor' => 'Warehouse'],
        ];

        $status = [];

        foreach ($devices as $device) {
            try {
                $zk = new ZKTeco($device['ip'], $device['port']); // Ensure port is used if needed
                $isConnected = $zk->connect(); // Try connecting to device
                $status[] = [
                    'floor' => $device['floor'],
                    'ip' => $device['ip'],
                    'port' => $device['port'],
                    'status' => $isConnected ? 'Connected' : 'Disconnected, Please contact administrator',
                ];
            } catch (\Exception $e) {
                $status[] = [
                    'floor' => $device['floor'],
                    'ip' => $device['ip'],
                    'port' => $device['port'],
                    'status' => 'Error: ' . $e->getMessage(),
                ];
            }
        }
        return response()->json(array_values($status));
    }
}
