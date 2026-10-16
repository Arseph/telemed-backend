<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Facility;
use App\Models\DocCategory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;


class UserController extends Controller
{
    public function users()
    {
        $users = User::with(['facility', 'doccat'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($user) {
            return [
                'id' => $user->id,
                'fname' => $user->fname,
                'mname' => $user->mname,
                'lname' => $user->lname,
                'username' => $user->username,
                'role' => $user->level,        
                'status' => $user->status,
                
                // Return BOTH the id and the display title
                'facility_id' => $user->facility?->id,
                'facility' => $user->facility?->facilityname,
                
                'doc_cat_id' => $user->doccat?->id,
                'doc_category' => $user->doccat?->category_name,
                
                'picture' => $user->picture,
                'email' => $user->email,
                'contact' => $user->contact,
                'designation' => $user->designation,
                'prefix' => $user->prefix,
                'license_no' => $user->license_no,
                'accrediation_no' => $user->accrediation_no,
                'accrediation_validity' => $user->accrediation_validity,
            ];
        });

        $facilities = Facility::all()->map(function($f) {
            return [
                'title' => $f->facilityname,
                'value' => $f->id,
            ];
        });
        $docCategories = DocCategory::all()->map(function($c) {
            return [
                'title' => $c->category_name,
                'value' => $c->id,
            ];
        });
        return response()->json([
            'users' => $users,
            'facilities' => $facilities,
            'doc_categories' => $docCategories,
        ]);
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'facility_id' => 'required|exists:facilities,id',
            'doc_cat_id' => 'required|exists:doctor_categories,id',
            'status' => 'required|string',
            'role' => 'required|string',
            'license_no' => 'nullable|string|max:255',
            'accrediation_no' => 'nullable|string|max:255',
            'accrediation_validity' => 'nullable|date',
            'picture' => 'nullable|image|max:2048',
        ]);

        $user = User::create([
            'prefix' => $request->prefix,
            'title' => $request->title,
            'fname' => $request->fname,
            'mname' => $request->mname,
            'lname' => $request->lname,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'contact' => $request->contact,
            'license_no' => $request->license_no,
            'accrediation_no' => $request->accrediation_no,
            'accrediation_validity' => $request->accrediation_validity,
            'facility_id' => $request->facility_id,
            'doc_cat_id' => $request->doc_cat_id,
            'designation' => $request->designation,
            'status' => $request->status,
            'level' => $request->role,
            'picture' => $this->storePicture($request),
            'eref' => 0,
        ]);
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete(); 
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    public function deactivate($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->status = 'inactive'; 
        $user->save();
        return response()->json([
            'message' => 'User deactivated successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'username' => [
                'required',
                'string',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6', // optional: only update if provided
            'facility_id' => 'required|exists:facilities,id',
            'doc_cat_id' => 'required|exists:doctor_categories,id',
            'status' => 'required|string',
            'role' => 'required|string',

            'license_no' => 'nullable|string|max:255',
            'accrediation_no' => 'nullable|string|max:255',
            'accrediation_validity' => 'nullable|date',
            'picture' => 'nullable|image|max:2048',
        ]);
        if ($request->hasFile('picture')) {
            $user->picture = $this->storePicture($request);
        }
        $user->prefix = $request->prefix;
        $user->title = $request->title;
        $user->fname = $request->fname;
        $user->mname = $request->mname;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->username = $request->username;
        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }
        $user->contact = $request->contact;
        $user->license_no = $request->license_no;
        $user->accrediation_no = $request->accrediation_no;
        $user->accrediation_validity = $request->accrediation_validity;
        $user->facility_id = $request->facility_id;
        $user->doc_cat_id = $request->doc_cat_id;
        $user->designation = $request->designation;
        $user->status = $request->status;
        $user->level = $request->role;

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }

    /**
     * Move an uploaded profile picture into public/images/profilepictures
     * and return its relative path, or null when no file was sent.
     */
    private function storePicture(Request $request)
    {
        if (!$request->hasFile('picture') || !$request->file('picture')->isValid()) {
            return null;
        }

        $destinationPath = public_path('images/profilepictures');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $filename = time() . '_' . uniqid() . '.' . $request->file('picture')->getClientOriginalExtension();
        $request->file('picture')->move($destinationPath, $filename);

        return 'images/profilepictures/' . $filename;
    }
}
