<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MobileSyncController extends Controller
{


    public function uploadData(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'sync_token' => 'required|string',
            'data' => 'required|array',
            'last_sync' => 'required|date'
        ]);

        try {
            $result = $this->syncService->processUploadedData(
                $request->user()->id,
                $request->device_id,
                $request->data,
                $request->last_sync
            );

            return response()->json([
                'success' => true,
                'sync_token' => $result['new_sync_token'],
                'processed_records' => $result['processed']
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile sync upload failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed'
            ], 500);
        }
    }

    public function downloadData(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'sync_token' => 'required|string',
            'last_sync' => 'required|date'
        ]);

        try {
            $data = $this->syncService->prepareDownloadData(
                $request->user()->id,
                $request->device_id,
                $request->last_sync
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'sync_token' => $this->syncService->generateNewSyncToken()
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile sync download failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Download failed'
            ], 500);
        }
    }
}