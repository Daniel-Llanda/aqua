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

    // Get all payloads for the user's ponds
    $payLoads = Payload::whereIn('pond_id', $ponds->pluck('id'))
                ->orderBy('created_at', 'asc')
                ->get();

    $labels = [];
    $phData = [];
    $tempData = [];
    $ammoniaData = [];

    foreach ($payLoads as $data) {
       $decoded = $data->payload; // no json_decode needed

        if (!$decoded) continue; // skip invalid JSON

        $labels[] = $data->created_at->format('H:i:s');
$phData[] = $decoded['ph'];
$tempData[] = $decoded['water_temp'];
$ammoniaData[] = $decoded['ammonia'];

    }

    // Latest payload for status
   $latest = $payLoads->last();
$status = 'No Data';

if ($latest) {
    $latestDecoded = $latest->payload; // already array

    $status = 'Normal';

    if (
        $latestDecoded['ph'] < 6 || $latestDecoded['ph'] > 9 ||
        $latestDecoded['water_temp'] > 35 ||
        $latestDecoded['ammonia'] > 0.05
    ) {
        $status = 'Warning';
    }

    if (
        $latestDecoded['ph'] < 5 || $latestDecoded['ph'] > 10 ||
        $latestDecoded['water_temp'] > 38 ||
        $latestDecoded['ammonia'] > 0.1
    ) {
        $status = 'Critical';
    }
}

    return view('dashboard', compact(
        'ponds',
        'payLoads',
        'labels',
        'phData',
        'tempData',
        'ammoniaData',
        'status'
    ));
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
