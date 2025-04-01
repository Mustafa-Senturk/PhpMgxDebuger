<?php
namespace Core;

class Debug
{
    private static $startTime;
    private static $queries = [];
    private static $requests = [];
    private static $logs = [];
    private static $twigLogs = []; // Twig debug mesajları için yeni dizi
    private static $latteLogs = []; // Latte debug mesajları için yeni dizi
    private static $errors = [];
    private static $includedFiles = [];
    private static $definedFunctions = [];

    public static function init()
    {
        if (DEBUG === false) {
            return;
        }

        self::$startTime = microtime(true);

        // ÖNEMLİ: İlk satırda output buffering başlat
        ob_start();

        // Dahil edilen dosyaları başlangıçta kaydet
        self::$includedFiles = get_included_files();

        // Hata yakalama
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);

        // Request bilgilerini kaydet
        self::logRequest();

        // Sayfa işleminden sonra çalışacak fonksiyon
        register_shutdown_function(function() {
            // Fatal error kontrolü - bu normal error handler ile yakalanamayan hataları yakalar
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
                // Fatal error oluşmuş - hata bilgilerini kaydet
                $translatedError = self::translateError($error['type'], $error['message']);
                self::$errors[] = [
                    'type' => $translatedError['type'],
                    'message' => $translatedError['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'trace' => [],  // Fatal hatalarda trace bilgisi alınamaz
                    'fatal' => true
                ];
                
                // Fatal hata detaylarını göster (DEBUG modda)
                if (DEBUG) {
                    $exception = new \ErrorException(
                        $error['message'], 
                        0, 
                        $error['type'], 
                        $error['file'], 
                        $error['line']
                    );
                    self::renderDebugError($exception);
                    exit; // Çıkış yap
                }
            }
            
            // Debug çıktısı sadece adminlere gösterilsin
            if (isset($_SESSION['is_MicroMan']) && $_SESSION['is_MicroMan'] == 1) {
                // Çıktı tamponunu topla
                $output = ob_get_contents();
                // Tamponlanmış çıktıda var_dump veya debug çıktıları var mı kontrol et
                if (strpos($output, '<pre>') !== false || strpos($output, 'array(') !== false) {
                    // Debug çıktılarını yakala ve kaydet
                    self::$logs[] = [
                        'timestamp' => time(),
                        'datetime' => date('Y-m-d H:i:s'),
                        'type' => 'output',
                        'message' => $output
                    ];
                    
                    // Tamponu temizle (hata olmadıysa) ve çıktıyı oluştur
                    if (!self::hasErrors()) {
                        ob_clean(); // Tamponu temizle ama kapatma
                        echo self::renderPage($output);
                    }
                }
                
                // Fonksiyon listesini al
                self::$definedFunctions = get_defined_functions();

                // Dosya listesini güncelle
                self::$includedFiles = get_included_files();
            }
            
            if (DEBUG == true) {
                echo self::renderDebugBar();
            }
        });
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $translatedError = self::translateError($errno, $errstr);

        self::$errors[] = [
            'type' => $translatedError['type'],
            'message' => $translatedError['message'],
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
    }

    public static function exceptionHandler($exception)
    {
        $translatedError = self::translateError(E_ERROR, $exception->getMessage());

        self::$errors[] = [
            'type' => get_class($exception),
            'message' => $translatedError['message'],
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ];

        if (DEBUG) {
            self::renderDebugError($exception);
        } else {
            // Production hatası göster
            include_once (DIR_FOLDER.'Views/debug/500.php');
        }
    }

