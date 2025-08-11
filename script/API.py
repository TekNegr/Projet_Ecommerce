from fastapi import FastAPI
from fastapi.responses import JSONResponse

app = FastAPI(
    title="AI FastAPI Service",
    description="FastAPI service for executing Python AI programs",
    version="1.0.0"
)

@app.get("/health", response_class=JSONResponse)
async def health_check():
    """
    Health check endpoint to verify service connectivity
    """
    return {
        "status": "healthy",
        "message": "Python est bel et bien connecté",
        "service": "AI FastAPI Service",
        "version": "1.0.0"
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
