<?php

namespace App\Http\Controllers\Event;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\CsPerson;
use App\Models\Evaluation;
use App\Models\EvaluationResource;
use App\Models\EmpMain;
use Illuminate\Support\Facades\Http;
class EventCtrl extends Controller
{
    public function index() {
        $event = Evaluation::where('emp_id',Auth::user()->emp_id)->orderBy('created_at','desc')->get();
        $inc = CsPerson::where('emp_id', Auth::user()->emp_id)->get();
        foreach ($inc as $csPerson) {
            $createdBy = $csPerson->event->created_by->user;
            if ($createdBy && $createdBy->profile) {
                $base64ProfileImage = base64_encode($createdBy->profile->data);
                $createdBy->profile->data = 'data:image/jpeg;base64,' . $base64ProfileImage;
            }
        }
        $emp = EmpMain::select(
            'hris_main.*',
            'hr_infos.emp_status as emp_status',
            'hr_infos.division_id as division_id',
            'hr_infos.emp_type as emp_type',
            'hr_infos.section_id as section_id',
            'hr_infos.position_id as position_id',
            'positions.position as Pos',
            'divisions.office as off',
            'sections.office as sec',
        )->leftJoin('hr_infos as hr_infos', 'hris_main.id','=','hr_infos.emp_id')
        ->leftJoin('positions as positions','hr_infos.position_id','=','positions.id')
        ->leftJoin('sections as sections','hr_infos.section_id','=','sections.id')
        ->leftJoin('divisions as divisions','hr_infos.division_id','=','divisions.id')
        ->where('hr_infos.emp_status', 1)->orderBy('hris_main.sur_name', 'asc')->get();
        return response()->json([
            'event' => $event,
            'employees' => $emp,
            'inc' => $inc,
        ]);
    }
    public function store(Request $req) {
        try {
            $forms = implode('|', $req->forms);
            $data = array(
                'emp_id' => Auth::user()->info->id, 
                'course_title' => $req->course_title,
                'venue' => $req->venue,
                'date_conduct' => $req->date_conduct,
                'forms' => $forms,
            );
            if($req->event_id) {
                $event = Evaluation::find($req->event_id)->update($data);
            } else {
                $event = Evaluation::create($data);
            }
            if($req->rs != null) {
                foreach ($req->rs as $key => $val) {
                    $rn = array(
                        'evaluation_id' => $event->id,
                        'resource_person' => $val['resource_person'],
                    );
                    EvaluationResource::create($rn);
                }
            }
            if($req->pi != null) {
                foreach ($req->pi as $key => $val) {
                    $pi = array(
                        'evaluation_id' => $event->id,
                        'emp_id' => $val['person_involved'],
                        'is_resources' => $val['is_resources'],
                    );
                    CsPerson::create($pi);
                }
            }
            return response()->json(['message' => 'Events/Activities saved successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong.'], 400);
        }
    }
    public function generateEventQr(Request $req, $id) {
        $event = Evaluation::find($id);
        if($event) {
            $url = 'https://hrmis.dohsox.com';
            $forms = explode('|',$event->forms);
            $l1qrCode = '';
            $l1 = '';
            $l2qrCode = '';
            $l2 = '';
            $l3qrCode = '';
            $l3 = '';
            $l4qrCode = '';
            $l4 = '';
            foreach ($forms as $key => $value) {
                if($value == 1) {
                    $l1 = rtrim($url, '/')."/e/".base64_encode($event->id);
                    $l1qr = QrCode::format('png')
                        ->size(250)
                        ->generate(json_encode($l1,JSON_UNESCAPED_SLASHES));
                    $l1qrCode = base64_encode($l1qr);
                } if($value == 2) {
                    $l2 = rtrim($url, '/')."/cf2/".base64_encode($event->id);
                    $l2qr = QrCode::format('png')
                        ->size(250)
                        ->generate(json_encode($l2,JSON_UNESCAPED_SLASHES));
                    $l2qrCode = base64_encode($l2qr);
                } if($value == 3) {
                    $l3 = rtrim($url, '/')."/cf3/".base64_encode($event->id);
                    $l3qr = QrCode::format('png')
                        ->size(250)
                        ->generate(json_encode($l3,JSON_UNESCAPED_SLASHES));
                    $l3qrCode = base64_encode($l3qr);
                } if($value == 4) {
                    $l4 = rtrim($url, '/')."/csm/".base64_encode($event->id);
                    $l4qr = QrCode::format('png')
                        ->size(250)
                        ->generate(json_encode($l4,JSON_UNESCAPED_SLASHES));
                    $l4qrCode = base64_encode($l4qr);
                }
            }
            return response()->json([
                'l1qrCode' => 'data:image/png;base64,' . $l1qrCode,
                'l1' => $l1,
                'l2qrCode' => 'data:image/png;base64,' . $l2qrCode,
                'l2' => $l2,
                'l3qrCode' => 'data:image/png;base64,' . $l3qrCode,
                'l3' => $l3,
                'l4qrCode' => 'data:image/png;base64,' . $l4qrCode,
                'l4' => $l4,
            ]);
        } else {
            return response()->json(['message' => 'Something went wrong.'], 400);
        }
    }

