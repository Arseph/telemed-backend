<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LeaveDetails;
use App\Models\LeaveType;

class LeaveDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        // $vacationLeave = LeaveType::create(['leave_name' => 'Vacation Leave','leave_rate'=>'0','leave_desc'=>'(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)']);
        // $sickLeave = LeaveType::create(['leave_name' => 'Sick Leave','leave_rate'=>'0','leave_desc'=>'(Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)']);
        // $studyLeave = LeaveType::create(['leave_name'=>'Study Leave','leave_rate'=>'0','leave_desc'=>'(Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)']);
        // $specialLeave = LeaveType::create(['leave_name'=> 'Special Leave Benefits for Women','leave_rate'=>'0','leave_desc'=>'(RA No. 9710 / CSC MC No. 25, s. 2010)']);


        //Add details for Random
        LeaveDetails::create(['leave_type_id'=> 22,'details_name' => 'Monitazion of Leave Credits']);
        LeaveDetails::create(['leave_type_id'=> 22,'details_name' => 'Terminal Leave']);

        // Add details for "Vacation Leave"
        LeaveDetails::create(['leave_type_id' => 1, 'details_name' => 'Within the Philippines']);
        LeaveDetails::create(['leave_type_id' => 1, 'details_name' => 'Abroad']);

        // Add details for "Sick Leave"
        LeaveDetails::create(['leave_type_id' => 3, 'details_name' => 'In Hospital']);
        LeaveDetails::create(['leave_type_id' => 3, 'details_name' => 'Outpatient']);

        // Add details for "Study Leave"

        LeaveDetails::create(['leave_type_id' => 8, 'details_name' => 'Completion of Masters Degree']);
        LeaveDetails::create(['leave_type_id' => 8, 'details_name' => 'BAR/Board Examination Review']);

        // Add details for Special Women
        LeaveDetails::create(['leave_type_id' => 8, 'details_name' => 'Specify Illness']);

    }
}
