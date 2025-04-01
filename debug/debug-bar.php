<?php
// Debug verilerini al
$debugData = $debug_data ?? [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Bar</title>
    <style>
        /* Debug Bar için özel namespace - tüm seçiciler dbg_ öneki ile başlıyor */
        #dbg_container * {
            box-sizing: border-box;
            font-family: 'Fira Code', 'Courier New', monospace;
        }

        /* CSS Variables for theming */
        #dbg_container {
            --dbg-bg-color: #1e1e2e;
            --dbg-text-color: #cdd6f4;
            --dbg-accent-color: #74c7ec;
            --dbg-error-color: #f38ba8;
            --dbg-warning-color: #f9e2af;
            --dbg-success-color: #a6e3a1;
            --dbg-muted-color: #6c7086;
            --dbg-highlight-bg: #313244;
            --dbg-panel-bg: #24273a;
            --dbg-border-color: #6c7086;
            --dbg-border-radius: 8px;
            --dbg-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            --dbg-transition: all 0.3s ease;
            --dbg-z-index: 99999;
            --dbg-overlay-opacity: 0.95;
            --dbg-font-size: 13px;
            --dbg-line-height: 1.4;
        }

        /* Light theme support */
        #dbg_container.dbg_light_theme {
            --dbg-bg-color: #eff1f5;
            --dbg-text-color: #4c4f69;
            --dbg-accent-color: #1e66f5;
            --dbg-error-color: #d20f39;
            --dbg-warning-color: #df8e1d;
            --dbg-success-color: #40a02b;
            --dbg-muted-color: #9ca0b0;
            --dbg-highlight-bg: #e6e9ef;
            --dbg-panel-bg: #ccd0da;
            --dbg-border-color: #8c8fa1;
        }

        /* Main debug container */
        #dbg_container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: var(--dbg-z-index);
            background-color: var(--dbg-bg-color);
            color: var(--dbg-text-color);
            font-size: var(--dbg-font-size);
            line-height: var(--dbg-line-height);
            border-top: 1px solid var(--dbg-border-color);
            box-shadow: var(--dbg-shadow);
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            transition: var(--dbg-transition);
        }

        #dbg_container.dbg_minimized {
            transform: translateY(calc(100% - 32px));
        }

        /* Header with toggle buttons and stats */
        .dbg_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 12px;
            background-color: var(--dbg-bg-color);
            border-bottom: 1px solid var(--dbg-border-color);
            cursor: pointer;
        }

        .dbg_header_title {
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dbg_header_badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            background-color: var(--dbg-accent-color);
            color: var(--dbg-bg-color);
            font-size: 11px;
            font-weight: bold;
        }

        .dbg_header_badge.dbg_error {
            background-color: var(--dbg-error-color);
        }

        .dbg_header_actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .dbg_btn {
            background: transparent;
            border: 1px solid var(--dbg-border-color);
            border-radius: var(--dbg-border-radius);
            color: var(--dbg-text-color);
            padding: 3px 8px;
            cursor: pointer;
            font-size: 12px;
            transition: var(--dbg-transition);
        }

        .dbg_btn:hover {
            background-color: var(--dbg-accent-color);
            color: var(--dbg-bg-color);
        }

        .dbg_theme_toggle {
            background: transparent;
            border: none;
            color: var(--dbg-text-color);
            cursor: pointer;
            font-size: 16px;
        }

        /* Tab Navigation */
        .dbg_nav {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            background-color: var(--dbg-panel-bg);
            padding: 0 8px;
            border-bottom: 1px solid var(--dbg-border-color);
        }

        .dbg_nav_tab {
            padding: 8px 12px;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--dbg-muted-color);
            position: relative;
            transition: var(--dbg-transition);
            white-space: nowrap;
        }

        .dbg_nav_tab:hover {
            color: var(--dbg-text-color);
        }

        .dbg_nav_tab.dbg_active {
            color: var(--dbg-accent-color);
        }

        .dbg_nav_tab.dbg_active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--dbg-accent-color);
        }

        .dbg_nav_tab .dbg_badge {
            display: inline-block;
            background-color: var(--dbg-accent-color);
            color: var(--dbg-bg-color);
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 5px;
            min-width: 16px;
            text-align: center;
        }

        .dbg_nav_tab .dbg_badge.dbg_error {
            background-color: var(--dbg-error-color);
        }

        /* Tab Content */
        .dbg_content {
            flex: 1;
            overflow-y: auto;
            position: relative;
        }

        .dbg_tab_content {
            display: none;
            padding: 12px;
            min-height: 200px;
            max-height: calc(80vh - 90px);
            overflow-y: auto;
        }

        .dbg_tab_content.dbg_active {
            display: block;
        }

        /* Data tables */
        .dbg_table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .dbg_table th, .dbg_table td {
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid var(--dbg-border-color);
        }

        .dbg_table th {
            background-color: var(--dbg-panel-bg);
            color: var(--dbg-accent-color);
            font-weight: bold;
        }

        .dbg_table tr:hover td {
            background-color: var(--dbg-highlight-bg);
        }

        /* Log entries */
        .dbg_log_item {
            margin-bottom: 6px;
            padding: 8px 10px;
            background-color: var(--dbg-panel-bg);
            border-left: 3px solid var(--dbg-muted-color);
            border-radius: var(--dbg-border-radius);
        }

        .dbg_log_item.dbg_info {
            border-left-color: var(--dbg-accent-color);
        }

        .dbg_log_item.dbg_warning {
            border-left-color: var(--dbg-warning-color);
        }

        .dbg_log_item.dbg_error {
            border-left-color: var(--dbg-error-color);
        }

        .dbg_log_header {
            display: flex;
            gap: 12px;
            margin-bottom: 4px;
            font-size: 12px;
            color: var(--dbg-muted-color);
        }

        .dbg_log_type {
            font-weight: bold;
        }

        .dbg_log_type.dbg_info {
            color: var(--dbg-accent-color);
        }

        .dbg_log_type.dbg_warning {
            color: var(--dbg-warning-color);
        }

        .dbg_log_type.dbg_error {
            color: var(--dbg-error-color);
        }

        .dbg_log_message {
            white-space: pre-wrap;
            word-break: break-word;
            font-family: 'Fira Code', monospace;
        }

        /* Error items */
        .dbg_error_item {
            margin-bottom: 12px;
            padding: 10px;
            background-color: var(--dbg-panel-bg);
            border-left: 3px solid var(--dbg-error-color);
            border-radius: var(--dbg-border-radius);
        }

        .dbg_error_header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .dbg_error_type {
            font-weight: bold;
            color: var(--dbg-error-color);
        }

        .dbg_error_message {
            margin-bottom: 8px;
            color: var(--dbg-text-color);
            font-weight: bold;
        }

        .dbg_error_location {
            font-size: 12px;
            color: var(--dbg-muted-color);
            margin-bottom: 8px;
        }

        .dbg_error_trace {
            font-family: 'Fira Code', monospace;
            white-space: pre-wrap;
            margin-top: 8px;
            padding: 8px;
            background-color: var(--dbg-bg-color);
            border-radius: var(--dbg-border-radius);
            font-size: 12px;
            max-height: 180px;
            overflow-y: auto;
        }

        /* Code block styling */
        .dbg_code {
            background-color: var(--dbg-bg-color);
            padding: 8px 12px;
            border-radius: var(--dbg-border-radius);
            overflow-x: auto;
            white-space: pre;
            font-family: 'Fira Code', monospace;
        }

        /* Collapsible elements */
        .dbg_collapsible {
            cursor: pointer;
            padding: 8px 10px;
            width: 100%;
            border: none;
            text-align: left;
            outline: none;
            background-color: var(--dbg-panel-bg);
            color: var(--dbg-text-color);
            border-radius: var(--dbg-border-radius);
            margin: 4px 0;
            position: relative;
        }

        .dbg_collapsible:after {
            content: '+';
            position: absolute;
            right: 10px;
            transition: var(--dbg-transition);
        }

        .dbg_active.dbg_collapsible:after {
            content: '−';
        }

        .dbg_collapse_content {
            padding: 0 10px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: var(--dbg-bg-color);
            border-radius: var(--dbg-border-radius);
        }
        
        /* Search inputs */
        .dbg_search {
            display: flex;
            margin-bottom: 10px;
        }

        .dbg_search_input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--dbg-border-color);
            border-radius: var(--dbg-border-radius);
            background-color: var(--dbg-bg-color);
            color: var(--dbg-text-color);
            font-size: 13px;
        }

        /* File list */
        .dbg_file_item {
            padding: 6px 8px;
            border-radius: var(--dbg-border-radius);
            margin-bottom: 3px;
            transition: var(--dbg-transition);
        }

        .dbg_file_item:hover {
            background-color: var(--dbg-highlight-bg);
        }

        .dbg_file_path {
            font-size: 12px;
            color: var(--dbg-accent-color);
            word-break: break-all;
        }

        /* Query items */
        .dbg_query_item {
            margin-bottom: 8px;
            padding: 10px;
            background-color: var(--dbg-panel-bg);
            border-radius: var(--dbg-border-radius);
        }

        .dbg_query_sql {
            margin-bottom: 8px;
            padding: 8px;
            background-color: var(--dbg-bg-color);
            border-radius: var(--dbg-border-radius);
            overflow-x: auto;
            white-space: pre-wrap;
            font-family: 'Fira Code', monospace;
        }

        .dbg_query_info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--dbg-muted-color);
        }

        .dbg_time {
            color: var(--dbg-accent-color);
            font-weight: bold;
        }

        /* Variables */
        .dbg_var_item {
            margin-bottom: 6px;
        }

        .dbg_var_name {
            font-weight: bold;
            padding: 6px 8px;
            background-color: var(--dbg-panel-bg);
            border-radius: var(--dbg-border-radius) var(--dbg-border-radius) 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }

        .dbg_var_value {
            padding: 8px;
            background-color: var(--dbg-bg-color);
            border-radius: 0 0 var(--dbg-border-radius) var(--dbg-border-radius);
            overflow-x: auto;
            display: none;
            white-space: pre-wrap;
            font-family: 'Fira Code', monospace;
        }

        .dbg_var_value.dbg_active {
            display: block;
        }

        /* Tarayıcı bilgileri için stiller */
        .dbg_browser_container {
            padding: 10px 0;
        }

        .dbg_browser_container h3 {
            margin-bottom: 12px;
            color: var(--dbg-accent-color);
        }

        .dbg_browser_container h4 {
            margin: 20px 0 10px;
            color: var(--dbg-accent-color);
            border-bottom: 1px solid var(--dbg-border-color);
            padding-bottom: 5px;
        }

        .dbg_browser_container .dbg_info {
            color: var(--dbg-muted-color);
            font-size: 12px;
            margin-bottom: 15px;
        }

        .dbg_browser_container .dbg_table {
            margin-top: 10px;
        }

        .dbg_browser_container .dbg_table td:first-child {
            font-weight: bold;
            width: 30%;
        }
        #dbg_container pre {
           
            margin-top: 0;
            margin-bottom: 1rem;
            overflow: auto;
            font-size: 87.5%;
            color:rgb(0, 148, 57);
            padding: 0.2rem;
        }

        /* Error query styling */
        .dbg_error_query {
            background-color: rgba(244, 67, 54, 0.1);
            border-left: 3px solid #f44336;
        }

        .dbg_error_badge {
            background-color: #f44336;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            margin-left: 8px;
        }

        .dbg_error_message {
            background-color: rgba(244, 67, 54, 0.05);
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            color: #d32f2f;
            font-family: monospace;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .dbg_header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .dbg_header_actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .dbg_nav {
                overflow-x: auto;
                padding-bottom: 5px;
            }
            
            .dbg_tab_content {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Ana debug konteyner - tamamen izole edilmiş -->
    <div id="dbg_container" class="dbg_minimized">
        
        <!-- Header - basit stats ve toggle butonları -->
        <div class="dbg_header" id="dbg_header">
            <div class="dbg_header_title">
                <span>Debug Bar</span>
                <span class="dbg_header_badge">PHP <?= phpversion() ?></span>
                
                <?php 
                // Güvenli sayı formatlaması
                $executionTime = 0;
                if (isset($debugData['execution_time']) && is_numeric($debugData['execution_time'])) {
                    $executionTime = microtime(true) - $debugData['execution_time'];
                }
                
                $memoryUsage = 0;
                if (isset($debugData['memory_usage']) && is_numeric($debugData['memory_usage'])) {
                    $memoryUsage = $debugData['memory_usage'] / 1024 / 1024;
                }
                ?>
                
                <span class="dbg_header_badge"><?= number_format($executionTime, 4) ?> sn</span>
                <span class="dbg_header_badge"><?= number_format($memoryUsage, 1) ?> MB</span>
                
                <?php if (!empty($debugData['errors'])): ?>
                <span class="dbg_header_badge dbg_error"><?= count($debugData['errors']) ?> Hata</span>
                <?php endif; ?>
            </div>
            
            <div class="dbg_header_actions">
                <button class="dbg_theme_toggle" id="dbg_theme_toggle">🔆</button>
                <button class="dbg_btn" id="dbg_close_btn">Kapat</button>
            </div>
        </div>
        
        <!-- Navigasyon menüsü -->
        <div class="dbg_nav">
            <button class="dbg_nav_tab dbg_active" data-tab="general">📊 Genel</button>
            <button class="dbg_nav_tab" data-tab="queries">🐬 Sql Sorguları <span class="dbg_badge"><?= count($debugData['queries']) ?></span></button>
            <button class="dbg_nav_tab" data-tab="logs">📝 Loglar <span class="dbg_badge"><?= count($debugData['logs']) ?></span></button>
            <button class="dbg_nav_tab" data-tab="requests">🔄 İstekler <span class="dbg_badge"><?= count($debugData['requests']) ?></span></button>
            <button class="dbg_nav_tab" data-tab="twig">🌱 Twig <span class="dbg_badge"><?= count($debugData['twig_logs']) ?></span></button>
            <button class="dbg_nav_tab" data-tab="latte">☕ Latte <span class="dbg_badge"><?= count($debugData['latte_logs']) ?></span></button>
            <button class="dbg_nav_tab" data-tab="files">📁 Dosyalar</button>
            <button class="dbg_nav_tab" data-tab="browser">🌐 Tarayıcı</button>
            <button class="dbg_nav_tab" data-tab="resources">🖼️ Kaynaklar <span class="dbg_badge dbg_error" id="resourceErrorCount">0</span></button>
            <?php if (!empty($debugData['errors'])): ?>
            <button class="dbg_nav_tab" data-tab="errors">⚠️ Hatalar <span class="dbg_badge dbg_error"><?= count($debugData['errors']) ?></span></button>
            <?php endif; ?>
        </div>
        
        <!-- Tab içerikleri -->
        <div class="dbg_content">
        
            <!-- Genel bilgiler -->
            <div class="dbg_tab_content dbg_active" data-tab="general">
                <table class="dbg_table">
                    <tr>
                        <th>Ortam</th>
                        <td><?= DEBUG ? 'Geliştirme' : 'Üretim' ?></td>
                    </tr>
                    <tr>
                        <th>PHP Versiyonu</th>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <th>Yürütme Süresi</th>
                        <td><?= number_format($debugData['execution_time'], 4) ?> sn</td>
                    </tr>
                    <tr>
                        <th>Bellek Kullanımı</th>
                        <td><?= number_format($debugData['memory_usage'] / 1024 / 1024, 2) ?> MB</td>
                    </tr>
                    <tr>
                        <th>Sorgu Sayısı</th>
                        <td><?= count($debugData['queries']) ?></td>
                    </tr>
                    <tr>
                        <th>Log Sayısı</th>
                        <td><?= count($debugData['logs']) ?></td>
                    </tr>
                    <tr>
                        <th>Dahil Edilen Dosyalar</th>
                        <td><?= isset($debugData['includedFiles']) ? count($debugData['includedFiles']) : 'Bilinmiyor' ?></td>
                    </tr>
                    <tr>
                        <th>Hata Sayısı</th>
                        <td><?= count($debugData['errors']) ?></td>
                    </tr>
                    <tr>
                        <th>İstek Metodu</th>
                        <td><?= $_SERVER['REQUEST_METHOD'] ?? 'Bilinmiyor' ?></td>
                    </tr>
                    <tr>
                        <th>İstek URL</th>
                        <td><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Bilinmiyor') ?></td>
                    </tr>
                    <tr>
                        <th>IP Adresi</th>
                        <td><?= $_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor' ?></td>
                    </tr>
                    <tr>
                        <th>Session ID</th>
                        <td><?= session_id() ?: 'Oturum başlatılmamış' ?></td>
                    </tr>
                </table>
                
                <h3>Oturum İçeriği</h3>
                <?php if (empty($_SESSION)): ?>
                <div class="dbg_log_item">
                    <div class="dbg_log_message">Oturum boş veya başlatılmamış.</div>
                </div>
                <?php else: ?>
                <?php foreach ($_SESSION as $key => $value): ?>
                <div class="dbg_var_item">
                    <div class="dbg_var_name" onclick="toggleVarValue(this)">
                        <?= htmlspecialchars($key) ?>
                        <span>+</span>
                    </div>
                    <div class="dbg_var_value">
                        <pre><?= htmlspecialchars(print_r($value, true)) ?></pre>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sorgular -->
            <div class="dbg_tab_content" data-tab="queries">
                <div class="dbg_search">
                    <input type="text" class="dbg_search_input" id="querySearch" placeholder="SQL sorgularında ara..." oninput="filterQueries()">
                </div>
                
                <?php if (empty($debugData['queries'])): ?>
                <div class="dbg_log_item">
                    <div class="dbg_log_message">SQL sorgusu bulunamadı.</div>
                </div>
                <?php else: ?>
                <?php foreach ($debugData['queries'] as $index => $query): ?>
                <div class="dbg_query_item <?= isset($query['is_error']) && $query['is_error'] ? 'dbg_error_query' : '' ?>" data-search="<?= htmlspecialchars($query['sql']) ?>">
                    <div class="dbg_query_info">
                        <span class="dbg_query_time"><?= number_format($query['time'] * 1000, 2) ?> ms</span>
                        <?php if (isset($query['is_error']) && $query['is_error']): ?>
                            <span class="dbg_error_badge">HATA</span>
                        <?php endif; ?>
                    </div>
                    <div class="dbg_collapsible">
                        <code class="dbg_query_text"><?= htmlspecialchars($query['sql']) ?></code>
                    </div>
                    <div class="dbg_collapse_content">
                        <?php if (isset($query['is_error']) && $query['is_error']): ?>
                            <div class="dbg_error_message">
                                <strong>Hata:</strong> <?= htmlspecialchars($query['error']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($query['bindings'])): ?>
                            <div class="dbg_query_params">
                                <strong>Parametreler:</strong>
                                <pre><?= htmlspecialchars(print_r($query['bindings'], true)) ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Loglar -->
            <div class="dbg_tab_content" data-tab="logs">
                <div class="dbg_search">
                    <input type="text" class="dbg_search_input" id="logSearch" placeholder="Loglarda ara..." oninput="filterLogs()">
                </div>
                
                <?php if (empty($debugData['logs'])): ?>
                <div class="dbg_log_item">
                    <div class="dbg_log_message">Log kaydı bulunamadı.</div>
                </div>
                <?php else: ?>
                <?php foreach ($debugData['logs'] as $log): ?>
                <div class="dbg_log_item dbg_<?= $log['type'] ?>" data-search="<?= htmlspecialchars(is_string($log['message']) ? $log['message'] : '') ?>">
                    <div class="dbg_log_header">
                        <div class="dbg_log_time"><?= $log['datetime'] ?></div>
                        <div class="dbg_log_type dbg_<?= $log['type'] ?>">[<?= strtoupper($log['type']) ?>]</div>
                    </div>
                    <div class="dbg_log_message">
                        <?php if (is_string($log['message'])): ?>
                            <?= htmlspecialchars($log['message']) ?>
                        <?php else: ?>
                            <pre><?= htmlspecialchars(print_r($log['message'], true)) ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- İstekler -->
            <div class="dbg_tab_content" data-tab="requests">
                <?php if (empty($debugData['requests'])): ?>
                <div class="dbg_log_item">
                    <div class="dbg_log_message">HTTP istek kaydı bulunamadı.</div>
                </div>
                <?php else: ?>
                <?php foreach ($debugData['requests'] as $request): ?>
                <div class="dbg_query_item">
                    <div class="dbg_query_info">
                        <div>
                            <strong><?= $request['method'] ?></strong> <?= htmlspecialchars($request['url']) ?>
                        </div>
                        <div>
                            <span class="dbg_time">
                                <?= number_format(
    (is_numeric($log['time'] ?? 0) ? (float)$log['time'] : 0) - 
    (float)($_SERVER['REQUEST_TIME_FLOAT'] ?? 0), 4) ?> sn
                            </span>
                        </div>
                    </div>
                    
                    <button class="dbg_collapsible" >Detaylar</button>
                    <div class="dbg_collapse_content">
                        <h4>Headers</h4>
                        <div class="dbg_code"><?= htmlspecialchars(print_r($request['headers'] ?? [], true)) ?></div>
                        
                        <?php if (!empty($request['payload'])): ?>
                        <h4>Payload</h4>
                        <div class="dbg_code"><?= htmlspecialchars(print_r($request['payload'], true)) ?></div>
                        <?php endif; ?>
                        
                        <h4>Response</h4>
                        <div class="dbg_code"><?= htmlspecialchars(substr($request['response'] ?? '', 0, 1000)) ?><?php if (strlen($request['response'] ?? '') > 1000): ?>... (kesildi)<?php endif; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Twig Logları -->
            <div class="dbg_tab_content" data-tab="twig">
                <?php if (empty($debugData['twig_logs'])): ?>
                <div class="dbg_log_item">
                    <div class="dbg_log_message">Twig log kaydı bulunamadı.</div>
                </div>
                <?php else: ?>
                <table class="dbg_table">
                    <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>Şablon</th>
                            <th>Mesaj</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($debugData['twig_logs'] as $log): ?>
                        <tr>
                            <td><?= number_format(
                                (is_numeric($log['time'] ?? 0) ? (float)$log['time'] : 0) - 
                                (float)($_SERVER['REQUEST_TIME_FLOAT'] ?? 0), 4) ?> sn</td>
                            <td><?= htmlspecialchars($log['template'] ?? 'Bilinmiyor') ?></td>
                            <td>
                                <pre><?php 
                                    if (is_string($log['message'])) {
                                        echo htmlspecialchars($log['message']);
                                    } else {
                                        echo htmlspecialchars(print_r($log['message'], true));
                                    }
                                ?></pre>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Latte Logları -->
            <div class="dbg_tab_content" data-tab="latte">
                <?php if (empty($debugData['latte_logs'])): ?>
                <div class="dbg_log_item">
                    <div class="dbg_log_message">Latte log kaydı bulunamadı.</div>
                </div>
                <?php else: ?>
                <table class="dbg_table">
                    <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>Şablon</th>
                            <th>Mesaj</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($debugData['latte_logs'] as $log): ?>
                        <tr>
                            <td>
                                <?php
                                // Güvenli sayı formatlaması
                                $logTime = isset($log['time']) && is_numeric($log['time']) ? (float)$log['time'] : 0;
                                $requestTime = isset($_SERVER['REQUEST_TIME_FLOAT']) ? (float)$_SERVER['REQUEST_TIME_FLOAT'] : 0;
                                echo number_format($logTime - $requestTime, 4);
                                ?> sn
                            </td>
                            <td><?= htmlspecialchars($log['template'] ?? 'Bilinmiyor') ?></td>
                            <td>
                                <pre><?php 
                                    if (is_string($log['message'])) {
                                        echo htmlspecialchars($log['message']);
                                    } else {
                                        echo htmlspecialchars(print_r($log['message'], true));
                                    }
                                ?></pre>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Dosyalar -->
            <div class="dbg_tab_content" data-tab="files">
                <div class="dbg_search">
                    <input type="text" class="dbg_search_input" id="fileSearch" placeholder="Dosyalarda ara..." oninput="filterFiles()">
                </div>
                
                <?php
                // Dahil edilen dosyaları al
                $includedFiles = $debugData['includedFiles'] ?? get_included_files();
                $fileCount = count($includedFiles);
                $totalSize = 0;
                
                foreach ($includedFiles as $file) {
                    if (file_exists($file)) {
                        $totalSize += filesize($file);
                    }
                }
                ?>
                
                <div class="dbg_log_item dbg_info">
                    <div class="dbg_log_message">
                        <strong><?= $fileCount ?> dosya dahil edildi</strong> (Toplam boyut: <?= number_format($totalSize / 1024, 2) ?> KB)
                    </div>
                </div>
                
                <?php foreach ($includedFiles as $file): ?>
                <div class="dbg_file_item" data-search="<?= htmlspecialchars($file) ?>">
                    <div class="dbg_file_path"><?= htmlspecialchars($file) ?></div>
                    <?php if (file_exists($file)): ?>
                    <div class="dbg_file_info">
                        <?= number_format(filesize($file) / 1024, 2) ?> KB
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Tarayıcı Bilgileri -->
            <div class="dbg_tab_content" data-tab="browser">
                <div class="dbg_browser_container">
                    <h3>Tarayıcı ve Sistem Bilgileri</h3>
                    <p class="dbg_info">Bu bilgiler JavaScript aracılığıyla toplanmaktadır.</p>
                    
                    <div id="dbg_browser_info">Yükleniyor...</div>
                    
                    <h4>Tarayıcı Özellikleri</h4>
                    <div id="dbg_browser_features"></div>

                    <h4>Donanım Bilgileri</h4>
                    <div id="dbg_hardware_info"></div>
                    
                    <h4>Grafik ve Medya Yetenekleri</h4>
                    <div id="dbg_media_capabilities"></div>

                    <h4>Çerezler ve Depolama</h4>
                    <div id="dbg_storage_info"></div>
                </div>
            </div>
            
            <!-- Kaynaklar -->
            <div class="dbg_tab_content" data-tab="resources">
                <div id="dbg_resource_errors">
                    <div class="dbg_log_item">
                        <div class="dbg_log_message">Sayfa kaynakları yükleniyor...</div>
                    </div>
                </div>
            </div>
            
            <!-- Hatalar -->
            <?php if (!empty($debugData['errors'])): ?>
            <div class="dbg_tab_content" data-tab="errors">
                <?php foreach ($debugData['errors'] as $error): ?>
                <div class="dbg_error_item">
                    <div class="dbg_error_header">
                        <span class="dbg_error_type"><?= htmlspecialchars($error['type']) ?></span>
                        <?php if (!empty($error['fatal'])): ?>
                        <span class="dbg_header_badge dbg_error">FATAL</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dbg_error_message"><?= htmlspecialchars($error['message']) ?></div>
                    
                    <div class="dbg_error_location">
                        Dosya: <?= htmlspecialchars($error['file']) ?> (Satır: <?= $error['line'] ?>)
                    </div>
                    
                    <?php if (!empty($error['trace'])): ?>
                    <button class="dbg_collapsible" >Stack Trace</button>
                    <div class="dbg_collapse_content">
                        <pre class="dbg_error_trace"><?= htmlspecialchars(is_string($suggestion) ? $suggestion : json_encode($suggestion)) ?></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error['suggestions'])): ?>
                    <button class="dbg_collapsible" >Öneriler</button>
                    <div class="dbg_collapse_content">
                        <ul>
                            <?php foreach ($error['suggestions'] as $suggestion): ?>
                            <li><?= htmlspecialchars($suggestion) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- İzole edilmiş JavaScript - başka JS kütüphaneleri ile çakışmayı önler -->
    <script>
    // Event delegasyonu yaklaşımını kullanan yeni kod
    (function() {
        // Sabit elementleri tanımla
        const dbg_container = document.getElementById('dbg_container');
        const dbg_themeToggle = document.getElementById('dbg_theme_toggle');
        
        // Tüm tıklama olaylarını document seviyesinde yakalayıp ilgili hedefe yönlendir
        document.addEventListener('click', function(e) {
            // Debug başlığına tıklama - aç/kapat
            if (e.target.closest('#dbg_header') || e.target.closest('.dbg_header_title')) {
                if (dbg_container) {
                    dbg_container.classList.toggle('dbg_minimized');
                    console.log('Debug bar toggle clicked');
                }
            }
            
            // Tema değiştirme
            if (e.target.closest('#dbg_theme_toggle')) {
                e.stopPropagation();
                if (dbg_container) {
                    dbg_container.classList.toggle('dbg_light_theme');
                    if (dbg_themeToggle) {
                        dbg_themeToggle.innerText = dbg_container.classList.contains('dbg_light_theme') ? '🌙' : '🔆';
                    }
                    console.log('Theme toggle clicked');
                }
            }
            
            // Debug bar'ı kapat
            if (e.target.closest('#dbg_close_btn')) {
                e.stopPropagation();
                if (dbg_container) {
                    dbg_container.style.display = 'none';
                    console.log('Debug bar closed');
                }
            }
            
            // Tab değiştirme
            if (e.target.closest('.dbg_nav_tab')) {
                const tab = e.target.closest('.dbg_nav_tab');
                const targetTab = tab.getAttribute('data-tab');
                
                if (targetTab) {
                    // Tüm tabları ve içerikleri sıfırla
                    document.querySelectorAll('.dbg_nav_tab').forEach(t => t.classList.remove('dbg_active'));
                    document.querySelectorAll('.dbg_tab_content').forEach(tc => tc.classList.remove('dbg_active'));
                    
                    // Seçilen sekmeyi ve içeriğini aktifleştir
                    tab.classList.add('dbg_active');
                    const activeTabContent = document.querySelector(`.dbg_tab_content[data-tab="${targetTab}"]`);
                    if (activeTabContent) {
                        activeTabContent.classList.add('dbg_active');
                    }
                    console.log('Tab changed to:', targetTab);
                }
            }
            
            //Açılır-kapanır elementler için
            if (e.target.closest('.dbg_collapsible')) {
                toggleCollapsible(e.target.closest('.dbg_collapsible'));
            }
            
            // Değişken değerlerini göster/gizle

            if (e.target.closest('.dbg_var_name')) {
                toggleVarValue(e.target.closest('.dbg_var_name'));
            }

        });
        
        // Tarayıcı bilgilerini yükle
        loadBrowserInfo();
    })();

    // Açılır-kapanır elementler için global fonksiyon
    window.toggleCollapsible = function(element) {
        // console satırını kaldırdım (yanlış koddu)
       console.log(element);
        element.classList.toggle('dbg_active');
        const content = element.nextElementSibling;
        if (content && content.classList.contains('dbg_collapse_content')) {
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }
        }
    };
    // Değişken değerlerini göster/gizle için global fonksiyon
    window.toggleVarValue = function(element) {
        // + veya - işaretini değiştir
        const toggleIndicator = element.querySelector('span');
        if (toggleIndicator) {
            toggleIndicator.textContent = toggleIndicator.textContent === '+' ? '-' : '+';
        }
        
        // Değişken değerini göster/gizle
        const valueElement = element.nextElementSibling;
        if (valueElement && valueElement.classList.contains('dbg_var_value')) {
            valueElement.classList.toggle('dbg_active');
        }
    };

    // Tarayıcı bilgilerini topla
