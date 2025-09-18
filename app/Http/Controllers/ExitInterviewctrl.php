<?php

namespace App\Http\Controllers;

use App\Models\ExitInterview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class ExitInterviewctrl extends Controller
{
    //
    public function index()
    {
        $user = Auth::user();
        $exitInterview = ExitInterview::where('user_id', $user->id)->first();
 // Convert JSON fields back to arrays before returning (if needed)
//  $exitInterview->q1 = explode(',', $exitInterview->q1);
//  $exitInterview->q1a = explode(',', $exitInterview->q1a);
//  $exitInterview->q1b = explode(',', $exitInterview->q1b);
$exitInterview->q1 = json_decode($exitInterview->q1, true);
$exitInterview->q1a = json_decode($exitInterview->q1a, true);
$exitInterview->q1b = json_decode($exitInterview->q1b, true);

 return response()->json($exitInterview);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validatedData = $request->validate([
            'effect_date' => 'nullable|date',
            'q1' => 'required|array',
            'q1other' => 'nullable|string|max:255',
            'q1a' => 'nullable|array',
            'q1aother' => 'nullable|string|max:255',
            'q1acountry' => 'nullable|string|max:255',
            'q1b' => 'nullable|array',
            'q1bother' => 'nullable|string|max:255',
            'q2' => 'nullable|string|max:255',
            'q3' => 'nullable|string|max:255',
            'q4' => 'nullable|string|max:255',
            'ratings' => 'nullable|array',
            'comment' => 'nullable|string|max:255',
        ]);
    
        // ✅ Store arrays as JSON
        $validatedData['q1'] = json_encode($validatedData['q1'] ?? []);
         $validatedData['q1a'] = json_encode($validatedData['q1a'] ?? []);
         $validatedData['q1b'] = json_encode($validatedData['q1b'] ?? []);
    
        // Store ratings in choice1 - choice12
        $ratings = $validatedData['ratings'] ?? [];
for ($i = 1; $i <= 12; $i++) {
    $key = 'choice' . $i;
    $validatedData[$key] = $ratings[$key] ?? 1;
}
       unset($validatedData['ratings']);
   
       // Insert or update exit interview record
       $exitInterview = ExitInterview::updateOrCreate(
           ['user_id' => $user->id],
           $validatedData
       );
   
       return response()->json([
           'message' => 'Exit interview submitted successfully!',
           'data' => $exitInterview
       ], 201);
   }
}
