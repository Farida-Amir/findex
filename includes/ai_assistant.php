<?php
// AI Assistant Floating Widget - Advanced Complete Version
?>

<style>
/* ========================================
   AI ASSISTANT ADVANCED STYLES
   ======================================== */

/* Floating Button */
.ai-float-btn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 60px;
    height: 60px;
    border-radius: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f97316 100%);
    color: white;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    z-index: 9999;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    animation: aiPulse 2s infinite;
}

@keyframes aiPulse {
    0% { box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4); }
    50% { box-shadow: 0 4px 30px rgba(249, 115, 22, 0.6); }
    100% { box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4); }
}

.ai-float-btn:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 8px 30px rgba(249, 115, 22, 0.5);
}

/* Modal Overlay */
.ai-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9998;
    animation: fadeIn 0.3s ease;
}

.ai-overlay.show {
    display: block;
}

/* Main Modal */
.ai-modal {
    display: none;
    position: fixed;
    bottom: 100px;
    right: 24px;
    width: 450px;
    max-width: calc(100vw - 48px);
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    z-index: 10000;
    overflow: hidden;
    animation: slideUp 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
}

.ai-modal.open {
    display: block;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Modal Header */
.ai-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f97316 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ai-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.ai-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

/* Tabs */
.ai-tabs {
    display: flex;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 8px;
}

.ai-tab {
    flex: 1;
    padding: 12px 16px;
    text-align: center;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    position: relative;
}

.ai-tab i {
    font-size: 14px;
}

.ai-tab.active {
    color: #f97316;
    background: white;
}

.ai-tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #f97316, #ea580c);
}

.ai-tab:hover:not(.active) {
    color: #334155;
    background: #f8fafc;
}

/* Tab Content */
.ai-tab-content {
    display: none;
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
}

.ai-tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

/* Custom Scrollbar */
.ai-tab-content::-webkit-scrollbar {
    width: 6px;
}

.ai-tab-content::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.ai-tab-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.ai-tab-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Quick Actions Grid */
.ai-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.ai-quick-btn {
    background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
    border: 1px solid #fde68a;
    padding: 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    color: #d97706;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.ai-quick-btn i {
    font-size: 14px;
}

.ai-quick-btn:hover {
    background: linear-gradient(135deg, #fde68a 0%, #fef3c7 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2);
}

/* Form Groups */
.ai-input-group {
    margin-bottom: 16px;
}

.ai-input-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.ai-input-group label i {
    margin-right: 4px;
    color: #f97316;
}

.ai-input-group input,
.ai-input-group textarea,
.ai-input-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 13px;
    font-family: inherit;
    transition: all 0.2s;
    background: white;
}

.ai-input-group input:focus,
.ai-input-group textarea:focus,
.ai-input-group select:focus {
    outline: none;
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.ai-input-group textarea {
    resize: vertical;
    min-height: 80px;
}

/* Submit Button */
.ai-submit-btn {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.ai-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(249, 115, 22, 0.3);
}

.ai-submit-btn:active {
    transform: translateY(0);
}

/* Result Area */
.ai-result {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-left: 4px solid #22c55e;
    padding: 16px;
    border-radius: 16px;
    margin-top: 16px;
    font-size: 13px;
    color: #166534;
    max-height: 250px;
    overflow-y: auto;
    display: none;
    line-height: 1.5;
}

.ai-result.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

.ai-result img {
    max-width: 100%;
    border-radius: 12px;
    margin-top: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Loading Spinner */
.ai-loading {
    display: none;
    text-align: center;
    padding: 30px;
}

.ai-loading.show {
    display: block;
}

.ai-loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #f97316;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Voice Assistant Styles */
.ai-voice-area {
    text-align: center;
    padding: 20px;
}

.ai-voice-btn {
    width: 100px;
    height: 100px;
    border-radius: 50px;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 20px;
    box-shadow: 0 8px 25px rgba(249, 115, 22, 0.3);
    position: relative;
}

.ai-voice-btn.recording {
    animation: voicePulse 1.5s infinite;
    box-shadow: 0 0 0 15px rgba(249, 115, 22, 0.2);
}

@keyframes voicePulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(249, 115, 22, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
}

.ai-voice-btn i {
    font-size: 40px;
    color: white;
}

.ai-voice-status {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 16px;
    padding: 8px;
    background: #f8fafc;
    border-radius: 12px;
}

.ai-voice-text {
    background: #f1f5f9;
    padding: 16px;
    border-radius: 16px;
    font-size: 14px;
    color: #1e293b;
    margin-top: 16px;
    display: none;
    text-align: left;
}

.ai-voice-text.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

.ai-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
    justify-content: center;
}

.ai-suggestion-chip {
    background: #f1f5f9;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 11px;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
}

.ai-suggestion-chip:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
    color: #f97316;
}

