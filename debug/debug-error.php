<?php
// Hata bilgilerini tanımla
$errorDetails = $errorDetails ?? [
    'type' => 'Hata',
    'message' => 'Bilinmeyen hata',
    'file' => 'Bilinmeyen dosya',
    'line' => 0,
    'trace' => '',
    'suggestions' => []
];

// Çıktı tamponunu yakala
$debug_output = '';
if (ob_get_length()) {
    $debug_output = ob_get_contents();
    ob_clean();
}

// Debug çıktısını parçala
$debug_parts = [];
if (!empty($debug_output)) {
    // <pre> etiketlerini bul
    if (preg_match_all('/(.*?)(<pre>.*?<\/pre>)/s', $debug_output, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $debug_parts[] = ['type' => 'text', 'content' => $match[1]];
            }
            if (!empty($match[2])) {
                $debug_parts[] = ['type' => 'dump', 'content' => $match[2]];
            }
        }
        
        // Son metin parçasını ekle
        $lastPreEnd = strrpos($debug_output, '</pre>') + 6;
        $remainingText = substr($debug_output, $lastPreEnd);
        if (!empty($remainingText)) {
            $debug_parts[] = ['type' => 'text', 'content' => $remainingText];
        }
    } else {
        // Hiç <pre> etiketi yoksa, tüm çıktıyı metin olarak ekle
        $debug_parts[] = ['type' => 'text', 'content' => $debug_output];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hata Ayıklama</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Genel Sayfa Stilleri */
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
            --error-bg: rgba(239, 68, 68, 0.1);
            --error-color: #ef4444;
            --suggestion-bg: rgba(59, 130, 246, 0.1);
            --suggestion-color: #3b82f6;
            --trace-bg: #f1f5f9;
            --trace-color: #334155;
            --debug-bg: #ecfdf5;
            --debug-color: #059669;
            --dump-bg: #0f172a;
            --dump-color: #38bdf8;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        /* Koyu Tema Desteği */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #0f172a;
                --card-bg: #1e293b;
                --text-color: #e2e8f0;
                --border-color: rgba(255, 255, 255, 0.1);
                --error-bg: rgba(239, 68, 68, 0.15);
                --error-color: #f87171;
                --suggestion-bg: rgba(59, 130, 246, 0.15);
                --suggestion-color: #60a5fa;
                --trace-bg: #0f172a;
                --trace-color: #94a3b8;
                --debug-bg: rgba(5, 150, 105, 0.15);
                --debug-color: #34d399;
                --dump-bg: #020617;
                --dump-color: #7dd3fc;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 20px;
            transition: var(--transition);
            line-height: 1.6;
        }

        .container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1000px;
            padding: 0;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
            border: 1px solid var(--border-color);
            margin: 20px 0;
        }

        .header {
            padding: 24px 30px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            background-color: var(--error-bg);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .header-text h1 {
            font-size: 20px;
            font-weight: 600;
            color: var(--error-color);
            margin: 0;
        }

        .header-text p {
            font-size: 12px;
            margin: 0;
            opacity: 0.8;
        }

        .content {
            padding: 30px;
        }

        /* Debug bölümü stilleri */
        .debug-section {
            background: var(--debug-bg);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--debug-color);
        }
        
        .debug-section h3 {
            color: var(--debug-color);
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
        }

      


        .debug-output {
            margin-bottom: 15px;
        }
        
        .debug-text {
            font-family: monospace;
            font-size: 14px;
            white-space: pre-wrap;
            color: var(--debug-color);
            margin-bottom: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        .debug-dump {
            margin-bottom: 10px;
        }
        
        .debug-dump pre {
            background: var(--dump-bg);
            color: var(--dump-color);
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        
        /* Hata detayları stilleri */
        .error-details {
            background: var(--error-bg);
            border-radius: var(--border-radius);
            padding: 20px;
            color: var(--error-color);
            font-size: 12px;
            border-left: 4px solid var(--error-color);
            margin-bottom: 20px;
        }

        .error-details strong {
            display: inline-block;
            min-width: 100px;
        }

        .error-details div {
            margin-bottom: 8px;
        }

        .error-details div:last-child {
            margin-bottom: 0;
        }

        .suggestions {
            background: var(--suggestion-bg);
            border-radius: var(--border-radius);
            padding: 20px;
            color: var(--suggestion-color);
            font-size: 12px;
            border-left: 4px solid var(--suggestion-color);
            margin-bottom: 20px;
        }

        .suggestions h3 {
            color: var(--suggestion-color);
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
        }

        .suggestions ul {
            list-style-type: none;
        }

        .suggestions li {
            padding-left: 20px;
            position: relative;
            margin-bottom: 8px;
        }

        .suggestions li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--suggestion-color);
        }

        .error-trace {
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .trace-header {
            background: var(--trace-bg);
            color: var(--trace-color);
            padding: 10px 10px 5px 10px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }

        .trace-content {
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        pre {
            font-family: 'JetBrains Mono', monospace;
            margin: 0;
            padding: 0px 20px 20px;
            font-size: 12px;
            white-space: pre-wrap;
            background: var(--trace-bg);
            color: var(--trace-color);
            overflow-x: auto;
            tab-size: 4;
        }

        .toggle-button {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 12px;
            opacity: 0.7;
        }
        
        .toggle-button:hover {
            opacity: 1;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Tasarım */
        @media (max-width: 768px) {
            .container {
                border-radius: calc(var(--border-radius) - 4px);
            }
            .header {
                padding: 20px;
            }
            .content {
                padding: 20px;
            }
            .header-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            .header-text h1 {
                font-size: 18px;
            }
            .trace-content {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon">⚠️</div>
            <div class="header-text">
                <h1>Hata Ayıklama</h1>
                <p>Bir hata oluştu. Lütfen aşağıdaki detayları kontrol edin.</p>
            </div>
        </div>
        <div class="content">

            <div class="error-details">
                <div><strong>Hata Türü:</strong> <?= htmlspecialchars($errorDetails['type']) ?></div>
                <div><strong>Mesaj:</strong> <?= htmlspecialchars($errorDetails['message']) ?></div>
                <div><strong>Dosya:</strong> <?= htmlspecialchars($errorDetails['file']) ?></div>
                <div><strong>Satır:</strong> <?= htmlspecialchars($errorDetails['line']) ?></div>
            </div>


            
            <?php if (!empty($errorDetails['suggestions'])): ?>
            <div class="suggestions">
                <h3>
                    Öneriler
                    <button class="toggle-button"  onclick="toggleSection('suggestions-outputs')">Gizle/Göster</button>
                </h3>   
                <div id="suggestions-outputs" class="suggestions-outputs"  >
                    <ul>
                        <?php foreach ($errorDetails['suggestions'] as $suggestion): ?>
                        <li><?= htmlspecialchars($suggestion) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>


            <?php if (!empty($debug_parts)): ?>
            <div class="debug-section">
                <h3>
                    Debug Çıktıları
                    <button class="toggle-button"  onclick="toggleSection('debug-outputs')">Gizle/Göster</button>
                </h3>
                <div id="debug-outputs" class="debug-outputs" style="display: none;" >
                    <?php foreach ($debug_parts as $part): ?>
                        <?php if ($part['type'] === 'text' && !empty(trim($part['content']))): ?>
                            <div class="debug-output">
                                <div class="debug-text"><?= htmlspecialchars($part['content']) ?></div>
                            </div>
                        <?php elseif ($part['type'] === 'dump'): ?>
                            <div class="debug-output">
                                <div class="debug-dump"><?= $part['content'] ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        
            
        
            
            <?php if (!empty($errorDetails['trace'])): ?>
            <div class="error-trace">
                <div class="trace-header">
                    <strong>İzleme:</strong>
                    <button class="toggle-button" onclick="toggleSection('trace-content')">Gizle/Göster</button>
                </div>
                <div id="trace-content" class="trace-content" style="display: none;">
                    <pre><?= htmlspecialchars($errorDetails['trace']) ?></pre>
                </div>
            </div>
            <?php endif; ?>


        </div>
    </div>
    
    <script>
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>