    private static function translateError($errno, $errstr)
    {
        // Hata tiplerinin Türkçe karşılıkları
        $errorTypes = [
            E_ERROR => 'Hata',
            E_WARNING => 'Uyarı',
            E_PARSE => 'Ayrıştırma Hatası',
            E_NOTICE => 'Bildirim',
            E_CORE_ERROR => 'Çekirdek Hatası',
            E_CORE_WARNING => 'Çekirdek Uyarısı',
            E_COMPILE_ERROR => 'Derleme Hatası',
            E_COMPILE_WARNING => 'Derleme Uyarısı',
            E_USER_ERROR => 'Kullanıcı Hatası',
            E_USER_WARNING => 'Kullanıcı Uyarısı',
            E_USER_NOTICE => 'Kullanıcı Bildirimi',
            E_RECOVERABLE_ERROR => 'Kurtarılabilir Hata',
            E_DEPRECATED => 'Eskimiş Uyarı',
            E_USER_DEPRECATED => 'Kullanıcı Eskimiş Uyarısı',
        ];

        $translatedErrorType = $errorTypes[$errno] ?? 'Bilinmeyen Hata';
        
        // Yaygın PHP hata mesajlarını Türkçeye çevirme
        $errorMessages = [
            'Failed opening required' => 'Gerekli dosya açılamadı',
            'include_path' => 'dahil etme yolu',
            'Undefined variable' => 'Tanımlanmamış değişken',
            'Undefined array key' => 'Tanımlanmamış dizi anahtarı',
            'Call to undefined function' => 'Tanımlanmamış fonksiyona çağrı',
            'Call to undefined method' => 'Tanımlanmamış metoda çağrı',
            'Class not found' => 'Sınıf bulunamadı',
            'Cannot redeclare' => 'Yeniden tanımlanamaz',
            'Permission denied' => 'İzin reddedildi',
            'file_exists(): Filename cannot be empty' => 'file_exists(): Dosya adı boş olamaz',
            'Cannot use object of type stdClass as array' => 'stdClass türündeki nesne dizisi olarak kullanılamaz',
            'syntax error' => 'Sözdizimi hatası',
            'Unexpected' => 'Beklenmeyen',
            'Trying to get property' => 'Özelliğe erişmeye çalışılıyor',
            'must be an instance of' => 'bir örneği olmalıdır',
            'must implement interface' => 'arayüzü uygulamalıdır',
            'given in' => 'verilen',
            'expects at least' => 'en az bekliyor',
            'expects exactly' => 'tam olarak bekliyor',
            'expects parameter' => 'parametre bekliyor',
            'expects a string parameter' => 'bir dize parametre bekliyor',
            'expects parameter 1 to be string' => '1. parametrenin bir dize olması bekleniyor',
            'expects parameter 1 to be array' => '1. parametrenin bir dizi olması bekleniyor',
            'expects parameter 1 to be a valid callback' => '1. parametrenin geçerli bir geri çağrı olması bekleniyor',
            'expects parameter 1 to be a valid callback, class' => '1. parametrenin geçerli bir geri çağrı, sınıf',
            'expects parameter 1 to be a valid callback, function' => '1. parametrenin geçerli bir geri çağrı, fonksiyon',
            
            // Latte şablon motoru hata mesajları
            'Template not found' => 'Şablon bulunamadı',
            'Unknown macro' => 'Bilinmeyen makro',
            'Unexpected macro' => 'Beklenmeyen makro',
            'Compile error' => 'Derleme hatası',
            'Latte\CompileException' => 'Latte derleme hatası',
            'Variable has not been defined' => 'Değişken tanımlanmamış',
            'Missing template file' => 'Şablon dosyası eksik',
        ];
        
        // Hata mesajını çevir
        $translatedErrorMessage = $errstr;
        
        foreach ($errorMessages as $enMsg => $trMsg) {
            if (strpos($errstr, $enMsg) !== false) {
                $translatedErrorMessage = str_replace($enMsg, $trMsg, $errstr);
                break;
            }
        }

        return [
            'type' => $translatedErrorType,
            'message' => $translatedErrorMessage
        ];
    }

