<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function register(Request $request)
    {
        // Your register logic here
        return response()->json(['message' => 'Register not implemented yet']);
    }
}
