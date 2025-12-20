"""
ChromaDB Knowledge Base Service
FastAPI service for managing document uploads and vector search
"""
import os
import uuid
import shutil
from datetime import datetime
from typing import List, Optional

from fastapi import FastAPI, UploadFile, File, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import chromadb
from chromadb.config import Settings

import config

# Initialize FastAPI app
app = FastAPI(
    title="Knowledge Base Service",
    description="ChromaDB-powered knowledge base for AI Chat",
    version="1.0.0"
)

# CORS middleware for PHP frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Ensure directories exist
os.makedirs(config.UPLOAD_DIR, exist_ok=True)
os.makedirs(config.CHROMA_PERSIST_DIR, exist_ok=True)

# Initialize ChromaDB client
chroma_client = chromadb.PersistentClient(
    path=config.CHROMA_PERSIST_DIR,
    settings=Settings(anonymized_telemetry=False)
)

# Get or create collection
collection = chroma_client.get_or_create_collection(
    name=config.CHROMA_COLLECTION_NAME,
    metadata={"description": "Knowledge base documents"}
)


# Response models
class FileInfo(BaseModel):
    id: str
    filename: str
    original_filename: str
    file_type: str
    file_size: int
    upload_time: str
    chunk_count: int


class UploadResponse(BaseModel):
    success: bool
    message: str
    file: Optional[FileInfo] = None


class FileListResponse(BaseModel):
    success: bool
    files: List[FileInfo]
    total: int


class SearchResult(BaseModel):
    document: str
    metadata: dict
    distance: float


class SearchResponse(BaseModel):
    success: bool
    results: List[SearchResult]
    query: str


class DeleteResponse(BaseModel):
    success: bool
    message: str


def get_file_extension(filename: str) -> str:
    """Get file extension in lowercase"""
    return os.path.splitext(filename)[1].lower()


def validate_filename(filename: str) -> tuple:
    """Validate filename length and extension"""
    if len(filename) > config.MAX_FILENAME_LENGTH:
        return False, f"Filename too long. Maximum {config.MAX_FILENAME_LENGTH} characters allowed."

    ext = get_file_extension(filename)
    if ext not in config.ALLOWED_EXTENSIONS:
        return False, f"File type not allowed. Allowed types: {', '.join(config.ALLOWED_EXTENSIONS)}"

    return True, ""


def extract_text_from_file(file_path: str, file_type: str) -> str:
    """Extract text content from uploaded file"""
    text = ""

    try:
        if file_type == ".txt" or file_type == ".md":
            # Try multiple encodings for text files
            encodings = ['utf-8', 'gbk', 'gb2312', 'latin-1']
            for encoding in encodings:
                try:
                    with open(file_path, 'r', encoding=encoding) as f:
                        text = f.read()
                    break
                except UnicodeDecodeError:
                    continue

        elif file_type == ".pdf":
            try:
                from PyPDF2 import PdfReader
                reader = PdfReader(file_path)
                for page in reader.pages:
                    page_text = page.extract_text()
                    if page_text:
                        text += page_text + "\n"
            except Exception as e:
                raise HTTPException(status_code=400, detail=f"Failed to parse PDF: {str(e)}")

        elif file_type in [".doc", ".docx"]:
            try:
                import docx
                doc = docx.Document(file_path)
                for para in doc.paragraphs:
                    text += para.text + "\n"
            except ImportError:
                raise HTTPException(status_code=400, detail="python-docx not installed for Word file support")
            except Exception as e:
                raise HTTPException(status_code=400, detail=f"Failed to parse Word document: {str(e)}")

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Failed to extract text: {str(e)}")

    return text.strip()


def chunk_text(text: str, chunk_size: int = config.CHUNK_SIZE, overlap: int = config.CHUNK_OVERLAP) -> List[str]:
    """Split text into overlapping chunks"""
    if not text:
        return []

    chunks = []
    start = 0
    text_len = len(text)

    while start < text_len:
        end = start + chunk_size
        chunk = text[start:end]

        # Try to break at sentence boundary
        if end < text_len:
            last_period = chunk.rfind('.')
            last_newline = chunk.rfind('\n')
            break_point = max(last_period, last_newline)
            if break_point > chunk_size // 2:
                chunk = text[start:start + break_point + 1]
                end = start + break_point + 1

        chunks.append(chunk.strip())
        start = end - overlap

    return [c for c in chunks if c]


