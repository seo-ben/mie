<?php

namespace App\Http\Controllers\Web\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileDownloadController extends Controller
{


    /**
     * Upload d'un fichier
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:document,image,profile_photo,kyc_document',
            'category' => 'nullable|string',
            'description' => 'nullable|string|max:255'
        ]);

        try {
            $user = auth()->user();

            $result = $this->fileService->uploadFile(
                $request->file('file'),
                $request->get('type'),
                $user->id,
                [
                    'category' => $request->get('category'),
                    'description' => $request->get('description')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Fichier uploadé avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un fichier
     */
    public function delete($fileId)
    {
        try {
            $user = auth()->user();

            $this->fileService->deleteFile($fileId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
