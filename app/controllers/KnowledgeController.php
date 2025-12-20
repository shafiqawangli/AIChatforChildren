<?php
/**
 * Knowledge Base Controller
 * Handles API requests for knowledge base management
 * Proxies requests to the ChromaDB Python service
 */

namespace App\Controllers;

class KnowledgeController
{
    private string $serviceUrl;
    private int $requestTimeout = 120;

    public function __construct()
    {
        $host = $_ENV['CHROMA_SERVICE_HOST'] ?? '127.0.0.1';
        $port = $_ENV['CHROMA_SERVICE_PORT'] ?? '4001';
        $this->serviceUrl = "http://{$host}:{$port}";
    }

    /**
     * Get the maximum upload file size in bytes
     */
    private function getMaxUploadSize(): int
    {
        $phpLimit = $this->parseSize(ini_get('upload_max_filesize'));
        $postLimit = $this->parseSize(ini_get('post_max_size'));
        $envLimit = $this->parseSize(($_ENV['CHROMA_MAX_FILE_SIZE'] ?? '2') . 'M');

        return min($phpLimit, $postLimit, $envLimit);
    }

    /**
     * Parse size string (e.g., "2M", "512K") to bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = (int) $size;

        switch ($unit) {
            case 'G':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'M':
                $value *= 1024 * 1024;
                break;
            case 'K':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Format bytes to human readable size
     */
    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . 'GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . 'KB';
        }
        return $bytes . 'B';
    }

    /**
     * Check if ChromaDB service is running
     */
    private function isServiceRunning(): bool
    {
        $ch = curl_init($this->serviceUrl . '/api/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Make request to ChromaDB service
     */
    private function proxyRequest(string $endpoint, string $method = 'GET', array $data = null, array $files = null): array
    {
        if (!$this->isServiceRunning()) {
            return [
                'success' => false,
                'error' => 'Knowledge base service is not running. Please start the ChromaDB service first.',
                'code' => 503
            ];
        }

        $url = $this->serviceUrl . $endpoint;
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->requestTimeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        switch (strtoupper($method)) {
            case 'POST':
                $options[CURLOPT_POST] = true;
                if ($files) {
                    $postData = [];
                    foreach ($files as $key => $file) {
                        $postData[$key] = new \CURLFile(
                            $file['tmp_name'],
                            $file['type'],
                            $file['name']
                        );
                    }
                    $options[CURLOPT_POSTFIELDS] = $postData;
                } elseif ($data) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data);
                    $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
                }
                break;

            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if ($data) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data);
                    $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
                }
                break;

            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => 'Failed to connect to knowledge base service: ' . $error,
                'code' => 500
            ];
        }

        $decoded = json_decode($response, true);
        if ($decoded === null && $response !== 'null') {
            return [
                'success' => false,
                'error' => 'Invalid response from service',
                'code' => 500
            ];
        }

        return [
            'success' => true,
            'data' => $decoded,
            'code' => $httpCode
        ];
    }

    /**
     * Handle file upload
     */
    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $this->verifyCsrf();

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $maxSize = $this->formatSize($this->getMaxUploadSize());
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => "File exceeds server limit ({$maxSize})",
                UPLOAD_ERR_FORM_SIZE => "File exceeds form limit ({$maxSize})",
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            ];
            $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $errorMsg = $errorMessages[$errorCode] ?? 'Unknown upload error';
            $this->jsonResponse(['success' => false, 'error' => $errorMsg], 400);
        }

        $result = $this->proxyRequest('/api/upload', 'POST', null, ['file' => $_FILES['file']]);

        if (!$result['success']) {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['code']);
        }

        $this->jsonResponse($result['data'], $result['code']);
    }

    /**
     * List all files
     */
    public function listFiles(): void
    {
        $result = $this->proxyRequest('/api/files');

        if (!$result['success']) {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['code']);
        }

        $this->jsonResponse($result['data'], $result['code']);
    }

    /**
     * Delete a file
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $this->verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);
        $fileId = $input['file_id'] ?? null;

        if (!$fileId) {
            $this->jsonResponse(['success' => false, 'error' => 'File ID is required'], 400);
        }

        $result = $this->proxyRequest('/api/files/' . urlencode($fileId), 'DELETE');

        if (!$result['success']) {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['code']);
        }

        $this->jsonResponse($result['data'], $result['code']);
    }

    /**
     * Rename a file
     */
    public function rename(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $this->verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);
        $fileId = $input['file_id'] ?? null;
        $newName = $input['new_name'] ?? null;

        if (!$fileId || !$newName) {
            $this->jsonResponse(['success' => false, 'error' => 'File ID and new name are required'], 400);
        }

        $result = $this->proxyRequest(
            '/api/files/' . urlencode($fileId) . '/rename?new_name=' . urlencode($newName),
            'PUT'
        );

        if (!$result['success']) {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['code']);
        }

        $this->jsonResponse($result['data'], $result['code']);
    }

    /**
     * Search knowledge base
     */
    public function search(): void
    {
        $query = $_GET['query'] ?? '';
        $limit = (int)($_GET['limit'] ?? 5);

        if (empty($query)) {
            $this->jsonResponse(['success' => false, 'error' => 'Query is required'], 400);
        }

        $result = $this->proxyRequest(
            '/api/search?query=' . urlencode($query) . '&limit=' . $limit
        );

        if (!$result['success']) {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['code']);
        }

        $this->jsonResponse($result['data'], $result['code']);
    }

    /**
     * Get context for AI chat
     */
    public function getContext(): void
    {
        $query = $_GET['query'] ?? '';
        $limit = (int)($_GET['limit'] ?? 3);

        if (empty($query)) {
            $this->jsonResponse(['context' => '', 'sources' => []], 200);
        }

        $result = $this->proxyRequest(
            '/api/context?query=' . urlencode($query) . '&limit=' . $limit
        );

        if (!$result['success']) {
            $this->jsonResponse(['context' => '', 'sources' => []], 200);
        }

        $this->jsonResponse($result['data'], $result['code']);
    }

    /**
     * Check service health and return config
     */
    public function health(): void
    {
        $isRunning = $this->isServiceRunning();
        $maxSize = $this->getMaxUploadSize();

        $this->jsonResponse([
            'success' => true,
            'service_running' => $isRunning,
            'message' => $isRunning
                ? 'Knowledge base service is running'
                : 'Knowledge base service is not running',
            'config' => [
                'max_file_size' => $maxSize,
                'max_file_size_formatted' => $this->formatSize($maxSize),
                'python_path' => $_ENV['CHROMA_PYTHON_PATH'] ?? '/home/wkd/miniconda3/envs/py39/bin/python',
                'service_host' => $_ENV['CHROMA_SERVICE_HOST'] ?? '127.0.0.1',
                'service_port' => $_ENV['CHROMA_SERVICE_PORT'] ?? '4001',
            ]
        ], 200);
    }

    /**
     * Verify CSRF token
     */
    private function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$token || !isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
    }
}
