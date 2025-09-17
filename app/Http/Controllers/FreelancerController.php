<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class FreelancerController extends Controller
{
    public function index()
    {

        return view('freelancers.index');
    }
}