/* Behavior Analysis Styles */
.ai-behavior-score {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
}

.ai-behavior-value {
    font-size: 48px;
    font-weight: bold;
    color: white;
    line-height: 1;
}

.ai-behavior-label {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 8px;
}

.ai-risk-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 25px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 12px;
}

.ai-risk-low {
    background: #dcfce7;
    color: #166534;
}

.ai-risk-medium {
    background: #fed7aa;
    color: #9a3412;
}

.ai-risk-high {
    background: #fee2e2;
    color: #991b1b;
}

.ai-insight-item {
    background: #f8fafc;
    padding: 14px;
    border-radius: 14px;
    margin-bottom: 10px;
    font-size: 12px;
    color: #334155;
    border-left: 3px solid #f97316;
    line-height: 1.5;
}

.ai-insight-item.warning {
    border-left-color: #ef4444;
    background: #fef2f2;
}

.ai-insight-item.info {
    border-left-color: #3b82f6;
    background: #eff6ff;
}

.ai-insight-item.success {
    border-left-color: #22c55e;
    background: #f0fdf4;
}

/* Status Indicator */
.ai-status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 4px;
    margin-right: 6px;
}

.ai-status-online {
    background: #22c55e;
    box-shadow: 0 0 5px #22c55e;
    animation: blink 1.5s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Responsive Design */
@media (max-width: 640px) {
    .ai-modal {
        bottom: 80px;
        right: 16px;
        left: 16px;
        width: auto;
        max-width: none;
    }
    
    .ai-quick-actions {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .ai-quick-btn {
        padding: 8px;
        font-size: 10px;
    }
    
    .ai-tab {
        padding: 10px 8px;
        font-size: 11px;
    }
    
    .ai-voice-btn {
        width: 80px;
        height: 80px;
    }
    
    .ai-voice-btn i {
        font-size: 32px;
    }
}
</style>

<!-- AI Floating Button -->
<button class="ai-float-btn" id="aiFloatBtn">
    <i class="fas fa-robot"></i>
</button>

<div class="ai-overlay" id="aiOverlay"></div>

<div class="ai-modal" id="aiModal">
    <div class="ai-modal-header">
        <h3>
            <i class="fas fa-robot"></i> 
            AI Assistant
            <span class="ai-status-indicator ai-status-online"></span>
        </h3>
        <button class="ai-close-btn" id="aiCloseBtn">&times;</button>
    </div>
    
    <div class="ai-tabs">
        <button class="ai-tab active" data-tab="assistant">
            <i class="fas fa-comment-dots"></i> Assistant
        </button>
        <button class="ai-tab" data-tab="voice">
            <i class="fas fa-microphone-alt"></i> Voice
        </button>
        <button class="ai-tab" data-tab="behavior">
            <i class="fas fa-chart-line"></i> Insights
        </button>
    </div>
    
    <!-- Assistant Tab -->
    <div class="ai-tab-content active" id="tab-assistant">
        <div class="ai-quick-actions">
            <button class="ai-quick-btn" onclick="setAIAction('enhance')">
                <i class="fas fa-magic"></i> Enhance Image
            </button>
            <button class="ai-quick-btn" onclick="setAIAction('analyze')">
                <i class="fas fa-file-alt"></i> Analyze Text
            </button>
            <button class="ai-quick-btn" onclick="setAIAction('suspect')">
                <i class="fas fa-user-secret"></i> Generate Suspect
            </button>
            <button class="ai-quick-btn" onclick="setAIAction('reconstruct')">
                <i class="fas fa-gem"></i> Reconstruct Item
            </button>
        </div>
        
        <div id="aiFormContainer">
            <form id="aiForm" method="POST" action="api/ai_process.php" enctype="multipart/form-data">
                <div id="aiFormFields"></div>
                <button type="submit" class="ai-submit-btn" id="aiSubmitBtn">
                    <i class="fas fa-paper-plane"></i> Process Request
                </button>
            </form>
        </div>
        
        <div id="aiLoading" class="ai-loading">
            <div class="ai-loading-spinner"></div>
            <p style="font-size: 12px; color: #64748b;">AI is processing your request...</p>
        </div>
        <div id="aiResult" class="ai-result"></div>
    </div>
    
    <!-- Voice Tab -->
    <div class="ai-tab-content" id="tab-voice">
        <div class="ai-voice-area">
            <button class="ai-voice-btn" id="voiceRecordBtn">
                <i class="fas fa-microphone"></i>
            </button>
            <div class="ai-voice-status" id="voiceStatus">
                <i class="fas fa-info-circle"></i> Click the microphone and speak
            </div>
            <div class="ai-voice-text" id="voiceText"></div>
            <div class="ai-suggestions" id="voiceSuggestions"></div>
        </div>
    </div>
    
    <!-- Behavior Tab -->
    <div class="ai-tab-content" id="tab-behavior">
        <div id="behaviorLoading" class="ai-loading" style="display: none;">
            <div class="ai-loading-spinner"></div>
            <p>Analyzing behavior patterns...</p>
        </div>
        <div id="behaviorContent"></div>
    </div>
</div>

<script>
// ============================================
// AI ASSISTANT - COMPLETE IMPLEMENTATION
// ============================================

// DOM Elements
const aiFloatBtn = document.getElementById('aiFloatBtn');
const aiModal = document.getElementById('aiModal');
const aiOverlay = document.getElementById('aiOverlay');
const aiCloseBtn = document.getElementById('aiCloseBtn');
let currentAction = 'analyze';

// Voice Assistant Variables
let voiceRecognition = null;
let isVoiceSupported = 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;

// Behavior Tracking Variables
let movementData = [];
let clickData = [];
let typingData = [];
let lastMoveTime = Date.now();
let behaviorTrackingInterval = null;
let lastKeyTime = null;

// ============================================
// Modal Controls
// ============================================
function openAIModal() {
    aiModal.classList.add('open');
    aiOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    startBehaviorTracking();
    loadBehaviorData();
    
    // Log modal open
    console.log('AI Assistant opened');
}

function closeAIModal() {
    aiModal.classList.remove('open');
    aiOverlay.classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('aiResult').classList.remove('show');
    stopBehaviorTracking();
}

aiFloatBtn.addEventListener('click', openAIModal);
aiCloseBtn.addEventListener('click', closeAIModal);
aiOverlay.addEventListener('click', closeAIModal);

// Escape key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && aiModal.classList.contains('open')) {
        closeAIModal();
    }
});

