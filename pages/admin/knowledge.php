<?php

use Utils\Helper;

$user = $_SESSION['user'];
$csrfToken = Helper::generateCsrfToken();
$baseUrl = Helper::url('');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Base Management - Admin</title>

    <link rel="stylesheet" href="<?php echo Helper::url('assets/css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ===== KNOWLEDGE BASE ===== */
        .kb-card {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .08);
        }

        .kb-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .kb-header h2 {
            font-size: 22px;
            color: #2c3e50;
        }

        .kb-header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .primary-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .primary-btn:hover {
            background: #2980b9;
        }

        .primary-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .search-input {
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #ddd;
            width: 260px;
        }

        /* Service Status */
        .service-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .service-status.online {
            background: #d4edda;
            color: #155724;
        }

        .service-status.offline {
            background: #f8d7da;
            color: #721c24;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.online {
            background: #28a745;
            animation: pulse 2s infinite;
        }

        .status-dot.offline {
            background: #dc3545;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(40, 167, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            text-align: left;
        }

        tr:hover {
            background: #fafafa;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .pdf {
            background: #fdecea;
            color: #c0392b;
        }

        .txt {
            background: #e8f1fd;
            color: #1a73e8;
        }

        .md {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .doc,
        .docx {
            background: #e3f2fd;
            color: #1565c0;
        }

        .other {
            background: #eee;
            color: #555;
        }

        .actions button {
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 6px;
        }

        .rename-btn {
            background: #eef4ff;
            color: #1a73e8;
        }

        .rename-btn:hover {
            background: #d4e4ff;
        }

        .delete-btn {
            background: #ffeded;
            color: #c0392b;
        }

        .delete-btn:hover {
            background: #ffd6d6;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #bdc3c7;
        }

        .empty-state p {
            font-size: 16px;
            margin: 0;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* File size and chunks */
        .file-meta {
            font-size: 12px;
            color: #7f8c8d;
        }

        .chunk-badge {
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 6px;
        }

        /* ===== MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            width: 520px;
            max-width: 90vw;
            padding: 24px;
            border-radius: 12px;
        }

        .modal-content h3 {
            margin: 0 0 16px 0;
            color: #2c3e50;
        }

        .upload-box {
            border: 2px dashed #bbb;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            margin: 16px 0;
            transition: all 0.2s;
        }

        .upload-box:hover {
            border-color: #3498db;
            background: #f8fbff;
        }

        .upload-box.dragover {
            border-color: #3498db;
            background: #eef6ff;
        }

        .upload-box i {
            font-size: 36px;
            color: #bdc3c7;
            margin-bottom: 12px;
            display: block;
        }

        .upload-box p {
            margin: 0;
            color: #7f8c8d;
        }

        .upload-box .allowed-types {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 8px;
        }

        input[type=file] {
            display: none;
        }

        .file-row {
            background: #f8f9fa;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .file-row .file-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 12px;
        }

        .file-row .file-size {
            color: #7f8c8d;
            font-size: 12px;
            margin-right: 12px;
        }

        .file-row .file-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-row .file-status.uploading {
            color: #3498db;
        }

        .file-row .file-status.success {
            color: #27ae60;
        }

        .file-row .file-status.error {
            color: #e74c3c;
        }

        .remove-file {
            color: #c0392b;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .remove-file:hover {
            background: #ffeded;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #eee;
        }

        .modal-footer button {
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .cancel-btn {
            background: #f5f5f5;
            border: 1px solid #ddd;
            color: #666;
        }

        .cancel-btn:hover {
            background: #eee;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Filename warning */
        .filename-warning {
            background: #fff3cd;
            color: #856404;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <div class="admin-layout">

        <!-- ===== SIDEBAR ===== -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
                <div class="admin-info">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-details">
                        <div class="admin-name"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div class="admin-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="<?php echo Helper::url('admin-dashboard'); ?>" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo Helper::url('admin/prompts'); ?>" class="nav-link">
                            <i class="fas fa-edit"></i> Prompt Templates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo Helper::url('admin/users'); ?>" class="nav-link">
                            <i class="fas fa-users"></i> User Management
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="<?php echo Helper::url('admin/knowledge'); ?>" class="nav-link">
                            <i class="fas fa-database"></i> Knowledge Base
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo Helper::url('admin/profile'); ?>" class="nav-link">
                            <i class="fas fa-user-cog"></i> Profile Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo Helper::url('logout'); ?>" class="nav-link logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- ===== MAIN CONTENT ===== -->
        <div class="main-content">
            <div class="content-header">
                <h1>Knowledge Base Management</h1>
                <p class="breadcrumb">Upload and manage AI knowledge documents</p>
            </div>

            <div class="content-section">
                <!-- Service Status Alert -->
                <div id="serviceAlert" class="alert alert-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Knowledge base service is not running. Please start the ChromaDB service to enable file uploads.</span>
                </div>

                <div class="kb-card">
                    <div class="kb-header">
                        <div class="kb-header-actions">
                            <button id="uploadBtn" class="primary-btn" onclick="openModal()">
                                <i class="fas fa-upload"></i> Upload Files
                            </button>
                            <div id="serviceStatus" class="service-status offline">
                                <span class="status-dot offline"></span>
                                <span>Checking...</span>
                            </div>
                        </div>
                        <input class="search-input" placeholder="Search files..." onkeyup="searchFiles(this.value)">
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Uploaded At</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="fileTable">
                            <tr class="loading-state">
                                <td colspan="5">
                                    <span class="spinner"></span>Loading files...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== UPLOAD MODAL ===== -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h3><i class="fas fa-cloud-upload-alt"></i> Upload Files</h3>
            <div class="upload-box" id="uploadBox">
                <i class="fas fa-file-upload"></i>
                <p>Drag & drop files or click to browse</p>
                <p class="allowed-types" id="allowedTypes">Allowed: PDF, TXT, DOC, DOCX, MD (max <span id="maxSizeText">2MB</span>)</p>
                <input type="file" id="fileInput" multiple accept=".pdf,.txt,.doc,.docx,.md">
            </div>
            <div id="selectedFiles"></div>
            <div id="filenameWarning" class="filename-warning" style="display: none;">
                <i class="fas fa-exclamation-circle"></i>
                <span>Some filenames exceed 100 characters and may be truncated.</span>
            </div>
            <div class="modal-footer">
                <button class="cancel-btn" onclick="closeModal()">Cancel</button>
                <button id="uploadFilesBtn" class="primary-btn" onclick="uploadFiles()" disabled>
                    <i class="fas fa-upload"></i> Upload
                </button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo $baseUrl; ?>';
        const CSRF_TOKEN = '<?php echo $csrfToken; ?>';
        const MAX_FILENAME_LENGTH = 100;

        const modal = document.getElementById("modal");
        const fileInput = document.getElementById("fileInput");
        const uploadBox = document.getElementById("uploadBox");
        const fileTable = document.getElementById("fileTable");
        const selectedFilesDiv = document.getElementById("selectedFiles");
        const uploadFilesBtn = document.getElementById("uploadFilesBtn");
        const serviceStatus = document.getElementById("serviceStatus");
        const serviceAlert = document.getElementById("serviceAlert");
        const uploadBtn = document.getElementById("uploadBtn");
        const filenameWarning = document.getElementById("filenameWarning");
        const maxSizeText = document.getElementById("maxSizeText");

        let selectedFiles = [];
        let isServiceOnline = false;
        let maxFileSize = 2 * 1024 * 1024; // Default 2MB
        let maxFileSizeFormatted = '2MB';
        let serverConfig = {};

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            checkServiceHealth();
            loadFiles();

            // Check service health periodically
            setInterval(checkServiceHealth, 30000);
        });

        // Service health check
        async function checkServiceHealth() {
            try {
                const response = await fetch(BASE_URL + 'api/knowledge/health');
                const data = await response.json();

                isServiceOnline = data.service_running;
                updateServiceStatus(isServiceOnline);

                // Update config from server
                if (data.config) {
                    serverConfig = data.config;
                    maxFileSize = data.config.max_file_size || maxFileSize;
                    maxFileSizeFormatted = data.config.max_file_size_formatted || maxFileSizeFormatted;
                    maxSizeText.textContent = maxFileSizeFormatted;
                }
            } catch (error) {
                isServiceOnline = false;
                updateServiceStatus(false);
            }
        }

        function updateServiceStatus(online) {
            const statusDot = serviceStatus.querySelector('.status-dot');
            const statusText = serviceStatus.querySelector('span:last-child');

            if (online) {
                serviceStatus.className = 'service-status online';
                statusDot.className = 'status-dot online';
                statusText.textContent = 'Service Online';
                serviceAlert.style.display = 'none';
                uploadBtn.disabled = false;
            } else {
                serviceStatus.className = 'service-status offline';
                statusDot.className = 'status-dot offline';
                statusText.textContent = 'Service Offline';
                serviceAlert.style.display = 'flex';
                uploadBtn.disabled = true;
            }
        }

        // Load files from server
        async function loadFiles() {
            try {
                const response = await fetch(BASE_URL + 'api/knowledge/files');
                const data = await response.json();

                if (data.success && data.files) {
                    renderFileTable(data.files);
                } else {
                    showEmptyState();
                }
            } catch (error) {
                console.error('Failed to load files:', error);
                showEmptyState('Failed to load files. Please check if the service is running.');
            }
        }

        function renderFileTable(files) {
            if (!files || files.length === 0) {
                showEmptyState();
                return;
            }

            fileTable.innerHTML = files.map(file => {
                const ext = file.file_type.replace('.', '').toUpperCase();
                const cls = getTypeClass(ext);
                const size = formatFileSize(file.file_size);
                const date = new Date(file.upload_time).toLocaleString();

                return `
                    <tr data-id="${file.id}" data-name="${escapeHtml(file.original_filename)}">
                        <td>
                            <span class="file-name-text">${escapeHtml(file.original_filename)}</span>
                            <span class="chunk-badge">${file.chunk_count} chunks</span>
                        </td>
                        <td class="file-meta">${size}</td>
                        <td class="file-meta">${date}</td>
                        <td><span class="badge ${cls}">${ext}</span></td>
                        <td class="actions">
                            <button class="rename-btn" onclick="renameFile('${file.id}', '${escapeHtml(file.original_filename)}')">
                                <i class="fas fa-edit"></i> Rename
                            </button>
                            <button class="delete-btn" onclick="deleteFile('${file.id}', '${escapeHtml(file.original_filename)}')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function showEmptyState(message = null) {
            fileTable.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <p>${message || 'No files uploaded yet. Click "Upload Files" to add documents to the knowledge base.'}</p>
                        </div>
                    </td>
                </tr>
            `;
        }

        function getTypeClass(ext) {
            const typeMap = {
                'PDF': 'pdf',
                'TXT': 'txt',
                'MD': 'md',
                'DOC': 'doc',
                'DOCX': 'docx'
            };
            return typeMap[ext] || 'other';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Modal functions
        function openModal() {
            if (!isServiceOnline) {
                alert('Knowledge base service is not running. Please start the service first.');
                return;
            }
            modal.style.display = "flex";
            selectedFiles = [];
            renderSelected();
        }

        function closeModal() {
            modal.style.display = "none";
            selectedFiles = [];
            renderSelected();
        }

        // Drag and drop
        uploadBox.addEventListener('click', () => fileInput.click());
        uploadBox.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadBox.classList.add('dragover');
        });
        uploadBox.addEventListener('dragleave', () => {
            uploadBox.classList.remove('dragover');
        });
        uploadBox.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadBox.classList.remove('dragover');
            addFiles([...e.dataTransfer.files]);
        });

        fileInput.onchange = e => addFiles([...e.target.files]);

        function addFiles(files) {
            const allowedExtensions = ['.pdf', '.txt', '.doc', '.docx', '.md'];
            let hasLongFilename = false;

            files.forEach(file => {
                const ext = '.' + file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(ext)) {
                    alert(`File "${file.name}" is not allowed. Only PDF, TXT, DOC, DOCX, MD files are supported.`);
                    return;
                }

                if (file.size > maxFileSize) {
                    alert(`File "${file.name}" exceeds the maximum size limit of ${maxFileSizeFormatted}.`);
                    return;
                }

                if (selectedFiles.some(f => f.name === file.name)) {
                    alert(`File "${file.name}" is already selected.`);
                    return;
                }

                if (file.name.length > MAX_FILENAME_LENGTH) {
                    hasLongFilename = true;
                }

                selectedFiles.push({
                    file: file,
                    name: file.name,
                    size: file.size,
                    status: 'pending'
                });
            });

            filenameWarning.style.display = hasLongFilename ? 'block' : 'none';
            renderSelected();
        }

        function renderSelected() {
            if (selectedFiles.length === 0) {
                selectedFilesDiv.innerHTML = '';
                uploadFilesBtn.disabled = true;
                filenameWarning.style.display = 'none';
                return;
            }

            uploadFilesBtn.disabled = false;

            selectedFilesDiv.innerHTML = selectedFiles.map((f, i) => {
                let statusHtml = '';
                if (f.status === 'uploading') {
                    statusHtml = '<span class="file-status uploading"><span class="spinner" style="width:14px;height:14px;border-width:2px;"></span>Uploading...</span>';
                } else if (f.status === 'success') {
                    statusHtml = '<span class="file-status success"><i class="fas fa-check"></i> Done</span>';
                } else if (f.status === 'error') {
                    statusHtml = `<span class="file-status error" title="${escapeHtml(f.error || 'Upload failed')}"><i class="fas fa-times"></i> Failed</span>`;
                }

                const warningIcon = f.name.length > MAX_FILENAME_LENGTH ?
                    '<i class="fas fa-exclamation-triangle" style="color:#f39c12;margin-right:6px;" title="Filename exceeds 100 characters"></i>' : '';

                return `
                    <div class="file-row">
                        <span class="file-name">${warningIcon}${escapeHtml(f.name)}</span>
                        <span class="file-size">${formatFileSize(f.size)}</span>
                        ${statusHtml}
                        ${f.status === 'pending' ? `<span class="remove-file" onclick="removeFile(${i})"><i class="fas fa-times"></i></span>` : ''}
                    </div>
                `;
            }).join('');
        }

        function removeFile(i) {
            selectedFiles.splice(i, 1);

            // Check if any remaining files have long names
            const hasLongFilename = selectedFiles.some(f => f.name.length > MAX_FILENAME_LENGTH);
            filenameWarning.style.display = hasLongFilename ? 'block' : 'none';

            renderSelected();
        }

        async function uploadFiles() {
            if (selectedFiles.length === 0) return;

            uploadFilesBtn.disabled = true;

            for (let i = 0; i < selectedFiles.length; i++) {
                if (selectedFiles[i].status !== 'pending') continue;

                selectedFiles[i].status = 'uploading';
                renderSelected();

                try {
                    const formData = new FormData();
                    formData.append('file', selectedFiles[i].file);
                    formData.append('csrf_token', CSRF_TOKEN);

                    const response = await fetch(BASE_URL + 'api/knowledge/upload', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        selectedFiles[i].status = 'success';
                    } else {
                        selectedFiles[i].status = 'error';
                        selectedFiles[i].error = data.error || 'Upload failed';
                    }
                } catch (error) {
                    selectedFiles[i].status = 'error';
                    selectedFiles[i].error = error.message;
                }

                renderSelected();
            }

            // Refresh file list
            await loadFiles();

            // Close modal if all successful
            const allSuccess = selectedFiles.every(f => f.status === 'success');
            if (allSuccess) {
                setTimeout(() => {
                    closeModal();
                }, 1000);
            }
        }

        async function deleteFile(fileId, fileName) {
            if (!confirm(`Are you sure you want to delete "${fileName}"?\nThis action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(BASE_URL + 'api/knowledge/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        file_id: fileId,
                        csrf_token: CSRF_TOKEN
                    })
                });

                const data = await response.json();

                if (data.success) {
                    await loadFiles();
                } else {
                    alert('Failed to delete file: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Failed to delete file: ' + error.message);
            }
        }

        async function renameFile(fileId, currentName) {
            const newName = prompt('Enter new file name:', currentName);
            if (!newName || newName === currentName) return;

            if (newName.length > MAX_FILENAME_LENGTH) {
                alert(`Filename is too long. Maximum ${MAX_FILENAME_LENGTH} characters allowed.`);
                return;
            }

            try {
                const response = await fetch(BASE_URL + 'api/knowledge/rename', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        file_id: fileId,
                        new_name: newName,
                        csrf_token: CSRF_TOKEN
                    })
                });

                const data = await response.json();

                if (data.success) {
                    await loadFiles();
                } else {
                    alert('Failed to rename file: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Failed to rename file: ' + error.message);
            }
        }

        function searchFiles(val) {
            const rows = fileTable.querySelectorAll('tr[data-id]');
            rows.forEach(row => {
                const name = row.dataset.name.toLowerCase();
                row.style.display = name.includes(val.toLowerCase()) ? '' : 'none';
            });
        }

        // Close modal on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

</body>

</html>
