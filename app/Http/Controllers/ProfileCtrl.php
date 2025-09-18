<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Models\ProfilePic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use App\Models\User;
use App\Models\EmpMain;
use App\Models\Attendance;
use App\Models\AttendanceUser;
use Carbon\Carbon;
use App\Models\ZkEmp;
use App\Models\Signature;
use App\Models\EmpTimeLog;
use App\Models\CsPerson;
use App\Models\Evaluation;
class ProfileCtrl extends Controller
{
    public function me() {
        $user = Auth::user();
        return response()->json($user);
    }

    public function profile(Request $req) {
        $id = Auth::user()->id;
        $profile = ProfilePic::where('user_id', $id)->first(); 
        return Response::make($profile->data, 200, [
            'Content-Type' => $profile->mime_type,
            'Content-Disposition' => 'inline; filename="' . $profile->name . '"'
        ]);
    }

    public function storeProfilePic(Request $request) {
        $id = Auth::user()->id;
        $file = $request->file('file');

        if ($file && $file->isValid()) {
            $blobData = file_get_contents($file);

            ProfilePic::updateOrCreate(
                ['user_id' => $id], 
                [
                    'mime_type' => $file->getClientMimeType(),
                    'name' => 'ro12.' . $file->getClientOriginalExtension(),
                    'data' => $blobData,
                ]
            );

            return response()->json(['message' => 'Profile saved successfully!']);
        }

        return response()->json(['message' => 'File upload failed.'], 400);
    }

    public function storeProfile(Request $req) {
        $user = Auth::user();
        if($user) {
            $user->info->update([
                'first_name' => $req->firstName,
                'middle_name' => $req->middleName,
                'sur_name' => $req->lastName,
                'email_add' => $req->email,
                'cell_no' => $req->phone,
                'title_name' => $req->titleName,
            ]);
            $user->update([
                'email' => $req->email,
            ]);
            return response()->json(['message' => 'Profile saved successfully!']);
        }
        return response()->json(['message' => 'User not Found.'], 400);
    }

    public function storeSecurity(Request $req) {
        $user = Auth::user();
        if($user) {
            if(Hash::check($req->currentPassword, $user->password)) {
                $validator = Validator::make($req->all(), [
                    'newPassword' => [
                        'required',
                        'min:8', // Minimum 8 characters
                        'regex:/[a-z]/', // At least one lowercase character
                        'regex:/[0-9!$%^&*()_+|~=`{}\[\]:";<>?,.\/]/', // At least one number or special character
                    ],
                ]);
    
                if ($validator->fails()) {
                    return response()->json(['message' => 'Password must be at least 8 characters long, include one lowercase character, and one number or symbol.'], 400);
                }
                $user->username = $req->userName;
                $user->password = Hash::make($req->newPassword);
                $user->save();
                return response()->json(['message' => 'Profile saved security']);
            } else {
                return response()->json(['message' => 'Current password does not match'], 400);
            }
        }
        return response()->json(['message' => 'User not Found.'], 400);
    }

