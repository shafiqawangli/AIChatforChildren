<?php
session_start();
use Utils\Helper;

// Load environment variables
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$user = $_SESSION['user'];
$csrfToken = Helper::generateCsrfToken();
include("connect.php");
?>
<!doctype html>
<html lang="en">

<head>
    <title>Bitty - Chat with me!</title>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1,maximum-scale=1,user-scalable=no" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="stylesheet" href="<?php echo Helper::url('assets/css/child.css'); ?>?v=<?php echo time(); ?>">

    <style>
    /* Chat list styles - inline to ensure they apply */
    .chat-list-container {
        flex: 1;
        overflow-y: auto;
        margin-top: 10px;
        border-radius: 12px;
        background: #f8f9fa;
    }

    .chat-list {
        display: flex;
        flex-direction: column;
        padding: 8px;
    }

    .chat-item {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        padding: 10px 12px !important;
        background: white !important;
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
        position: relative;
        border-radius: 0 !important;
        border: none !important;
    }

    .chat-item:not(:last-child)::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 12px;
        right: 12px;
        height: 1px;
        background: linear-gradient(90deg, transparent, #e0e0e0 10%, #e0e0e0 90%, transparent);
    }

    .chat-item:hover {
        background: linear-gradient(90deg, #f0f0f0 0%, #fafafa 100%) !important;
        border-left-color: #667eea80 !important;
    }

    .chat-item.active {
        background: linear-gradient(90deg, #667eea15 0%, #764ba215 100%) !important;
        border-left-color: #667eea !important;
    }

    .chat-item.active::after {
        opacity: 0;
    }

    .chat-item-content {
        flex: 1 !important;
        min-width: 0 !important;
        position: relative;
        overflow: hidden;
        margin-right: 8px;
    }

    .chat-item-title {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #444 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        padding-right: 20px;
        position: relative;
        line-height: 1.4;
    }

    .chat-item-content::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 35px;
        background: linear-gradient(90deg, transparent, white);
        pointer-events: none;
    }

    .chat-item:hover .chat-item-content::after {
        background: linear-gradient(90deg, transparent, #f5f5f5);
    }

    .chat-item.active .chat-item-content::after {
        background: linear-gradient(90deg, transparent, #f0eef8);
    }

    .chat-item-title-input {
        width: 100%;
        padding: 4px 8px;
        border: 1px solid #667eea;
        border-radius: 4px;
        font-size: 13px;
        outline: none;
        background: white;
    }

    .chat-item-actions {
        display: flex !important;
        flex-direction: row !important;
        gap: 4px !important;
        margin-left: auto !important;
        opacity: 0.35;
        transition: opacity 0.2s ease;
        flex-shrink: 0 !important;
    }

    .chat-item:hover .chat-item-actions {
        opacity: 1;
    }

    .chat-item-btn {
        width: 26px !important;
        height: 26px !important;
        min-width: 26px !important;
        padding: 0 !important;
        border: none !important;
        border-radius: 6px !important;
        background: transparent !important;
        color: #888 !important;
        cursor: pointer;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 11px !important;
        transition: all 0.2s ease;
    }

    .chat-item-btn:hover {
        background: #667eea !important;
        color: white !important;
        transform: scale(1.1);
    }

    .chat-item-btn.delete-btn:hover {
        background: #f44336 !important;
    }

    .chat-list-empty {
        text-align: center;
        padding: 30px 15px;
        color: #999;
        font-size: 14px;
    }

    /* Language toggle button fix */
    .lang-toggle-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        padding: 8px 16px !important;
        margin-top: 10px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border: none !important;
        border-radius: 20px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .lang-toggle-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .lang-toggle-btn i {
        font-size: 16px !important;
    }

    #lang-toggle-text {
        color: white !important;
    }
    </style>
</head>

<body>
    <!-- Help Modal -->
    <div id="help-modal" class="help-modal">
        <div class="help-content">
            <h2 id="help-title">🎤 Voice Chat Setup Help</h2>

            <h3 id="help-reason-title">Why can't the voice feature be used?</h3>
            <p id="help-reason-text">The browser's security policy requires speech recognition to run in a <strong>secure environment</strong>下运行:</p>
            <ul>
                <li>✅ HTTPS (https://)</li>
                <li>✅ localhost (http://localhost)</li>
                <li>❌ HTTP (http://)</li>
                <li>❌ IP Address (http://127.0.0.1 or http://192.168.x.x)</li>
            </ul>

            <h3 id="help-solution1-title">Solution 1: Use localhost</h3>
            <p id="help-solution1-text">If you're using XAMPP/WAMP, please make sure the access URL is:</p>
            <code>http://localhost/your-project/homepage.php</code>
            <p style="margin-top: 10px;" id="help-solution1-text2">Instead of:</p>
            <code>http://127.0.0.1/your-project/homepage.php</code>

            <h3 id="help-solution2-title">Solution 2: Enable HTTPS (XAMPP)</h3>
            <ol>
                <li id="help-solution2-step1">Edit <code>C:\xampp\apache\conf\httpd.conf</code></li>
                <li id="help-solution2-step2">Uncomment these two lines:
                    <br><code>LoadModule ssl_module modules/mod_ssl.so</code>
                    <br><code>Include conf/extra/httpd-ssl.conf</code>
                </li>
                <li id="help-solution2-step3">Restart Apache</li>
                <li id="help-solution2-step4">Access: <code>https://localhost/your-project/</code></li>
            </ol>

            <h3 id="help-solution3-title">Solution 3: Chrome Developer Mode (Temporary)</h3>
            <ol>
                <li id="help-solution3-step1">Enter in Chrome address bar: <code>chrome://flags</code></li>
                <li id="help-solution3-step2">Search: "Insecure origins treated as secure"</li>
                <li id="help-solution3-step3">Add your URL (e.g., <code>http://127.0.0.1</code>)</li>
                <li id="help-solution3-step4">Restart browser</li>
            </ol>

            <h3 id="help-status-title">Current Status</h3>
            <p id="help-status-protocol">
                Protocol: <strong id="current-protocol"></strong>
                <span id="security-badge" class="status-badge"></span>
            </p>
            <p id="help-status-host">Host: <strong id="current-host"></strong></p>
            <p id="help-status-speech">Speech Recognition: <strong id="speech-support"></strong></p>

            <button onclick="hideHelpModal()" id="help-close-btn">我知道了</button>
        </div>
    </div>
    <div class="navbar">
        <div class="brand">Bitty</div>
        <div class="profile" id="profileLink">
            <img src="https://picsum.photos/200/300" alt="Avatar">
            <span><?= $user['name'] ?></span>
            <i class="fas fa-chevron-down" style="margin-left: 8px; color: white;"></i>
            <div class="dropdown" id="profileDropdown">
                <button onclick="showChildProfileModal()"><i class="fas fa-user"></i> Profile</button>
                <a href="<?php echo Helper::url('logout'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Recording Indicator -->
    <div id="recording-indicator" class="recording-indicator">
        <div class="recording-content">
            <div class="mic-animation">
                <i class="fa-solid fa-microphone"></i>
            </div>
            <p id="recording-text">Recording... Please speak</p>
            <p class="hint" id="recording-hint">Click anywhere or press ESC to cancel</p>
        </div>
    </div>

    <!-- Left Sidebar (Bitty is here) -->
    <div class="extra-container">
        <div class="extra-options-vertical">
            <div class="profile">
                <img src="img/pic4.jpg" alt="Bitty" class="profile-pic" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22150%22 height=%22150%22%3E%3Crect width=%22150%22 height=%22150%22 fill=%22%236C63FF%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22 fill=%22white%22%3EBitty%3C/text%3E%3C/svg%3E'">
                <h2 id="profile-name">Bitty is here</h2>
                <button id="lang-toggle-btn" class="lang-toggle-btn">
                    <i class="fa-solid fa-language"></i>
                    <span id="lang-toggle-text">中文</span>
                </button>
            </div>

            <!-- Action buttons -->
            <button id="new-chat-btn" class="action-btn new-chat-btn">
                <i class="fa-solid fa-plus"></i>
                <span id="new-chat-btn-text">开始新对话</span>
            </button>
            <div class="search-container">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" id="search-input" placeholder="搜索对话..." class="search-input">
            </div>

            <!-- Chat list -->
            <div class="chat-list-container">
                <div id="chat-list" class="chat-list">
                    <!-- Chat items will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Container (main area) -->
    <div class="chat-container">
        <div class="header">
            <span id="chat-title">Bitty Chat</span>
            <div class="logout-link" style="display: none;">
                <a href="<?php echo Helper::url('logout'); ?>" id="logout-link">Logout</a>
            </div>

        </div>

        <div id="chat-box">
            <div id="typing-indicator" class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="input-area">
            <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
            <button id="voice-btn" class="voice-btn" title="Voice Chat">
                <span class="warning-badge" id="warning-badge">!</span>
                <i class="fa-solid fa-microphone"></i>
            </button>
            <button id="send-btn">Send</button>
            <button id="stop-btn">Stop</button>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="profile-modal" id="childProfileModal" style="display: none;">
        <div class="profile-modal-content">
            <div class="profile-modal-header">
                <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                <span class="profile-modal-close" onclick="closeChildProfileModal()">&times;</span>
            </div>
            <div class="profile-modal-body">
                <!-- Alert Message Area -->
                <div id="childAlertMessage" style="display: none;"></div>

                <form id="childProfileForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="profile-form-group">
                        <label for="child-name"><i class="fas fa-signature"></i> Name</label>
                        <input type="text" name="name" id="child-name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        <small class="profile-error-text" id="childNameError"></small>
                    </div>
                    <div class="profile-form-group">
                        <label for="child-email"><i class="fas fa-envelope"></i> Email (Cannot be changed)</label>
                        <input id="child-email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                    <button name="update-profile" type="submit" class="profile-btn">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>

                <div class="profile-divider"></div>

                <h3 style="margin-bottom: 20px; color: #667eea;"><i class="fas fa-lock"></i> Change Password</h3>

                <form id="childPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="profile-form-group">
                        <label for="child-current-password"><i class="fas fa-key"></i> Current Password</label>
                        <input type="password" name="current-password" id="child-current-password" required>
                        <small class="profile-error-text" id="childCurrentPasswordError"></small>
                    </div>
                    <div class="profile-form-group">
                        <label for="child-new-password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" name="password" id="child-new-password" required>
                        <small class="profile-error-text" id="childNewPasswordError"></small>
                    </div>
                    <div class="profile-form-group">
                        <label for="child-confirm-password"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                        <input type="password" name="confirm-password" id="child-confirm-password" required>
                        <small class="profile-error-text" id="childConfirmPasswordError"></small>
                    </div>
                    <button name="update-password" type="submit" class="profile-btn">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                </form>

                <div class="profile-divider"></div>

                <a href="<?php echo Helper::url('logout'); ?>" class="profile-btn profile-btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <script>
        const DEEPSEEK_API_KEY = "<?= $_ENV['LLM_API_KEY'] ?>";
        const DEEPSEEK_API_URL = "<?= $_ENV['LLM_API_URL'] ?>";
        const API_BASE_URL = "<?= Helper::url('api/conversations') ?>";

        // Language configuration - centralized translations
        const translations = {
            'zh-CN': {
                chatTitle: 'Bitty 聊天室',
                profileName: 'Bitty 在这里',
                messagePlaceholder: '输入消息...',
                send: '发送',
                stop: '停止',
                recordingText: '正在录音... 请说话',
                recordingHint: '点击任意处或按 ESC 取消',
                secure: '✓ 安全',
                insecure: '⚠ 不安全',
                requireHttps: '需要HTTPS或localhost',
                notSupported: '不支持',
                supported: '✓ 支持',
                voiceReady: '✅ 语音识别已就绪',
                unrecognized: '未能识别语音，请重试',
                noSpeech: '未检测到语音，请重试',
                micError: '无法访问麦克风<br>请检查设备和浏览器权限',
                micDenied: '麦克风权限被拒绝<br>请点击地址栏的🔒图标允许麦克风访问',
                networkError: '网络错误，请检查网络连接',
                recordCancelled: '录音已取消',
                responseInterrupted: '响应已中断',
                errorOccurred: '抱歉,发生错误。请重试。',
                newChat: '开始新对话',
                searchPlaceholder: '搜索对话...',
                newChatTitle: '新对话',
                noChats: '暂无对话',
                deleteConfirm: '确定要删除这个对话吗？'
            },
            'en-US': {
                chatTitle: 'Bitty Chat',
                profileName: 'Bitty is here',
                messagePlaceholder: 'Type a message...',
                send: 'Send',
                stop: 'Stop',
                recordingText: 'Recording... Please speak',
                recordingHint: 'Click anywhere or press ESC to cancel',
                secure: '✓ Secure',
                insecure: '⚠ Insecure',
                requireHttps: 'Requires HTTPS or localhost',
                notSupported: 'Not supported',
                supported: '✓ Supported',
                voiceReady: '✅ Voice recognition ready',
                unrecognized: 'Failed to recognize speech, please try again',
                noSpeech: 'No speech detected, please try again',
                micError: 'Cannot access microphone<br>Please check device and browser permissions',
                micDenied: 'Microphone permission denied<br>Please click the 🔒 icon in the address bar to allow microphone access',
                networkError: 'Network error, please check your connection',
                recordCancelled: 'Recording cancelled',
                responseInterrupted: 'Response interrupted',
                errorOccurred: 'Sorry, an error occurred. Please try again.',
                newChat: 'New Chat',
                searchPlaceholder: 'Search chats...',
                newChatTitle: 'New Chat',
                noChats: 'No chats yet',
                deleteConfirm: 'Are you sure you want to delete this chat?'
            }
        };

        // Get translation helper
        const t = (key) => translations[currentLanguage][key] || key;

        const sendButton = document.getElementById("send-btn");
        const stopButton = document.getElementById("stop-btn");
        const messageInput = document.getElementById("message-input");
        const chatBox = document.getElementById("chat-box");
        const voiceBtn = document.getElementById("voice-btn");
        const warningBadge = document.getElementById("warning-badge");
        const langToggleBtn = document.getElementById("lang-toggle-btn");
        const langToggleText = document.getElementById("lang-toggle-text");
        const newChatBtn = document.getElementById("new-chat-btn");
        const searchInput = document.getElementById("search-input");
        const chatList = document.getElementById("chat-list");
        const recordingIndicator = document.getElementById("recording-indicator");
        const recordingText = document.getElementById("recording-text");
        const typingIndicator = document.getElementById("typing-indicator");
        const helpModal = document.getElementById("help-modal");

        // Chat management
        let allChats = [];
        let currentChatId = null;
        let currentChatAutoRenamed = false;
        let conversationHistory = [];
        let currentLanguage = 'zh-CN';
        let isRecording = false;
        let isProcessing = false;
        let abortController = null;
        let recognition = null;
        let speechSynthesis = window.speechSynthesis;
        let currentUtterance = null;
        let voiceAvailable = false;
        let isVoiceChat = false;  // Track if current message is from voice chat


        // Update all UI text elements
        function updateUI() {
            document.getElementById('chat-title').textContent = t('chatTitle');
            document.getElementById('message-input').placeholder = t('messagePlaceholder');
            document.getElementById('send-btn').textContent = t('send');
            document.getElementById('stop-btn').textContent = t('stop');
            document.getElementById('recording-hint').textContent = t('recordingHint');

            // Update profile name
            document.getElementById('profile-name').textContent = t('profileName');

            // Update language toggle button - show current language
            langToggleText.textContent = currentLanguage === 'zh-CN' ? '中文' : 'English';

            // Update new chat button and search
            document.getElementById('new-chat-btn-text').textContent = t('newChat');
            searchInput.placeholder = t('searchPlaceholder');

            // Re-render chat list with updated language
            renderChatList();

            checkSecurityContext();
        }

        // Check security context
        function checkSecurityContext() {
            const isSecure = window.isSecureContext;
            const protocol = window.location.protocol;
            const hostname = window.location.hostname;

            console.log("Security Context:", {
                isSecure,
                protocol,
                hostname,
                isLocalhost: hostname === 'localhost' || hostname === '127.0.0.1'
            });

            document.getElementById('current-protocol').textContent = protocol;
            document.getElementById('current-host').textContent = hostname;

            const badge = document.getElementById('security-badge');
            if (isSecure || hostname === 'localhost') {
                badge.textContent = t('secure');
                badge.className = 'status-badge secure';
            } else {
                badge.textContent = t('insecure');
                badge.className = 'status-badge insecure';
            }

            document.getElementById('speech-support').textContent = t('requireHttps');

            return isSecure || hostname === 'localhost';
        }

        // Initialize Speech Recognition
        function initSpeechRecognition() {
            const isSecure = checkSecurityContext();

            if (!isSecure) {
                const protocol = window.location.protocol;
                const hostname = window.location.hostname;

                // Show warning badge
                warningBadge.classList.add('show');
                voiceBtn.classList.add('warning-btn');

                // Add system message (only once, not intrusive)
                console.warn("⚠️ 语音功能需要HTTPS或localhost环境");

                const supportText = currentLanguage === 'zh-CN' ? '需要HTTPS或localhost' : 'Requires HTTPS or localhost';
                document.getElementById('speech-support').textContent = supportText;
                document.getElementById('speech-support').style.color = '#ff9800';

                voiceAvailable = false;
                return false;
            }

            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                warningBadge.classList.add('show');
                voiceBtn.classList.add('warning-btn');

                console.warn("⚠️ 浏览器不支持语音识别");

                const supportText = currentLanguage === 'zh-CN' ? '不支持' : 'Not supported';
                document.getElementById('speech-support').textContent = supportText;
                document.getElementById('speech-support').style.color = '#f44336';

                voiceAvailable = false;
                return false;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();

            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = currentLanguage;
            recognition.maxAlternatives = 1;

            recognition.onstart = () => {
                console.log("✅ 语音识别已启动");
                isRecording = true;
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                const confidence = event.results[0][0].confidence;

                console.log(`识别结果: "${transcript}" (置信度: ${(confidence * 100).toFixed(1)}%)`);

                stopRecording();

                if (transcript && transcript.trim()) {
                    isVoiceChat = true;  // Mark this as voice chat
                    addMessage(transcript, 'user');
                    sendToDeepSeek(transcript);
                } else {
                    const errorMsg = currentLanguage === 'zh-CN' ? "未能识别语音，请重试" : "Failed to recognize speech, please try again";
                    addSystemMessage(errorMsg);
                }
            };

            recognition.onerror = (event) => {
                console.error("❌ 语音识别错误:", event.error);
                stopRecording();

                let errorMsg = "";

                switch (event.error) {
                    case 'no-speech':
                        errorMsg = currentLanguage === 'zh-CN' ? "未检测到语音，请重试" : "No speech detected, please try again";
                        break;
                    case 'audio-capture':
                        errorMsg = currentLanguage === 'zh-CN' ?
                            "无法访问麦克风<br>请检查设备和浏览器权限" :
                            "Cannot access microphone<br>Please check device and browser permissions";
                        break;
                    case 'not-allowed':
                        errorMsg = currentLanguage === 'zh-CN' ?
                            "麦克风权限被拒绝<br>请点击地址栏的🔒图标允许麦克风访问" :
                            "Microphone permission denied<br>Please click the 🔒 icon in the address bar to allow microphone access";
                        break;
                    case 'network':
                        errorMsg = currentLanguage === 'zh-CN' ? "网络错误，请检查网络连接" : "Network error, please check your connection";
                        break;
                    case 'aborted':
                        errorMsg = currentLanguage === 'zh-CN' ? "录音已取消" : "Recording cancelled";
                        break;
                    default:
                        errorMsg = currentLanguage === 'zh-CN' ?
                            `语音识别错误: ${event.error}` :
                            `Speech recognition error: ${event.error}`;
                }

                addErrorMessage(errorMsg);
            };

            recognition.onend = () => {
                console.log("语音识别已结束");
                if (isRecording) {
                    stopRecording();
                }
            };

            const supportText = currentLanguage === 'zh-CN' ? '✓ 支持' : '✓ Supported';
            document.getElementById('speech-support').textContent = supportText;
            document.getElementById('speech-support').style.color = '#4CAF50';

            const readyMsg = currentLanguage === 'zh-CN' ? "✅ Voice recognition is ready" : "✅ 语音识别已就绪";
            addSystemMessage(readyMsg);

            // Hide warning badge when voice is available
            warningBadge.classList.remove('show');
            voiceBtn.classList.remove('warning-btn');

            console.log("✅ 语音识别已就绪");
            voiceAvailable = true;

            return true;
        }

        function showHelpModal() {
            helpModal.classList.add('show');
        }

        function hideHelpModal() {
            helpModal.classList.remove('show');
        }

        helpModal.addEventListener('click', (e) => {
            if (e.target === helpModal) {
                hideHelpModal();
            }
        });

        window.addEventListener('load', () => {
            console.log("🚀 页面加载完成");
            initChats();
            updateUI();
            initSpeechRecognition();
        });

        // Language toggle button click handler
        langToggleBtn.addEventListener("click", () => {
            currentLanguage = currentLanguage === 'zh-CN' ? 'en-US' : 'zh-CN';
            if (recognition) {
                recognition.lang = currentLanguage;
            }
            updateUI();
        });

        // ========== Chat Management Functions ==========

        // API helper function
        async function apiRequest(endpoint, method = 'GET', data = null) {
            const url = API_BASE_URL + endpoint;
            console.log(`API Request: ${method} ${url}`, data);

            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };
            if (data) {
                options.body = JSON.stringify(data);
            }

            try {
                const response = await fetch(url, options);
                console.log(`API Response status: ${response.status}`);

                const text = await response.text();
                console.log(`API Response body:`, text);

                // Try to parse as JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', text);
                    return { error: 'Invalid JSON response', raw: text };
                }
            } catch (error) {
                console.error('API Request failed:', error);
                throw error;
            }
        }

        // Create a new chat
        async function createNewChat() {
            console.log('createNewChat called', { currentChatId, conversationHistoryLength: conversationHistory.length });

            // If current chat is empty (no messages), don't create new one
            if (currentChatId && conversationHistory.length === 0) {
                // Already have an empty chat, just ensure it's highlighted
                clearChatBox();
                renderChatList();
                console.log('Current chat is empty, not creating new one');
                return;
            }

            // Create a new chat
            try {
                console.log('Creating new chat via API...');
                const result = await apiRequest('/create', 'POST', { title: t('newChatTitle') });
                console.log('Create result:', result);
                if (result.id) {
                    const newChat = {
                        id: result.id,
                        title: result.title,
                        auto_renamed: false
                    };
                    allChats.unshift(newChat);
                    currentChatId = result.id;
                    currentChatAutoRenamed = false;
                    conversationHistory = [];
                    renderChatList();
                    clearChatBox();
                    console.log('New chat created successfully:', newChat);
                } else if (result.error) {
                    console.error('API error:', result.error);
                    alert('Failed to create chat: ' + result.error);
                }
            } catch (error) {
                console.error('Failed to create chat:', error);
                alert('Failed to create chat. Please check the console for details.');
            }
        }

        // Switch to a chat
        async function switchToChat(chatId) {
            if (isProcessing) {
                stopCurrentResponse();
            }

            try {
                const result = await apiRequest('/get?id=' + chatId);
                if (result.id) {
                    currentChatId = result.id;
                    currentChatAutoRenamed = result.auto_renamed == 1;
                    conversationHistory = (result.messages || []).map(m => ({
                        role: m.role,
                        content: m.content
                    }));
                    renderChatList();
                    renderChatMessages();
                }
            } catch (error) {
                console.error('Failed to switch chat:', error);
            }
        }

        // Delete a chat
        async function deleteChat(chatId) {
            if (!confirm(t('deleteConfirm'))) return;

            try {
                const result = await apiRequest('/delete', 'POST', { id: chatId });
                if (result.success) {
                    allChats = allChats.filter(c => c.id != chatId);

                    if (currentChatId == chatId) {
                        if (allChats.length > 0) {
                            await switchToChat(allChats[0].id);
                        } else {
                            await createNewChat();
                        }
                    } else {
                        renderChatList();
                    }
                }
            } catch (error) {
                console.error('Failed to delete chat:', error);
            }
        }

        // Rename a chat
        function startRenameChat(chatId) {
            const chatItem = document.querySelector(`[data-chat-id="${chatId}"]`);
            const titleEl = chatItem.querySelector('.chat-item-title');
            const currentTitle = titleEl.textContent;

            titleEl.innerHTML = `<input type="text" class="chat-item-title-input" value="${currentTitle}">`;
            const input = titleEl.querySelector('input');
            input.focus();
            input.select();

            const finishRename = async () => {
                const newTitle = input.value.trim() || currentTitle;
                try {
                    await apiRequest('/update', 'POST', {
                        id: chatId,
                        title: newTitle,
                        auto_renamed: 1
                    });
                    const chat = allChats.find(c => c.id == chatId);
                    if (chat) {
                        chat.title = newTitle;
                        chat.auto_renamed = true;
                    }
                    if (chatId == currentChatId) {
                        currentChatAutoRenamed = true;
                    }
                } catch (error) {
                    console.error('Failed to rename chat:', error);
                }
                renderChatList();
            };

            input.addEventListener('blur', finishRename);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    finishRename();
                }
                if (e.key === 'Escape') {
                    renderChatList();
                }
            });
        }

        // Auto-rename chat based on first message
        async function autoRenameChat(message) {
            // Check for length === 1 because we call this after adding the first message
            if (!currentChatAutoRenamed && conversationHistory.length === 1) {
                const newTitle = message.length > 20 ? message.substring(0, 20) + '...' : message;
                try {
                    await apiRequest('/update', 'POST', {
                        id: currentChatId,
                        title: newTitle,
                        auto_renamed: 1
                    });
                    const chat = allChats.find(c => c.id == currentChatId);
                    if (chat) {
                        chat.title = newTitle;
                        chat.auto_renamed = true;
                    }
                    currentChatAutoRenamed = true;
                    renderChatList();
                } catch (error) {
                    console.error('Failed to auto-rename chat:', error);
                }
            }
        }

        // Save a message to current conversation
        async function saveMessage(role, content) {
            if (!currentChatId) return;
            try {
                await apiRequest('/message', 'POST', {
                    conversation_id: currentChatId,
                    role: role,
                    content: content
                });
            } catch (error) {
                console.error('Failed to save message:', error);
            }
        }

        // Clear chat box
        function clearChatBox() {
            chatBox.innerHTML = '';
            chatBox.appendChild(typingIndicator);
        }

        // Render chat messages from history
        function renderChatMessages() {
            clearChatBox();
            conversationHistory.forEach(msg => {
                addMessage(msg.content, msg.role === 'user' ? 'user' : 'bot', false);
            });
        }

        // Render chat list
        function renderChatList(filter = '') {
            const filterLower = filter.toLowerCase();
            const filteredChats = filter
                ? allChats.filter(c => c.title.toLowerCase().includes(filterLower))
                : allChats;

            if (filteredChats.length === 0) {
                chatList.innerHTML = `<div class="chat-list-empty">${t('noChats')}</div>`;
                return;
            }

            chatList.innerHTML = filteredChats.map(chat => `
                <div class="chat-item ${chat.id == currentChatId ? 'active' : ''}" data-chat-id="${chat.id}">
                    <div class="chat-item-content">
                        <div class="chat-item-title">${escapeHtml(chat.title)}</div>
                    </div>
                    <div class="chat-item-actions">
                        <button class="chat-item-btn rename-btn" title="Rename" onclick="event.stopPropagation(); startRenameChat(${chat.id})">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="chat-item-btn delete-btn" title="Delete" onclick="event.stopPropagation(); deleteChat(${chat.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            // Add click handlers for switching chats
            chatList.querySelectorAll('.chat-item').forEach(item => {
                item.addEventListener('click', () => {
                    switchToChat(item.dataset.chatId);
                });
            });
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // New chat button click handler
        newChatBtn.addEventListener("click", createNewChat);

        // Search input handler
        let searchTimeout = null;
        searchInput.addEventListener("input", (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                renderChatList(e.target.value);
            }, 300);
        });

        // Initialize: load chats from server
        async function initChats() {
            try {
                const result = await apiRequest('');
                allChats = result.conversations || [];

                if (allChats.length === 0) {
                    await createNewChat();
                } else {
                    // Load first chat
                    await switchToChat(allChats[0].id);
                }
            } catch (error) {
                console.error('Failed to load chats:', error);
                // Fallback: create new chat
                await createNewChat();
            }
        }

        voiceBtn.addEventListener("click", () => {
            // If voice is not available, show help modal
            if (!voiceAvailable) {
                showHelpModal();
                return;
            }

            // If voice is available, toggle recording
            if (isRecording) {
                stopRecording();
            } else {
                startRecording();
            }
        });

        function startRecording() {
            try {
                console.log("🎤 开始录音...");

                voiceBtn.classList.add('active');
                recordingIndicator.classList.add('active');

                const langText = currentLanguage === 'zh-CN' ?
                    '正在录音... 请说话' :
                    'Recording... Please speak';
                recordingText.textContent = langText;

                recognition.start();

            } catch (error) {
                console.error("启动录音失败:", error);
                stopRecording();
                const errorMsg = currentLanguage === 'zh-CN' ?
                    "启动录音失败: " + error.message :
                    "Failed to start recording: " + error.message;
                addErrorMessage(errorMsg);
            }
        }

        function stopRecording() {
            isRecording = false;
            voiceBtn.classList.remove('active');
            // Restore warning badge if voice not available
            if (!voiceAvailable) {
                warningBadge.classList.add('show');
            }
            recordingIndicator.classList.remove('active');

            try {
                if (recognition) {
                    recognition.stop();
                }
            } catch (error) {
                console.error("停止录音失败:", error);
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (isRecording) {
                    stopRecording();
                    const cancelMsg = currentLanguage === 'zh-CN' ? "录音已取消" : "Recording cancelled";
                    addSystemMessage(cancelMsg);
                }
                hideHelpModal();
            }
        });

        recordingIndicator.addEventListener('click', () => {
            if (isRecording) {
                stopRecording();
                const cancelMsg = currentLanguage === 'zh-CN' ? "录音已取消" : "Recording cancelled";
                addSystemMessage(cancelMsg);
            }
        });

        sendButton.addEventListener("click", sendMessage);
        messageInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        stopButton.addEventListener("click", () => {
            stopCurrentResponse();
        });

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message && !isProcessing) {
                console.log("发送消息:", message);
                isVoiceChat = false;  // Mark this as text chat
                addMessage(message, 'user');
                messageInput.value = "";
                sendToDeepSeek(message);
            } else {
                console.log("无法发送 - 消息为空或正在处理中", {
                    message,
                    isProcessing
                });
            }
        }

        function addMessage(text, sender, save = true) {
            const messageDiv = document.createElement("div");
            messageDiv.classList.add("message");

            if (sender === 'user') {
                messageDiv.classList.add("user");
            }

            messageDiv.textContent = text;
            chatBox.insertBefore(messageDiv, typingIndicator);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function addSystemMessage(text) {
            const messageDiv = document.createElement("div");
            messageDiv.classList.add("message", "system");
            messageDiv.innerHTML = text;
            chatBox.insertBefore(messageDiv, typingIndicator);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function addErrorMessage(text) {
            const messageDiv = document.createElement("div");
            messageDiv.classList.add("message", "error");
            messageDiv.innerHTML = text;
            chatBox.insertBefore(messageDiv, typingIndicator);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function addInfoMessage(text) {
            const messageDiv = document.createElement("div");
            messageDiv.classList.add("message", "info");
            messageDiv.innerHTML = text;
            chatBox.insertBefore(messageDiv, typingIndicator);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        async function sendToDeepSeek(userMessage) {
            if (isProcessing) {
                console.log("已经在处理中,忽略请求");
                return;
            }

            console.log("开始处理消息:", userMessage);
            isProcessing = true;
            sendButton.disabled = true;
            stopButton.style.display = 'inline-block';
            typingIndicator.style.display = 'block';
            chatBox.scrollTop = chatBox.scrollHeight;

            if (currentUtterance) {
                speechSynthesis.cancel();
            }

            conversationHistory.push({
                role: "user",
                content: userMessage
            });

            // Auto-rename chat based on first message and save user message
            autoRenameChat(userMessage);
            saveMessage('user', userMessage);

            abortController = new AbortController();

            try {
                const response = await fetch(DEEPSEEK_API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${DEEPSEEK_API_KEY}`
                    },
                    body: JSON.stringify({
                        model: "deepseek-chat",
                        messages: conversationHistory,
                        temperature: 0.7,
                        max_tokens: 2000,
                        stream: true
                    }),
                    signal: abortController.signal
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                typingIndicator.style.display = 'none';

                const aiMessageDiv = document.createElement("div");
                aiMessageDiv.classList.add("message");
                chatBox.insertBefore(aiMessageDiv, typingIndicator);

                let fullResponse = "";
                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const {
                        done,
                        value
                    } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value);
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const data = line.slice(6);

                            if (data.trim() === '[DONE]') {
                                continue;
                            }

                            try {
                                const parsed = JSON.parse(data);
                                const content = parsed.choices?.[0]?.delta?.content;

                                if (content) {
                                    fullResponse += content;
                                    aiMessageDiv.textContent = fullResponse;
                                    chatBox.scrollTop = chatBox.scrollHeight;
                                }
                            } catch (e) {
                                // Skip invalid JSON
                            }
                        }
                    }
                }

                conversationHistory.push({
                    role: "assistant",
                    content: fullResponse
                });

                // Save AI response message
                saveMessage('assistant', fullResponse);

                // Only read aloud if this is a voice chat
                if (isVoiceChat) {
                    textToSpeech(fullResponse);
                }

                console.log("消息处理完成");

            } catch (error) {
                typingIndicator.style.display = 'none';

                if (error.name === 'AbortError') {
                    const abortMsg = currentLanguage === 'zh-CN' ? "响应已中断" : "Response interrupted";
                    addSystemMessage(abortMsg);
                    console.log("用户中断了响应");
                } else {
                    console.error("Error:", error);
                    const errorMsg = currentLanguage === 'zh-CN' ? "抱歉,发生错误。请重试。" : "Sorry, an error occurred. Please try again.";
                    addErrorMessage(errorMsg);
                }
            } finally {
                isProcessing = false;
                sendButton.disabled = false;
                stopButton.style.display = 'none';
                abortController = null;
                console.log("处理结束,恢复输入");
            }
        }

        function stopCurrentResponse() {
            console.log("停止当前响应");

            if (abortController) {
                abortController.abort();
            }

            if (currentUtterance) {
                speechSynthesis.cancel();
                currentUtterance = null;
            }

            typingIndicator.style.display = 'none';
            isProcessing = false;
            sendButton.disabled = false;
            stopButton.style.display = 'none';
        }

        function textToSpeech(text) {
            if (!speechSynthesis) {
                return;
            }

            speechSynthesis.cancel();

            currentUtterance = new SpeechSynthesisUtterance(text);

            const isChinese = /[\u4e00-\u9fa5]/.test(text);
            currentUtterance.lang = isChinese ? 'zh-CN' : 'en-US';
            currentUtterance.rate = isChinese ? 1.1 : 1.0;
            currentUtterance.pitch = 1.0;
            currentUtterance.volume = 1.0;

            let voices = speechSynthesis.getVoices();
            if (voices.length === 0) {
                speechSynthesis.onvoiceschanged = () => {
                    voices = speechSynthesis.getVoices();
                    setVoiceAndSpeak(voices);
                };
            } else {
                setVoiceAndSpeak(voices);
            }

            function setVoiceAndSpeak(voices) {
                const preferredVoice = voices.find(voice =>
                    voice.lang.startsWith(currentUtterance.lang)
                );

                if (preferredVoice) {
                    currentUtterance.voice = preferredVoice;
                }

                currentUtterance.onend = () => {
                    currentUtterance = null;
                };

                try {
                    speechSynthesis.speak(currentUtterance);
                } catch (error) {
                    console.error("Speech synthesis error:", error);
                }
            }
        }
        //from here the nav js

        document.addEventListener("DOMContentLoaded", () => {
            const inputGroups = document.querySelectorAll(".input-group");

            if (inputGroups.length > 0) {
                inputGroups.forEach((group) => {
                    const passwordField = group.querySelector("input[type='password']");
                    const eyeIcon = group.querySelector(".fa-eye");

                    if (passwordField && eyeIcon) {
                        eyeIcon.addEventListener("click", () => {
                            const isPassword = passwordField.type === "password";
                            passwordField.type = isPassword ? "text" : "password";
                            eyeIcon.classList.toggle("fa-eye-slash", isPassword);
                            eyeIcon.classList.toggle("fa-eye", !isPassword);
                        });
                    }
                });
            }



            const profileLink = document.getElementById("profileLink");
            const dropdown = document.querySelector(".dropdown"); // Get first matching dropdown

            if (profileLink && dropdown) {
                profileLink.addEventListener("click", (event) => {
                    event.stopPropagation(); // Prevent clicks from closing immediately
                    dropdown.classList.toggle("show"); // Toggle class
                });

                // Hide dropdown when clicking outside
                document.addEventListener("click", (event) => {
                    if (
                        !profileLink.contains(event.target) &&
                        !dropdown.contains(event.target)
                    ) {
                        dropdown.classList.remove("show");
                    }
                });
            }

            // Child Profile Modal Functions
            let childAlertTimeout = null;

            window.showChildProfileModal = function() {
                document.getElementById('childProfileModal').style.display = 'flex';
                document.getElementById('profileDropdown').classList.remove('show');
                clearChildAlert();
            };

            window.closeChildProfileModal = function() {
                document.getElementById('childProfileModal').style.display = 'none';
                clearChildAlert();
                clearChildFormErrors();
            };

            function showChildAlert(message, type = 'success') {
                const alertDiv = document.getElementById('childAlertMessage');
                alertDiv.textContent = message;
                alertDiv.className = type;
                alertDiv.style.display = 'block';

                // Clear existing timeout
                if (childAlertTimeout) {
                    clearTimeout(childAlertTimeout);
                }

                // Auto-hide after 5 seconds
                childAlertTimeout = setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 5000);
            }

            function clearChildAlert() {
                const alertDiv = document.getElementById('childAlertMessage');
                alertDiv.style.display = 'none';
                if (childAlertTimeout) {
                    clearTimeout(childAlertTimeout);
                }
            }

            function clearChildFormErrors() {
                document.querySelectorAll('.profile-error-text').forEach(el => el.textContent = '');
            }

            function displayChildErrors(errors) {
                clearChildFormErrors();
                for (const [field, messages] of Object.entries(errors)) {
                    let errorElementId = 'child' + field.charAt(0).toUpperCase() + field.slice(1) + 'Error';

                    // Handle special field names
                    if (field === 'current-password') {
                        errorElementId = 'childCurrentPasswordError';
                    } else if (field === 'password') {
                        errorElementId = 'childNewPasswordError';
                    } else if (field === 'confirm-password') {
                        errorElementId = 'childConfirmPasswordError';
                    } else if (field === 'name') {
                        errorElementId = 'childNameError';
                    }

                    const errorElement = document.getElementById(errorElementId);
                    if (errorElement && messages.length > 0) {
                        errorElement.textContent = messages[0];
                    }
                }
            }

            // Handle child profile form submission
            const childProfileForm = document.getElementById('childProfileForm');
            if (childProfileForm) {
                childProfileForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    clearChildFormErrors();

                    const formData = new FormData(this);
                    formData.append('update-profile', '1');

                    try {
                        const response = await fetch('update-profile', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            showChildAlert(result.message, 'success');

                            // Update the displayed username
                            if (result.newName) {
                                const userNameSpan = document.querySelector('.profile span');
                                if (userNameSpan) {
                                    userNameSpan.textContent = result.newName;
                                }
                                document.getElementById('child-name').value = result.newName;
                            }
                        } else {
                            if (result.errors) {
                                displayChildErrors(result.errors);
                                showChildAlert('Please fix the errors and try again.', 'error');
                            } else {
                                showChildAlert(result.message || 'An error occurred', 'error');
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showChildAlert('An error occurred. Please try again.', 'error');
                    }
                });
            }

            // Handle child password form submission
            const childPasswordForm = document.getElementById('childPasswordForm');
            if (childPasswordForm) {
                childPasswordForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    clearChildFormErrors();

                    const formData = new FormData(this);
                    formData.append('update-password', '1');

                    try {
                        const response = await fetch('update-profile', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            showChildAlert(result.message, 'success');

                            // Clear password fields
                            this.reset();
                            // Re-add the CSRF token
                            const csrfInput = this.querySelector('input[name="csrf_token"]');
                            csrfInput.value = '<?= htmlspecialchars($csrfToken) ?>';
                        } else {
                            if (result.errors) {
                                displayChildErrors(result.errors);
                                showChildAlert('Please fix the errors and try again.', 'error');
                            } else {
                                showChildAlert(result.message || 'An error occurred', 'error');
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showChildAlert('An error occurred. Please try again.', 'error');
                    }
                });
            }

            // Close modal when clicking outside
            const childProfileModal = document.getElementById('childProfileModal');
            if (childProfileModal) {
                childProfileModal.addEventListener('click', function(event) {
                    if (event.target === this) {
                        closeChildProfileModal();
                    }
                });
            }
        });
    </script>
</body>

</html>