<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MobileAuthController extends Controller
{


    public function biometricLogin(Request $request): JsonResponse
    {
        $request->validate([
            'biometric_token' => 'required|string',
            'device_id' => 'required|string'
        ]);

        $authResult = $this->biometricService->verifyAndAuthenticate(
            $request->biometric_token,
            $request->device_id
        );

        if (!$authResult['success']) {
            return response()->json([
                'message' => 'Authentication failed'
            ], 401);
        }

        return response()->json([
            'token' => $authResult['token'],
            'user' => $authResult['user']
        ]);
    }
}