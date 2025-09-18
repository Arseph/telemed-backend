<html>
<head>
    <title>Separate Pages PDS</title>
    <link rel="icon" href="{{ asset('public/img/doh.png') }}">
</head>
<style>
    @page { margin: 10px !important; }
    html { margin: 10px !important;}
    body {
        font-family: Arial, sans-serif;
        margin: 10px;
    }
    .upper, .info, .table {
        width: 100%;
    }   
    .upper td, .info td, .table td {
        border:1px solid #000;
    }
    .upper td {
        padding:10px;
    }
    .info {
        margin-top: 90px;
    }
    .info td {
        padding: 5px;
        vertical-align: top;
    }
    .table-header {
        border:1px solid #000;
        text-align: left;
    }
    .table th {
        border:1px solid #000;
    }
    .table td {
        padding: 3px;
        vertical-align: top;
    }
    .ow {
      overflow-wrap: break-word;
      word-wrap: break-word;
      hyphens: auto;
    }
    .fs {
        font-family: Arial, sans-serif;
        font-size: 11px;
        font-weight: bold;
        text-align: center;
        padding: 3px;
    }
    .fsheader {
        font-family: Arial, sans-serif;
        font-size: 10px;
        font-weight: normal;
        padding: 5px;
    }
    .alc {
        text-align: center;
    }
</style>
<body>
@if(count($emp->educadd) > 0)
<h3>III. Educational Background</h3>
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
      <tr style=" background-color: #EAEAEA;">
        <th style="text-align: center;"class="fs">LEVEL</th>
        <th style="text-align: center;"class="fs">NAME OF SCHOOL</th>
        <th style="text-align: center;"class="fs">BASIC EDUCATION/ DEGREE/ COURSE</th>
        <th colspan="2" style="text-align: center;"class="fs">PERIOD OF ATTENDANCE</th>
        <th style="text-align: center;"class="fs">HIGHEST LEVEL/UNITS EARNED</th>
        <th style="text-align: center;"class="fs">YEAR GRADUATED</th>
        <th style="text-align: center;"class="fs">SCHOLARSHIP/ ACADEMIC HONORS RECEIVED</th>
      </tr>
      @foreach($emp->educadd as $edu)
      <tr style="page-break-inside:avoid; page-break-after:auto">
        <td class="ow fs">@if($edu->highed){{$edu->highed->highest_education}}@endif</td>
        <td style="white-space: normal;"class="ow fs">{{$edu->school_name}}</td>
        <td style="white-space: normal;"class="ow fs">{{$edu->course}}</td>
        <td style="white-space: normal; text-align: center;"class="ow fs">{{$edu->from_date}}</td>
        <td style="white-space: normal; text-align: center;"class="ow fs">{{$edu->to_date}}</td>
        <td style="white-space: normal;"class="ow fs">{{$edu->highest_level}}</td>
        <td style="white-space: normal; text-align: center;"class="ow fs">{{$edu->year_graduated}}</td>
        <td style="white-space: normal;"class="ow fs">{{$edu->scholarship_honors}}</td>
      </tr>
      @endforeach
</table>
<div style="page-break-after: always;"></div>
@endif
@if($emp->cs_eligible)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">IV. CIVIL SERVICE ELIGIBILITY</th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
    <tr style=" background-color: #EAEAEA;">
        <th width="30%" class="fsheader">CAREER SERVICE/ RA 1080 (BOARD/ BAR) UNDER SPECIAL LAWS/ CES/ CSEE BARANGAY ELIGIBILITY / DRIVER'S LICENSE</th>
        <th width="10%" class="fsheader">RATING</th>
        <th width="10%" class="fsheader">DATE OF EXAM</th>
        <th width="30%" class="fsheader">PLACE OF EXAMINATION / CONFERMENT</th>
        <th colspan="2" width="20%" class="fsheader">LICENSE Number/Date</th>
    </tr>
    @foreach($emp->cs_eligible as $cs)
    <tr>
        <td class="ow fs">{{$cs->career_service}}</td>
        <td class="ow fs">{{$cs->rating}}</td>
        <td class="ow fs">{{date('m/d/Y', strtotime($cs->date))}}</td>
        <td class="ow fs">{{$cs->place_exam}}</td>
        <td class="ow fs">{{$cs->license_no}}</td>
        <td class="ow fs">{{$cs->date_released}}</td>
    </tr>
    @endforeach
