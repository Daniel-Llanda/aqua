<?php

namespace App\Http\Controllers;

use App\Models\Payload;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        // Get all ponds
        $ponds = Pond::latest()->get();

        // Get all payloads from all ponds
        $payLoads = Payload::whereIn('pond_id', $ponds->pluck('id'))
            ->orderBy('created_at', 'asc')
            ->get();
            

        $labels = [];
        $phData = [];
        $tempData = [];
        $ammoniaData = [];

        foreach ($payLoads as $data) {
            $decoded = $data->payload; // already array

            if (!$decoded) continue;

            $labels[] = $data->created_at 
    ? $data->created_at->format('H:i:s') 
    : '';
            $phData[] = $decoded['ph'] ?? 0;
            $tempData[] = $decoded['water_temp'] ?? 0;
            $ammoniaData[] = $decoded['ammonia'] ?? 0;
        }

        return view('admin.dashboard', compact(
            'ponds',
            'payLoads',
            'labels',
            'phData',
            'tempData',
            'ammoniaData'
        ));
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
