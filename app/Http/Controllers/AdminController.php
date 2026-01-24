<?php

namespace App\Http\Controllers;

use App\Models\Payload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function user()
    {
        $users = User::with('ponds')->get();

        return view('admin.user', compact('users'));
    }

    public function telemetry()
    {
        $payloads = Payload::latest()->get();
        $latestPayload = Payload::latest()->first();

        return view('admin.telemetry', compact('payloads', 'latestPayload'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');

    }

}
