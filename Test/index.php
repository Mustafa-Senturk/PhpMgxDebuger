<?php
/**
 * Debug Test Sayfası
 */
session_start();
$_SESSION['is_Admin'] = true;
$_SESSION['is_Logged'] = true;

// Konfigürasyon dosyasını dahil et
require_once __DIR__ . '/../config.php';
$_SESSION['is_MicroMan'] = 1;
// Test için bir HTML sayfası oluştur
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Test Sayfası</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        .test-panel {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 5px solid #007bff;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .log-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Sistemi Test Sayfası</h1>
        <p>Bu sayfa Debug sınıfının özelliklerini test etmek için kullanılır. Aşağıdaki butonları kullanarak çeşitli hata senaryolarını test edebilirsiniz.</p>
        
        <div class="test-panel">
            <h2>PHP Hata Testleri</h2>
            <a href="?test=undefined_variable" class="btn">Tanımlanmamış Değişken</a>
            <a href="?test=undefined_function" class="btn">Tanımlanmamış Fonksiyon</a>
            <a href="?test=division_by_zero" class="btn">Sıfıra Bölme</a>
            <a href="?test=type_error" class="btn">Tip Hatası</a>
            <a href="?test=class_not_found" class="btn">Sınıf Bulunamadı</a>
            <a href="?test=memory_limit" class="btn btn-danger">Bellek Limiti Aşma</a>
        </div>
        
        <div class="test-panel">
            <h2>Twig Hata Testleri</h2>
            <a href="?test=twig_template_not_found" class="btn">Twig Şablon Bulunamadı</a>
            <a href="?test=twig_syntax_error" class="btn">Twig Sözdizimi Hatası</a>
            <a href="?test=twig_undefined_variable" class="btn">Twig Tanımlanmamış Değişken</a>
        </div>
        
        <div class="test-panel">
            <h2>Latte Hata Testleri</h2>
            <a href="?test=latte_template_not_found" class="btn">Latte Şablon Bulunamadı</a>
            <a href="?test=latte_syntax_error" class="btn">Latte Sözdizimi Hatası</a>
            <a href="?test=latte_undefined_variable" class="btn">Latte Tanımlanmamış Değişken</a>
            <a href="?test=latte_unknown_macro" class="btn">Latte Bilinmeyen Makro</a>
        </div>
        
        <div class="test-panel">
            <h2>Veritabanı Hata Testleri</h2>
            <a href="?test=db_connection_error" class="btn">Bağlantı Hatası</a>
            <a href="?test=db_query_error" class="btn">Sorgu Hatası</a>
            <a href="?test=db_table_not_found" class="btn">Tablo Bulunamadı</a>
        </div>
        
        <div class="test-panel">
            <h2>Dosya Hata Testleri</h2>
            <a href="?test=file_not_found" class="btn">Dosya Bulunamadı</a>
            <a href="?test=file_permission" class="btn">Dosya İzin Hatası</a>
        </div>
        
        <div class="test-panel">
            <h2>Debug Özellikleri</h2>
            <a href="?test=log_message" class="btn btn-warning">Log Mesajı Ekle</a>
            <a href="?test=log_query" class="btn btn-warning">SQL Sorgusu Log'la</a>
            <a href="?test=log_twig" class="btn btn-warning">Twig Log Ekle</a>
        </div>
        
        <div class="log-section">
<?php
// Test işlemleri
if (isset($_GET['test'])) {
    switch ($_GET['test']) {
        // PHP Hataları
        case 'undefined_variable':
            echo $undefined_var;
            break;
            
        case 'undefined_function':
            nonexistent_function();
            break;
            
        case 'division_by_zero':
            $result = 10 / 0;
            break;
            
        case 'type_error':
            $array = null;
            echo $array['key'];
            break;
            
        case 'class_not_found':
            try {
                $obj = new NonExistentClass();
            } catch (\Error $e) {
                echo "Expected error: " . $e->getMessage();
            }
            break;
            
        case 'memory_limit':
            echo "Bellek limiti testini gerçekleştiriyor...";
            $data = array();
            try {
                // Maksimum döngü sayısını sınırla
                $maxIterations = 1000000;
                $memoryCheck = 10000; // Her 10000 iterasyonda bir bellek kontrolü
                
                for ($i = 0; $i < $maxIterations; $i++) {
                    $data[] = str_repeat('A', 1024);
                    
                    // Belirli aralıklarla ilerleme göster ve belleği kontrol et
                    if ($i % $memoryCheck == 0) {
                        echo '.';
                        flush();
                        
                        // Bellek kullanımı %90'ı aştıysa durdur
                        if (memory_get_usage() > memory_get_peak_usage(true) * 0.9) {
                            throw new \Exception("Bellek sınırına yaklaşıldı, test kontrollü bir şekilde durduruldu.");
                        }
                    }
                }
            } catch (\Exception $e) {
                echo "<br>Test sonlandırıldı: " . $e->getMessage();
            }
            break;
        
        // Twig Hataları
        case 'twig_template_not_found':
            echo "Twig şablon bulunamadı hatası simüle ediliyor...";
            // Twig yüklüyse gerçekten bir hata fırlatır
            if (class_exists('\Twig\Environment')) {
                try {
                    $loader = new \Twig\Loader\FilesystemLoader(DIR_FOLDER . '/Views');
                    $twig = new \Twig\Environment($loader);
                    echo $twig->render('nonexistent_template.twig');
                } catch (\Exception $e) {
                    throw new Exception("Unable to find template \"nonexistent_template.twig\" (looked into: " . DIR_FOLDER . "/Views)");
                }
            } else {
                throw new Exception("Unable to find template \"nonexistent_template.twig\" (looked into: " . DIR_FOLDER . "/Views)");
            }
            break;
            
        case 'twig_syntax_error':
            echo "Twig sözdizimi hatası simüle ediliyor...";
            throw new Exception("Unexpected token \"name\" of value \"item\" in template.twig at line 8");
            break;
            
        case 'twig_undefined_variable':
            echo "Twig tanımlanmamış değişken hatası simüle ediliyor...";
            throw new Exception("Variable \"product\" does not exist in template.twig at line 5");
            break;
            
        // Latte Hataları
        case 'latte_template_not_found':
            echo "Latte şablon bulunamadı hatası simüle ediliyor...";
            throw new Exception("Template not found. Missing template file 'product.latte'.");
            break;
            
        case 'latte_syntax_error':
            echo "Latte sözdizimi hatası simüle ediliyor...";
            throw new Exception("Latte\\CompileException: Unexpected '{/if}', expecting </script> or text in homepage.latte on line 23");
            break;
            
        case 'latte_undefined_variable':
            echo "Latte tanımlanmamış değişken hatası simüle ediliyor...";
            throw new Exception("Variable 'productName' has not been defined, did you mean 'product'?");
            break;
            
        case 'latte_unknown_macro':
            echo "Latte bilinmeyen makro hatası simüle ediliyor...";
            throw new Exception("Unknown macro {alternative}, did you mean {foreach}?");
            break;
            
        // Veritabanı Hataları    
        case 'db_connection_error':
            echo "Veritabanı bağlantı hatası simüle ediliyor...";
            throw new Exception("SQLSTATE[HY000] [2002] Connection refused: Could not connect to database server");
            break;
            
        case 'db_query_error':
            echo "Veritabanı sorgu hatası simüle ediliyor...";
            throw new Exception("SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'WHERE id = 1' at line 1");
            break;
            
        case 'db_table_not_found':
            echo "Veritabanı tablo bulunamadı hatası simüle ediliyor...";
            throw new Exception("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'testdb.nonexistent_table' doesn't exist");
            break;
            
        // Dosya Hataları
        case 'file_not_found':
            echo "Dosya bulunamadı hatası simüle ediliyor...";
            require_once 'nonexistent_file.php';
            break;
            
        case 'file_permission':
            echo "Dosya izin hatası simüle ediliyor...";
            throw new Exception("Failed to open stream: Permission denied");
            break;
            
        // Debug Özellikleri
        case 'log_message':
            echo "Debug log'a mesaj ekleniyor...";
            \Core\Debug::log("Bu bir test log mesajıdır", "info");
            \Core\Debug::log("Bu bir uyarı mesajıdır", "warning");
            \Core\Debug::log("Bu bir hata mesajıdır", "error");
            echo "<p>3 adet log mesajı eklendi. Debug bar'ı kontrol edin.</p>";
            break;
            
        case 'log_query':
            echo "Debug SQL sorgu log'u ekleniyor...";
            \Core\Debug::logQuery("SELECT * FROM users WHERE id = ?", [1], 0.0025);
            \Core\Debug::logQuery("INSERT INTO logs (user_id, action, timestamp) VALUES (?, ?, NOW())", [5, "login"], 0.0018);
            echo "<p>2 adet SQL sorgusu eklendi. Debug bar'ı kontrol edin.</p>";
            break;
            
        case 'log_twig':
            echo "Debug Twig log'u ekleniyor...";
            \Core\Debug::addTwigLog("Şablon derlendi: homepage.twig", "render");
            \Core\Debug::addTwigLog("Değişkeni yerleştirildi: user.name", "variable");
            echo "<p>2 adet Twig log'u eklendi. Debug bar'ı kontrol edin.</p>";
            break;
            
        default:
            echo "Bilinmeyen test: " . htmlspecialchars($_GET['test']);
    }
}

// DEBUG sabitinin ve Debug sınıfının doğru çalışıp çalışmadığını test et
echo "<div style='background:#333; color:#fff; padding:10px; margin-top:20px;'>";
echo "DEBUG sabiti: " . (defined('DEBUG') ? (DEBUG ? 'true' : 'false') : 'tanımlı değil') . "<br>";
echo "DEBUG_REQUESTS sabiti: " . (defined('DEBUG_REQUESTS') ? (DEBUG_REQUESTS ? 'true' : 'false') : 'tanımlı değil') . "<br>";

// Debug sınıfı manuel olarak başlat ve test et
if (class_exists('\Core\Debug')) {
    \Core\Debug::init();
    \Core\Debug::log("Manuel debug testi", "debug-test");
    echo "Debug sınıfı bulundu ve log eklendi. <br>";
} else {
    echo "Debug sınıfı bulunamadı! <br>";
}
echo "</div>";
?>
        </div>
    </div>
</body>
</html>