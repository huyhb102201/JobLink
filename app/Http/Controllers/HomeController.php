<?php

namespace App\Http\Controllers;
use App\Models\AccountType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\Payment;
class HomeController extends Controller
{
public function index()
{
    $account = Account::with('type')->find(auth()->id());
    return view('home', compact('account'));
}
}