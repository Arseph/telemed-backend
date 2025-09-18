<!DOCTYPE html>
<html>
<head>
    <style>
    @page { margin: 40px !important; }
    html { margin: 40px !important; font-family: Arial, sans-serif;}
    .upper, .info, .table {
        width: 100%;
    } 
    .right {
        text-align: right;
    } 
    .center {
        text-align: center;
    }
    .section {
        margin-top: 10px;
    }
    p {
        font-size: 11pt;
    }
    </style>
</head>
<body>
    <table class="upper" cellpadding="0" cellspacing="0">
        <tr>
            <td width="20%" style="border: none;"><center><img src="{{ public_path('doh.png') }}" width="80"></center></td>
            <td width="60%" style="font-size: 11pt; border: none;">
                <center>
                    <strong>Republic of the Philippines</strong><br>
                    DEPARTMENT OF HEALTH<br>
                    <strong>CENTER FOR HEALTH DEVELOPMENT <br> SOCCSKSARGEN Region</strong><br>
                </center>
            </td>
            <td width="20%" style="border: none;"><center><img src="{{ public_path('ro12.png') }}" width="80"></center></td>
        </tr>
    </table>
    <hr>
    <table class="upper" cellpadding="0" cellspacing="0">
        <tr>
            <td width="20%" style="border: none;"></td>
            <td width="60%" style="font-size: 11pt; border: none;">
                <center>
                    <strong>PERSONNEL PASS SLIP</strong>
                </center>
            </td>
            <td width="20%" style="border: none;">
                <div class="center">
                <p><u>{{ \Carbon\Carbon::parse($ps->created_at)->format('F j, Y') }}</u></p>
                    <p>Date</p>
                </div>
            </td>
        </tr>
    </table>
    <p>TO THE GUARD ON DUTY:</p>
    <p style="text-indent: 40px;">Dr./Mr./Ms.
        <u>{{$ps->creator->info->first_name}} {{ strtoupper(substr($ps->creator->info->middle_name, 0, 1)) }}. {{$ps->creator->info->sur_name}} - {{$ps->creator->info->hrinfo->pos->position}}</u> is permitted/authorized to leave the office at <u>{{ \Carbon\Carbon::parse($ps->request_time_out)->format('g:i A') }}</u>.</p>
    <p>REASON FOR LEAVING:
        @if($ps->reason === 'personal')
        <label style="font-family: 'DejaVu Sans';">(&check;) Personal ( ) Official</label>
        @else
        <label style="font-family: 'DejaVu Sans';">( ) Personal (&check;) Official</label>
        @endif
    </p>
    @if($ps->reason === 'official')<p>If official, please state the nature of business: <u>{{$ps->nature_business}}</u></p>@endif
    <p>Estimated Time of Arrival: <u>{{ \Carbon\Carbon::parse($ps->estimated_arrival)->format('g:i A') }}</u></p>
    <p>Actual Time of Arrival: @if($ps->actual_time) <u>{{ \Carbon\Carbon::parse($ps->actual_time)->format('g:i A') }}</u>@endif</p>
    <table class="upper" cellpadding="0" cellspacing="0">
        <tr>
            <td width="20%" style="border: none; font-size: 11pt;">
            @if($creator_sig)
                <img src="data:image/png;base64,{{ $creator_sig }}" width="160" alt="Signature" style="display: block; margin-bottom: 5px;">
            @endif
                <div style="border-top: 1px solid #000; width: 150px; margin-bottom: 3px;"></div>

                <span style="font-size: 11pt;">Signature of Employee</span>
            </td>
            <td width="20%" style="border: none;">
                <p>I hereby certify that the nature of business specified above in official.</p>
                <div class="center">
                @if($supervisor_sig)
                <img src="data:image/png;base64,{{ $supervisor_sig }}" width="200" alt="Signature" style="display: block; margin-bottom: 5px;">
                @endif
                <div class="center" style="border-bottom: 1px solid #000; margin-top: -15px; align:center">{{$ps->supervisor?->first_name}} {{ strtoupper(substr($ps->supervisor?->middle_name, 0, 1)) }}. {{$ps->supervisor?->sur_name}}</div>

                <span style="font-size: 11pt;">Chief of Section/Immediate Supervisor</span>
                </div>
                <p>Approve By:</p>
                <div class="center">
                @if($supervisor_sig)
                <img src="data:image/png;base64,{{ $supervisor_sig }}" width="200" alt="Signature" style="display: block; margin-bottom: 5px;">
                @endif
                <div class="center" style="border-bottom: 1px solid #000; margin-top: -15px; align:center">{{$ps->approver?->first_name}} {{ strtoupper(substr($ps->approver?->middle_name, 0, 1)) }}. {{$ps->approver?->sur_name}}</div>

                <span style="font-size: 11pt;">Chief Administrative Officer</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