// ============================================
// Tab Switching
// ============================================
document.querySelectorAll('.ai-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        
        document.querySelectorAll('.ai-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.ai-tab-content').forEach(c => c.classList.remove('active'));
        
        this.classList.add('active');
        document.getElementById(`tab-${tabId}`).classList.add('active');
        
        if (tabId === 'behavior') {
            loadBehaviorData();
        }
        if (tabId === 'voice' && isVoiceSupported && !voiceRecognition) {
            initVoiceRecognition();
        }
    });
});

// ============================================
// Assistant Functions
// ============================================
function setAIAction(action) {
    currentAction = action;
    const container = document.getElementById('aiFormFields');
    document.getElementById('aiResult').classList.remove('show');
    let html = '';
    
    switch(action) {
        case 'enhance':
            html = `
                <div class="ai-input-group">
                    <label><i class="fas fa-image"></i> Upload Image <span style="color: #ef4444;">*</span></label>
                    <input type="file" name="image" accept="image/*" required>
                    <small style="font-size: 10px; color: #64748b;">Supports JPG, PNG, GIF. Max 10MB</small>
                </div>
                <input type="hidden" name="action" value="enhance_image">
            `;
            break;
        case 'analyze':
            html = `
                <div class="ai-input-group">
                    <label><i class="fas fa-gem"></i> Describe Your Jewelry <span style="color: #ef4444;">*</span></label>
                    <textarea name="description" rows="4" placeholder="Example: 18k gold ring with 2 carat diamond, floral design, engraved with initials 'JD'..." required></textarea>
                </div>
                <input type="hidden" name="action" value="analyze_description">
            `;
            break;
        case 'suspect':
            html = `
                <div class="ai-input-group">
                    <label><i class="fas fa-user"></i> Describe the Suspect <span style="color: #ef4444;">*</span></label>
                    <textarea name="suspect_description" rows="4" placeholder="Age: 30-35, Height: 5'10, Build: Medium, Hair: Black, Clothing: Dark hoodie, Distinctive: Tattoo on left hand..." required></textarea>
                </div>
                <input type="hidden" name="action" value="generate_suspect">
            `;
            break;
        case 'reconstruct':
            html = `
                <div class="ai-input-group">
                    <label><i class="fas fa-ring"></i> Describe the Jewelry Item <span style="color: #ef4444;">*</span></label>
                    <textarea name="item_description" rows="4" placeholder="Type: Necklace, Material: White gold, Gemstones: Sapphire, Design: Vintage, Chain length: 18 inches..." required></textarea>
                </div>
                <input type="hidden" name="action" value="reconstruct_item">
            `;
            break;
    }
    container.innerHTML = html;
}

