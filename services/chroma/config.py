"""
Configuration for ChromaDB Knowledge Base Service
Reads settings from environment variables or uses defaults
"""
import os
from pathlib import Path

# Load .env file if python-dotenv is available
try:
    from dotenv import load_dotenv
    # Find .env file in project root
    env_path = Path(__file__).parent.parent.parent / '.env'
    if env_path.exists():
        load_dotenv(env_path)
except ImportError:
    pass

# Base paths
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(os.path.dirname(BASE_DIR))
STORAGE_DIR = os.path.join(PROJECT_ROOT, "storage", "knowledge")

# ChromaDB settings
CHROMA_PERSIST_DIR = os.path.join(STORAGE_DIR, "chroma_db")
CHROMA_COLLECTION_NAME = "knowledge_base"

# File upload settings
UPLOAD_DIR = os.path.join(STORAGE_DIR, "uploads")
ALLOWED_EXTENSIONS = {".pdf", ".txt", ".doc", ".docx", ".md"}

# Get max file size from env (in MB), default to 5MB
_max_size_mb = int(os.getenv('CHROMA_MAX_FILE_SIZE', '5'))
MAX_FILE_SIZE = _max_size_mb * 1024 * 1024

MAX_FILENAME_LENGTH = 100

# Server settings
HOST = os.getenv('CHROMA_SERVICE_HOST', '127.0.0.1')
PORT = int(os.getenv('CHROMA_SERVICE_PORT', '4001'))

# Embedding model - uses local ONNX model from cache
EMBEDDING_MODEL = "all-MiniLM-L6-v2"

# Text processing settings
CHUNK_SIZE = 500
CHUNK_OVERLAP = 50
