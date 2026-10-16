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
        'picture' => $user && $user->picture ? url($user->picture) : null,
        'role' => $user?->level,
        ]);
    }

    // Alias kept so the /dbms/login route resolves to the same handler.
    public function dbmslogin(Request $request) {
        return $this->login($request);
    }
    public function logout(Request $request) {
        $user = Auth::user();
        if ($user) {
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        }

        return response()->json(['error' => 'Not authenticated'], 401);
    }

    // public function users(Request $request)
    // {
    //     $users = User::select(
    //             'users.id',
    //             'users.username',
    //             'users.fname',
    //             'users.mname',
    //             'users.lname',
    //             'users.level as role',
    //             'users.picture',
    //             'users.status',
    //             'users.title',
    //             'users.prefix',
    //             'users.contact',
    //             'users.email',
    //             'users.designation',
    //             'users.license_no',
    //             'users.accrediation_no as accred_no',
    //             'users.accrediation_validity as accred_validity',
    //             'facilities.facilityname as facility',
    //             'doctor_categories.category_name as doc_category'
    //         )
    //         ->leftJoin('facilities', 'users.facility_id', '=', 'facilities.id')
    //         ->leftJoin('doctor_categories', 'users.doc_cat_id', '=', 'doctor_categories.id') 
    //         ->orderBy('users.created_at', 'desc') // Most recent first
    //         ->get();

    //     return response()->json($users);
    // }
    public function validateToken() {
        if (Auth::check()) {
            return response()->json(['valid' => true]);
        }
    
        return response()->json(['valid' => false], 401);
    }
}
