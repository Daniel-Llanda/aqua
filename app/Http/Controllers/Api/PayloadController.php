<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payload;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PayloadController extends Controller
{
    /**
     * Store a new payload in the database.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Get all request data (entire payload)
        $payloadData = $request->all();

        // Validate that some data was sent
        if (empty($payloadData)) {
            return response()->json([
                'success' => false,
                'message' => 'No payload data received',
            ], 400);
        }

        // Create the payload record with the entire request data
        $payload = Payload::create([
            'payload' => $payloadData,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payload saved successfully',
            'data' => [
                'id' => $payload->id,
                'payload' => $payload->payload,
                'created_at' => $payload->created_at,
            ],
        ], 201);
    }
}
