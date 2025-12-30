<?php
/**
 * Setup Script for Hostinger
 * Extracts vendor.zip and clears caches
 * DELETE THIS FILE AFTER USE for security!
 */

// Security key
$secret_key = 'citropak_extract_2024';

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die('Unauthorized. Use: ?key=YOUR_SECRET_KEY');
}

// Disable time limit
set_time_limit(600);
ini_set('memory_limit', '512M');

$basePath = __DIR__ . '/../';

echo "<pre>";
ob_flush(); flush();

// PHP Version Check
echo "🔍 PHP Version Check\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP Binary: " . PHP_BINARY . "\n";
echo "PHP 64-bit: " . (PHP_INT_SIZE === 8 ? 'Yes' : 'No') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
ob_flush(); flush();

// Step 1: Extract vendor.zip if it exists
$vendorZip = $basePath . 'vendor.zip';

echo "📦 Checking for vendor.zip...\n";
ob_flush(); flush();

if (file_exists($vendorZip)) {
    echo "✅ Found vendor.zip (" . round(filesize($vendorZip) / 1024 / 1024, 2) . " MB)\n";
    echo "📂 Extracting vendor.zip...\n";
    ob_flush(); flush();
    
    // Remove existing vendor folder first
    if (is_dir($basePath . 'vendor')) {
        echo "  → Removing old vendor folder...\n";
        ob_flush(); flush();
        
        // Use recursive delete
        function deleteDir($dir) {
            if (!is_dir($dir)) return;
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? deleteDir($path) : @unlink($path);
            }
            @rmdir($dir);
        }
        deleteDir($basePath . 'vendor');
        echo "  → Old vendor folder removed\n";
        ob_flush(); flush();
    }
    
    // Extract zip
    $zip = new ZipArchive;
    if ($zip->open($vendorZip) === TRUE) {
        $zip->extractTo($basePath);
        $zip->close();
        echo "✅ vendor.zip extracted successfully!\n";
        
        // Delete the zip file
        @unlink($vendorZip);
        echo "🗑️ vendor.zip deleted\n";
    } else {
        echo "❌ Failed to extract vendor.zip\n";
    }
} else {
    echo "ℹ️ No vendor.zip found\n";
}
echo "\n";
ob_flush(); flush();

// Step 2: Check vendor status
echo "📦 Vendor Status\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (is_dir($basePath . 'vendor')) {
    echo "✅ vendor/ directory exists\n";
    
    if (file_exists($basePath . 'vendor/autoload.php')) {
        echo "✅ autoload.php exists\n";
    } else {
        echo "❌ autoload.php MISSING\n";
    }
    
    if (file_exists($basePath . 'vendor/maatwebsite/excel/src/Facades/Excel.php')) {
        echo "✅ maatwebsite/excel package installed\n";
    } else {
        echo "❌ maatwebsite/excel package MISSING\n";
    }
} else {
    echo "❌ vendor/ directory MISSING\n";
}
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
ob_flush(); flush();

// Step 3: Clear caches via Artisan
echo "🔧 Running Artisan Commands...\n";
chdir($basePath);

try {
    require $basePath . 'vendor/autoload.php';
    $app = require_once $basePath . 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $commands = [
        'config:clear' => 'Clearing config cache',
        'cache:clear' => 'Clearing application cache',
        'view:clear' => 'Clearing view cache',
        'route:clear' => 'Clearing route cache',
    ];

    foreach ($commands as $cmd => $desc) {
        echo "  → {$desc}... ";
        ob_flush(); flush();
        
        try {
            Illuminate\Support\Facades\Artisan::call($cmd);
            echo "✅\n";
        } catch (Exception $e) {
            echo "⚠️ " . $e->getMessage() . "\n";
        }
        ob_flush(); flush();
    }
} catch (Exception $e) {
    echo "⚠️ Could not run Artisan: " . $e->getMessage() . "\n";
}

echo "\n";

// Clear bootstrap cache manually
echo "🧹 Clearing bootstrap cache...\n";
$files = glob($basePath . 'bootstrap/cache/*.php');
foreach ($files as $file) {
    if (is_file($file)) {
        @unlink($file);
    }
}
echo "Bootstrap cache cleared.\n\n";
ob_flush(); flush();

echo "✅ Setup complete!\n";
echo "⚠️ IMPORTANT: Delete this extract.php file now for security!\n";
echo "</pre>";
