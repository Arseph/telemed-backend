<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use Rats\Zkteco\Lib\ZKTeco;
class AuthController extends Controller
{
    public function login(Request $request) {
        $request->validate([
        'username' => 'required|string',
        'password' => 'required|string'
        ]);

        $credentials = request(['username','password']);
        if(!Auth::attempt($credentials))
        {
            return response()->json([
                'message' => 'Unauthorized'
            ],401);
        }

        $user = $request->user();
        $name = '';
        if($user) {
            $name = $user->fname.' '.$user->lname;
        }
        // if ($user->tokens()->exists()) {
        //     return response()->json(['has_token' => true], 401);
        // }
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        return response()->json([
        'accessToken' =>$token,
        'token_type' => 'Bearer',
        'name' => $name,
        ]);
    }
    public function logout(Request $request) {
        $user = Auth::user();
        if ($user) {
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        }

        return response()->json(['error' => 'Not authenticated'], 401);
    }

    public function users(Request $request) {
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
        ->orderBy('hris_main.sur_name', 'asc')->get();
        return response()->json($users);
    }
    public function validateToken() {
        if (Auth::check()) {
            return response()->json(['valid' => true]);
        }
    
        return response()->json(['valid' => false], 401);
    }
}
