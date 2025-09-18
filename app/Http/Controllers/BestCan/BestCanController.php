<?php

namespace App\Http\Controllers\BestCan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BestCan;
use App\Models\Division;
use App\Models\Section;
use App\Models\EmpMain;
use App\Models\EmpHrInfo;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; 
use Auth;
class BestCanController extends Controller
{
    public function bestcandiv() {
        
        $div = Division::orderBy('office','asc')->get();
        return response()->json([
            'division' => $div,
        ]);
    }
    public function getSectionByDivision($divisionid){
        try {
            $sections = Section::with(['division']) 
                ->where('office', $divisionid)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $sections
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch section',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getUserEmp(Request $req){
        // Get the logged-in user's information
        $user = Auth::user();
        // Assuming emp_type stores the 'is_permanent' value
        $ispermanent = (int) $user->mypds->hrinfo->emp_type ?? null;
        
        $req->validate([
            'divid' => 'nullable|integer|exists:divisions,id',
            'secid' => 'nullable|integer|exists:sections,id',
        ]);
        try {
            // Query for employees based on filters
            $data = EmpMain::with(['hrinfo', 'position', 'section', 'division'])
            ->when($req->divid, function ($query, $divid) {
                $query->whereHas('hrinfo', function ($q) use ($divid) {
                    $q->where('division_id', $divid);
                });
            })
            ->when($req->secid, function ($query, $secid) {
                $query->whereHas('hrinfo', function ($q) use ($secid) {
                    $q->where('section_id', $secid);
                });
            })
            ->when($ispermanent === 0, function ($query) {
               
                $query->whereHas('position', function ($q) {
                    $q->where('is_permanent', 1);
                });
            })
            ->when($ispermanent === 1, function ($query) {
                $query->whereHas('position', function ($q) {
                    $q->where('is_permanent', 0);
                });
            })
            ->orderBy('first_name')
            ->paginate(1000);
            // Add full name to each employee for easier display
            $data->getCollection()->transform(function ($item) {
                $item->full_name = "{$item->first_name} {$item->sur_name}";
                return $item;
            });
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getPosition($emp_id){
        try {
             // Validate that the employee exists
             if (!EmpHrInfo::where('emp_id', $emp_id)->exists()) {
                return response()->json(['error' => 'Employee not found'], 404);
             }
             // Find the employee and load their position
             $employee = EmpMain::with('position')
                ->where('id', $emp_id)
                ->first();
    
            if (!$employee) {
                return response()->json(['message' => 'Employee not found'], 404);
            }
    
            return response()->json([
                'success' => true,
                'employee' => $employee,
                'position' => $employee->position,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
    }
    public function aeBestCan(Request $req) {
        // Validate required fields
        if (!isset($req->nominee_id)) {
            return response()->json(['error' => 'Nominee ID is missing'], 400);
        }
    
        // Determine the current quarter
        $first_quarter = Carbon::now()->startOfYear();
        $quarters = [
            '1' => [$first_quarter, $first_quarter->copy()->addMonths(3)],
            '2' => [$first_quarter->copy()->addMonths(3), $first_quarter->copy()->addMonths(6)],
            '3' => [$first_quarter->copy()->addMonths(6), $first_quarter->copy()->addMonths(9)],
            '4' => [$first_quarter->copy()->addMonths(9), Carbon::now()->endOfYear()],
        ];
    
        $currentQuarter = array_filter($quarters, fn($q) => Carbon::now()->between($q[0], $q[1]));
        $quarter = key($currentQuarter) ?? '4';
    
        // Get the authenticated user's ID
        $uid = Auth::user()->mypds->id;
    
        // Prepare the data for saving or updating
        $data = $req->only([
            'sec_id', 'div_id', 'nominee_id', 'q1', 'q2', 'q3', 'q4',
            'q5', 'q6', 'q7', 'q8', 'q9', 'q10', 'q11', 'q12',
            'q13', 'q14', 'add_good'
        ]);
    
        // Generate additional fields
        $data['ctrlno'] = $req->best_id ? BestCan::find($req->best_id)->ctrlno : Str::limit('NF' . now()->format('mdyHis') . $req->nominee_id . $uid, 20, '');
        $data['emp_id'] = $uid;
        $data['year'] = date('Y');
        $data['quarter'] = $quarter;
    
        // Save or update the record
        if ($req->best_id) {
            $best = BestCan::find($req->best_id);
            $best->update($data);
        } else {
            BestCan::create($data);
        }
    
        return response()->json(['message' => 'Nomination successfully saved or updated.'], 200);
    }
    public function getBestcan($id,Request $req) {

        return ($req);
        $bestemp = BestCan::find($id);
        if($bestemp) {
            $bestemp->sec;
            $bestemp->div;
            $bestemp->employee->hrinfo->pos;
            return response()->json($bestemp);
        }
        return response()->json(['error' => 'Not Found'], 404);
    }
    public function fetchNominees(Request $req) {
        $uid = Auth::user()->mypds ? Auth::user()->mypds->id : '';
    
        $bestcan = BestCan::with(['employee' => function ($query) {
            $query->select('id', 'sur_name', 'first_name', 'middle_name');
        }]);
    
        if ($uid) {
            $bestcan->where('emp_id', $uid);
        }
        if ($req->quarter && $req->quarter != '0') {
            $bestcan->where('quarter', $req->quarter);
        }
        if ($req->year) {
            $bestcan->where('year', $req->year);
        }
    
        $bestcan = $bestcan->orderBy('quarter', 'asc')->get();
    
        // Append the nominee's full name
        $bestcan->each(function ($item) {
            $item->nominee_full_name = $item->employee 
                ? $item->employee->sur_name . ', ' . $item->employee->first_name . ' ' . $item->employee->middle_name 
                : null;

                $item->formatted_quarter = $item->quarts(); 
                $item->formatted_date = Carbon::parse($item->created_at)->format('M d, Y');
        });
    
        return response()->json(['best' => $bestcan]);
    }
    public function getNominee(Request $req) {
        $user = Auth::user()->mypds;
        $first_quarter = Carbon::now()->startOfYear();
        $second_quarter = Carbon::now()->startOfYear()->addMonth(3);
        $third_quarter = Carbon::now()->startOfYear()->addMonth(6);
        $fourth_quarter = Carbon::now()->startOfYear()->addMonth(9);
        $end_year = Carbon::now()->endOfYear();
        $check1q = Carbon::now()->between($first_quarter,$second_quarter);
        $check2q = Carbon::now()->between($second_quarter,$third_quarter);
        $check3q = Carbon::now()->between($third_quarter,$fourth_quarter);
        $check4q = Carbon::now()->between($fourth_quarter,$end_year);
        $quarter = '';
        if($check1q) {
            $quarter = '1';
        } else if($check2q) {
            $quarter = '2';
        } else if($check3q) {
            $quarter = '3';
        } else if($check4q) {
            $quarter = '4';
        }
        $bestemp = BestCan::where('nominee_id',$req->id)
        ->where('emp_id',$user->id)
        ->where('year',date('Y'))
        ->where('quarter',$quarter)
        ->first();
        $emp = EmpMain::find($req->id);
        if($bestemp) {
            $bestemp->sec;
            $bestemp->div;
            $bestemp->employee->hrinfo->pos->position;
            return response()->json($bestemp);
        } else {
            $position = $emp->hrinfo ? ($emp->hrinfo->pos ? $emp->hrinfo->pos->position : 'N/A') : 'N/A';
            return $position;
        }
    }
    
    
    
    
    

    
}


