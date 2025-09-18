<?php

namespace App\Services\Leave;


use App\Models\Leave;

class LeaveService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }


    public function getLeave (){
        $data = Leave::all();

        return $data;
    }
}