// Tarayıcı bilgilerini topla
function loadBrowserInfo() {
    // Tarayıcı bilgileri
    const browserInfo = document.getElementById('dbg_browser_info');
    const browserFeatures = document.getElementById('dbg_browser_features');
    const hardwareInfo = document.getElementById('dbg_hardware_info');
    const mediaCapabilities = document.getElementById('dbg_media_capabilities');
    const storageInfo = document.getElementById('dbg_storage_info');
    
    // Temel tarayıcı bilgileri
    const browserData = {
        'Kullanıcı Ajanı': navigator.userAgent,
        'Tarayıcı': navigator.appName + ' ' + navigator.appVersion,
        'Platform': navigator.platform,
        'Dil': navigator.language,
        'Çerezler Aktif': navigator.cookieEnabled ? 'Evet' : 'Hayır',
        'Do Not Track': navigator.doNotTrack ? 'Aktif' : 'Pasif',
        'Çevrimiçi': navigator.onLine ? 'Evet' : 'Hayır'
    };
    
    // Tablo olarak göster
    browserInfo.innerHTML = createTable(browserData);
    
    // Tarayıcı özellikleri
    const features = {
        'LocalStorage': typeof localStorage !== 'undefined',
        'SessionStorage': typeof sessionStorage !== 'undefined',
        'IndexedDB': typeof indexedDB !== 'undefined',
        'Web Workers': typeof Worker !== 'undefined',
        'Service Workers': 'serviceWorker' in navigator,
        'Fetch API': typeof fetch !== 'undefined',
        'WebSockets': typeof WebSocket !== 'undefined',
        'Web Audio': typeof AudioContext !== 'undefined',
        'WebRTC': typeof RTCPeerConnection !== 'undefined',
        'WebGL': detectWebGL(),
        'WebGL2': detectWebGL2(),
        'WebP': detectWebP(),
        'WebAssembly': typeof WebAssembly !== 'undefined',
        'Geolocation': 'geolocation' in navigator,
        'Notifications': 'Notification' in window,
        'Push API': 'PushManager' in window,
        'Payment Request API': 'PaymentRequest' in window,
        'Web Share API': 'share' in navigator,
        'Credential Management': 'credentials' in navigator,
        'Web Bluetooth': 'bluetooth' in navigator,
        'Web USB': 'usb' in navigator
    };
    
    browserFeatures.innerHTML = createFeatureTable(features);
    
    // Donanım bilgileri
    const hardware = {
        'Ekran Çözünürlüğü': `${window.screen.width}x${window.screen.height}`,
        'Renk Derinliği': `${window.screen.colorDepth} bit`,
        'Piksel Oranı': window.devicePixelRatio,
        'Maksimum Dokunma Noktası': navigator.maxTouchPoints || 'Bilinmiyor'
    };

    if (navigator.hardwareConcurrency) {
        hardware['İşlemci Çekirdek Sayısı'] = navigator.hardwareConcurrency;
    }

    if (navigator.deviceMemory) {
        hardware['Cihaz Hafızası'] = navigator.deviceMemory + ' GB';
    }

    hardwareInfo.innerHTML = createTable(hardware);
    
    // Medya Özellikleri
    const media = {
        'Video Codec Desteği': checkVideoSupport(),
        'Audio Codec Desteği': checkAudioSupport(),
        'Canvas Desteği': typeof CanvasRenderingContext2D !== 'undefined',
        'WebGL Desteği': detectWebGL() ? 'Evet' : 'Hayır',
        'WebGL2 Desteği': detectWebGL2() ? 'Evet' : 'Hayır',
        'WebRTC Desteği': typeof RTCPeerConnection !== 'undefined' ? 'Evet' : 'Hayır'
    };

    mediaCapabilities.innerHTML = createTable(media);
    
    // Depolama ve Çerez Bilgileri
    checkStorageSize().then(storage => {
        storageInfo.innerHTML = createTable(storage);
    });
}