    public function attendance($id, Request $req) {
        $auth = Auth::user();
        $jsonString = str_replace("'", '"', $req->data);
        $data = json_decode($jsonString, true);
        $attendance = Attendance::where('id',$data['id'])
                                ->where('token',$data['token'])
                                ->first();
        $date = Carbon::now()->toDateString();
        if($attendance) {
            if($date == $attendance->date_attend) {
                if(!Carbon::parse($attendance->expired_at)->isPast()) {
                    $att = AttendanceUser::where('user_id',$auth->id)->where('attendance_id',$attendance->id)->first();
                    if(!$att) {
                        AttendanceUser::create([
                            'user_id' => $auth->id,
                            'attendance_id' => $attendance->id,
                        ]);
                        return response()->json(['message' => 'Successfully attend '.$attendance->name]);
                    }
                    return response()->json(['message' => 'You already attended '.$attendance->name], 400);
                }
                return response()->json(['message' => 'Qr code expired'], 400);
            }
            $dt = Carbon::parse($attendance->date_attend)->format('F jS, Y');
            return response()->json(['message' => 'The QR code will become valid on '.$dt], 400);
        }
        return response()->json(['message' => 'Qr Code is not valid'], 400);
    }
    public function storeSignature(Request $request) {
        $id = Auth::user()->id;
        $data = $request->input('signature');
        $image = explode(',', $data)[1];
        $decodedImage = base64_decode($image);
        Signature::updateOrCreate(
            ['user_id' => $id], 
            [
                'signature' => $decodedImage,
            ]
        );

        return response()->json(['message' => 'Signature saved successfully.']);
    }
    public function fetchSignature() {
        $user = Auth::user();
        $sig = Signature::where('user_id',$user->id)->first();
        if($sig) {
            $signatureDataUrl = 'data:image/png;base64,' . base64_encode($sig->signature);
            return response()->json(['signature' => $signatureDataUrl]);
        }

    }
    public function dashboard(Request $req) {
        $userId = (int)Auth::user()->id;
        $date = Carbon::now();
        $currentMonth = $date->month;
        $currentYear = $date->year;
        $bdays = EmpMain::select('hris_main.*', 'hr_infos.emp_status')
                ->leftJoin('hr_infos as hr_infos', 'hris_main.id', '=', 'hr_infos.emp_id')
                ->where('hr_infos.emp_status', 1)
                ->whereRaw('MONTH(hris_main.date_birth) = ?', [$currentMonth])
                ->whereRaw('DAY(hris_main.date_birth) >= ?', [date('d')]) // Exclude past days
                ->orderByRaw('DAY(hris_main.date_birth)')
                ->get();
        $bdays->load('hrinfo.pos', 'hrinfo.sec', 'user.profile');
        $bdays->transform(function ($emp) {
            if ($emp->user && $emp->user->profile) {
                $emp->user->profile->data = base64_encode($emp->user->profile->data);
            }
            return $emp;
        });
        $dtr = EmpTimeLog::where('user_id',$userId)->whereYear('timestamp', $currentYear)
        ->whereMonth('timestamp', $currentMonth)
        ->orderBy('timestamp', 'asc')
        ->pluck('timestamp')
        ->toArray();
        $attendance = [];
        $totalHours = 0;
        if($dtr) {
            for ($i = 0; $i < count($dtr) - 1; $i += 2) {
                $timeIn = Carbon::parse($dtr[$i]);
                $timeOut = Carbon::parse($dtr[$i + 1]);
                
                $totalHours += $timeIn->diffInHours($timeOut);
            }
        }
        $event = Evaluation::where('emp_id',Auth::user()->emp_id)->orderBy('created_at','desc')->count();
        $inc = CsPerson::where('emp_id', Auth::user()->emp_id)->count();
        $te = (int)$event + (int)$inc;
        $ageGroups = DB::table('hr_infos as hr_infos')
            ->join('hris_main as hris_main', 'hr_infos.emp_id', '=', 'hris_main.id')
            ->selectRaw('TIMESTAMPDIFF(YEAR, hris_main.date_birth, CURDATE()) AS age')
            ->where('hr_infos.emp_status', 1)
            ->get()
            ->reduce(function ($groups, $employee) {
                if ($employee->age >= 18 && $employee->age <= 29) {
                    $groups['y18o']++;
                } elseif ($employee->age >= 30 && $employee->age <= 39) {
                    $groups['y30o']++;
                } elseif ($employee->age >= 40 && $employee->age <= 49) {
                    $groups['y40o']++;
                } elseif ($employee->age >= 50 && $employee->age <= 60) {
                    $groups['y50o']++;
                } elseif ($employee->age > 60) {
                    $groups['y60o']++;
                }
                return $groups;
            }, [
                'y18o' => 0,
                'y30o' => 0,
                'y40o' => 0,
                'y50o' => 0,
                'y60o' => 0,
            ]);
        return response()->json([
            'age' => $ageGroups,
            'bdays' => $bdays,
            'totalHours' => round($totalHours),
            'te' => $te,
        ]);
    }


}
