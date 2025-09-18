<html>
<head>
    <title>Page 2</title>
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
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">III. Educational Background</th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
      <tr style=" background-color: #EAEAEA;">
        <th width="13%" style="text-align: center;"class="fsheader">LEVEL</th>
        <th width="20%" style="text-align: center;"class="fsheader">NAME OF SCHOOL</th>
        <th width="20%" style="text-align: center;"class="fsheader">BASIC EDUCATION/ DEGREE/ COURSE</th>
        <th width="10%" colspan="2" style="text-align: center;"class="fsheader">PERIOD OF ATTENDANCE</th>
        <th width="10%" style="text-align: center;"class="fsheader">HIGHEST LEVEL/UNITS EARNED</th>
        <th width="10%" style="text-align: center;"class="fsheader">YEAR GRADUATED</th>
        <th width="7%"style="text-align: center;"class="fsheader">SCHOLARSHIP/ ACADEMIC HONORS RECEIVED</th>
      </tr>
      @foreach($emp->educadd as $edu)
      <tr>
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
      <tfoot>
        <tr>
            <td colspan="8" class="fs" style="text-align: center; font-weight: bold; background-color: #EAEAEA; color: red;">
                *** End of Educational Background ***
            </td>
        </tr>
    </tfoot>
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
    <thead>
        <tr style=" background-color: #EAEAEA;">
            <th width="30%" class="fsheader">27. CAREER SERVICE/ RA 1080 (BOARD/ BAR) UNDER SPECIAL LAWS/ CES/ CSEE BARANGAY ELIGIBILITY / DRIVER'S LICENSE</th>
            <th width="10%" class="fsheader">RATING<br>(If Applicable)</th>
            <th width="10%" class="fsheader">DATE OF EXAMINATION /CONFERMENT</th>
            <th width="30%" class="fsheader">PLACE OF EXAMINATION / CONFERMENT</th>
            <th colspan="2" width="20%" class="fsheader">LICENSE Number/Date</th>
        </tr>
    </thead>
    <tbody>
        @if(count($emp->cs_eligible) === 0)
        <tr>
            <td class="ow fs">N/A</td>
            <td class="ow fs">N/A</td>
            <td class="ow fs">N/A</td>
            <td class="ow fs">N/A</td>
            <td class="ow fs">N/A</td>
            <td class="ow fs">N/A</td>
        </tr>
        @endif
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
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="fs" style="text-align: center; font-weight: bold; background-color: #EAEAEA; color: red;">
                *** End of CIVIL SERVICE ELIGIBILITY ***
            </td>
        </tr>
    </tfoot>
</table>
@endif
@if($emp->workex)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">
            V.WORK EXPERIENCE<br>
            <small style="font-size: 9px !important;">(Include private employment.Start from your recent work) Description of duties should beindicated in the attached Work Experience sheet)</small>
        </th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:auto;">
	<thead>
		<tr style=" background-color: #EAEAEA;">
			<th colspan="2" width="20%" class="fsheader" style="border-bottom: none !important;">INCLUSIVE DATES</th>
			<th width="40%" class="fsheader" style="border-bottom: none !important;">POSITION TITLE</th>
			<th width="20%" class="fsheader" style="border-bottom: none !important;">DEPARTMENT / AGENCY / OFFICE / COMPANY</th>
			<th width="10%" class="fsheader" rowspan="2">MONTHLY SALARY</th>
			<th width="10%" class="fsheader" style="border-bottom: none !important;">SALARY GRADE</th>
			<th width="10%" class="fsheader" style="font-size: 8px;" rowspan="2">STATUS OF APPOINTMENT</th>
			<th width="5%" class="fsheader" style="font-size: 8px;" rowspan="2">GOV'T SERVICE (Y/ N)</th>
		</tr>
		<tr style=" background-color: #EAEAEA;">
			<th width="10%" class="fsheader" style="font-size: 7px; border-top: none; border-right: none;">FROM</th>
			<th width="10%" class="fsheader" style="font-size: 7px; border-top: none; border-left: none;">TO</th>
			<th width="20%" class="fsheader" style="font-size: 7px; border-top: none;">(Write in full/Do not abbreviate)</th>
			<th width="20%" class="fsheader" style="font-size: 7px; border-top: none;">(Write in full/Do not abbreviate)</th>
			<th width="10%" class="fsheader" style="font-size: 4px; border-top: none;">(if applicable)& STEP (Format "00-0")/From INCREMENT</th>

		</tr>
	</thead>
	<tbody>
		@foreach($emp->workex as $we)
		<tr>
			<td class="ow fs">{{date('m/d/Y',strtotime($we->date_from))}}</td>
			<td class="ow fs">{{date('m/d/Y',strtotime($we->date_to))}}</td>
			<td class="ow fs">{{$we->position}}</td>
			<td class="ow fs">{{$we->company}}</td>
			<td class="ow fs">{{$we->monthly_sal}}</td>
			<td class="ow fs">{{$we->salary_grade}}</td>
			<td class="ow fs">{{$we->status}}</td>
			<td class="ow fs">{{$we->govt}}</td>
		</tr>
		@endforeach
	</tbody>
	<tfoot style="display: table-footer-group;">
        <tr>
            <td colspan="8" class="fs" style="text-align: center; font-weight: bold; background-color: #EAEAEA; color: red;">
                *** End of Work Experience ***
            </td>
        </tr>
		<tr>
            <td colspan="2" class="fs" style="padding: 10px;text-align: center; font-weight: bold; background-color: #EAEAEA; font-style: italic;">
                SIGNATURE
            </td>
			<td colspan="2"></td>
			<td colspan="2" class="fs" style="padding: 10px;text-align: center; font-weight: bold; background-color: #EAEAEA; font-style: italic;">
                DATE
            </td>
			<td colspan="2" style="padding: 10px;font-size: 10px; font-weight: bold; text-align:center;">{{date('m/d/Y', strtotime(now()))}}</td>
        </tr>
		<tr>
			<td colspan="8" style="border:none;font-size: 10px; text-align:right; font-style: italic;">CS FORM 212 (Revised 2017), Page 2 of 4</td>
        </tr>
    </tfoot>
</table>
@endif
</body>
</html>
