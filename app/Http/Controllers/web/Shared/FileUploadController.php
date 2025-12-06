<?php

namespace App\Http\Controllers\web\Shared;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function __construct(
        private FileUploadService $fileService
    ) {}

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

            $result = $this->fileService->deleteFile($fileId, $user->id);

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

    /**
     * Informations sur un fichier
     */
    public function info($fileId)
    {
        try {
            $user = auth()->user();

            $info = $this->fileService->getFileInfo($fileId, $user->id);

            return response()->json([
                'success' => true,
                'data' => $info
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Télécharger un fichier
     */
    public function download($fileId)
    {
        try {
            $user = auth()->user();

            $file = $this->fileService->downloadFile($fileId, $user->id);

            return response()->download($file['path'], $file['name']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
