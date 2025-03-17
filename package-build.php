<?php
// Script untuk mengobfuscate kode sebelum publishing

// Tentukan direktori
$srcDir = __DIR__ . '/src';
$distDir = __DIR__ . '/dist';

// Buat direktori dist jika belum ada
if (!is_dir($distDir)) {
    mkdir($distDir, 0755, true);
}

// Obfuscate kode
function obfuscateDirectory($src, $dist) {
    // Buat direktori tujuan jika belum ada
    if (!is_dir($dist)) {
        mkdir($dist, 0755, true);
    }
    
    // Proses semua file dalam direktori
    $files = scandir($src);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $srcPath = $src . '/' . $file;
        $distPath = $dist . '/' . $file;
        
        if (is_dir($srcPath)) {
            obfuscateDirectory($srcPath, $distPath);
        } else if (pathinfo($srcPath, PATHINFO_EXTENSION) === 'php') {
            obfuscateFile($srcPath, $distPath);
        } else {
            // Salin file non-PHP
            copy($srcPath, $distPath);
        }
    }
}

function obfuscateFile($srcFile, $distFile) {
    echo "Obfuscating: $srcFile\n";
    $code = file_get_contents($srcFile);
    
    // 1. Encode strings
    $code = preg_replace_callback('/([\'"])(.*?)\\1/', function($matches) {
        // Skip empty strings
        if (empty($matches[2])) return $matches[0];
        
        // Skip common strings & class names
        $skipPatterns = [
            'alexafers\\\\', 
            'App\\\\', 
            'Illuminate\\\\',
            'system.runtime',
            'web',
            '_sys_',
            'function',
            'singleton'
        ];
        
        foreach ($skipPatterns as $pattern) {
            if (strpos($matches[2], $pattern) !== false) {
                return $matches[0];
            }
        }
        
        // Simple encoding for strings
        $encoded = base64_encode($matches[2]);
        return 'base64_decode("' . $encoded . '")';
    }, $code);
    
    // 2. Rename variables using a prefix (be careful not to break code)
    $code = preg_replace('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)([ =);])/', '\$_$1$2', $code);
    
    // 3. Add random comments
    $junkCode = '/* ' . bin2hex(random_bytes(16)) . ' */';
    $code = preg_replace('/^<\?php/', '<?php ' . $junkCode, $code);
    
    // Save obfuscated file
    file_put_contents($distFile, $code);
}

// Salin composer.json dan README
copy(__DIR__ . '/composer.json', $distDir . '/composer.json');
copy(__DIR__ . '/README.md', $distDir . '/README.md');

// Start obfuscation
echo "Starting obfuscation...\n";
obfuscateDirectory($srcDir, $distDir . '/src');
echo "Obfuscation complete. Files saved to $distDir\n";