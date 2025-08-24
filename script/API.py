from fastapi import FastAPI
from fastapi.responses import JSONResponse
from joblib import load
import pandas as pd
from typing import List
import numpy as np
from pydantic import BaseModel
import os

# ================================
# Charger le modèle de pipeline
# ================================
rf_pipeline = load("rf_pipeline.pkl")

# ================================
# Schéma des données d'entrée
# ================================
class InputData(BaseModel):
    total_price: float
    total_items: int
    total_payment: float
    payment_count: int
    distance: float
    delivery_time: int
    product_category_name: str  # catégorie produit

# ================================
# Définir l'application FastAPI
# ================================
app = FastAPI(
    title="AI FastAPI Service",
    description="FastAPI service for Random Forest pipeline prediction",
    version="1.0.0"
)

# ================================
# Endpoint santé
# ================================
@app.get("/health", response_class=JSONResponse)
async def health_check():
    return {
        "status": "healthy",
        "message": "Python est bel et bien connecté",
        "service": "AI FastAPI Service",
        "version": "1.0.0"
    }

# ================================
# Endpoint : prédiction simple
# ================================
@app.post("/predict", response_class=JSONResponse)
async def predict(data: InputData):
    df = pd.DataFrame([data.dict()])
    prediction = rf_pipeline.predict(df)[0]
    confidence = rf_pipeline.predict_proba(df)[0].max()

    return {
        "status": "success",
        "message": "Prediction effectuée",
        "data": {
            "prediction": int(prediction),
            "confidence": float(confidence)
        }
    }

# ================================
# Endpoint : prédiction en lot
# ================================
@app.post("/batch_predict", response_class=JSONResponse)
async def batch_predict(data: List[InputData]):
    df = pd.DataFrame([d.dict() for d in data])
    predictions = rf_pipeline.predict(df).tolist()
    confidences = rf_pipeline.predict_proba(df).max(axis=1).tolist()

    results = [
        {"prediction": int(pred), "confidence": float(conf)}
        for pred, conf in zip(predictions, confidences)
    ]

    return {
        "status": "success",
        "message": "Batch prediction effectuée",
        "data": results
    }

# ================================
# Lancer l'API
# ================================
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)
