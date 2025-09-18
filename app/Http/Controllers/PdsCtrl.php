<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\EmpMain;
use App\Models\EmpAddress;
use App\Models\EmpChild;
use App\Models\EmpEducation;
use App\Models\EmpEligibility;
use App\Models\EmpWorkEx;
use App\Models\EmpVolWork;
use App\Models\EmpSkill;
use App\Models\EmpReference;
use App\Models\EmpGovId;
use App\Models\EmpTrainAttend;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\EmpBarcode;
use Illuminate\Support\Str;
use App\Models\HighEduc;
class PdsCtrl extends Controller
{
    public function basic() {
        $user = Auth::user()->load('info');
        return response()->json($user);
    }
    public function basicStore(Request $req) {
        $id = Auth::user()->info->id;
        $data = array(
            'sur_name' => $req->sur_name,
            'title_name' => $req->title_name,
            'suffix_name' => $req->suffix_name,
            'first_name' => $req->first_name,
            'middle_name' => $req->middle_name,
            'date_birth' =>  $req->date_birth,
            'place_birth' => $req->place_birth,
            'nick_name' => $req->nick_name,
            'sex' => $req->sex['value'] ?? $req->sex,
            'civil_status' => $req->civil_status['value'] ?? $req->civil_status,
            'citizenship' => $req->citizenship,
            'citizenship2' => $req->citizenship2,
            'dualcit_type' => $req->dualcit_type,
            'country_cit' => $req->country_cit,
            'height' => $req->height,
            'weight' => $req->weight,
            'blood_type' => $req->blood_type['value'] ?? $req->blood_type,
            'emergency_name' => $req->emergency_name,
            'emergency_address' => $req->emergency_address,
            'emergency_contact' => $req->emergency_contact,
        );
        if($id) {
            $emp = EmpMain::find($id);
            if($emp) {
                $emp->update($data);
                return response()->json(['message' => 'Basic information saved successfully!']);
            }
        }
        return response()->json(['message' => 'Something went wrong.'], 400);
    }
    public function address() {
        $user = Auth::user()->info?->address;
        return response()->json($user);
    }
    public function addressStore(Request $req) {
    // Validate the incoming request data
    $validatedData = $req->validate([
        'id' => 'nullable|integer|exists:hr_address,id',
        'r_house' => 'required|string|max:255',
        'r_street' => 'required|string|max:255',
        'r_village' => 'nullable|string|max:255',
        'r_brgy' => 'required|string|max:255',
        'r_city' => 'required|string|max:255',
        'r_province' => 'required|string|max:255',
        'r_zip' => 'required|string|max:10',
        'is_same' => 'required|boolean',
        'p_house' => 'nullable|string|max:255',
        'p_street' => 'nullable|string|max:255',
        'p_village' => 'nullable|string|max:255',
        'p_brgy' => 'nullable|string|max:255',
        'p_city' => 'nullable|string|max:255',
        'p_province' => 'nullable|string|max:255',
        'p_zip' => 'nullable|string|max:10',
    ]);

    // Get the current authenticated user's ID
    $employeeId = Auth::user()->info->id;

    // Prepare the data for insertion or update
    $data = array_merge($validatedData, [
        'emp_id' => $employeeId,
    ]);

    // Find the existing address by ID if provided, otherwise create a new one
    $address = EmpAddress::find($req->id);
    $my = EmpMain::find($employeeId);
    if ($address) {
        $address->update($data);
        return response()->json(['message' => 'Address information updated successfully!']);
    } else {
        $s = EmpAddress::create($data);
        $my->update(
            array('address_id' => $s->id,)
        );
        return response()->json(['message' => 'Address information saved successfully!']);
    }

    return response()->json(['message' => 'Something went wrong.'], 400);
    }
    public function identification() {
        $user = Auth::user()->info()->select('id', 'gsis_id', 'pagibig_id', 'philhealth_id', 'sss_id', 'tin', 'tel_no', 'cell_no', 'email_add', 'agency_employeeno')->first();

        return response()->json($user);
    }
    public function identificationStore(Request $req) {
        $id = Auth::user()->info->id;
        $data = array(
    		'gsis_id' => $req->gsis_id,
			'pagibig_id' => $req->pagibig_id,
			'philhealth_id' => $req->philhealth_id,
			'sss_id' => $req->sss_id,
			'tin' => $req->tin,
    		'tel_no' => $req->tel_no,
    		'cell_no' => $req->cell_no,
    		'email_add' => $req->email_add,
            'agency_employeeno' => $req->agency_employeeno,
    	);
        $my = EmpMain::find($id);
        if($my) {
            $my->update($data);
            return response()->json(['message' => 'Identification information saved successfully!']);
        }
        return response()->json(['message' => 'Something went wrong.'], 400);
    }
    public function family() {
        $user = Auth::user()->load('info','info.child');
        return response()->json($user);
    }
    public function familyStore(Request $req) {
        $id = Auth::user()->info->id;
        $data = array(
    		'Spouse_Surname' => $req->Spouse_Surname,
    		'Spouse_firstname' => $req->Spouse_firstname,
    		'Spouse_middle' => $req->Spouse_middle,
    		'spouse_occupation' => $req->spouse_occupation,
    		'spouse_employ_biz' => $req->spouse_employ_biz,
    		'spouse_employ_biz_address' => $req->spouse_employ_biz_address,
    		'spouse_telno' => $req->spouse_telno,
    		'fathers_suffix' => $req->fathers_suffix,
            'fathers_surname' => $req->fathers_surname,
    		'fathers_firstname' => $req->fathers_firstname,
    		'fathers_middlename' => $req->fathers_middlename,
            'mothers_maiden' => $req->mothers_maiden,
    		'mothers_surname' => $req->mothers_surname,
    		'mothers_firstname' => $req->mothers_firstname,
    		'mothers_middlename' => $req->mothers_middlename,
    	);
        $name = collect($req->child)->pluck('name')->implode('|');
        $bdate = collect($req->child)->pluck('bdate')->implode('|');
        $ch = array(
    		'emp_id' => $id,
    		'name' => $name,
    		'bdate' => $bdate,
    	);
        $emp = EmpChild::where('emp_id', $id)->first();
        $my = EmpMain::find($id);
        if($my) {
            $my->update($data);
        }
        if($emp) {
            $emp->update($ch);
        } else {
            if($name && $bdate) {
                $s = EmpChild::create($ch);
                $my->update(
                    array('child_id' => $s->id,)
                );
            }
        }
    }
    public function education(Request $req) {
        $educ = Auth::user()->info->educ;
        $educadd = Auth::user()->info->educadd;
        $higheduc = HighEduc::all();
        return response()->json([
            'educ' => $educ,
            'educadd' => $educadd,
            'higheduc' => $higheduc,
        ]);
    }
    public function educationStore(Request $req) {
        $id = Auth::user()->info->id;
        if($req->school_name){
        	foreach ($req->school_name as $key => $val) {
        		$lvl = (int)$key + (int)'1';
        		$data = array(
        			'id_main' => $id,
        			'level' => $lvl,
        			'school_name' => $req->school_name[$key],
        			'course' => $req->course[$key],
        			'year_graduated' => $req->year_graduated[$key],
        			'highest_level' => $req->highest_level[$key],
        			'from_date' => $req->from_date[$key],
        			'to_date' => $req->to_date[$key],
        			'scholarship_honors' => $req->scholarship_honors[$key],
        		);
        		if(!$id) {
        		return 'error';
    	    	} else {
    	    		$emp = EmpEducation::where('level', $lvl)->where('id_main', $id)->where('is_add', null)->first();
    	    		if($emp) {
    	    			$emp->update($data);
    	    		} else {
    	    			$s = EmpEducation::create($data);
    	    		}
    	    	}
        	}
        }
        if(count($req->additional) > 0) {

            foreach ($req->additional as $key => $val) {
                $data = array(
        			'id_main' => $id,
        			'level' => $val['level'],
                    'is_add'=> 1,
        			'school_name' => $val['school_name'],
        			'course' => $val['course'],
        			'year_graduated' => $val['year_graduated'],
        			'highest_level' => $val['highest_level'],
        			'from_date' => $val['from_date'],
        			'to_date' => $val['to_date'],
        			'scholarship_honors' => $val['scholarship_honors'],
        		);
                if (!isset($val['id']) || $val['id'] == 0) {
                    EmpEducation::create($data);
                } else {
                    EmpEducation::where('id', $val['id'])->update($data);
                }
            }
        }
    }
    public function educationDelete($id) {
        $user = EmpEducation::find($id);
        if($user) {
            $user->delete();
        }
    }
    public function eligibility(Request $req) {
        $educ = Auth::user()->info->cs_eligible;
        return response()->json($educ);
    }
    public function eligibilityStore(Request $req) {
        $id = Auth::user()->info->id;
        foreach ($req->career_service as $key => $val) {
            $data = array(
                'id_main' => $id,
                'career_service' => $req->career_service ? $req->career_service[$key] : '',
                'rating' => $req->rating ? $req->rating[$key] : '',
                'date' => $req->date ? $req->date[$key] : '',
                'place_exam' => $req->place_exam ? $req->place_exam[$key] : '',
                'license_no' => $req->license_no ? $req->license_no[$key] : '',
                'date_released' => $req->date_released ? $req->date_released[$key] : '',
            );
            if(!$id) {
            return 'error';
            } else {
                $emp = EmpEligibility::find($req->id[$key] ?? 0);
                if($emp) {
                    $emp->update($data);
                } else {
                    $emp = EmpEligibility::create($data);
                }
            }
        }
    }
    public function eligibilityDelete($id) {
        $user = EmpEligibility::find($id);
        if($user) {
            $user->delete();
        }
    }
    public function work(Request $req) {
        $educ = Auth::user()->info->workex;
        return response()->json($educ);
    }
    public function workStore(Request $req) {
        $id = Auth::user()->info->id;
        if($req->position) {
        	foreach ($req->position as $key => $val) {
        		$data = array(
        			'id_main' => $id,
                    'is_present' => $req->is_present[$key],
        			'date_from' => Carbon::createFromFormat('F j, Y',$req->date_from[$key])->format('Y-m-d'),
        			'date_to' => Carbon::createFromFormat('F j, Y',$req->date_to[$key])->format('Y-m-d'),
        			'position' => $req->position[$key],
        			'company' => $req->company[$key],
        			'monthly_sal' => $req->monthly_sal[$key],
        			'salary_grade' => $req->salary_grade[$key],
        			'status' => $req->status[$key],
        			'govt' => $req->govt[$key],
        		);
        		if(!$id) {
        		return 'error';
    	    	} else {
    	    		$emp = EmpWorkEx::find($req->id[$key] ?? 0);
    	    		if($emp) {
    	    			$emp->update($data);
    	    		} else {
    	    			$emp = EmpWorkEx::create($data);
    	    		}
    	    	}
        	}
        }
    }
    public function workDelete($id) {
        $user = EmpWorkEx::find($id);
        if($user) {
            $user->delete();
        }
    }
    public function voluntary(Request $req) {
        $vol = Auth::user()->info->volwork;
        return response()->json($vol);
    }
    public function voluntaryStore(Request $req) {
        $id = Auth::user()->info->id;
        if($req->name) {
        	foreach ($req->name as $key => $val) {
        		$data = array(
        			'id_main' => $id,
                    'name' => $req->name[$key],
        			'date_from' => Carbon::createFromFormat('F j, Y',$req->date_from[$key])->format('Y-m-d'),
        			'date_to' => Carbon::createFromFormat('F j, Y',$req->date_to[$key])->format('Y-m-d'),
        			'hours' => $req->hours[$key],
        			'nature_work' => $req->nature_work[$key],
        		);
        		if(!$id) {
        		return 'error';
    	    	} else {
    	    		$emp = EmpVolWork::find($req->id[$key] ?? 0);
    	    		if($emp) {
    	    			$emp->update($data);
    	    		} else {
    	    			$emp = EmpVolWork::create($data);
    	    		}
    	    	}
        	}
        }
    }
    public function voluntaryDelete($id) {
        $user = EmpVolWork::find($id);
        if($user) {
            $user->delete();
        }
    }
    public function training() {
        $vol = Auth::user()->info->trainatt;
        return response()->json($vol);
    }
    public function trainingStore(Request $req) {
        $id = Auth::user()->info->id;
        if($req->seminar_title) {
        	foreach ($req->seminar_title as $key => $val) {
        		$data = array(
        			'id_main' => $id,
        			'seminar_title' => $req->seminar_title[$key],
                    'datefrom' => Carbon::createFromFormat('F j, Y',$req->datefrom[$key])->format('Y-m-d'),
        			'date_end' => Carbon::createFromFormat('F j, Y',$req->date_end[$key])->format('Y-m-d'),
        			'hours' => $req->hours[$key],
        			'ld_type' => $req->ld_type[$key],
        			'conducted' => $req->conducted[$key],
        		);
        		if(!$id) {
        		return 'error';
    	    	} else {
    	    		$emp = EmpTrainAttend::find($req->id[$key] ?? 0);
    	    		if($emp) {
    	    			$emp->update($data);
    	    		} else {
    	    			$emp = EmpTrainAttend::create($data);
    	    		}
    	    	}
        	}
        }
    }
    public function trainingDelete($id) {
        $user = EmpTrainAttend::find($id);
        if($user) {
            $user->delete();
        }
    }
    public function other() {
        $user = Auth::user()->load('info','info.skills','info.refs','info.govid');
        return response()->json($user);
    }
    public function otherStore(Request $req) {
        $id = Auth::user()->info->id;
        $data = array(
            "q34a" => $req->q34a,
            "q34b" => $req->q34b,
            "q34abd" => $req->q34abd,
            "q35a" => $req->q35a,
            "q35ad" => $req->q35ad,
            "q35b" => $req->q35b,
            "q35bd" => $req->q35bd,
            "q35bstatus" => $req->q35bstatus,
            "q36a" => $req->q36a,
            "q36ad" => $req->q36ad,
            "q37a" => $req->q37a,
            "q37ad" => $req->q37ad,
            "q38a" => $req->q38a,
            "q38ad" => $req->q38ad,
            "q38b" => $req->q38b,
            "q38bd" => $req->q38bd,
            "q39" => $req->q39,
            "q39d" => $req->q39d,
            "q40a" => $req->q40a,
            "q40ad" => $req->q40ad,
            "q40b" => $req->q40b,
            "q40bd" => $req->q40bd,
            "q40c" => $req->q40c,
            "q40cd" => $req->q40cd,
          );
          if($req->skill){
              foreach ($req->skill as $key => $value) {
                  $sk = array(
                      'emp_id' => $id,
                      'skill' => $req->skill[$key]['skill'],
                      'non_acad' => $req->skill[$key]['non_acad'],
                      'member_org' => $req->skill[$key]['member_org'],
                  );
                  if(!$id) {
                  return 'error';
                  } else {
                      $emp = EmpSkill::find($req->skill[$key]['id'] ?? 0);
                      if($emp) {
                          $emp->update($sk);
                      } else {
                          $skillsinfo = EmpSkill::create($sk);
                      }
                  }
              }
          }
          $name = collect($req->name)->implode('|');
          $address = collect($req->address)->implode('|');
          $tel_no = collect($req->tel_no)->implode('|');
          $ref = array(
              'emp_id' => $id,
              'name' => $name,
              'address' => $address,
              'tel_no' => $tel_no,
          );
          $gid = array(
              'emp_id' => $id,
              'gov_issue' => $req->govid['gov_issue'],
              'id_no' => $req->govid['id_no'],
              'date' => $req->govid['date'],
          );
          if(!$id) {
              return 'error';
          } else {
              $reference = EmpReference::where('emp_id', $id)->first();
              if($reference) {
                  $reference->update($ref);
              } else {
                  EmpReference::create($ref);
              }
              $gov = EmpGovId::where('id',$req->govid['id'])->orWhere('emp_id', $id)->first();
              if($gov) {
                  $gov->update($gid);
              } else {
                  EmpGovId::create($gid);
              }
              $my = EmpMain::find($id);
              if($my) {
                  $my->update($data);
              }
          }
    }
    public function generatePDS(Request $req) {
        $id = base64_decode($req->id);
        $code = Str::limit('PHRIS'.$id.date('mdYHis'),20,'');
        $barcode = EmpBarcode::where('emp_id', $id)->first();
        $emp = EmpMain::find(base64_decode($req->id));
        if(!$barcode) {
            $data = array(
                'emp_id' => $id,
                'barcode' => $code,
            );
            $barcode = EmpBarcode::create($data);
        }
        $mybarcode = $barcode->barcode;
        return view('pdf.pds.pds', [
            'emp' => $emp,
            'barcode' => $mybarcode,
        ]);
    }
    public function generatePDS2(Request $req) {
        $emp = EmpMain::find(base64_decode($req->id));
        $pdf = PDF::loadView('pdf.pds.separate', [
            'emp' => $emp
        ])->setPaper([0, 0, 612, 936], 'portrait');
        return $pdf->download('pds.pdf', ['Content-Type' => 'application/pdf']);
    }
    public function generatePDS3(Request $req) {
        $emp = EmpMain::find(base64_decode($req->id));
        $pdf = PDF::loadView('pdf.pds.page3', [
            'emp' => $emp
        ])->setPaper([0, 0, 612, 936], 'portrait');
        return $pdf->download('pds.pdf', ['Content-Type' => 'application/pdf']);
    }
    public function generatePDS4(Request $req) {
        $emp = EmpMain::find(base64_decode($req->id));
        return view('pdf.pds.pds4', compact('emp'));
    }
}
