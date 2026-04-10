<?php

namespace App\Http\Controllers;

use App\Services\OcrParserService;
use App\Services\OcrService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'entity_type' => 'nullable|string|in:generic,commande,bon_livraison,facture,contrat',
        ]);

        $file = $request->file('file');
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
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
        }
}
}