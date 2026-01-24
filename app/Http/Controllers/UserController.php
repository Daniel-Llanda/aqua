<?php

namespace App\Http\Controllers;

use App\Models\Payload;
use App\Models\Pond;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Show user dashboard
     */
    public function dashboard()
    {
        $userId = Auth::id();

        // Get all ponds for this user
        $ponds = Pond::where('user_id', $userId)->latest()->get();

        // Get latest payload for each pond
        $payloads = Payload::whereIn('pond_id', $ponds->pluck('id'))
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->groupBy('pond_id')
            ->map(function ($group) {
                return $group->first()->payload;
            });

        return view('dashboard', [
            'ponds' => $ponds,
            'payloads' => $payloads
        ]);
    }

    /**
     * Show fish pond information page
     */
    public function pondInfo()
    {
        $ponds = Pond::where('user_id', auth()->id())->latest()->get();

        return view('pond-info', compact('ponds'));
    }

    public function storePond(Request $request)
    {
        $request->validate([
            'hectares' => 'required|numeric|min:0.1',
            'fish_type' => 'required|array|min:1',
            'fish_type.*' => 'string',
            'hatching_date' => 'required|date',
            'harvest_date' => 'required|date|after:hatching_date',
        ]);

        Pond::create([
            'user_id' => auth()->id(),
            'hectares' => $request->hectares,
            'fish_type' => json_encode($request->fish_type), // store as JSON
            'hatching_date' => $request->hatching_date,
            'harvest_date' => $request->harvest_date,
        ]);

        return redirect()->back()->with('success', 'Fish pond information saved successfully.');
    }
    public function telemetrylog(Request $request)
    {
        $user = auth()->user();

        // Get ponds owned by this user
        $ponds = Pond::where('user_id', $user->id)->get();

        // If a pond is selected, load its payloads
        $payloads = collect();

        if ($request->filled('pond_id')) {
            $payloads = Payload::where('pond_id', $request->pond_id)
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return view('telemetrylog', compact('ponds', 'payloads'));
    }

}
