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

// Step 1: Run Composer Install
echo "📦 Running Composer Install...\n";
chdir($basePath);
// Put the site down if needed, or just install
// Note: Ensure `composer` is in the path or use full path (e.g. /usr/bin/composer or /usr/local/bin/composer)
// check if composer exists
$composerCheck = shell_exec('which composer');
if(empty($composerCheck)){
   $composerCmd = 'php composer.phar'; // Fallback if composer binary not found
} else {
   $composerCmd = 'composer';
}

$composerOutput = shell_exec("$composerCmd install --no-dev --optimize-autoloader 2>&1");
echo $composerOutput . "\n";
echo "✅ Composer dependencies installed.\n\n";

// Step 2: Generate App Key if missing
echo "🔑 Generating application key...\n";
echo shell_exec('php artisan key:generate --force 2>&1');
echo "\n";

// Step 2: Publish Assets
echo "� Publishing assets...\n";
echo shell_exec('php artisan vendor:publish --tag=laravel-assets --force 2>&1');
echo "\n";

// Step 3: Run migrations fresh and seed
echo "🔄 Running migrate:fresh --seed...\n";
chdir($basePath);
$migrateOutput = shell_exec('php artisan migrate:fresh --seed --force 2>&1');
echo $migrateOutput . "\n";

// Step 4: Clear caches
echo "🧹 Clearing caches...\n";
echo shell_exec('php artisan config:clear 2>&1');
echo shell_exec('php artisan cache:clear 2>&1');
echo shell_exec('php artisan view:clear 2>&1');

echo "\n✅ Setup complete!\n";
echo "⚠️ IMPORTANT: Delete this extract.php file now for security!\n";
echo "</pre>";