document.getElementById('aiForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const loading = document.getElementById('aiLoading');
    const resultDiv = document.getElementById('aiResult');
    
    loading.classList.add('show');
    resultDiv.classList.remove('show');
    
    try {
        const response = await fetch('api/ai_process.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        loading.classList.remove('show');
        
        if (data.success) {
            resultDiv.innerHTML = `
                <i class="fas fa-robot" style="margin-bottom: 8px; display: block;"></i>
                <strong>🤖 AI Response:</strong><br>
                <div style="margin-top: 8px;">${data.message.replace(/\n/g, '<br>')}</div>
                ${data.image ? `<div class="mt-3"><img src="${data.image}" alt="AI Generated" style="max-width:100%; border-radius:12px; margin-top:12px;"></div>` : ''}
            `;
            resultDiv.classList.add('show');
            
            // Scroll to result
            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            resultDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                <strong>Error:</strong> ${data.error}
            `;
            resultDiv.classList.add('show');
        }
    } catch (error) {
        loading.classList.remove('show');
        resultDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
            <strong>Network Error:</strong> Please check your connection and try again.
        `;
        resultDiv.classList.add('show');
    }
});

// ============================================
// Voice Assistant Functions
// ============================================
function initVoiceRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    
    if (!SpeechRecognition) {
        document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Voice recognition not supported in your browser. Try Chrome, Edge, or Safari.';
        return;
    }
    
    voiceRecognition = new SpeechRecognition();
    voiceRecognition.continuous = false;
    voiceRecognition.interimResults = false;
    voiceRecognition.lang = 'en-US';
    voiceRecognition.maxAlternatives = 1;
    
    voiceRecognition.onstart = function() {
        document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-circle" style="color: #ef4444; font-size: 10px;"></i> Listening... Speak clearly now';
        document.getElementById('voiceRecordBtn').classList.add('recording');
        document.getElementById('voiceText').classList.remove('show');
    };
    
    voiceRecognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        const confidence = event.results[0][0].confidence;
        
        document.getElementById('voiceText').innerHTML = `
            <i class="fas fa-quote-left" style="color: #f97316;"></i>
            <strong>You said:</strong> "${transcript}"
            <div style="font-size: 11px; color: #64748b; margin-top: 5px;">Confidence: ${Math.round(confidence * 100)}%</div>
        `;
        document.getElementById('voiceText').classList.add('show');
        document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Processing your request...';
        
        processVoiceCommand(transcript);
    };
    
    voiceRecognition.onerror = function(event) {
        console.error('Voice recognition error:', event.error);
        let errorMsg = 'Please try again.';
        if (event.error === 'not-allowed') errorMsg = 'Microphone access denied. Please allow microphone access.';
        if (event.error === 'no-speech') errorMsg = 'No speech detected. Please try again.';
        
        document.getElementById('voiceStatus').innerHTML = `<i class="fas fa-exclamation-triangle"></i> Error: ${errorMsg}`;
        document.getElementById('voiceRecordBtn').classList.remove('recording');
    };
    
    voiceRecognition.onend = function() {
        document.getElementById('voiceRecordBtn').classList.remove('recording');
        if (!document.getElementById('voiceText').classList.contains('show')) {
            document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-microphone"></i> Click the microphone and speak';
        }
    };
}

