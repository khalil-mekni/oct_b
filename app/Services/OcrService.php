<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrService
{
    public function analyzeFile(UploadedFile $file): array
    {
        $apiKey = config('services.ocr_space.key');
        $apiUrl = config('services.ocr_space.url', 'https://api.ocr.space/parse/image');
        $language = config('services.ocr_space.language', 'fre');

        if (!$apiKey) {
            throw new \Exception('OCR API key is missing.');
        }

       $response = Http::timeout(120)
       ->withOptions([
        'verify' => false,
        ])
        ->attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )
        ->asMultipart()
        ->post($apiUrl, [
            'apikey' => $apiKey,
            'language' => $language,
            'isOverlayRequired' => 'false',
            'OCREngine' => '2',
            'scale' => 'true',
        ]);

        if (!$response->successful()) {
            Log::error('OCR HTTP error', ['status' => $response->status(),'body' => $response->body(),]);
            throw new \Exception('OCR provider request failed. Status: ' .
            $response->status() .
            ' Body: ' .
            $response->body()
        );
        }

        $json = $response->json();

        $parsedResults = $json['ParsedResults'] ?? [];
        $rawText = collect($parsedResults)
            ->pluck('ParsedText')
            ->filter()
            ->implode("\n");

        return [
            'provider_response' => $json,
            'raw_text' => trim($rawText),
        ];
    }
}