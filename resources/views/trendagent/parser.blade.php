<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TrendAgent Parser - Управление парсером</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 30px;
            color: #60a5fa;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #334155;
        }
        
        .card h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #60a5fa;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-indicator.running {
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulse 2s infinite;
        }
        
        .status-indicator.stopped {
            background: #6b7280;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .status-text {
            font-size: 18px;
            font-weight: 600;
        }
        
        .status-details {
            margin-top: 16px;
            padding: 12px;
            background: #0f172a;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .status-details div {
            margin: 4px 0;
        }
        
        .controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 14px;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 14px;
        }
        
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .stat-item {
            padding: 16px;
            background: #0f172a;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #60a5fa;
        }
        
        .stat-label {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        .logs-container {
            grid-column: 1 / -1;
        }
        
        .logs {
            background: #0f172a;
            border-radius: 8px;
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.5;
        }
        
        .logs::-webkit-scrollbar {
            width: 8px;
        }
        
        .logs::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        .logs::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        
        .message.show {
            display: block;
        }
        
        .message.success {
            background: #10b98120;
            border: 1px solid #10b981;
            color: #10b981;
        }
        
        .message.error {
            background: #ef444420;
            border: 1px solid #ef4444;
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 TrendAgent Parser</h1>
        
        <div id="message" class="message"></div>
        
        <div class="grid">
            <!-- Status Card -->
            <div class="card">
                <h2>📊 Статус</h2>
                <div>
                    <span class="status-indicator" id="statusIndicator"></span>
                    <span class="status-text" id="statusText">Загрузка...</span>
                </div>
                <div class="status-details" id="statusDetails"></div>
            </div>
            
            <!-- Controls Card -->
            <div class="card">
                <h2>⚙️ Управление</h2>
                <form id="parserForm">
                    <div class="form-group">
                        <label>Регион</label>
                        <select name="region">
                            <option value="spb" selected>Санкт-Петербург</option>
                            <option value="msk">Москва</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Тип объектов</label>
                        <select name="type">
                            <option value="complexes" selected>Комплексы (с квартирами)</option>
                            <option value="all">Все типы</option>
                            <option value="apartments">Квартиры</option>
                            <option value="parkings">Паркинги</option>
                            <option value="houses">Дома</option>
                            <option value="plots">Участки</option>
                            <option value="commercial">Коммерческая</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Лимит объектов</label>
                        <input type="number" name="limit" value="100000" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="details" checked>
                            Парсить детали (включая все квартиры комплексов)
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="save_raw" checked>
                            Сохранять сырые данные
                        </label>
                    </div>
                    
                    <div class="controls">
                        <button type="submit" class="btn btn-primary" id="btnStart">
                            ▶️ Запустить
                        </button>
                        <button type="button" class="btn btn-danger" id="btnStop">
                            ⏹️ Остановить
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Card -->
            <div class="card" style="grid-column: 1 / -1;">
                <h2>📈 Статистика</h2>
                <div class="stats-grid" id="statsGrid">
                    <div class="stat-item">
                        <div class="stat-value" id="totalFiles">0</div>
                        <div class="stat-label">Всего файлов</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="totalSize">0 MB</div>
                        <div class="stat-label">Общий размер</div>
                    </div>
                </div>
                <div id="typeStats" style="margin-top: 20px;"></div>
            </div>
            
            <!-- Logs Card -->
            <div class="card logs-container">
                <h2>📋 Логи (обновление каждые 2 сек)</h2>
                <div class="logs" id="logs">Логи появятся здесь...</div>
            </div>
        </div>
    </div>
    
    <script>
        const API_BASE = '/trendagent/parser';
        let updateInterval = null;
        
        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Elements
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        const statusDetails = document.getElementById('statusDetails');
        const logs = document.getElementById('logs');
        const btnStart = document.getElementById('btnStart');
        const btnStop = document.getElementById('btnStop');
        const message = document.getElementById('message');
        const parserForm = document.getElementById('parserForm');
        
        // Show message
        function showMessage(text, type = 'success') {
            message.textContent = text;
            message.className = `message ${type} show`;
            setTimeout(() => message.classList.remove('show'), 5000);
        }
        
        // Update status
        async function updateStatus() {
            try {
                const response = await fetch(`${API_BASE}/status`);
                const data = await response.json();
                
                if (data.running) {
                    statusIndicator.className = 'status-indicator running';
                    statusText.textContent = 'Парсер запущен';
                    btnStart.disabled = true;
                    btnStop.disabled = false;
                    
                    const duration = Math.floor(data.duration || 0);
                    const hours = Math.floor(duration / 3600);
                    const minutes = Math.floor((duration % 3600) / 60);
                    const seconds = duration % 60;
                    
                    statusDetails.innerHTML = `
                        <div>PID: ${data.pid}</div>
                        <div>Запущен: ${data.started_at}</div>
                        <div>Длительность: ${hours}ч ${minutes}м ${seconds}с</div>
                    `;
                } else {
                    statusIndicator.className = 'status-indicator stopped';
                    statusText.textContent = 'Парсер остановлен';
                    btnStart.disabled = false;
                    btnStop.disabled = true;
                    statusDetails.innerHTML = '<div>Парсер не запущен</div>';
                }
            } catch (error) {
                console.error('Error updating status:', error);
            }
        }
        
        // Update logs
        async function updateLogs() {
            try {
                const response = await fetch(`${API_BASE}/logs?lines=200`);
                const data = await response.json();
                
                if (data.success) {
                    logs.textContent = data.logs;
                    logs.scrollTop = logs.scrollHeight;
                }
            } catch (error) {
                // Ignore errors - logs might not exist yet
            }
        }
        
        // Update statistics
        async function updateStatistics() {
            try {
                const response = await fetch(`${API_BASE}/statistics`);
                const stats = await response.json();
                
                document.getElementById('totalFiles').textContent = stats.total_files;
                document.getElementById('totalSize').textContent = stats.total_size_mb + ' MB';
                
                const typeStatsHtml = Object.entries(stats.by_type).map(([type, data]) => `
                    <div style="margin-top: 8px; padding: 8px; background: #0f172a; border-radius: 6px;">
                        <strong>${type}:</strong> ${data.files} файлов (${data.size_mb} MB)
                    </div>
                `).join('');
                
                document.getElementById('typeStats').innerHTML = typeStatsHtml;
            } catch (error) {
                console.error('Error updating statistics:', error);
            }
        }
        
        // Start parser
        parserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(parserForm);
            const data = {
                region: formData.get('region'),
                type: formData.get('type'),
                limit: parseInt(formData.get('limit')),
                details: formData.get('details') === 'on',
                save_raw: formData.get('save_raw') === 'on',
            };
            
            try {
                const response = await fetch(`${API_BASE}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(data),
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Парсер запущен!', 'success');
                    updateStatus();
                    startAutoUpdate();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('Ошибка запуска: ' + error.message, 'error');
            }
        });
        
        // Stop parser
        btnStop.addEventListener('click', async () => {
            try {
                const response = await fetch(`${API_BASE}/stop`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Парсер остановлен!', 'success');
                    updateStatus();
                    stopAutoUpdate();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('Ошибка остановки: ' + error.message, 'error');
            }
        });
        
        // Auto-update
        function startAutoUpdate() {
            if (updateInterval) return;
            updateInterval = setInterval(() => {
                updateStatus();
                updateLogs();
                updateStatistics();
            }, 2000);
        }
        
        function stopAutoUpdate() {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        }
        
        // Initial load
        updateStatus();
        updateStatistics();
        startAutoUpdate();
    </script>
</body>
</html>