function startVoiceRecording() {
    if (!voiceRecognition) {
        initVoiceRecognition();
        setTimeout(() => {
            if (voiceRecognition) {
                try {
                    voiceRecognition.start();
                } catch(e) {
                    document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please click again to start';
                }
            }
        }, 100);
    } else {
        try {
            voiceRecognition.start();
        } catch(e) {
            document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please wait and try again';
        }
    }
}

async function processVoiceCommand(command) {
    try {
        const response = await fetch('api/ai_voice.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=process_voice&voice_text=' + encodeURIComponent(command) + '&command_type=general'
        });
        const data = await response.json();
        
        if (data.success) {
            const result = data.data;
            document.getElementById('voiceStatus').innerHTML = `
                <i class="fas fa-check-circle" style="color: #22c55e;"></i> 
                ${result.response_text.substring(0, 100)}${result.response_text.length > 100 ? '...' : ''}
            `;
            
            // Show suggestions
            if (result.entities && Object.keys(result.entities).length > 0) {
                let suggestionsHtml = '';
                if (result.entities.item_type) {
                    suggestionsHtml += `<div class="ai-suggestion-chip" onclick="processSuggestion('file report for ${result.entities.item_type[0]}')">
                        <i class="fas fa-file-alt"></i> File report for ${result.entities.item_type[0]}
                    </div>`;
                }
                suggestionsHtml += `<div class="ai-suggestion-chip" onclick="processSuggestion('help')">
                    <i class="fas fa-question-circle"></i> Get help
                </div>`;
                document.getElementById('voiceSuggestions').innerHTML = suggestionsHtml;
            } else {
                document.getElementById('voiceSuggestions').innerHTML = '';
            }
            
            // Speak response
            speakResponse(result.response_text);
            
            // Execute action if needed
            if (result.action_taken) {
                handleVoiceAction(result.action_taken, result.entities);
            }
        } else {
            document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Sorry, I couldn\'t process that. Please try again.';
        }
    } catch (error) {
        console.error('Voice command error:', error);
        document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error. Please check your connection.';
    }
}

function processSuggestion(suggestion) {
    document.getElementById('voiceText').innerHTML = `
        <i class="fas fa-quote-left" style="color: #f97316;"></i>
        <strong>Selected:</strong> "${suggestion}"
    `;
    document.getElementById('voiceText').classList.add('show');
    processVoiceCommand(suggestion);
}

function handleVoiceAction(action, entities) {
    console.log('Voice action:', action, entities);
    
    switch(action) {
        case 'file_report':
            if (window.location.href.indexOf('process_report.php') === -1) {
                window.location.href = 'process_report.php';
            }
            break;
        case 'search':
            if (entities && entities.item_type) {
                window.location.href = 'search.php?q=' + encodeURIComponent(entities.item_type[0]);
            } else {
                window.location.href = 'search.php';
            }
            break;
        case 'my_reports':
            window.location.href = 'my_reports.php';
            break;
        case 'generate_suspect':
            document.querySelector('.ai-tab[data-tab="assistant"]').click();
            setAIAction('suspect');
            break;
        case 'dashboard':
            window.location.href = 'dashboard_user.php';
            break;
        case 'profile':
            window.location.href = 'edit_profile.php';
            break;
        case 'analyze_jewelry':
            document.querySelector('.ai-tab[data-tab="assistant"]').click();
            setAIAction('analyze');
            break;
    }
}

function speakResponse(text) {
    if ('speechSynthesis' in window) {
        // Cancel any ongoing speech
        window.speechSynthesis.cancel();
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.volume = 1;
        
        // Optional: Select a female voice if available
        const voices = window.speechSynthesis.getVoices();
        const femaleVoice = voices.find(voice => voice.name.includes('Google UK') || voice.name.includes('Samantha'));
        if (femaleVoice) utterance.voice = femaleVoice;
        
        window.speechSynthesis.speak(utterance);
    }
}

