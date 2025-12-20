#!/bin/bash

# ChromaDB Knowledge Base Service Startup Script
# Usage: ./start.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
ENV_FILE="$PROJECT_ROOT/.env"

# Default Python path
PYTHON="/home/wkd/miniconda3/envs/py39/bin/python"

# Read Python path from .env if exists
if [ -f "$ENV_FILE" ]; then
    ENV_PYTHON=$(grep -E "^CHROMA_PYTHON_PATH=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    if [ -n "$ENV_PYTHON" ]; then
        PYTHON="$ENV_PYTHON"
    fi
fi

cd "$SCRIPT_DIR"

echo "============================================"
echo "  ChromaDB Knowledge Base Service"
echo "============================================"
echo "Python: $PYTHON"
echo "Working directory: $SCRIPT_DIR"
echo "Project root: $PROJECT_ROOT"
echo ""

# Check if Python exists
if [ ! -f "$PYTHON" ]; then
    echo "Error: Python not found at $PYTHON"
    echo "Please update CHROMA_PYTHON_PATH in $ENV_FILE"
    exit 1
fi

echo "Starting service..."
echo "Press Ctrl+C to stop"
echo ""

# Start the service
exec "$PYTHON" main.py
