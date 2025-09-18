<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\LeaveRequest;
use App\Models\Leave;
use App\Facades\Responder;
use App\Http\Resources\Leave\LeaveResource;
use Illuminate\Support\Facades\DB;
use App\Services\Leave\LeaveService;
use Carbon\Carbon;

class LeaveCtrl extends Controller
{
    private LeaveService $LeaveService;

    public function __construct(LeaveService $LeaveService)
	{
		$this->LeaveService = $LeaveService;
	}

    public function index (LeaveRequest $request){
        $leaves = $this->LeaveService->getLeave();
        $specificDate = Carbon::createFromFormat('Y-m-d', '2011-11-10');
        $logs = DB::connection('old_database')
        ->table('viewTimeInOut')
        ->whereMonth('DateToDay', $specificDate->month)
        ->whereYear('DateToDay', $specificDate->year)
        ->where('EmpID', '20020008')
        ->get();
    
        $processedLogs = [];
        
        foreach ($logs as $log) {
            $date = Carbon::parse($log->DateToday)->toDateString();
            $time = Carbon::parse($log->timelog);
            $timeOfDay = $time->hour < 12 ? 'am' : 'pm';
            
            // Initialize date if not already processed
            if (!isset($processedLogs[$date])) {
                $processedLogs[$date] = [
                    'am' => ['time_in' => null, 'time_out' => null],
                    'pm' => ['time_in' => null, 'time_out' => null],
                    'total_hours' => 0,
                ];
            }
        
            // Classify the log as AM/PM and In/Out
            if ($timeOfDay === 'am') {
                if ($log->logtype === 'in' && !$processedLogs[$date]['am']['time_in']) {
                    $processedLogs[$date]['am']['time_in'] = $time;
                } elseif ($log->logtype === 'out' && !$processedLogs[$date]['am']['time_out']) {
                    $processedLogs[$date]['am']['time_out'] = $time;
                }
            } elseif ($timeOfDay === 'pm') {
                if ($log->logtype === 'in' && !$processedLogs[$date]['pm']['time_in']) {
                    $processedLogs[$date]['pm']['time_in'] = $time;
                } elseif ($log->logtype === 'out' && !$processedLogs[$date]['pm']['time_out']) {
                    $processedLogs[$date]['pm']['time_out'] = $time;
                }
            }
        }
        
        // Compute the total hours for each day
        foreach ($processedLogs as $date => &$shifts) {
            $totalHours = 0;
        
            // Calculate AM shift hours
            if ($shifts['am']['time_in'] && $shifts['am']['time_out']) {
                $amHours = $shifts['am']['time_in']->diffInMinutes($shifts['am']['time_out']) / 60;
                $totalHours += $amHours;
            }
        
            // Calculate PM shift hours
            if ($shifts['pm']['time_in'] && $shifts['pm']['time_out']) {
                $pmHours = $shifts['pm']['time_in']->diffInMinutes($shifts['pm']['time_out']) / 60;
                $totalHours += $pmHours;
            }
        
            // Cap the total hours to 8 hours maximum
            $shifts['total_hours'] = min($totalHours, 8);
        }
        
       return response()->json([
        'logs' => $processedLogs,
        'total_hours' => $totalHours
       ]);
    }
}
