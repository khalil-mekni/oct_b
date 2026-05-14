from fastapi import FastAPI
from pydantic import BaseModel
import joblib
from feature_builder import build_features

app = FastAPI(title="API ML Prediction Emballage OCT")

model = joblib.load("best_model_prediction_emballage_oct.pkl")


class PredictionRequest(BaseModel):
    date_prediction: str

    emballage_id: int
    entrepot_id: int

    type_emballage: str
    region: str

    prix_unitaire: float
    capacite_totale: float

    stock_initial: float
    stock_final: float

    reception_ent: float = 0
    transfert_in_cdd: float = 0
    transfert_out_cdd: float = 0

    contrat_actif: int = 0
    commandes_en_cours: int = 0

    consommation_j_1: float = 0
    consommation_j_7: float = 0
    consommation_j_30: float = 0

    rolling_mean_7j: float = 0
    rolling_mean_30j: float = 0
    rolling_std_30j: float = 0


@app.post("/predict")
def predict(payload: PredictionRequest):
    features = build_features(payload.dict())

    prediction = model.predict(features)[0]
    prediction = max(0, float(prediction))

    return {
        "quantite_predite": round(prediction, 2)
    }