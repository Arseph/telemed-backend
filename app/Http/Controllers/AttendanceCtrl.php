<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\AttendanceUser;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceCtrl extends Controller
{
    public function attendanceUser(Request $request) {
        $user = Auth::user();

        $start = $request->query('start');
        $end = $request->query('end');
        if ($start && $end) {
            try {
                $startDate = Carbon::createFromFormat('F j, Y', $start)->startOfDay();
                $endDate = Carbon::createFromFormat('F j, Y', $end)->endOfDay();
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }
        }

        $md = AttendanceUser::where('user_id', $user->id)
            ->whereHas('attendance', function ($md) {
                $md->where('name', 'MONDAY DIALOGUE');
            })
            ->with('attendance');

        if (isset($startDate) && isset($endDate)) {
            $md->whereBetween('created_at', [$startDate, $endDate]);
        }

        $mdresult = $md->count();
        $fc = AttendanceUser::where('user_id', $user->id)
            ->whereHas('attendance', function ($fc) {
                $fc->where('name', 'FLAG CEREMONY');
            })
            ->with('attendance');

        if (isset($startDate) && isset($endDate)) {
            $fc->whereBetween('created_at', [$startDate, $endDate]);
        }

        $fcresult = $fc->count();
        $attendances = Attendance::whereBetween('date_attend', [$startDate, $endDate])->get();

        $result = $attendances->map(function ($attendance) use ($user) {
            $attendanceUser = $attendance->attendees()
                ->where('user_id', $user->id)
                ->first();

            return [
                'id' => $attendance->id,
                'name' => $attendance->name,
                'date' => $attendance->date_attend,
                'attendance_user' => $attendanceUser ? [
                    'id' => $attendanceUser->id,
                    'user_id' => $attendanceUser->user_id,
                    'created_at' => $attendanceUser->created_at,
                ] : null,
            ];
        });
        $mdall = Attendance::whereBetween('date_attend', [$startDate, $endDate])
                            ->where('name', 'MONDAY DIALOGUE')
                            ->count();
        $fcall = Attendance::whereBetween('date_attend', [$startDate, $endDate])
                            ->where('name', 'FLAG CEREMONY')
                            ->count();
        return response()->json([
            'md' => $mdresult,
            'mdall' => $mdall,
            'fc' => $fcresult,
            'fcall' => $fcall,
            'attendance' => $result,
        ]);
    }
}
