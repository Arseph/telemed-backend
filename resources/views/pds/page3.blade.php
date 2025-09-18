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
@if($emp->volwork)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
	<tr style=" background-color: #949494; color: white;">
		<th width="100%" style="text-align: left; font-style: italic;">VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S</th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
	<thead>
		<tr style=" background-color: #EAEAEA;">
			<th width="30%" class="fsheader">NAME & ADDRESS OF ORGANIZATION</th>
			<th colspan="2" width="40%" class="fsheader">INCLUSIVE DATES</th>
			<th width="10%" class="fsheader">NUMBER OF HOURS</th>
			<th width="20%" class="fsheader">POSITION / NATURE OF WORK</th>
		</tr>
	</thead>
	<tbody>
		@if(count($emp->volwork) === 0)
		<tr>
			<td class="ow fs">N/A</td>
			<td class="ow fs">N/A</td>
			<td class="ow fs">N/A</td>
			<td class="ow fs">N/A</td>
			<td class="ow fs">N/A</td>
		</tr>
		@endif
		@foreach($emp->volwork as $vw)
		<tr>
			<td class="ow fs">{{$vw->name}}</td>
			<td class="ow fs">{{date('m/d/Y',strtotime($vw->date_from))}}</td>
			<td class="ow fs">{{date('m/d/Y',strtotime($vw->date_to))}}</td>
			<td class="ow fs">{{$vw->hours}}</td>
			<td class="ow fs">{{$vw->nature_work}}</td>
		</tr>
		@endforeach
	</tbody>
	<tfoot>
        <tr>
            <td colspan="5" class="fs" style="text-align: center; font-weight: bold; background-color: #EAEAEA; color: red;">
                *** End of VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S ***
            </td>
        </tr>
    </tfoot>
</table>
@endif
@if($emp->trainatt)
<table width="100%"  cellspacing="0" class="table-header" style="table-layout:fixed;">
    <tr style=" background-color: #949494; color: white;">
        <th width="100%" style="text-align: left; font-style: italic;">
		VI I.  LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED
        </th>
    </tr>
</table>
<table width="100%"  cellspacing="0" class="table" style="table-layout:fixed;">
	<thead>
		<tr style=" background-color: #EAEAEA;">
			<th width="30%" class="fsheader">TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS</th>
			<th colspan="2" width="25%" class="fsheader">INCLUSIVE DATES</th>
			<th width="10%" class="fsheader">NUMBER OF HOURS</th>
			<th width="10%" class="fsheader">TYPE OF ID</th>
			<th width="25%" class="fsheader">CONDUCTED/ SPONSORED BY</th>
		</tr>
	</thead>
	<tbody>
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
	</tbody>
	<tfoot>
        <tr>
            <td colspan="6" class="fs" style="text-align: center; font-weight: bold; background-color: #EAEAEA; color: red;">
                *** End of LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED ***
            </td>
        </tr>
		<tr>
            <td colspan="1" class="fs" style="padding: 10px;text-align: center; font-weight: bold; background-color: #EAEAEA; font-style: italic;">
                SIGNATURE
            </td>
			<td colspan="2"></td>
			<td colspan="1" class="fs" style="padding: 10px;text-align: center; font-weight: bold; background-color: #EAEAEA; font-style: italic;">
                DATE
            </td>
			<td colspan="2" style="padding: 10px;font-size: 10px; font-weight: bold; text-align:center;">{{date('m/d/Y', strtotime(now()))}}</td>
        </tr>
		<tr>
			<td colspan="6" style="border:none;font-size: 10px; text-align:right; font-style: italic;">CS FORM 212 (Revised 2017), Page 3 of 4</td>
        </tr>
    </tfoot>
</table>
@endif
</body>
</html>
