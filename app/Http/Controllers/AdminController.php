<?php

namespace App\Http\Controllers;

use App\Models\Payload;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function user()
    {
        $users = User::all();
        return view('admin.user', compact('users'));
    }

    public function telemetry()
    {
        $payloads = Payload::latest()->get();
        $latestPayload = Payload::latest()->first();

        return view('admin.telemetry', compact('payloads', 'latestPayload'));
    }
}