// Yardımcı fonksiyonlar
function createTable(data) {
    let html = '<table class="dbg_table">';
    for (const [key, value] of Object.entries(data)) {
        html += `<tr>
            <td>${key}</td>
            <td>${value}</td>
        </tr>`;
    }
    html += '</table>';
    return html;
}

function createFeatureTable(features) {
    let html = '<table class="dbg_table">';
    for (const [feature, supported] of Object.entries(features)) {
        const status = supported === true ? 
            '<span style="color:var(--dbg-success-color);">✓ Destekliyor</span>' : 
            '<span style="color:var(--dbg-error-color);">✗ Desteklemiyor</span>';
        html += `<tr>
            <td>${feature}</td>
            <td>${status}</td>
        </tr>`;
    }
    html += '</table>';
    return html;
}

function detectWebGL() {
    try {
        const canvas = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && 
            (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
    } catch (e) {
        return false;
    }
}

function detectWebGL2() {
    try {
        const canvas = document.createElement('canvas');
        return !!(window.WebGL2RenderingContext && canvas.getContext('webgl2'));
    } catch (e) {
        return false;
    }
}

function detectWebP() {
    const elem = document.createElement('canvas');
    if (elem.getContext && elem.getContext('2d')) {
        return elem.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }
    return false;
}

function checkVideoSupport() {
    const video = document.createElement('video');
    const formats = {
        'MP4/H.264': 'video/mp4; codecs="avc1.42E01E"',
        'WebM/VP8': 'video/webm; codecs="vp8"',
        'WebM/VP9': 'video/webm; codecs="vp9"',
        'Ogg/Theora': 'video/ogg; codecs="theora"'
    };
    
    const supported = [];
    for (const [format, codec] of Object.entries(formats)) {
        if (video.canPlayType(codec) !== '') {
            supported.push(format);
        }
    }
    
    return supported.length ? supported.join(', ') : 'Bilinmiyor';
}

function checkAudioSupport() {
    const audio = document.createElement('audio');
    const formats = {
        'MP3': 'audio/mpeg',
        'AAC': 'audio/aac',
        'Ogg/Vorbis': 'audio/ogg; codecs="vorbis"',
        'WAV': 'audio/wav',
        'FLAC': 'audio/flac'
    };
    
    const supported = [];
    for (const [format, codec] of Object.entries(formats)) {
        if (audio.canPlayType(codec) !== '') {
            supported.push(format);
        }
    }
    
    return supported.length ? supported.join(', ') : 'Bilinmiyor';
}

async function checkStorageSize() {
    const storage = {
        'Çerez Desteği': navigator.cookieEnabled ? 'Aktif' : 'Pasif'
    };
    
    if (typeof localStorage !== 'undefined') {
        storage['LocalStorage Desteği'] = 'Evet';
        try {
            let estimatedSize = 0;
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                const value = localStorage.getItem(key);
                estimatedSize += key.length + value.length;
            }
            storage['LocalStorage Kullanımı'] = `~${Math.round(estimatedSize / 1024)} KB`;
        } catch (e) {
            storage['LocalStorage Kullanımı'] = 'Hesaplanamadı (Erişim kısıtlı)';
        }
    } else {
        storage['LocalStorage Desteği'] = 'Hayır';
    }
    
    if (typeof sessionStorage !== 'undefined') {
        storage['SessionStorage Desteği'] = 'Evet';
    } else {
        storage['SessionStorage Desteği'] = 'Hayır';
    }
    
    if (navigator.storage && navigator.storage.estimate) {
        try {
            const estimate = await navigator.storage.estimate();
            storage['Site Veri Kullanımı'] = `${Math.round(estimate.usage / 1024 / 1024)} MB / ${Math.round(estimate.quota / 1024 / 1024)} MB`;
        } catch (e) {
            storage['Site Veri Kullanımı'] = 'Hesaplanamadı (Erişim kısıtlı)';
        }
    }
    
    return storage;
}

    // Arama fonksiyonları
    window.filterQueries = function() {
        const searchText = document.getElementById('querySearch').value.toLowerCase();
        const queryItems = document.querySelectorAll('.dbg_query_item');
        
        queryItems.forEach(item => {
            const searchContent = item.getAttribute('data-search').toLowerCase();
            item.style.display = searchContent.includes(searchText) ? '' : 'none';
        });
    };

    window.filterLogs = function() {
        const searchText = document.getElementById('logSearch').value.toLowerCase();
        const logItems = document.querySelectorAll('.dbg_log_item');
        
        logItems.forEach(item => {
            const searchContent = (item.getAttribute('data-search') || '').toLowerCase();
            item.style.display = searchContent.includes(searchText) ? '' : 'none';
        });
    };

    window.filterFiles = function() {
        const searchText = document.getElementById('fileSearch').value.toLowerCase();
        const fileItems = document.querySelectorAll('.dbg_file_item');
        
        fileItems.forEach(item => {
            const searchContent = item.getAttribute('data-search').toLowerCase();
            item.style.display = searchContent.includes(searchText) ? '' : 'none';
        });
    };




    // Resource hatalarını izleyen kod - debug-bar.php script bölümüne ekleyin
    (function() {
        // Kaynak hatalarını toplamak için dizi
        const resourceErrors = [];
        
        // Sayfa tamamen yüklendikten sonra
        window.addEventListener('load', function() {
            // Performance API kullanarak kaynak hatalarını tespit et
            if (window.performance && window.performance.getEntriesByType) {
                const resources = window.performance.getEntriesByType('resource');
                
                // Tüm kaynakları kontrol et
                resources.forEach(resource => {
                    // Hatalarını tespit için networktiming verilerine bak
                    // responseEnd === 0 genellikle yüklenememiş kaynak demektir
                    if (resource.responseEnd === 0 || resource.transferSize === 0) {
                        resourceErrors.push({
                            url: resource.name,
                            type: resource.initiatorType,
                            status: 'Failed to load',
                            time: new Date().toLocaleTimeString()
                        });
                    }
                });
            }
            
            // Kaydedilen hataları görüntüle
            updateResourceErrorsDisplay();
        });
        
        // Sayfa yüklenirken oluşan hataları yakala
        window.addEventListener('error', function(e) {
            // Sadece kaynak hatalarını filtrele (img, script, css, vb.)
            if (e.target && e.target.tagName) {
                const tag = e.target.tagName.toLowerCase();
                
                if (['img', 'script', 'link', 'audio', 'video', 'iframe'].includes(tag)) {
                    resourceErrors.push({
                        url: e.target.src || e.target.href,
                        type: tag,
                        status: 'Failed to load',
                        time: new Date().toLocaleTimeString()
                    });
                    
                    // Hataları güncelleyin
                    updateResourceErrorsDisplay();
                }
            }
        }, true); // true = capture phase
        
        // Fetch/XHR hatalarını yakalamak için
        const originalFetch = window.fetch;
        window.fetch = function() {
            return originalFetch.apply(this, arguments)
                .catch(error => {
                    const url = arguments[0];
                    resourceErrors.push({
                        url: typeof url === 'string' ? url : url.url,
                        type: 'fetch',
                        status: error.message,
                        time: new Date().toLocaleTimeString()
                    });
                    
                    updateResourceErrorsDisplay();
                    throw error;
                });
        };
        
        // Hataları görüntüleme
        function updateResourceErrorsDisplay() {
            const errorCount = resourceErrors.length;
            document.getElementById('resourceErrorCount').textContent = errorCount;
            
            const container = document.getElementById('dbg_resource_errors');
            if (container) {
                if (errorCount === 0) {
                    container.innerHTML = `
                        <div class="dbg_log_item">
                            <div class="dbg_log_message">Kaynak hatası bulunamadı.</div>
                        </div>`;
                } else {
                    // Hata tablosunu oluştur
                    let html = `
                        <div class="dbg_log_item dbg_error">
                            <div class="dbg_log_message">Toplam ${errorCount} kaynak hatası tespit edildi.<br>Bazı dış kaynaklar Cross Block boxing Takılma durumunda hata verbilmektedir.</div>
                        </div>
                        <table class="dbg_table">
                            <thead>
                                <tr>
                                    <th>Zaman</th>
                                    <th>Tür</th>
                                    <th>URL</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    
                    resourceErrors.forEach(err => {
                        html += `
                            <tr>
                                <td>${err.time}</td>
                                <td>${err.type}</td>
                                <td><div style="max-width:500px;overflow:hidden;text-overflow:ellipsis;">${err.url}</div></td>
                                <td>${err.status}</td>
                            </tr>`;
                    });
                    
                    html += `
                            </tbody>
                        </table>`;
                    
                    container.innerHTML = html;
                }
            }
        }
    })();
    </script>
</body>
</html>