    public static function logQuery($sql, $bindings, $time)
    {
        if (!DEBUG) return;

        self::$queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time
        ];
    }

    /**
     * SQL hatalarını kaydet
     * 
     * @param string $sql Hatalı SQL sorgusu
     * @param string $errorMessage Hata mesajı
     * @param float $time Sorgu süresi
     */
    public static function logQueryError($sql, $errorMessage, $time)
    {
        if (!DEBUG) return;
        
        self::$queries[] = [
            'sql' => $sql,
            'bindings' => [],
            'time' => $time,
            'error' => $errorMessage,
            'is_error' => true
        ];
    }

    public static function logRequest()
    {
        if (!DEBUG) return;

        self::$requests[] = [
            'url' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'params' => $_REQUEST,
            'headers' => getallheaders(),
            'time' => date('Y-m-d H:i:s')
        ];
    }

    public static function log($message, $type = 'info')
    {
        $log = [
            'timestamp' => time(), // Unix timestamp olarak sakla
            'datetime' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message
        ];
        self::$logs[] = $log;
        // Konsola da yazdır
        error_log("[{$log['datetime']}] [{$log['type']}] {$log['message']}");
    }

    /**
     * Twig'den gelen debug mesajlarını ekle
     * 
     * @param mixed $message Debug mesajı
     * @param string $context Bağlam (template adı vb.)
     * @return void
     */

    public static function addTwigLog($message, $context = 'twig')
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? $backtrace[0] ?? null;
        
        self::$twigLogs[] = [
            'time' => microtime(true),
            'message' => $message,
            'context' => $context,
            'file' => $caller['file'] ?? 'unknown',
            'line' => $caller['line'] ?? 'unknown',
            'template' => $caller['args'][0] ?? 'unknown'
        ];
    }

    /**
     * Latte'den gelen debug mesajlarını ekle
     * 
     * @param mixed $message Debug mesajı
     * @param string $context Bağlam (template adı vb.)
     * @return void
     * 
     */
    public static function addLatteLog($message, $context = 'latte')
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? $backtrace[0] ?? null;
        
        self::$latteLogs[] = [
            'time' => microtime(true),
            'message' => $message,
            'context' => $context,
            'file' => $caller['file'] ?? 'unknown',
            'line' => $caller['line'] ?? 'unknown',
            'template' => $caller['args'][0] ?? 'unknown'
        ];
    }

    public static function renderDebugBar() {
        // API yanıtlarında debug bar'ı gösterme
        if (defined('API_RESPONSE') || 
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
            return '';
        }

        if (!DEBUG || !DEBUG_REQUESTS) {
            return '';
        }
        
        // Sadece admin veya belirli kullanıcılar için debug bar göster
        $isDeveloper = isset($_SESSION['is_MicroMan']) && $_SESSION['is_MicroMan'] == 1;
        
        if (!$isDeveloper && !DEBUG) {
            return '';
        }

        try {
            // Log verilerini debug_data değişkenine ekleyin
            $debug_data = [
                'execution_time' => microtime(true) - self::$startTime,
                'memory_usage' => memory_get_usage(),
                'queries' => self::$queries,
                'requests' => self::$requests,
                'logs' => self::$logs,
                'twig_logs' => self::$twigLogs,
                'latte_logs' => self::$latteLogs,
                'errors' => self::$errors,
            ];
            
            // Debug bar şablonunu yükle
            ob_start();
            extract(['debug_data' => $debug_data]);
            include DIR_FOLDER. '/Views/debug/debug-bar.php';
            $content = ob_get_clean();
            
            return $content;
        } catch (\Exception $e) {
            // Hata durumunda basit bir debug bar göster
            return "<div style='position:fixed; bottom:0; left:0; background:#f44336; color:#fff; padding:10px; z-index:9999;'>
                    Debug Bar Error: {$e->getMessage()}</div>";
        }
    }

    private static function renderDebugError($exception)
    {
        $translatedError = self::translateError(E_ERROR, $exception->getMessage());
        
        // Dosya yolu ve satırını daha okunabilir hale getir
        $file = $exception->getFile();
        $line = $exception->getLine();
        
        // Dosya yolunu kısaltma (opsiyonel)
        $baseDir = dirname(__DIR__);
        $relativeFile = str_replace($baseDir, '', $file);
        
        $errorDetails = [
            'type' => $translatedError['type'],
            'message' => $translatedError['message'],
            'file' => $file,
            'line' => $line,
            'trace' => $exception->getTraceAsString(),
            'relativeFile' => $relativeFile,
            'suggestions' => self::getSuggestions($exception)
        ];

        include_once DIR_FOLDER.'Views/debug/debug-error.php';
    }

    /**
     * Hataya göre öneriler sunar
     */
    private static function getSuggestions($exception)
    {
        $message = $exception->getMessage();
        $file = $exception->getFile();
        
        // JSON dosyasından hata öneri yapılandırmasını yükle
        $suggestionsPath = __DIR__ . '/data/hata_oneri.json';
        if (!file_exists($suggestionsPath)) {
            // Alternatif konum deneyin
            $suggestionsPath = dirname(__DIR__) . '/Core/data/hata_oneri.json';
            if (!file_exists($suggestionsPath)) {
                return ['Hata önerileri JSON dosyası bulunamadı: ' . $suggestionsPath];
            }
        }
        
        try {
            $errorSuggestions = json_decode(file_get_contents($suggestionsPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['Hata önerileri JSON dosyası ayrıştırılamadı: ' . json_last_error_msg()];
            }
            
            // Değişkenler için gerekli verileri topla
            $variables = [
                'templateName' => '',
                'lookPath' => '',
                'unexpectedPart' => '',
                'macroName' => '',
                'className' => '',
                'errorMessage' => $message
            ];
            
            // Twig şablon hataları için özel değişken işleme
            if (strpos($message, 'Unable to find template') !== false) {
                preg_match('/"([^"]+)"/', $message, $matches);
                $variables['templateName'] = $matches[1] ?? 'bilinmeyen şablon';
                
                if (preg_match('/looked into: ([^)]+)/', $message, $pathMatches)) {
                    $variables['lookPath'] = $pathMatches[1];
                }
            }
            
            // Beklenmeyen token veya sözdizimi hataları
            if (strpos($message, 'Unexpected token') !== false || strpos($message, 'Unexpected') !== false) {
                preg_match('/Unexpected (.*?) in/i', $message, $matches);
                $variables['unexpectedPart'] = $matches[1] ?? 'bilinmeyen token';
            }
            
            // Sınıf bulunamama hataları
            if ((strpos($message, 'Class') !== false && strpos($message, 'not found') !== false) || 
                strpos($message, 'Class \'') !== false) {
                
                preg_match('/Class [\'"](.*?)[\'"]/', $message, $matches);
                $variables['className'] = $matches[1] ?? 'belirtilmemiş sınıf';
            }
            
            // Latte için bilinmeyen makro
            if (strpos($message, 'Unknown macro') !== false) {
                preg_match('/Unknown macro [\'"]?(.*?)[\'"]?/', $message, $matches);
                $variables['macroName'] = $matches[1] ?? 'bilinmeyen makro';
            }
            
            // En iyi eşleşmeyi saklayacak değişkenler
            $bestMatchedSuggestions = [];
            $bestMatchCategory = '';
            $bestMatchConfidence = 0;
            $debugInfo = "";
            
            // Her kategori için en iyi eşleşmeyi bul
            foreach ($errorSuggestions as $category => $errorTypes) {
                foreach ($errorTypes as $errorType => $config) {
                    $patternMatches = 0;
                    $totalPatterns = count($config['patterns']);
                    
                    // Bu hata için pattern eşleşme sayısını say
                    foreach ($config['patterns'] as $pattern) {
                        if (strpos($message, $pattern) !== false) {
                            $patternMatches++;
                        }
                    }
                    
                    // Eşleşme varsa
                    if ($patternMatches > 0) {
                        // Eşleşme güveni: eşleşen pattern sayısı / toplam pattern sayısı
                        $confidence = $patternMatches / $totalPatterns;
                        
                        // Eğer bu kategori daha iyi bir eşleşmeyse
                        if ($confidence > $bestMatchConfidence) {
                            $bestMatchConfidence = $confidence;
                            $bestMatchCategory = $category . '.' . $errorType;
                            
                            // Önerileri format ve değişkenlerle doldur
                            $formattedSuggestions = [];
                            foreach ($config['suggestions'] as $suggestion) {
                                $formattedSuggestion = $suggestion;
                                
                                // Değişkenleri değerleriyle değiştir
                                foreach ($variables as $varName => $varValue) {
                                    $formattedSuggestion = str_replace('{' . $varName . '}', $varValue, $formattedSuggestion);
                                }
                                
                                $formattedSuggestions[] = $formattedSuggestion;
                            }
                            
                            $bestMatchedSuggestions = $formattedSuggestions;
                            $debugInfo = "En iyi eşleşme: $category - $errorType (Güven: " . round($confidence * 100) . "%)";
                        }
                    }
                }
            }
            
            // Eğer bir eşleşme bulduysa, önerileri göster
            if (!empty($bestMatchedSuggestions)) {
                if (DEBUG) {
                    array_unshift($bestMatchedSuggestions, "--- $debugInfo ---");
                }
                return $bestMatchedSuggestions;
            }
            
            // Eşleşme bulunamadıysa, genel öneriler göster
            $generalSuggestions = [];
            
            // Hata mesajına göre genel öneriler
            if (strpos($message, 'Table') !== false && (strpos($message, 'not found') !== false || strpos($message, "doesn't exist") !== false)) {
                $generalSuggestions[] = "Genel Tablo bulunamadı hatası:";
                $generalSuggestions[] = "1. Tablonun veritabanında var olduğunu kontrol edin.";
                $generalSuggestions[] = "2. Tablo adının doğru yazıldığından emin olun.";
                $generalSuggestions[] = "3. Veritabanı ön ekini (prefix) kontrol edin.";
            }
            else if (strpos($message, 'Template') !== false || strpos($message, 'template') !== false) {
                $generalSuggestions[] = "Şablonla ilgili bir hata oluştu.";
                $generalSuggestions[] = "Şablon dosyasının var olduğunu ve doğru yolda olduğunu kontrol edin.";
            } 
            else if (strpos($message, 'Class') !== false) {
                $generalSuggestions[] = "Sınıf tanımıyla ilgili bir hata oluştu.";
                $generalSuggestions[] = "Sınıf adı ve namespace'i kontrol edin.";
                $generalSuggestions[] = "Composer autoload ayarlarını kontrol edin.";
            }
            else if (strpos($message, 'SQL') !== false || strpos($message, 'Database') !== false) {
                $generalSuggestions[] = "Veritabanı hatası oluştu.";
                $generalSuggestions[] = "SQL sorgusunu ve veritabanı bağlantı ayarlarını kontrol edin.";
            }
            else {
                $generalSuggestions[] = "Hata mesajını dikkatlice okuyun ve ilgili satırı kontrol edin.";
                $generalSuggestions[] = "Benzer durumlarda PHP dokümantasyonunu inceleyebilirsiniz: https://www.php.net/manual/tr/";
            }
            
            if (DEBUG) {
                $generalSuggestions[] = "\n--- Debug Bilgisi ---";
                $generalSuggestions[] = "Hata mesajı: " . $message;
                $generalSuggestions[] = "Hiçbir özel eşleşme bulunamadı. JSON yapılandırmanızı kontrol edin.";
            }
            
            return $generalSuggestions;
                
        } catch (\Exception $e) {
            return ['Hata öneri sistemi çalışırken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public static function getStats()
    {
        return [
            'execution_time' => round((microtime(true) - self::$startTime) * 1000, 2),
            'memory_usage' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'queries' => self::$queries,
            'requests' => self::$requests,
            'logs' => self::$logs,
            'errors' => self::$errors,
            'included_files' => self::getIncludedFiles(),
            'functions' => self::getDefinedFunctions(),
            'variables' => self::getDefinedVariables(),
            'page_info' => self::getPageInfo()
        ];
    }

    public static function display()
    {
        echo "<pre>";
        foreach (self::$logs as $log) {
            echo "[{$log['timestamp']}] [{$log['type']}] {$log['message']}\n";
        }
        echo "</pre>";
    }

    /**
     * Tanımlanan fonksiyonları kategorize eder
     */
    public static function getDefinedFunctions($onlyUser = true)
    {
        $functions = get_defined_functions();
        return $onlyUser ? $functions['user'] : $functions;
    }
    
    /**
     * Dahil edilen dosyaları döndürür
     */
    public static function getIncludedFiles()
    {
        return get_included_files();
    }
    
    /**
     * Tanımlanan değişkenleri döndürür
     */
    public static function getDefinedVariables()
    {
        $vars = [];
        $globals = $GLOBALS;
        
        // Bazı kritik değişkenleri filtrele
        $excludeVars = ['GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV'];
        
        // Tehlikeli/karmaşık sınıflar için filtre ekle
        $excludeClasses = ['Dotenv\Dotenv', 'Twig\Environment', 'PDO', 'Illuminate\Database\Capsule\Manager'];
        
        try {
            foreach ($globals as $key => $value) {
                if (!in_array($key, $excludeVars)) {
                    // Nesneleri filtrele
                    if (is_object($value)) {
                        $className = get_class($value);
                        if (in_array($className, $excludeClasses) || strpos($className, 'Twig\\') === 0 || strpos($className, 'Dotenv\\') === 0) {
                            $vars[$key] = "(FILTERED) " . $className;
                            continue;
                        }
                    }
                    
                    // Değişkenleri basitleştir
                    $vars[$key] = self::simplifyVar($value);
                }
            }
        } catch (\Throwable $e) {
            return ['ERROR' => "Değişkenler listelenirken hata oluştu: " . $e->getMessage()];
        }
        
        return $vars;
    }

    /**
     * Karmaşık değişkenleri basitleştir
     */
    private static function simplifyVar($var, $depth = 0, $maxDepth = 2, $processedObjects = [])
    {
        if ($depth >= $maxDepth) {
            if (is_object($var)) {
                return '(OBJECT) ' . get_class($var);
            } elseif (is_array($var)) {
                return '(ARRAY) ' . count($var) . ' items';
            } else {
                return $var;
            }
        }
        
        // Özyinelemeyi önlemek için kontrol
        if (is_object($var)) {
            $objectHash = spl_object_hash($var);
            if (in_array($objectHash, $processedObjects)) {
                return '(RECURSION) ' . get_class($var);
            }
            $processedObjects[] = $objectHash;
        }
        
        try {
            if (is_array($var)) {
                $result = [];
                $i = 0;
                foreach ($var as $key => $value) {
                    // Çok büyük dizileri kısalt
                    if ($i++ > 50) {
                        $result['...'] = '(' . (count($var) - 50) . ' daha fazla öğe)';
                        break;
                    }
                    $result[$key] = self::simplifyVar($value, $depth + 1, $maxDepth, $processedObjects);
                }
                return $result;
            } elseif (is_object($var)) {
                $className = get_class($var);
                
                // Özel sınıf kontrolleri
                if (
                    $className === 'Dotenv\Dotenv' || 
                    $className === 'Twig\Environment' || 
                    $className === 'PDO' || 
                    strpos($className, 'Illuminate\\') === 0 ||
                    strpos($className, 'Twig\\') === 0 ||
                    strpos($className, 'Dotenv\\') === 0
                ) {
                    return '(SPECIAL CLASS) ' . $className;
                }
                
                // Closure nesneleri
                if ($className === 'Closure') {
                    return '(CLOSURE) anonymous function';
                }
                
                // Normal nesne özelliklerini basitleştir
                try {
                    $reflection = new \ReflectionObject($var);
                    $props = $reflection->getProperties();
                    
                    $result = [
                        '__class' => $className
                    ];
                    
                    foreach ($props as $prop) {
                        $prop->setAccessible(true);
                        if ($prop->isInitialized($var)) {
                            $propName = $prop->getName();
                            try {
                                $propValue = $prop->getValue($var);
                                $result[$propName] = self::simplifyVar($propValue, $depth + 1, $maxDepth, $processedObjects);
                            } catch (\Throwable $e) {
                                $result[$propName] = "(ERROR) " . $e->getMessage();
                            }
                        }
                    }
                    
                    return $result;
                } catch (\Throwable $e) {
                    return '(OBJECT) ' . $className . ' [Erişilemiyor: ' . $e->getMessage() . ']';
                }
            } else {
                return $var;
            }
        } catch (\Throwable $e) {
            return "(ERROR) Değer basitleştirilemedi: " . $e->getMessage();
        }
    }
    
    /**
     * Sayfa bilgilerini döndürür
     */
    public static function getPageInfo()
    {
        return [
            'url' => $_SERVER['REQUEST_URI'] ?? 'Bilinmiyor',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'Bilinmiyor',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Bilinmiyor',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'Doğrudan Giriş',
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Bilinmiyor',
            'time' => date('Y-m-d H:i:s'),
            'get_params' => $_GET,
            'post_params' => $_POST,
            'files' => $_FILES
        ];
    }

    /**
     * Yakalanan hata olup olmadığını kontrol et
     */
    private static function hasErrors() {
        return !empty(self::$errors);
    }

    /**
     * Sayfa içeriğini ve debug panelini birleştir
     */
    private static function renderPage($output) {
        // Eğer HTML çıktısı içeriyorsa debug panelini body'nin sonuna ekle
        if (strpos($output, '</body>') !== false) {
            $debugPanel = self::renderDebugBar();
            $output = str_replace('</body>', $debugPanel . '</body>', $output);
        } else {
            // HTML değilse debug çıktısını bir panel içinde göster
            $output = '<div class="debug-raw-output">' . $output . '</div>' . self::renderDebugBar();
        }

        return $output;
    }
}

