# ML_prediction/feature_builder.py

import pandas as pd
import numpy as np


EXPECTED_COLUMNS = [
    "annee",
    "mois",
    "jour",
    "jour_semaine",
    "is_weekend",
    "trimestre",
    "emballage_id",
    "entrepot_id",
    "prix_unitaire",
    "capacite_totale",
    "stock_initial",
    "reception_ent",
    "transfert_in_cdd",
    "transfert_out_cdd",
    "stock_final",
    "taux_occupation",
    "contrat_actif",
    "commandes_en_cours",
    "consommation_j_1",
    "consommation_j_7",
    "consommation_j_30",
    "rolling_mean_7j",
    "rolling_mean_30j",
    "rolling_std_30j",
    "type_emballage",
    "region",
]


def get_quarter(month: int) -> int:
    return ((month - 1) // 3) + 1


def safe_float(value, default=0.0) -> float:
    if value is None or value == "":
        return default

    try:
        return float(value)
    except (ValueError, TypeError):
        return default


def safe_int(value, default=0) -> int:
    if value is None or value == "":
        return default

    try:
        return int(value)
    except (ValueError, TypeError):
        return default


def build_features(payload: dict) -> pd.DataFrame:
    """
    Construit les variables nécessaires au modèle ML.

    Le payload vient de Laravel.
    Il doit contenir les informations de l'emballage, entrepôt, stock
    et historique de consommation.

    La sortie doit garder exactement les mêmes colonnes que celles utilisées
    pendant l'entraînement du modèle.
    """

    if "date_prediction" not in payload:
        raise ValueError("date_prediction est obligatoire.")

    if "emballage_id" not in payload:
        raise ValueError("emballage_id est obligatoire.")

    if "entrepot_id" not in payload:
        raise ValueError("entrepot_id est obligatoire.")

    if "type_emballage" not in payload:
        raise ValueError("type_emballage est obligatoire.")

    if "region" not in payload:
        raise ValueError("region est obligatoire.")

    prediction_date = pd.to_datetime(payload["date_prediction"])

    stock_final = safe_float(payload.get("stock_final"), 0.0)
    capacite_totale = safe_float(payload.get("capacite_totale"), 1.0)

    if capacite_totale <= 0:
        capacite_totale = 1.0

    taux_occupation = stock_final / capacite_totale

    data = {
        "annee": prediction_date.year,
        "mois": prediction_date.month,
        "jour": prediction_date.day,
        "jour_semaine": prediction_date.dayofweek,
        "is_weekend": 1 if prediction_date.dayofweek >= 5 else 0,
        "trimestre": get_quarter(prediction_date.month),

        "emballage_id": safe_int(payload.get("emballage_id")),
        "entrepot_id": safe_int(payload.get("entrepot_id")),

        "prix_unitaire": safe_float(payload.get("prix_unitaire")),
        "capacite_totale": capacite_totale,

        "stock_initial": safe_float(payload.get("stock_initial"), stock_final),
        "reception_ent": safe_float(payload.get("reception_ent")),

        "transfert_in_cdd": safe_float(payload.get("transfert_in_cdd")),
        "transfert_out_cdd": safe_float(payload.get("transfert_out_cdd")),

        "stock_final": stock_final,
        "taux_occupation": taux_occupation,

        "contrat_actif": safe_int(payload.get("contrat_actif")),
        "commandes_en_cours": safe_int(payload.get("commandes_en_cours")),

        "consommation_j_1": safe_float(payload.get("consommation_j_1")),
        "consommation_j_7": safe_float(payload.get("consommation_j_7")),
        "consommation_j_30": safe_float(payload.get("consommation_j_30")),

        "rolling_mean_7j": safe_float(payload.get("rolling_mean_7j")),
        "rolling_mean_30j": safe_float(payload.get("rolling_mean_30j")),
        "rolling_std_30j": safe_float(payload.get("rolling_std_30j")),

        "type_emballage": str(payload.get("type_emballage")),
        "region": str(payload.get("region")),
    }

    df = pd.DataFrame([data])

    df = df[EXPECTED_COLUMNS]

    return df