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
        $ponds = Pond::where('user_id', Auth::id())
                ->latest()
                ->get();
$telemetry = Payload::latest()->first();

return view('dashboard', [
    'ponds' => $ponds,
    'payload' => $telemetry ? $telemetry->payload : []
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

}
