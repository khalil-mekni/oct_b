<?php

namespace App\Services;

class OcrParserService
{
    public function parse(string $entityType, string $text): array
    {
        $clean = $this->cleanText($text);

        return match ($entityType) {
            'commande' => $this->parseCommande($clean),
            'bon_livraison' => $this->parseBonLivraison($clean),
            'facture' => $this->parseFacture($clean),
            'contrat' => $this->parseContrat($clean),
            default => [],
        };
    }

    private function cleanText(string $text): string
    {
        $text = str_replace("\r", ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n+/', "\n", $text);

        return trim($text);
    }

    private function extractDate(string $text): ?string
    {
        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\b(\d{2}\/\d{2}\/\d{4})\b/', $text, $matches)) {
            [$day, $month, $year] = explode('/', $matches[1]);
            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    private function extractNumberAfterKeywords(string $text, array $keywords): ?float
    {
        foreach ($keywords as $keyword) {
            $pattern = '/(?:' . preg_quote($keyword, '/') . ')\s*[:\-]?\s*([0-9]+(?:[.,][0-9]+)?)/i';
            if (preg_match($pattern, $text, $matches)) {
                return (float) str_replace(',', '.', $matches[1]);
            }
        }

        return null;
    }

    private function extractStringAfterKeywords(string $text, array $keywords): ?string
    {
        foreach ($keywords as $keyword) {
            $pattern = '/(?:' . preg_quote($keyword, '/') . ')\s*[:\-]?\s*([^\n]+)/i';
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }
    private function extractDateAfterKeywords(string $text, array $keywords): ?string
    {
        foreach ($keywords as $keyword) {
        $pattern = '/(?:' . preg_quote($keyword, '/') . ')\s*[:\-]?\s*(\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}-\d{2})/i';
        if (preg_match($pattern, $text, $matches)) {
            $value = $matches[1];

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                [$day, $month, $year] = explode('/', $value);
                return "{$year}-{$month}-{$day}";
            }
        }
        }
        return null;
    }

    

    private function parseBonLivraison(string $text): array
    {
        return [
            'numero_bl' => $this->extractStringAfterKeywords($text, [
                'bon livraison',
                'numéro bl',
                'numero bl',
                'bl',
            ]),
            'date_reception' => $this->extractDate($text),
            'numero_commande' => $this->extractStringAfterKeywords($text, [
                'commande',
                'numéro commande',
                'numero commande',
            ]),
            'quantite_recue' => $this->extractNumberAfterKeywords($text, [
                'quantité',
                'quantite',
                'quantité reçue',
                'quantite recue',
            ]),
            'fournisseur_nom' => $this->extractStringAfterKeywords($text, [
                'fournisseur',
                'supplier',
            ]),
        ];
    }

    private function parseFacture(string $text): array
    {
        return [
            'numero_facture' => $this->extractStringAfterKeywords($text, [
                'facture',
                'numéro facture',
                'numero facture',
                'invoice',
            ]),
            'date_facture' => $this->extractDate($text),
            'montant_ht' => $this->extractNumberAfterKeywords($text, [
                'montant ht',
                'total ht',
                'ht',
            ]),
            'montant_ttc' => $this->extractNumberAfterKeywords($text, [
                'montant ttc',
                'total ttc',
                'ttc',
            ]),
            'fournisseur_nom' => $this->extractStringAfterKeywords($text, [
                'fournisseur',
                'supplier',
            ]),
        ];
    }

    private function parseContrat(string $text): array
    {
        return [
            'numero_contrat' => $this->extractStringAfterKeywords($text, [
            'contrat',
            'numéro contrat',
            'numero contrat',
            'référence contrat',
            'reference contrat',
        ]),
        'objet' => $this->extractStringAfterKeywords($text, [
            'objet',
            'description',]),
        'date_signature' => $this->extractDateAfterKeywords($text, [
            'date signature',
            'signature',]),
        'date_debut' => $this->extractDateAfterKeywords($text, [
            'date debut',
            'date début',
            'début',
            'debut',]),
        'date_fin' => $this->extractDateAfterKeywords($text, [
            'date fin',
            'fin',
            'échéance',
            'echeance',]),
        'quantite_contractuelle' => $this->extractNumberAfterKeywords($text, [
            'quantité contractuelle',
            'quantite contractuelle',
            'volume',
            'quantité',
            'quantite',]),
        'montant_ht' => $this->extractNumberAfterKeywords($text, [
            'montant ht',
            'total ht',
            'ht',]),
        'montant_tva' => $this->extractNumberAfterKeywords($text, [
            'montant tva',
            'tva',]),
        'prix_unitaire' => $this->extractNumberAfterKeywords($text, [
            'prix unitaire',
            'pu',
        ]),
        'fournisseur_nom' => $this->extractStringAfterKeywords($text, [
            'fournisseur',
            'supplier',
            'partenaire',
        ]),
        'emballage_nom' => $this->extractStringAfterKeywords($text, [
            'emballage',
            'type emballage',
            'packaging',
            'package',]),
        ];
    }
}