</table>
<div style="page-break-after: always;"></div>
@endif
@if($emp->workex)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">
            V.WORK EXPERIENCE<br>
            <small>(Include private employment.Start from your recent work) Description of duties should beindicated in the attached Work Experience sheet</small>
        </th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
    <tr style=" background-color: #EAEAEA;">
        <th colspan="2" width="40%" class="fsheader">INCLUSIVE DATES</th>
        <th width="20%" class="fsheader">POSITION TITLE</th>
        <th width="20%" class="fsheader">DEPARTMENT / AGENCY / OFFICE / COMPANY</th>
        <th width="10%" class="fsheader">MONTHLY SALARY</th>
        <th width="10%" class="fsheader">SALARY GRADE</th>
        <th width="10%" class="fsheader" style="font-size: 8px;">STATUS OF APPOINTMENT</th>
        <th width="5%" class="fsheader" style="font-size: 8px;">GOV'T SERVICE (Y/ N)</th>
    </tr>
    @foreach($emp->workex as $we)
    <tr>
        <td class="ow fs">{{date('m/d/Y',strtotime($we->date_from))}}</td>
        <td class="ow fs">{{$we->date_to}}</td>
        <td class="ow fs">{{$we->position}}</td>
        <td class="ow fs">{{$we->company}}</td>
        <td class="ow fs">{{$we->monthly_sal}}</td>
        <td class="ow fs">{{$we->salary_grade}}</td>
        <td class="ow fs">{{$we->status}}</td>
        <td class="ow fs">{{$we->govt}}</td>
    </tr>
    @endforeach
</table>
<div style="page-break-after: always;"></div>
@endif
@if($emp->volwork)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S</th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
    <tr style=" background-color: #EAEAEA;">
        <th width="30%" class="fsheader">NAME & ADDRESS OF ORGANIZATION</th>
        <th colspan="2" width="40%" class="fsheader">INCLUSIVE DATES</th>
        <th width="10%" class="fsheader">NUMBER OF HOURS</th>
        <th width="20%" class="fsheader">POSITION / NATURE OF WORK</th>
    </tr>
    @foreach($emp->volwork as $vw)
    <tr>
        <td class="ow fs">{{$vw->name}}</td>
        <td class="ow fs">{{date('m/d/Y',strtotime($vw->date_from))}}</td>
        <td class="ow fs">{{date('m/d/Y',strtotime($vw->date_to))}}</td>
        <td class="ow fs">{{$vw->hours}}</td>
        <td class="ow fs">{{$vw->nature_work}}</td>
    </tr>
    @endforeach
</table>
<div style="page-break-after: always;"></div>
@endif
@if($emp->trainatt)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">VI I.  LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED</th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
    <tr style=" background-color: #EAEAEA;">
        <th width="30%" class="fsheader">TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS</th>
        <th colspan="2" width="25%" class="fsheader">INCLUSIVE DATES</th>
        <th width="10%" class="fsheader">NUMBER OF HOURS</th>
        <th width="10%" class="fsheader">TYPE OF ID</th>
        <th width="25%" class="fsheader">CONDUCTED/ SPONSORED BY</th>
    </tr>
    @foreach($emp->trainatt as $ta)
    <tr>
        <td class="ow fs">{{$ta->seminar_title}}</td>
        <td class="ow fs">{{date('m/d/Y',strtotime($ta->datefrom))}}</td>
        <td class="ow fs">{{date('m/d/Y',strtotime($ta->date_end))}}</td>
        <td class="ow fs">{{$ta->hours}}</td>
        <td class="ow fs">@if($ta->ldtype){{$ta->ldtype->ld_type}}@endif</td>
        <td class="ow fs">{{$ta->conducted}}</td>
    </tr>
    @endforeach
</table>
<div style="page-break-after: always;"></div>
@endif
@if($emp->skills)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">VI II.OTHER INFORMATION</th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
    <tr style=" background-color: #EAEAEA;">
        <th width="34%" class="fsheader">SPECIAL SKILLS and HOBBIES</th>
        <th width="33%" class="fsheader">NON-ACADEMIC DISTINCTIONS / RECOGNITION</th>
        <th width="33%" class="fsheader">MEMBERSHIP IN ASSOCIATION/ORGANIZATION</th>
    </tr>
    @foreach($emp->skills as $sk)
    <tr>
        <td class="ow fs">{{$sk->skill}}</td>
        <td class="ow fs">{{$sk->non_acad}}</td>
        <td class="ow fs">{{$sk->member_org}}</td>
    </tr>
    @endforeach
</table>
@endif
</body>
</html>