    public function viewEventDetails($id) {
        $event = Evaluation::find($id);
        if($event) {
            $evaluation = [];
            $evpartfe = ['Female',$event->evals->where('sex','0')->count()];
            $evpartma = ['Male',$event->evals->where('sex','1')->count()];
            array_push($evaluation, $evpartma);
            array_push($evaluation, $evpartfe);
            $css = [];
            $cssfe = ['Female',$event->cssform->where('sex','0')->count()];
            $cssma = ['Male',$event->cssform->where('sex','1')->count()];
            array_push($css, $cssfe);
            array_push($css, $cssma);
            $csm = [];
            $csmfe = ['Female', $event->csmform->where('sex', '0')->count()];
            $csmma = ['Male', $event->csmform->where('sex', '1')->count()];
            array_push($csm, $csmfe);
            array_push($csm, $csmma);
            $evadoh = [];
            $evdoh = ['DOH Employees',$event->evals->where('ex_in','1')->count()];
            $evNonDoh = ['Non DOH Employees',$event->evals->where('ex_in','0')->count()];
            array_push($evadoh, $evdoh);
            array_push($evadoh, $evNonDoh);
            $cssdoh = [];
            $cssedoh = ['DOH Employees',$event->cssform->where('doh_emp','1')->count()];
            $cssNonDoh = ['Non DOH Employees',$event->cssform->where('doh_emp','0')->count()];
            array_push($cssdoh, $cssedoh);
            array_push($cssdoh, $cssNonDoh);
            $csmAge = [
                ['18-29', $event->csmform->whereBetween('age', [18, 29])->count()],
                ['30-39', $event->csmform->whereBetween('age', [30, 39])->count()],
                ['40-49', $event->csmform->whereBetween('age', [40, 49])->count()],
                ['50-60', $event->csmform->whereBetween('age', [50, 60])->count()],
                ['60+', $event->csmform->where('age', '>', 60)->count()],
            ];
            $customerType = [
                ['Citizen', $event->csmform->where('cus_type', '0')->count()], 
                ['Business', $event->csmform->where('cus_type', '1')->count()], 
                ['Government', $event->csmform->where('cus_type', '2')->count()],
            ];
            $css3 = [];
            $css3fe = ['Female', $event->cssform3->where('sex', '0')->count()];
            $css3ma = ['Male', $event->cssform3->where('sex', '1')->count()];
            array_push($css3, $css3fe);
            array_push($css3, $css3ma);
            $css3doh = [];
            $css3dohEmp = ['DOH Employees', $event->cssform3->where('is_doh_emp', '1')->count()]; 
            $css3NonDoh = ['Non DOH Employees', $event->cssform3->where('is_doh_emp', '0')->count()];
            array_push($css3doh, $css3dohEmp);
            array_push($css3doh, $css3NonDoh);
            return response()->json([
               'event' => $event,
                'evaluation' => $evaluation,
                'css' => $css,
                'csm' => $csm,   
                'css3' => $css3,
                'evadoh' => $evadoh,
                'cssdoh' => $cssdoh,
                'css3doh' => $css3doh,
                'csmAge' => $csmAge,
                'customerType' => $customerType 
            ]);
        }
    }
    public function exportEval(Request $req, $id) {
        $event = Evaluation::find($id);
        $dates = explode(' - ', $event->date_conduct);
        $dateOutput = '';
        if (count($dates) == 2) {
            $dateStart = strtotime($dates[0]);
            $dateEnd = strtotime($dates[1]);
            if ($dateStart === $dateEnd) {
                $dateOutput = date('F j, Y', $dateStart);
            } elseif (date('Y-m', $dateStart) == date('Y-m', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('j, Y', $dateEnd);
            } elseif (date('Y', $dateStart) == date('Y', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            } else {
                $dateOutput = date('F j, Y', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            }
        }

        if ($event && ($event->resource_speaker->isNotEmpty() || $event->resource_persons->isNotEmpty())) {
                $participants = [];
                foreach ($event->resource_speaker as $resourceSpeaker) {
                    $participants[] = (object)[
                        'name' => $resourceSpeaker->resource_person ?? 'Unknown Speaker',
                        'type' => 'speaker',
                        'scores' => $resourceSpeaker->scores
                    ];
                }

                foreach ($event->resource_persons as $resourcePerson) {
                    $employee = $resourcePerson->employee;
                    $participants[] = (object)[
                        'name' => ($employee ? $employee->first_name . ' ' . $employee->sur_name : 'Unknown Person'),
                        'type' => 'person',
                        'scores' => $resourcePerson->scores
                    ];
                }

                $headers = [
                    "Content-Type" => "application/vnd.ms-excel", 
                    "Content-Disposition" => "inline; filename=Summary_EvaluationForm.xls", 
                    "Pragma" => "no-cache",
                    "Expires" => "0",
                ]; 
                
                $output = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
                $output .= '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
                $output .= '<body>';

                $output .= '<h2>Summary Report of Participant Responses</h2>';
                $output .= '<h3>This report summarizes the responses collected from the evaluation survey during the ' . $event->course_title . ' held on ' . $dateOutput . ' at ' . $event->venue . '.</h3>';
                $output .= '<table border="1">';

                $output .= '
                    <tr style="font-size: 16px; background-color: #00B050;">
                        <th colspan="6" rowspan="2">Statement</th>
                        <th colspan="2">1</th>
                        <th colspan="2">2</th>
                        <th colspan="2">3</th>
                        <th colspan="2">4</th>
                    </tr>
                    <tr style="font-size: 16px; background-color: #00B050;">
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                    </tr>';


                //1. TRAINING/WORKSHOP OBJECTIVES    
                $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                <td colspan="14"><strong>1. TRAINING/WORKSHOP OBJECTIVES</strong></td>
                            </tr>';
                $objectives = [
                    'a' => 'The objectives were clearly stated',
                    'b' => 'The objectives were met',
                    'c' => 'The training/workshop is relevant to my line of work'
                ];
                foreach ($objectives as $key => $question) {
                    $output .= '<tr style="font-size: 16px;">
                                    <td colspan="6">' . $key . '. ' . $question . '</td>';
                    foreach ([1, 2, 3, 4] as $response) {
                        $totalParticipants = $event->evals->count();
                        $scoreCount = $event->evals->where("q1$key", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
                        $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';
                }

                //2. TOPICS
                $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                <td colspan="14"><strong>2. TOPICS</strong></td>
                            </tr>';
                $objectives = [
                    'a' => 'The topics presented were relevant to the stated objectives',
                    'b' => 'The topics were discussed clearly',
                ];
                foreach ($objectives as $key => $question) {
                    $output .= '<tr style="font-size: 16px;">
                                    <td colspan="6">' . $key . '. ' . $question . '</td>';
                    foreach ([1, 2, 3, 4] as $response) {
                        $totalParticipants = $event->evals->count();
                        $scoreCount = $event->evals->where("q2$key", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';

                        $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';
                }

                //3. METHODOLOGY
                $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                <td colspan="14"><strong>3. METHODOLOGY</strong></td>
                            </tr>';
                $objectives = [
                    'a' => 'The strategies or methods used were appropriate to achieve desired outputs',
                    'b' => 'The strategies or methods used provided for optimum interaction between and among the Resource Person and participants',
                    'c' => 'The course dynamics were conducive to optimum learning',
                ];
                foreach ($objectives as $key => $question) {
                    $output .= '<tr style="font-size: 16px;">
                                    <td colspan="6">' . $key . '. ' . $question . '</td>';
                    foreach ([1, 2, 3, 4] as $response) {
                        $totalParticipants = $event->evals->count();
                        $scoreCount = $event->evals->where("q3$key", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';

                        $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';
                }

                //4. PRESENTATION AND VISUAL AIDS
                $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                <td colspan="14"><strong>4. PRESENTATION AND VISUAL AIDS</strong></td>
                            </tr>';
                $objectives = [
                    'a' => 'The presentations were clear and concise',
                    'b' => 'The visual aids and/or instructional materials are adequate and suitable to facilitate learning',
                ];
                foreach ($objectives as $key => $question) {
                    $output .= '<tr style="font-size: 16px;">
                                    <td colspan="6">' . $key . '. ' . $question . '</td>';
                    foreach ([1, 2, 3, 4] as $response) {
                        $totalParticipants = $event->evals->count();
                        $scoreCount = $event->evals->where("q4$key", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';

                        $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';
                }

                //5. TIME
                $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                <td colspan="14"><strong>5. TIME</strong></td>
                            </tr>';
                $objectives = [
                    'a' => 'Training/workshop starts and ends on the agreed time',
                    'b' => 'Time allotted was sufficient to cover all activities',
                ];
                foreach ($objectives as $key => $question) {
                    $output .= '<tr style="font-size: 16px;">
                                    <td colspan="6">' . $key . '. ' . $question . '</td>';
                    foreach ([1, 2, 3, 4] as $response) {
                        $totalParticipants = $event->evals->count();
                        $scoreCount = $event->evals->where("q5$key", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';

                        $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';
                }


                //6. Resource Speakers/Facilitators 
                foreach ($participants as $key => $participant) {
                    $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                    <td colspan="14"><strong>6.' . ($key + 1) . ' RESOURCE SPEAKERS/FACILITATORS: ' . $participant->name . '</strong></td>
                                </tr>';

                    foreach (['a' => 'She/he has demonstrated thorough knowledge of the subject matter', 
                              'b' => 'She/he adequately responded to participants\' questions', 
                              'c' => 'She/he elicited the active participation of everyone'] as $qKey => $qText) {

                        $output .= '<tr style="font-size: 16px;">
                                        <td colspan="6">' . $qKey . '. ' . $qText . '</td>';
                        foreach ([1, 2, 3, 4] as $response) {
                            $totalParticipants = $event->evals->count();
                            $scoreCount = $participant->scores->where("q6$qKey", $response)->count();
                            $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';

                            $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                        }
                        $output .= '</tr>';
                    }
                }

                //7. SECRETARIAT
                $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                <td colspan="14"><strong>7. SECRETARIAT</strong></td>
                            </tr>';
                $objectives = [
                    'a' => 'They were approachable and promptly attended to concerns and queries',
                ];
                foreach ($objectives as $key => $question) {
                    $output .= '<tr style="font-size: 16px;">
                                    <td colspan="6">' . $key . '. ' . $question . '</td>';
                    foreach ([1, 2, 3, 4] as $response) {
                        $totalParticipants = $event->evals->count();
                        $scoreCount = $event->evals->where("q7$key", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';

                        $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';
                }

                //8. VENUE AND MEALS
                $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                                <td colspan="14"><strong>8. VENUE AND MEALS</strong></td>
                            </tr>';
                $objectives = [
                    'a' => 'Meals and snacks were satisfying and serving amounts were sufficient',
                    'b' => 'Facilities were conducive to learning',
                    'c' => 'The venue was appropriate with the training/workshop objectives',
                ];
                foreach ($objectives as $key => $question) {
                    $output .= '<tr style="font-size: 16px;">
                                    <td colspan="6">' . $key . '. ' . $question . '</td>';
                    foreach ([1, 2, 3, 4] as $response) {
                        $totalParticipants = $event->evals->count();
                        $scoreCount = $event->evals->where("q8$key", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';

                        $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';
                }

            $output .= '</table></body></html>';
            return response($output, 200, $headers);
        }
    }
    public function exportCss2($id) {
        $event = Evaluation::find($id);
        $dates = explode(' - ', $event->date_conduct);
        $dateOutput = '';
        if (count($dates) == 2) {
            $dateStart = strtotime($dates[0]);
            $dateEnd = strtotime($dates[1]);
            if ($dateStart === $dateEnd) {
                $dateOutput = date('F j, Y', $dateStart);
            } elseif (date('Y-m', $dateStart) == date('Y-m', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('j, Y', $dateEnd);
            } elseif (date('Y', $dateStart) == date('Y', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            } else {
                $dateOutput = date('F j, Y', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            }
        }

        $headers = [
            "Content-Type" => "application/vnd.ms-excel", 
            "Content-Disposition" => "inline; filename=Summary_CSSForm2.xls", 
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

        $output = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $output .= '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
        $output .= '<body>';
        $output .= '<h2>Summary Report of Participant Responses</h2>';
        $output .= '<h3>This report summarizes the responses collected from the CSS Form 2.0 survey during the ' . $event->course_title . ' held on ' . $dateOutput . ' at ' . $event->venue . '.</h3>';
        $output .= '<table border="1">';
        $output .= '
            <tr style="font-size: 16px; background-color: #00B050;">
                <th colspan="4" rowspan="2">Statement</th>
                <th colspan="2">1</th>
                <th colspan="2">2</th>
                <th colspan="2">3</th>
                <th colspan="2">4</th>
                <th colspan="2">5</th>
                <th colspan="2">6</th>
                <th colspan="2">7</th>
            </tr>
            <tr style="font-size: 16px; background-color: #00B050;">
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
            </tr>';
        // A. OVERALL EXPECTATION OF THE TRAINING/WORKSHOP
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td colspan="4"><strong>A. OVERALL EXPECTATION OF THE TRAINING/WORKSHOP</strong></td>';
        foreach ([1, 2, 3, 4, 5, 6, 7] as $key => $response) {  
            $totalParticipants = $event->cssform->count();
            $scoreCount = $event->cssform->where("qa1", $response)->count();
            $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
            $output .= '<td>' . $scoreCount . '</td>
                        <td>' . $average . '%</td>';
        }
        $output .= '</tr>';
        // B. TRAINING/WORKSHOP FEATURES
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td colspan="18"><strong>B. TRAINING/WORKSHOP FEATURES</strong></td>
                    </tr>';
        $objectives = [
            '1' => 'The objectives of the event were met',
            '2' => 'Adequate time was provided for questions and discussions.',
            '3' => 'The Content of the activity was organized and easy to follow.',
            '4' => 'The resource speakers/s is/are knowledgeable.',
            '5' => 'The organizers are prompt and always willing to help clients.',
            '6' => 'The organizers are polite.',
            '7' => 'The organizers are sensitive to the participants needs.',
            '8' => 'The appearance of the physical/virtual facility of the service provider is in keeping with the type of service/s provided.',
            '9' => 'The quality of the physical/virtual amenities are excellent',
        ];
        foreach ($objectives as $key => $question) {
            $output .= '<tr style="font-size: 16px;">
                            <td colspan="4">' . $key . '. ' . $question . '</td>';
            foreach ([1, 2, 3, 4, 5, 6, 7] as $response) {
                $totalParticipants = $event->cssform->count();
                $scoreCount = $event->cssform->where("qb$key", $response)->count();
                $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
                $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</table>';
        $output .= '<h3></h3>';
        $output .= '<table border="1">';
        //C. OVERALL QUALITY RATE OF THE TRAINING/WORKSHOP PROVIDED
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <th rowspan="3" colspan="4" style="text-align: left;">C. OVERALL QUALITY RATE OF THE TRAINING/WORKSHOP PROVIDED</th>
                        <th colspan="2">1 (Poor)</th>
                        <th colspan="2">2 (Fair)</th>
                        <th colspan="2">3 (Good)</th>
                        <th colspan="2">4 (Excellent)</th>
                    </tr>
                    <tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                    </tr>';
                    $output .= '<tr style="background-color: #C6E0B4;">';
                    foreach ([1, 2, 3, 4] as $key => $response) {  
                        $totalParticipants = $event->cssform->count();
                        $scoreCount = $event->cssform->where("qc1", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
                        $output .= '<td>' . $scoreCount . '</td>
                                    <td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';

        $output .= '</table>';

        $output .= '</body></html>';

        return response($output, 200, $headers);
    }    

    public function exportCss3($id) {
        $event = Evaluation::find($id);
        $dates = explode(' - ', $event->date_conduct);
        $dateOutput = '';
        if (count($dates) == 2) {
            $dateStart = strtotime($dates[0]);
            $dateEnd = strtotime($dates[1]);
            if ($dateStart === $dateEnd) {
                $dateOutput = date('F j, Y', $dateStart);
            } elseif (date('Y-m', $dateStart) == date('Y-m', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('j, Y', $dateEnd);
            } elseif (date('Y', $dateStart) == date('Y', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            } else {
                $dateOutput = date('F j, Y', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            }
        }

        $headers = [
            "Content-Type" => "application/vnd.ms-excel", 
            "Content-Disposition" => "inline; filename=Summary_CSSForm3.xls", 
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

        $output = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $output .= '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
        $output .= '<body>';
        $output .= '<h2>Summary Report of Participant Responses</h2>';
        $output .= '<h3>This report summarizes the responses collected from the CSS Form 3.0 survey during the ' . $event->course_title . ' held on ' . $dateOutput . ' at ' . $event->venue . '.</h3>';
        $output .= '<table border="1">';
        $output .= '
            <tr style="font-size: 16px; background-color: #00B050;">
                <th colspan="4" rowspan="2">Statement</th>
                <th colspan="2">1</th>
                <th colspan="2">2</th>
                <th colspan="2">3</th>
                <th colspan="2">4</th>
                <th colspan="2">5</th>
                <th colspan="2">6</th>
                <th colspan="2">7</th>
            </tr>
            <tr style="font-size: 16px; background-color: #00B050;">
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
            </tr>';
        // A. OVERALL EXPECTATION OF THE RESOURCE PERSON
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td colspan="4"><strong>A. OVERALL EXPECTATION OF THE RESOURCE PERSON</strong></td>';
        foreach ([1, 2, 3, 4, 5, 6, 7] as $key => $response) {  
            $totalParticipants = $event->cssform3->count();
            $scoreCount = $event->cssform3->where("q1a", $response)->count();
            $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
            $output .= '<td>' . $scoreCount . '</td>
                        <td>' . $average . '%</td>';
        }
        $output .= '</tr>';
        // B. RESOURCE PERSON FEATURES
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td colspan="18"><strong>B. RESOURCE PERSON FEATURES</strong></td>
                    </tr>';
        $objectives = [
            '1' => 'The presentation was clear and on point.',
            '2' => 'I gained new insight/s relevant to the objectives of the activity.',
            '3' => 'The Resource person is well-versed on the subject matter.',
            '4' => 'The resource person is well prepared.',
            '5' => 'The resource person is accomodating to questions and discussions.',
            '6' => 'The resource person is polite.',
            '7' => 'The presentation was delivered professionally and with confidence.',
            '8' => 'The resource person is sensitive to the participant\'s needs.',
            '9' => 'The resource person is well dressed and appears neat.',
        ];
        foreach ($objectives as $key => $question) {
            $output .= '<tr style="font-size: 16px;">
                            <td colspan="4">' . $key . '. ' . $question . '</td>';
            foreach ([1, 2, 3, 4, 5, 6, 7] as $response) {
                $totalParticipants = $event->cssform->count();
                $scoreCount = $event->cssform->where("qb$key", $response)->count();
                $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
                $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</table>';
        $output .= '<h3></h3>';
        $output .= '<table border="1">';
        //C. QUALITY RATE OF SERVICES PROVIDED BY THE RESOURCE PERSON
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <th rowspan="3" colspan="4" style="text-align: left;">C. QUALITY RATE OF SERVICES PROVIDED BY THE RESOURCE PERSON</th>
                        <th colspan="2">1 (Poor)</th>
                        <th colspan="2">2 (Fair)</th>
                        <th colspan="2">3 (Good)</th>
                        <th colspan="2">4 (Excellent)</th>
                    </tr>
                    <tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                        <td>Total</td><td>Average</td>
                    </tr>';
                    $output .= '<tr style="background-color: #C6E0B4;">';
                    foreach ([1, 2, 3, 4] as $key => $response) {  
                        $totalParticipants = $event->cssform->count();
                        $scoreCount = $event->cssform->where("qc1", $response)->count();
                        $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
                        $output .= '<td>' . $scoreCount . '</td>
                                    <td>' . $average . '%</td>';
                    }
                    $output .= '</tr>';

        $output .= '</table>';

        $output .= '</body></html>';

        return response($output, 200, $headers);
    }
    public function exportCsm($id) {
        $event = Evaluation::find($id);
        $dates = explode(' - ', $event->date_conduct);
        $dateOutput = '';
        if (count($dates) == 2) {
            $dateStart = strtotime($dates[0]);
            $dateEnd = strtotime($dates[1]);
            if ($dateStart === $dateEnd) {
                $dateOutput = date('F j, Y', $dateStart);
            } elseif (date('Y-m', $dateStart) == date('Y-m', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('j, Y', $dateEnd);
            } elseif (date('Y', $dateStart) == date('Y', $dateEnd)) {
                $dateOutput = date('F j', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            } else {
                $dateOutput = date('F j, Y', $dateStart) . ' - ' . date('F j, Y', $dateEnd);
            }
        }

        $headers = [
            "Content-Type" => "application/vnd.ms-excel", 
            "Content-Disposition" => "inline; filename=Summary_CSM.xls", 
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

        $output = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $output .= '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
        $output .= '<body>';
        $output .= '<h2>Summary Report of Participant Responses</h2>';
        $output .= '<h3>This report summarizes the responses collected from the Client Satisfaction Measurement (CSM) survey during the ' . $event->course_title . ' held on ' . $dateOutput . ' at ' . $event->venue . '.</h3>';
        $output .= '<table border="1">';
        $output .= '
            <tr style="font-size: 16px; background-color: #00B050;">
                <th colspan="4" rowspan="2">Statement</th>
                <th colspan="2">1</th>
                <th colspan="2">2</th>
                <th colspan="2">3</th>
            </tr>
            <tr style="font-size: 16px; background-color: #00B050;">
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
            </tr>';
        // CITIZEN’S CHARTER
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td colspan="10"><strong>CITIZEN’S CHARTER</strong></td>
                    </tr>';

        $objectives = [
            'one' => [
                'text' => 'Do you know about the Citizen’s Charter (document of an agency’s services and reqs.)?',
                'no' => 'CC1'
            ],
            'two' => [
                'text' => 'If Yes to the previous question, did you see this office’s Citizen’s Charter?',
                'no' => 'CC2'
            ],
            'three' => [
                'text' => 'If Yes to the previous question, did you use the Citizen’s Charter as a guide for the service/s you availed?',
                'no' => 'CC3'
            ]
        ];

        foreach ($objectives as $key => $question) {
            $output .= '<tr style="font-size: 16px;">
                            <td colspan="4">' . $question['no'] . ': ' . $question['text'] . '</td>';
            foreach ([1, 2, 3] as $response) {
                $totalParticipants = $event->csmform->count();
                $scoreCount = $event->csmform->where("cc_$key", $response)->count();
                $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
                $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
            }
            $output .= '</tr>';
        }

        $output .= '</table>';
        $output .= '<h3></h3>';
        $output .= '<table border="1">';

        $output .= '
            <tr style="font-size: 16px; background-color: #00B050;">
                <th colspan="4" rowspan="2">Statement</th>
                <th colspan="2">1 (SD)</th>
                <th colspan="2">2 (D)</th>
                <th colspan="2">3 (NAD)</th>
                <th colspan="2">4 (A)</th>
                <th colspan="2">5 (SA)</th>
            </tr>
            <tr style="font-size: 16px; background-color: #00B050;">
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
                <td>Total</td><td>Average</td>
            </tr>';
        
       // RESOURCE PERSON FEATURES
        $output .= '<tr style="font-size: 16px; background-color: #C6E0B4;">
                        <td colspan="14"><strong>RESOURCE PERSON FEATURES</strong></td>
                    </tr>';

        $objectives = [
            'one' => [
                'text' => ' I spent an acceptable amount of time to complete my transaction (Responsiveness)',
                'no' => 'SQD1'
            ],
            'two' => [
                'text' => 'The office accurately informed and followed the transaction’s requirements and steps (Reliability)',
                'no' => 'SQD2'
            ],
            'three' => [
                'text' => 'My online transaction (including steps and payment) was simple and convenient (Access and Facilities) ',
                'no' => 'SQD3'
            ],
            'four' => [
                'text' => 'I easily found information about my transaction from the office or its website (Communication) ',
                'no' => 'SQD4'
            ],
            'five' => [
                'text' => 'I paid an acceptable amount of fees for my transaction (Costs)',
                'no' => 'SQD5'
            ],
            'six' => [
                'text' => 'I am confident my online transaction was secure (Integrity)',
                'no' => 'SQD6'
            ],
            'seven' => [
                'text' => 'The office\'s online support was available, or (if asked questions) online support was quick to respond (Assurance) ',
                'no' => 'SQD7'
            ],
            'eight' => [
                'text' => 'I got what I needed from the government office (Outcome)',
                'no' => 'SQD8'
            ]
        ];
        

        foreach ($objectives as $key => $question) {
            $output .= '<tr style="font-size: 16px;">
                            <td colspan="4">' . $question['no'] . '. ' . $question['text'] . '</td>';
            foreach ([1, 2, 3, 4, 5] as $response) {
                $totalParticipants = $event->csmform->count();
                $scoreCount = $event->csmform->where("sqd_$key", $response)->count();
                $average = $totalParticipants > 0 ? number_format((($scoreCount / $totalParticipants) * 100), 2) : '0';
                $output .= '<td>' . $scoreCount . '</td><td>' . $average . '%</td>';
            }
            $output .= '</tr>';
        }

        $output .= '</table>';

        $output .= '</body></html>';

        return response($output, 200, $headers);
    }
    public function formEval($id) {
        $baseid = base64_encode($id);
        $response = Http::get('http://192.168.1.56/hrmis/evaluation-details-all',[
            'event' => $baseid,
        ]);
        return $response->body();
    }
    public function formCss2($id) {
        $baseid = base64_encode($id);
        $response = Http::get('http://192.168.1.56/hrmis/css-form-details-all',[
            'event' => $baseid,
        ]);
        return $response->body();
    }
    public function formCss3($id) {
        $baseid = base64_encode($id);
        $response = Http::get('http://192.168.1.56/hrmis/cssform3-details-all',[
            'event' => $baseid,
        ]);
        return $response->body();
    }
    public function formCsm($id) {
        $baseid = base64_encode($id);
        $response = Http::get('http://192.168.1.56/hrmis/csm-form-details-all',[
            'event' => $baseid,
        ]);
        return $response->body();
    }
}