def get_file_metadata() -> dict:
    """Get metadata for all uploaded files from ChromaDB"""
    files = {}

    try:
        # Get all documents from collection
        results = collection.get()

        if results and results['metadatas']:
            for metadata in results['metadatas']:
                file_id = metadata.get('file_id')
                if file_id and file_id not in files:
                    files[file_id] = {
                        'id': file_id,
                        'filename': metadata.get('filename', ''),
                        'original_filename': metadata.get('original_filename', ''),
                        'file_type': metadata.get('file_type', ''),
                        'file_size': metadata.get('file_size', 0),
                        'upload_time': metadata.get('upload_time', ''),
                        'chunk_count': 0
                    }
                if file_id:
                    files[file_id]['chunk_count'] += 1

    except Exception as e:
        print(f"Error getting file metadata: {e}")

    return files


@app.get("/")
async def root():
    """Health check endpoint"""
    return {"status": "ok", "service": "Knowledge Base API", "version": "1.0.0"}


@app.get("/api/health")
async def health_check():
    """Health check endpoint"""
    return {"status": "healthy", "timestamp": datetime.now().isoformat()}


@app.post("/api/upload", response_model=UploadResponse)
async def upload_file(file: UploadFile = File(...)):
    """Upload a file to the knowledge base"""

    # Validate filename
    is_valid, error_msg = validate_filename(file.filename)
    if not is_valid:
        raise HTTPException(status_code=400, detail=error_msg)

    file_type = get_file_extension(file.filename)
    file_id = str(uuid.uuid4())

    # Generate safe filename
    safe_filename = f"{file_id}{file_type}"
    file_path = os.path.join(config.UPLOAD_DIR, safe_filename)

    try:
        # Save file
        content = await file.read()
        file_size = len(content)

        if file_size > config.MAX_FILE_SIZE:
            raise HTTPException(
                status_code=400,
                detail=f"File too large. Maximum size is {config.MAX_FILE_SIZE // (1024*1024)}MB"
            )

        with open(file_path, 'wb') as f:
            f.write(content)

        # Extract text
        text = extract_text_from_file(file_path, file_type)

        if not text:
            os.remove(file_path)
            raise HTTPException(status_code=400, detail="Could not extract text from file")

        # Chunk text
        chunks = chunk_text(text)

        if not chunks:
            os.remove(file_path)
            raise HTTPException(status_code=400, detail="File contains no processable text")

        # Store in ChromaDB
        upload_time = datetime.now().isoformat()

        chunk_ids = [f"{file_id}_chunk_{i}" for i in range(len(chunks))]
        metadatas = [{
            "file_id": file_id,
            "filename": safe_filename,
            "original_filename": file.filename,
            "file_type": file_type,
            "file_size": file_size,
            "upload_time": upload_time,
            "chunk_index": i
        } for i in range(len(chunks))]

        collection.add(
            documents=chunks,
            ids=chunk_ids,
            metadatas=metadatas
        )

        file_info = FileInfo(
            id=file_id,
            filename=safe_filename,
            original_filename=file.filename,
            file_type=file_type,
            file_size=file_size,
            upload_time=upload_time,
            chunk_count=len(chunks)
        )

        return UploadResponse(
            success=True,
            message=f"File uploaded successfully. Created {len(chunks)} text chunks.",
            file=file_info
        )

    except HTTPException:
        raise
    except Exception as e:
        # Cleanup on error
        if os.path.exists(file_path):
            os.remove(file_path)
        raise HTTPException(status_code=500, detail=f"Upload failed: {str(e)}")


@app.get("/api/files", response_model=FileListResponse)
async def list_files():
    """List all uploaded files"""
    files_dict = get_file_metadata()
    files = list(files_dict.values())

    # Sort by upload time (newest first)
    files.sort(key=lambda x: x['upload_time'], reverse=True)

    return FileListResponse(
        success=True,
        files=[FileInfo(**f) for f in files],
        total=len(files)
    )


