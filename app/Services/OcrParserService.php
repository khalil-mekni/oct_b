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
            $pattern = '/(?:' . preg_quote($keyword, '/') . ')\s*[:\-]?\s*([0-9\s]+(?:[.,][0-9]+)?)/iu';
            if (preg_match($pattern, $text, $matches)) {
                $value = preg_replace('/\s+/', '', $matches[1]);
                return (float) str_replace(',', '.', $value);
            }
        }

        return null;
    }

    private function extractStringAfterKeywords(string $text, array $keywords): ?string
    {
        foreach ($keywords as $keyword) {
            $pattern = '/(?:' . preg_quote($keyword, '/') . ')\s*[:\-]?\s*([^\n]+)/iu';
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

<<<<<<< HEAD
    
=======
    private function parseCommande(string $text): array
    {
        return [
            'numero_commande' => $this->extractStringAfterKeywords($text, [
                'commande',
                'numéro commande',
                'numero commande',
                'order',
            ]),
            'date_commande' => $this->extractDateAfterKeywords($text, [
                'date commande',
                'date de commande',
                'date',
            ]),
            'date_livraison_prevue' => $this->extractDateAfterKeywords($text, [
                'livraison prévue',
                'date prévue',
                'livraison',
                'delivery date',
            ]),
            'quantite' => $this->extractNumberAfterKeywords($text, [
                'quantité',
                'quantite',
                'total quantité',
                'quantity',
                'qty',
            ]),
            'montant_total' => $this->extractNumberAfterKeywords($text, [
                'montant total',
                'total',
                'total général',
            ]),
            'fournisseur_nom' => $this->extractStringAfterKeywords($text, [
                'fournisseur',
                'supplier',
            ]),
            'emballage_nom' => $this->extractStringAfterKeywords($text, [
                'emballage',
                'type emballage',
                'packaging',
                'package',
            ]),
            'entrepot_nom' => $this->extractStringAfterKeywords($text, [
                'entrepot',
                'destination',
                'warehouse',
                'site',
            ]),
        ];
    }
>>>>>>> origin/predict1.1

    private function parseBonLivraison(string $text): array
    {
        return [
            'numero_bl' => $this->extractStringAfterKeywords($text, [
                'bon livraison',
                'numéro bl',
                'numero bl',
                'bl',
                'delivery note',
            ]),
            'date_livraison' => $this->extractDateAfterKeywords($text, [
                'date livraison',
                'date de livraison',
                'date reception',
                'date de reception',
                'date',
            ]) ?? $this->extractDate($text),
            'commande_numero' => $this->extractStringAfterKeywords($text, [
                'commande',
                'numéro commande',
                'numero commande',
                'order no',
                'order #',
            ]),
            'quantite_recue' => $this->extractNumberAfterKeywords($text, [
                'quantité',
                'quantite',
                'qté',
                'qte',
                'quantité reçue',
                'quantite recue',
                'qté reçue',
                'qte recue',
                'quantity',
                'qty',
            ]),
            'fournisseur_nom' => $this->extractStringAfterKeywords($text, [
                'fournisseur',
                'supplier',
                'vendu par',
            ]),
            'emballage_nom' => $this->extractStringAfterKeywords($text, [
                'emballage',
                'type emballage',
                'packaging',
                'package',
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
            'qté contractuelle',
            'qte contractuelle',
            'volume',
            'quantité',
            'quantite',
            'qté',
            'qte',
        ]),
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