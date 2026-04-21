<?php

namespace App\Http\Controllers;

use App\Services\OcrParserService;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OcrController extends Controller
{
    public function __construct(
        protected OcrService $ocrService,
        protected OcrParserService $ocrParserService
    ) {}

    public function analyze(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:1024',
                'entity_type' => 'nullable|string|in:generic,commande,bon_livraison,facture,contrat',
            ]);

            $file = $request->file('file');

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun fichier reçu.',
                ], 422);
            }

            $entityType = $request->input('entity_type', 'generic');

            $ocrResult = $this->ocrService->analyzeFile($file);
            $rawText = $ocrResult['raw_text'] ?? '';

            $mappedData = $this->ocrParserService->parse($entityType, $rawText);

            return response()->json([
                'success' => true,
                'entity_type' => $entityType,
                'file_name' => $file->getClientOriginalName(),
                'raw_text' => $rawText,
                'mapped_data' => $mappedData,
                'provider_response' => $ocrResult['provider_response'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier invalide ou trop volumineux. Taille maximale autorisée : 1 Mo.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'maximum permissible file size limit of 1024 KB')) {
                $message = 'Le fichier dépasse la limite de 1 Mo imposée par OCR.Space.';
            }

            if (str_contains($message, 'SSL certificate problem')) {
                $message = 'Problème SSL local entre Laravel et OCR.Space. En local, utilisez verify=false ou configurez cacert.pem dans php.ini.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }
    }
}