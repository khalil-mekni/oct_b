# ML_prediction/feature_builder.py

import pandas as pd
import numpy as np

# Les colonnes attendues par le nouveau modèle ML
EXPECTED_COLUMNS = [
    "annee",
    "mois",
    "jour",
    "entrepot_source_id",
    "emballage_nom",
]

# Mapping pour emballage_nom pour assurer la cohérence avec l'entraînement
# Supporte int et str pour les clés
EMBALLAGE_ID_TO_NAME = {
    1: "Cartons", "1": "Cartons",
    2: "Riz Blanc", "2": "Riz Blanc",
    3: "Sucre Blanc", "3": "Sucre Blanc",
    4: "Riz Étuvé", "4": "Riz Étuvé",
    5: "Riz Basmati", "5": "Riz Basmati",
    6: "Complexe", "6": "Complexe",
    7: "Rouleaux Adhésifs", "7": "Rouleaux Adhésifs",
    8: "TNCeylon 150 G", "8": "TNCeylon 150 G",
    9: "TNExtra 250 G", "9": "TNExtra 250 G",
    10: "TNExtra Plus 100 G", "10": "TNExtra Plus 100 G",
    11: "TNExtra Plus 250 G", "11": "TNExtra Plus 250 G",
    12: "TVBourgeon 250 G", "12": "TVBourgeon 250 G",
    13: "TVSuperieur 100 G", "13": "TVSuperieur 100 G",
    14: "TVSuperieur 250 G", "14": "TVSuperieur 250 G",
    15: "Thermo 200µ", "15": "Thermo 200µ",
    16: "Thermo 500µ", "16": "Thermo 500µ",
    17: "Étirable", "17": "Étirable",
    18: "Étirable GINOR", "18": "Étirable GINOR",
}

def build_features(payload: dict) -> pd.DataFrame:
    """
    Construit les variables nécessaires au nouveau modèle ML.
    """

    if "date_prediction" not in payload:
        raise ValueError("Le champ 'date_prediction' est obligatoire.")

    # 1. Extraction temporelle
    try:
        prediction_date = pd.to_datetime(payload["date_prediction"])
    except Exception as e:
        raise ValueError(f"Format de date invalide : {e}")

    # 2. Emballage
    emballage_id = payload.get("emballage_id")
    emballage_nom = EMBALLAGE_ID_TO_NAME.get(emballage_id)
    
    if not emballage_nom:
        # Fallback sur type_emballage envoyé par Laravel
        emballage_nom = payload.get("type_emballage", "UNKNOWN")
        print(f"DEBUG - Emballage ID {emballage_id} not in mapping, using fallback: {emballage_nom}")

    # 3. Entrepot
    entrepot_id = payload.get("entrepot_id")
    if entrepot_id is None:
        raise ValueError("Le champ 'entrepot_id' est obligatoire.")

    # Construction du dictionnaire
    # On force entrepot_source_id en float car les catégories du modèle sont [1.0, 2.0, ...]
    data = {
        "annee": int(prediction_date.year),
        "mois": int(prediction_date.month),
        "jour": int(prediction_date.day),
        "entrepot_source_id": float(entrepot_id), 
        "emballage_nom": str(emballage_nom),
    }

    # Debug print pour inspecter les entrées du modèle
    print(f"DEBUG - Model Input: {data}")

    df = pd.DataFrame([data])
    
    # S'assurer de l'ordre exact des colonnes
    df = df[EXPECTED_COLUMNS]

    return df