// Load voice help when needed
async function loadVoiceHelp() {
    try {
        const response = await fetch('api/ai_voice.php?action=voice_help');
        const data = await response.json();
        
        if (data.success) {
            let helpHtml = '<div style="margin-top: 16px;"><strong><i class="fas fa-lightbulb"></i> Things you can say:</strong></div>';
            for (const [category, description] of Object.entries(data.data.categories)) {
                helpHtml += `
                    <div class="ai-suggestion-chip" style="display: block; margin: 8px 0; text-align: left;" onclick="processSuggestion('${description.split("'")[0]}')">
                        <i class="fas fa-comment"></i> ${category}: ${description}
                    </div>
                `;
            }
            document.getElementById('voiceSuggestions').innerHTML = helpHtml;
            document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-info-circle"></i> Try one of these voice commands';
        }
    } catch (error) {
        console.error('Error loading voice help:', error);
    }
}

// Add help button to voice tab
document.getElementById('voiceSuggestions').innerHTML = `
    <div class="ai-suggestion-chip" onclick="loadVoiceHelp()">
        <i class="fas fa-question-circle"></i> Show me what I can say
    </div>
    <div class="ai-suggestion-chip" onclick="processSuggestion('help')">
        <i class="fas fa-robot"></i> Get AI assistance
    </div>
`;

document.getElementById('voiceRecordBtn').addEventListener('click', startVoiceRecording);

// ============================================
// Behavior Analysis Functions
// ============================================
function startBehaviorTracking() {
    if (behaviorTrackingInterval) clearInterval(behaviorTrackingInterval);
    
    movementData = [];
    clickData = [];
    typingData = [];
    lastMoveTime = Date.now();
    
    // Track mouse movements
    document.addEventListener('mousemove', trackMouseMovement);
    document.addEventListener('click', trackClick);
    document.addEventListener('keydown', trackKeyPress);
    
    // Send data every 30 seconds
    behaviorTrackingInterval = setInterval(sendBehaviorData, 30000);
    
    // Send data on page unload
    window.addEventListener('beforeunload', sendBehaviorData);
}

function stopBehaviorTracking() {
    document.removeEventListener('mousemove', trackMouseMovement);
    document.removeEventListener('click', trackClick);
    document.removeEventListener('keydown', trackKeyPress);
    
    if (behaviorTrackingInterval) {
        clearInterval(behaviorTrackingInterval);
        behaviorTrackingInterval = null;
    }
    
    sendBehaviorData();
}

function trackMouseMovement(e) {
    const now = Date.now();
    if (now - lastMoveTime > 50) {
        movementData.push({
            x: e.clientX,
            y: e.clientY,
            timestamp: now
        });
        lastMoveTime = now;
        
        if (movementData.length > 100) movementData.shift();
    }
}

function trackClick(e) {
    clickData.push({
        x: e.clientX,
        y: e.clientY,
        target: e.target.tagName,
        timestamp: Date.now()
    });
    
    if (clickData.length > 50) clickData.shift();
}

function trackKeyPress(e) {
    const now = Date.now();
    typingData.push({
        key: e.key,
        timestamp: now,
            timeSinceLast: lastKeyTime ? now - lastKeyTime : 0
    });
    lastKeyTime = now;
    
    if (typingData.length > 100) typingData.shift();
}

function sendBehaviorData() {
    if (movementData.length === 0 && clickData.length === 0 && typingData.length === 0) return;
    
    const behaviorData = {
        movements: movementData.slice(),
        clicks: clickData.slice(),
        typing: typingData.slice(),
        timestamp: Date.now()
    };
    
    fetch('api/ai_behavior.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=track_behavior&behavior_data=' + encodeURIComponent(JSON.stringify(behaviorData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data && document.getElementById('tab-behavior').classList.contains('active')) {
            updateBehaviorDisplay(data.data);
        }
        
        if (data.data && data.data.risk_level === 'high') {
            showBehaviorWarning(data.data.recommendation);
        }
    })
    .catch(error => console.log('Behavior tracking error:', error));
    
    movementData = [];
    clickData = [];
    typingData = [];
}

