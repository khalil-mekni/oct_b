from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
import joblib
import pandas as pd
import logging
from feature_builder import build_features
from typing import List, Optional
import os

# Configuration du logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("ML_Prediction_API")

app = FastAPI(title="API ML Prediction Emballage OCT - v2")

# Chargement du nouveau modèle
MODEL_PATH = "modele_prediction.pkl"

if not os.path.exists(MODEL_PATH):
    logger.error(f"Modèle non trouvé à l'emplacement : {MODEL_PATH}")
    # On ne lève pas d'exception ici pour permettre au serveur de démarrer, 
    # mais les appels API échoueront.
    model = None
else:
    try:
        model = joblib.load(MODEL_PATH)
        logger.info(f"Modèle '{MODEL_PATH}' chargé avec succès.")
    except Exception as e:
        logger.error(f"Erreur lors du chargement du modèle : {e}")
        model = None


class PredictionRequest(BaseModel):
    date_prediction: str = Field(..., description="Date au format YYYY-MM-DD")
    type_emballage: str = Field(..., description="Nom de l'emballage (utilisé pour emballage_nom)")
    
    # Champs optionnels conservés pour compatibilité avec l'ancien contrat Laravel
    emballage_id: Optional[int] = None
    entrepot_id: Optional[int] = None
    region: Optional[str] = None
    prix_unitaire: Optional[float] = 0.0
    capacite_totale: Optional[float] = 0.0
    stock_initial: Optional[float] = 0.0
    stock_final: Optional[float] = 0.0
    reception_ent: Optional[float] = 0.0
    transfert_in_cdd: Optional[float] = 0.0
    transfert_out_cdd: Optional[float] = 0.0
    contrat_actif: Optional[int] = 0
    commandes_en_cours: Optional[int] = 0
    consommation_j_1: Optional[float] = 0.0
    consommation_j_7: Optional[float] = 0.0
    consommation_j_30: Optional[float] = 0.0
    rolling_mean_7j: Optional[float] = 0.0
    rolling_mean_30j: Optional[float] = 0.0
    rolling_std_30j: Optional[float] = 0.0


def run_prediction(payload_dict: dict):
    if model is None:
        raise HTTPException(status_code=500, detail="Modèle ML non chargé sur le serveur.")

    try:
        # Préparation des features (annee, mois, emballage_nom)
        features_df = build_features(payload_dict)
        
        # Prédiction
        # Le modèle est supposé être une Pipeline scikit-learn contenant
        # le ColumnTransformer (OneHotEncoder, StandardScaler, etc.)
        prediction = model.predict(features_df)[0]
        
        # On s'assure que la prédiction n'est pas négative
        prediction = max(0.0, float(prediction))
        
        # Calcul du coût prédit (pour compatibilité API)
        prix_unitaire = float(payload_dict.get("prix_unitaire", 0.0))
        cout_predit = prediction * prix_unitaire
        
        return {
            "quantite_predite": round(prediction, 2),
            "cout_predit": round(cout_predit, 2)
        }
    except Exception as e:
        logger.error(f"Erreur lors de la prédiction : {e}")
        raise HTTPException(status_code=400, detail=str(e))


@app.post("/predict")
def predict(payload: PredictionRequest):
    return run_prediction(payload.dict())


@app.post("/predict-batch")
def predict_batch(payloads: List[PredictionRequest]):
    results = []
    logger.info(f"Received batch of {len(payloads)} payloads")
    for i, p in enumerate(payloads):
        try:
            p_dict = p.dict()
            # Log periodically or for the first few to avoid flooding but see the data
            if i < 5 or i == len(payloads) - 1:
                logger.info(f"Payload {i}: {p_dict}")
            res = run_prediction(p_dict)
            logger.info(f"Result {i}: {res}")
            results.append(res)
        except Exception as e:
            logger.error(f"Error in batch item {i}: {e}")
            results.append({"quantite_predite": 0.0, "cout_predit": 0.0})
    return results


@app.get("/health")
def health():
    return {
        "status": "ok", 
        "model_loaded": model is not None,
        "model_path": MODEL_PATH
    }
