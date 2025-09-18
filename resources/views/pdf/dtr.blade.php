<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Time Record - {{$data->month}} {{$data->year}}</title>
    <style>
        @page { margin: 0px !important; }
        html {
            margin: 0px !important;
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        p {
            margin: 0;
            padding: 0;
        }
        .center-text {
            text-align: center;
        }
        .table-main {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
        }
        .table-sub {
            width: 100%;
            border-collapse: collapse;
        }
        .table-dtr {
            width: 100%;
            border: 1px solid black;
            border-collapse: collapse;
            height: 50%;
        }
        .table-dtr th, .table-dtr td {
            border: 1px solid black;
            text-align: center;
        }
        .td-total {
            border-bottom: 0 !important;
            border-right: 0 !important;
            border-top: 0 !important;
            text-align: left !important;
        }
        .column {
            vertical-align: top;
        }
        .table-total {
            border: 1px solid black;
        }
    </style>
</head>
<body>
    <table class="table-main">
        <tr>
            @for($i = 0; $i < 2; $i++)
            <td class="column"width="50%"style="padding-right: 12px;">
                <p>CSC Form No. 48</p>
                <p class="center-text"><b>DAILY TIME RECORD</b></p>
                <br>
                <table class="table-sub">
                    <tr>
                        <td width="30%">Name of Employee:</td>
                        <td width="70%" style="border-bottom: 1px solid black;">{{$data->name}}</td>
                    </tr>
                    <tr>
                        <td width="30%">For the Month of:</td>
                        <td width="70%" style="border-bottom: 1px solid black;">{{$data->month}} {{$data->year}}</td>
                    </tr>
                    <tr>
                        <td width="100%" colspan="2">
                            Offical hours of arrival and departures:
                        </td>
                    </tr>
                    <tr>
                        <td width="30%">(Regular Days)</td>
                        <td width="70%" style="border-bottom: 1px solid black;"><b>{{$data->regdays}}</b></td>
                    </tr>
                    <tr>
                        <td width="30%">(Saturday)</td>
                        <td width="70%" style="border-bottom: 1px solid black;"><b>{{$data->sat}}</b></td>
                    </tr>
                </table>
                <br>
                <table class="table-dtr">
                    <tr>
                        <th></th>
                        <th colspan="2">AM</th>
                        <th colspan="2">PM</th>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <th></th>
                        <th>IN</th>
                        <th>OUT</th>
                        <th>IN</th>
                        <th>OUT</th>
                        <th>Tardiness</th>
                        <th>Undertime</th>
                    </tr>
                    @foreach ($data->attendance as $day)
                    <tr>
                        <td>
                            {{ $day->date }}
                            @php
                            $s = \Carbon\Carbon::create($data->year, $data->monthno, $day->date);
                            if($s->isSaturday() || $s->isSunday())
                            echo 'S';
                            @endphp
                        </td>
                        <td>{{ $day->in_am }}</td>
                        <td>{{ $day->out_am }}</td>
                        <td>{{ $day->in_pm }}</td>
                        <td>{{ $day->out_pm }}</td>
                        <th></th>
                        <th></th>
                    </tr>
                    @endforeach
                    <tr>
                        <td class="td-total" colspan="6">
                            <b style="margin: 10px !important;">Total Rendered Time(Hr)</b>
                        </td>
                        <td style="border: 0;">
                            <b>{{$data->total_rendered_hours}}</b>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-total" colspan="7">
                        <b style="margin: 10px !important;">Total Tardy Time(Min)</b>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-total" colspan="7">
                        <b style="margin: 10px !important;">Total Undertime(Min)</b>
                        </td>
                    </tr>
                </table>
                <br>
                <br>
                <p style="text-indent: 20px;">
                I certify on my behalf that the above entries are true and 
                correct of hours of work performance, record of which was made Daily
                at the time of arrival at and departure from the office.
                </p>
                <br>
                <br>
                <center>__________________________________________________</center>
                <center>(Signature)</center>
                <br>
                <br>
                <p style="text-indent: 20px;">
                Certified as to the prescribed Office hours.
                </p>
                <br>
                <br>
                <center>__________________________________________________</center>
            </td>
            @endfor
        </tr>
    </table>
</body>
</html>
