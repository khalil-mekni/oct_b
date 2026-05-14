from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import Optional
import joblib
import pandas as pd
import numpy as np
import os

app = FastAPI(
    title="API IA — Prédiction Quantité Emballage OCT",
    version="2.0.0",
    description="Modèle ML (Random Forest, R²=0.97) pour prédire la quantité d'emballage nécessaire"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ─── Chargement du modèle ───────────────────────────────────────────────────
MODEL_PATH = os.path.join(os.path.dirname(__file__), "model_prediction_quantite_emballage.pkl")
try:
    model = joblib.load(MODEL_PATH)
    print("✅ Modèle chargé avec succès")
except Exception as e:
    print(f"❌ Erreur chargement modèle : {e}")
    model = None

# ─── Données de référence (depuis le dataset) ───────────────────────────────
EMBALLAGES_REF = {
    1: {"emballage_type": "CARTON",  "material": "Carton ondule", "capacity_value": 25,   "capacity_unit": "KG", "min_stock": 500},
    2: {"emballage_type": "PALETTE", "material": "Plastique PEHD","capacity_value": 1000, "capacity_unit": "KG", "min_stock": 80},
    3: {"emballage_type": "SAC",     "material": "Papier kraft",   "capacity_value": 5,    "capacity_unit": "KG", "min_stock": 2000},
    4: {"emballage_type": "BIDON",   "material": "Metal etame",    "capacity_value": 20,   "capacity_unit": "L",  "min_stock": 200},
}

ENTREPOTS_REF = {
    1:  {"region": "Ben Arous",   "capacity_factor_entrepot": 1.35},
    2:  {"region": "Sfax",        "capacity_factor_entrepot": 1.20},
    3:  {"region": "Sousse",      "capacity_factor_entrepot": 1.05},
    4:  {"region": "Beja",        "capacity_factor_entrepot": 0.75},
    5:  {"region": "Gabes",       "capacity_factor_entrepot": 0.80},
    6:  {"region": "Kairouan",    "capacity_factor_entrepot": 0.85},
    7:  {"region": "Kasserine",   "capacity_factor_entrepot": 0.65},
    8:  {"region": "Gafsa",       "capacity_factor_entrepot": 0.70},
    9:  {"region": "Medenine",    "capacity_factor_entrepot": 0.70},
    10: {"region": "Medenine",    "capacity_factor_entrepot": 0.60},
    11: {"region": "Tozeur",      "capacity_factor_entrepot": 0.55},
    12: {"region": "Tataouine",   "capacity_factor_entrepot": 0.50},
    13: {"region": "Le Kef",      "capacity_factor_entrepot": 0.58},
    14: {"region": "Kebili",      "capacity_factor_entrepot": 0.48},
    15: {"region": "Siliana",     "capacity_factor_entrepot": 0.45},
    16: {"region": "Sidi Bouzid", "capacity_factor_entrepot": 0.68},
    17: {"region": "Tunis",       "capacity_factor_entrepot": 0.95},
}

SAISON_PAR_MOIS = {
    1: ("HIVER", 1), 2: ("HIVER", 1), 3: ("PRINTEMPS", 1),
    4: ("PRINTEMPS", 2), 5: ("PRINTEMPS", 2), 6: ("ETE", 2),
    7: ("ETE", 3), 8: ("ETE", 3), 9: ("AUTOMNE", 3),
    10: ("AUTOMNE", 4), 11: ("AUTOMNE", 4), 12: ("HIVER", 4),
}

# ─── Schémas ────────────────────────────────────────────────────────────────
class PredictionInput(BaseModel):
    annee: int = Field(..., example=2026)
    mois: int = Field(..., ge=1, le=12, example=12)
    emballage_id: int = Field(..., ge=1, le=4, example=1)
    entrepot_id: int = Field(..., ge=1, le=17, example=1)
    consommation_mois: float = Field(..., gt=0, example=5000.0)
    consommation_mois_precedent: Optional[float] = Field(None, example=4800.0)
    moyenne_3_mois: Optional[float] = Field(None, example=4900.0)
    stock_fin_mois: Optional[float] = Field(None, example=12000.0)
    quantite_a_commander_estimee: Optional[float] = Field(None, example=0.0)

class PredictionBatchInput(BaseModel):
    annee: int
    mois: int
    emballage_id: Optional[int] = None   # None = tous les emballages
    entrepot_id: Optional[int] = None    # None = tous les entrepôts

class PredictionOutput(BaseModel):
    quantite_predite: float
    unite: str
    emballage_id: int
    entrepot_id: int
    annee: int
    mois: int

# ─── Helper : construire la ligne feature pour le modèle ────────────────────
def build_features(
    annee: int, mois: int,
    emballage_id: int, entrepot_id: int,
    consommation_mois: float,
    consommation_mois_precedent: float,
    moyenne_3_mois: float,
    stock_fin_mois: float,
    quantite_a_commander_estimee: float,
) -> pd.DataFrame:
    emb = EMBALLAGES_REF[emballage_id]
    ent = ENTREPOTS_REF[entrepot_id]
    saison, trimestre = SAISON_PAR_MOIS[mois]

    variation = consommation_mois - consommation_mois_precedent
    taux_variation = variation / (consommation_mois_precedent + 1)
    stock_sous_min = int(stock_fin_mois < emb["min_stock"])
    ratio_capa = consommation_mois / (emb["capacity_value"] + 1)
    saison_haute = int(mois in [6, 7, 8, 11, 12])

    return pd.DataFrame([{
        "annee": annee,
        "mois": mois,
        "trimestre": trimestre,
        "saison": saison,
        "emballage_id": emballage_id,
        "emballage_type": emb["emballage_type"],
        "material": emb["material"],
        "capacity_value": emb["capacity_value"],
        "capacity_unit": emb["capacity_unit"],
        "min_stock": emb["min_stock"],
        "entrepot_id": entrepot_id,
        "region": ent["region"],
        "capacity_factor_entrepot": ent["capacity_factor_entrepot"],
        "consommation_mois": consommation_mois,
        "consommation_mois_precedent": consommation_mois_precedent,
        "moyenne_3_mois": moyenne_3_mois,
        "stock_fin_mois": stock_fin_mois,
        "quantite_a_commander_estimee": quantite_a_commander_estimee,
        "variation_consommation": variation,
        "taux_variation_consommation": taux_variation,
        "stock_sous_minimum": stock_sous_min,
        "ratio_consommation_capacite": ratio_capa,
        "saison_haute": saison_haute,
    }])

def get_defaults_from_dataset(emballage_id: int, entrepot_id: int) -> dict:
    """Retourne les moyennes historiques comme valeurs par défaut."""
    # Valeurs moyennes calculées du dataset (utilisées si non fournies)
    defaults_map = {
        (1,1):  {"conso_prec": 6300, "moy3": 6350, "stock": 40000, "cmd": 0},
        (1,2):  {"conso_prec": 5700, "moy3": 5750, "stock": 37000, "cmd": 0},
        (1,3):  {"conso_prec": 4900, "moy3": 4950, "stock": 32000, "cmd": 0},
        (2,1):  {"conso_prec": 960,  "moy3": 970,  "stock": 6000,  "cmd": 0},
        (3,1):  {"conso_prec": 1100, "moy3": 1120, "stock": 7500,  "cmd": 0},
        (4,1):  {"conso_prec": 430,  "moy3": 440,  "stock": 3200,  "cmd": 0},
    }
    # Fallback générique par emballage
    generic = {
        1: {"conso_prec": 3500, "moy3": 3500, "stock": 20000, "cmd": 0},
        2: {"conso_prec": 600,  "moy3": 600,  "stock": 4000,  "cmd": 0},
        3: {"conso_prec": 800,  "moy3": 800,  "stock": 5500,  "cmd": 0},
        4: {"conso_prec": 300,  "moy3": 300,  "stock": 2500,  "cmd": 0},
    }
    return defaults_map.get((emballage_id, entrepot_id), generic.get(emballage_id, generic[1]))

# ─── Routes ─────────────────────────────────────────────────────────────────
@app.get("/")
def home():
    return {
        "status": "ok",
        "message": "API IA — Prédiction Quantité Emballage OCT",
        "version": "2.0.0",
        "model_loaded": model is not None,
    }

@app.post("/predict", response_model=PredictionOutput)
def predict_single(data: PredictionInput):
    """
    Prédit la quantité d'emballage nécessaire pour le mois suivant.
    Seuls annee, mois, emballage_id, entrepot_id et consommation_mois sont obligatoires.
    Les autres champs sont auto-remplis avec les moyennes historiques si absents.
    """
    if model is None:
        raise HTTPException(status_code=503, detail="Modèle ML non disponible")
    if emballage_id := data.emballage_id not in EMBALLAGES_REF:
        raise HTTPException(status_code=400, detail=f"emballage_id {data.emballage_id} invalide")
    if data.entrepot_id not in ENTREPOTS_REF:
        raise HTTPException(status_code=400, detail=f"entrepot_id {data.entrepot_id} invalide")

    defaults = get_defaults_from_dataset(data.emballage_id, data.entrepot_id)

    features = build_features(
        annee=data.annee,
        mois=data.mois,
        emballage_id=data.emballage_id,
        entrepot_id=data.entrepot_id,
        consommation_mois=data.consommation_mois,
        consommation_mois_precedent=data.consommation_mois_precedent or defaults["conso_prec"],
        moyenne_3_mois=data.moyenne_3_mois or defaults["moy3"],
        stock_fin_mois=data.stock_fin_mois or defaults["stock"],
        quantite_a_commander_estimee=data.quantite_a_commander_estimee or defaults["cmd"],
    )

    prediction = float(model.predict(features)[0])

    return PredictionOutput(
        quantite_predite=round(prediction, 2),
        unite="unités",
        emballage_id=data.emballage_id,
        entrepot_id=data.entrepot_id,
        annee=data.annee,
        mois=data.mois,
    )

@app.post("/predict/batch")
def predict_batch(data: PredictionBatchInput):
    """
    Prédiction en masse :
    - Si emballage_id=null et entrepot_id=null → tous les emballages × tous les entrepôts
    - Si emballage_id fourni → tous les entrepôts pour cet emballage
    - Si entrepot_id fourni → tous les emballages pour cet entrepôt
    """
    if model is None:
        raise HTTPException(status_code=503, detail="Modèle ML non disponible")

    emballage_ids = [data.emballage_id] if data.emballage_id else list(EMBALLAGES_REF.keys())
    entrepot_ids  = [data.entrepot_id]  if data.entrepot_id  else list(ENTREPOTS_REF.keys())

    resultats = []
    for emb_id in emballage_ids:
        for ent_id in entrepot_ids:
            defaults = get_defaults_from_dataset(emb_id, ent_id)
            emb_ref = EMBALLAGES_REF[emb_id]
            ent_ref = ENTREPOTS_REF[ent_id]

            # Consommation de base estimée (capacity_factor * base de référence)
            base_conso = defaults["conso_prec"] * ent_ref["capacity_factor_entrepot"] / 0.75

            features = build_features(
                annee=data.annee,
                mois=data.mois,
                emballage_id=emb_id,
                entrepot_id=ent_id,
                consommation_mois=base_conso,
                consommation_mois_precedent=defaults["conso_prec"],
                moyenne_3_mois=defaults["moy3"],
                stock_fin_mois=defaults["stock"],
                quantite_a_commander_estimee=defaults["cmd"],
            )
            prediction = float(model.predict(features)[0])
            resultats.append({
                "emballage_id": emb_id,
                "entrepot_id": ent_id,
                "annee": data.annee,
                "mois": data.mois,
                "quantite_predite": round(prediction, 2),
                "unite": "unités",
            })

    return {"predictions": resultats, "total": len(resultats)}