@app.delete("/api/files/{file_id}", response_model=DeleteResponse)
async def delete_file(file_id: str):
    """Delete a file from the knowledge base"""

    try:
        # Get file info first
        results = collection.get(where={"file_id": file_id})

        if not results or not results['ids']:
            raise HTTPException(status_code=404, detail="File not found")

        # Get filename for deletion
        filename = results['metadatas'][0].get('filename', '')
        file_path = os.path.join(config.UPLOAD_DIR, filename)

        # Delete from ChromaDB
        collection.delete(ids=results['ids'])

        # Delete physical file
        if os.path.exists(file_path):
            os.remove(file_path)

        return DeleteResponse(success=True, message="File deleted successfully")

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Delete failed: {str(e)}")


@app.put("/api/files/{file_id}/rename", response_model=UploadResponse)
async def rename_file(file_id: str, new_name: str = Query(..., description="New filename")):
    """Rename a file in the knowledge base"""

    # Validate new name
    if len(new_name) > config.MAX_FILENAME_LENGTH:
        raise HTTPException(
            status_code=400,
            detail=f"Filename too long. Maximum {config.MAX_FILENAME_LENGTH} characters."
        )

    try:
        # Get all chunks for this file
        results = collection.get(where={"file_id": file_id})

        if not results or not results['ids']:
            raise HTTPException(status_code=404, detail="File not found")

        # Update metadata for all chunks
        for i, doc_id in enumerate(results['ids']):
            old_metadata = results['metadatas'][i]
            new_metadata = {**old_metadata, 'original_filename': new_name}

            # ChromaDB requires delete and re-add to update metadata
            collection.update(
                ids=[doc_id],
                metadatas=[new_metadata]
            )

        # Return updated file info
        file_info = FileInfo(
            id=file_id,
            filename=results['metadatas'][0].get('filename', ''),
            original_filename=new_name,
            file_type=results['metadatas'][0].get('file_type', ''),
            file_size=results['metadatas'][0].get('file_size', 0),
            upload_time=results['metadatas'][0].get('upload_time', ''),
            chunk_count=len(results['ids'])
        )

        return UploadResponse(
            success=True,
            message="File renamed successfully",
            file=file_info
        )

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Rename failed: {str(e)}")


@app.get("/api/search", response_model=SearchResponse)
async def search_knowledge(
    query: str = Query(..., description="Search query"),
    limit: int = Query(5, description="Maximum number of results")
):
    """Search the knowledge base"""

    if not query.strip():
        raise HTTPException(status_code=400, detail="Query cannot be empty")

    try:
        results = collection.query(
            query_texts=[query],
            n_results=min(limit, 20)
        )

        search_results = []
        if results and results['documents'] and results['documents'][0]:
            for i, doc in enumerate(results['documents'][0]):
                search_results.append(SearchResult(
                    document=doc,
                    metadata=results['metadatas'][0][i] if results['metadatas'] else {},
                    distance=results['distances'][0][i] if results['distances'] else 0
                ))

        return SearchResponse(
            success=True,
            results=search_results,
            query=query
        )

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Search failed: {str(e)}")


@app.get("/api/context")
async def get_context(
    query: str = Query(..., description="Query to get context for"),
    limit: int = Query(3, description="Number of context chunks")
):
    """Get relevant context for a query (used by AI chat)"""

    if not query.strip():
        return {"context": "", "sources": []}

    try:
        results = collection.query(
            query_texts=[query],
            n_results=limit
        )

        context_parts = []
        sources = []

        if results and results['documents'] and results['documents'][0]:
            for i, doc in enumerate(results['documents'][0]):
                context_parts.append(doc)
                if results['metadatas'] and results['metadatas'][0]:
                    sources.append(results['metadatas'][0][i].get('original_filename', 'Unknown'))

        return {
            "context": "\n\n".join(context_parts),
            "sources": list(set(sources))
        }

    except Exception as e:
        return {"context": "", "sources": [], "error": str(e)}


if __name__ == "__main__":
    import uvicorn
    print(f"Starting Knowledge Base Service on http://{config.HOST}:{config.PORT}")
    print(f"Upload directory: {config.UPLOAD_DIR}")
    print(f"ChromaDB directory: {config.CHROMA_PERSIST_DIR}")
    uvicorn.run(app, host=config.HOST, port=config.PORT)
