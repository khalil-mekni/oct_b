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
    # Mapping par ID (prioritaire et plus robuste)
    1: "TVSuperieur 100 G", "1": "TVSuperieur 100 G",
    2: "TVSuperieur 250 G", "2": "TVSuperieur 250 G",
    5: "TNExtra Plus 100 G", "5": "TNExtra Plus 100 G",
    6: "TNExtra 250 G", "6": "TNExtra 250 G",
    9: "Riz Etuvé", "9": "Riz Etuvé",
    10: "RIz Basmati", "10": "RIz Basmati",
    11: "Sucre Blanc", "11": "Sucre Blanc",

    # Mapping par Nom (Fallback si l'ID ne correspond pas)
    "Thé Vert Supérieur 100g": "TVSuperieur 100 G",
    "Thé Vert Supérieur 250g": "TVSuperieur 250 G",
    "Thé Noir Extra Plus 100g": "TNExtra Plus 100 G",
    "Thé Noir Extra 250g": "TNExtra 250 G",
    "Carton Riz Étuvé": "Riz Etuvé",
    "Complexe Riz Basmati": "RIz Basmati",
    "Carton Sucre Blanc": "Sucre Blanc",
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

    df = pd.DataFrame([data])
    
    # S'assurer de l'ordre exact des colonnes
    df = df[EXPECTED_COLUMNS]

    return df