async function loadBehaviorData() {
    const behaviorContent = document.getElementById('behaviorContent');
    const loading = document.getElementById('behaviorLoading');
    loading.style.display = 'block';
    
    try {
        const response = await fetch('api/ai_behavior.php?action=get_behavior_insights');
        const data = await response.json();
        
        loading.style.display = 'none';
        
        if (data.success) {
            displayBehaviorInsights(data.data);
        } else {
            behaviorContent.innerHTML = '<div class="ai-insight-item warning"><i class="fas fa-exclamation-triangle"></i> Unable to load behavior data. Please try again.</div>';
        }
    } catch (error) {
        loading.style.display = 'none';
        behaviorContent.innerHTML = '<div class="ai-insight-item warning"><i class="fas fa-wifi"></i> Error loading behavior data. Please check your connection.</div>';
    }
}

function displayBehaviorInsights(data) {
    const riskClass = data.average_score > 70 ? 'ai-risk-high' : (data.average_score > 40 ? 'ai-risk-medium' : 'ai-risk-low');
    const riskText = data.average_score > 70 ? '⚠️ High Risk' : (data.average_score > 40 ? '📊 Medium Risk' : '✅ Low Risk');
    
    let html = `
        <div class="ai-behavior-score">
            <div class="ai-behavior-value">${data.average_score}</div>
            <div class="ai-behavior-label">Behavior Score (0-100)</div>
            <span class="ai-risk-badge ${riskClass}">${riskText}</span>
        </div>
        
        <div class="ai-insight-item info">
            <i class="fas fa-chart-simple"></i>
            <strong>${data.total_sessions} sessions analyzed</strong>
        </div>
    `;
    
    for (const insight of data.insights) {
        let icon = '📌';
        let warningClass = 'info';
        
        if (insight.includes('⚠️') || insight.includes('🚨')) {
            warningClass = 'warning';
            icon = '⚠️';
        } else if (insight.includes('✅')) {
            warningClass = 'success';
            icon = '✅';
        }
        
        html += `<div class="ai-insight-item ${warningClass}">${icon} ${insight}</div>`;
    }
    
    html += `
        <div class="ai-insight-item info">
            <i class="fas fa-chart-pie"></i>
            <strong>Risk Distribution:</strong><br>
            🟢 Low: ${data.risk_distribution.low} sessions<br>
            🟡 Medium: ${data.risk_distribution.medium} sessions<br>
            🔴 High: ${data.risk_distribution.high} sessions
        </div>
        
        <div class="ai-insight-item success">
            <i class="fas fa-shield-alt"></i>
            <strong>Privacy Note:</strong> Your behavior data is only used for security purposes and is never shared with third parties.
        </div>
    `;
    
    document.getElementById('behaviorContent').innerHTML = html;
}

function updateBehaviorDisplay(behaviorData) {
    const behaviorValue = document.querySelector('.ai-behavior-value');
    if (behaviorValue) {
        behaviorValue.textContent = behaviorData.behavior_score;
        
        const badge = document.querySelector('.ai-risk-badge');
        if (badge) {
            const riskClass = behaviorData.behavior_score > 70 ? 'ai-risk-high' : (behaviorData.behavior_score > 40 ? 'ai-risk-medium' : 'ai-risk-low');
            const riskText = behaviorData.behavior_score > 70 ? '⚠️ High Risk' : (behaviorData.behavior_score > 40 ? '📊 Medium Risk' : '✅ Low Risk');
            badge.className = `ai-risk-badge ${riskClass}`;
            badge.textContent = riskText;
        }
    }
}

function showBehaviorWarning(message) {
    const resultDiv = document.getElementById('aiResult');
    const currentTab = document.querySelector('.ai-tab.active').getAttribute('data-tab');
    
    if (currentTab === 'assistant') {
        resultDiv.innerHTML = `
            <i class="fas fa-shield-alt" style="color: #f97316;"></i>
            <strong>Security Alert:</strong><br>
            ${message}
        `;
        resultDiv.classList.add('show');
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (resultDiv.classList.contains('show')) {
                resultDiv.classList.remove('show');
            }
        }, 5000);
    }
}

// ============================================
// Initialize Assistant
// ============================================
setAIAction('analyze');

// Initialize voice if supported
if (isVoiceSupported) {
    initVoiceRecognition();
}

// Add Font Awesome if not present
if (!document.querySelector('link[href*="font-awesome"]') && !document.querySelector('link[href*="fontawesome"]')) {
    const fontAwesome = document.createElement('link');
    fontAwesome.rel = 'stylesheet';
    fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
    document.head.appendChild(fontAwesome);
}

console.log('AI Assistant loaded successfully');
</script>