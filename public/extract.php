<?php
/**
 * Vendor Extractor & Setup Script
 * Visit this URL after deployment to extract vendor.zip and run migrations
 * DELETE THIS FILE AFTER USE for security!
 */

// Security key - change this!
$secret_key = 'citropak_extract_2024';

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die('Unauthorized. Use: ?key=YOUR_SECRET_KEY');
}

$basePath = __DIR__ . '/../';
$zipFile = $basePath . 'vendor.zip';

echo "<pre>";

// Step 1: Extract vendor.zip if exists
if (file_exists($zipFile)) {
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        echo "📦 Extracting vendor.zip...\n";
        $zip->extractTo($basePath);
        $zip->close();
        unlink($zipFile);
        echo "✅ Vendor extracted and zip deleted.\n\n";
    } else {
        die('Error: Could not open vendor.zip');
    }
} else {
    echo "ℹ️ vendor.zip not found, skipping extraction.\n\n";
}

// Step 2: Run migrations
echo "🔄 Running migrations...\n";
chdir($basePath);
$migrateOutput = shell_exec('php artisan migrate --force 2>&1');
echo $migrateOutput . "\n";

// Step 3: Run seeders
echo "🌱 Running database seeders...\n";
$seedOutput = shell_exec('php artisan db:seed --force 2>&1');
echo $seedOutput . "\n";

// Step 4: Clear caches
echo "🧹 Clearing caches...\n";
echo shell_exec('php artisan config:clear 2>&1');
echo shell_exec('php artisan cache:clear 2>&1');
echo shell_exec('php artisan view:clear 2>&1');

echo "\n✅ Setup complete!\n";
echo "⚠️ IMPORTANT: Delete this extract.php file now for security!\n";
echo "</pre>";
