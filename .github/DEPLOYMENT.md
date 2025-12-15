# GitHub Actions Deployment Setup for Hostinger

This document explains how GitHub Actions is configured for CitroPak DMS with Hostinger webhook deployment.

## 📁 Workflow Files

Three GitHub Actions workflow files have been created:

1. **`deploy.yml`** - Build & validate on push to main branch
2. **`tests.yml`** - Run tests on push and pull requests  
3. **`code-quality.yml`** - Code quality checks and security audits

## 🔄 How Deployment Works with Hostinger

### Automated Workflow

1. You push code to the `main` branch on GitHub
2. GitHub Actions automatically:
   - ✅ Checks out the code
   - ✅ Sets up PHP 8.2 and Node.js 20
   - ✅ Installs Composer dependencies
   - ✅ Installs NPM dependencies
   - ✅ Builds Vue.js/Vite frontend assets
   - ✅ Validates the build succeeded
3. Hostinger's webhook detects the push and automatically:
   - Pulls the latest code to your server
   - Runs your deployment script (if configured)
   - Updates the live application

### No GitHub Secrets Required

Since Hostinger handles the deployment via webhook, you **don't need to configure any GitHub Secrets** for SSH access. The workflow only validates that your code builds successfully.

## 🎯 Hostinger Webhook Setup

### Setting up Git Deployment on Hostinger

1. **Log in to Hostinger Panel**
2. **Go to Advanced → Git**
3. **Click "Create Repository"** or connect existing repository
4. **Configure Git Deployment**:
   - Repository URL: Your GitHub repository URL
   - Branch: `main`
   - Deployment path: Path to your application folder
5. **Set up Post-Receive Script** (runs after each deployment):

```bash
#!/bin/bash

# Navigate to your deployment directory
cd $DEPLOYMENT_PATH || exit

echo "🚀 Starting CitroPak deployment..."

# Install/Update Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# Install/Update NPM dependencies
echo "📦 Installing NPM dependencies..."
npm install --force

# Build Vue/Vite assets
echo "🔨 Building frontend assets..."
npm run build

# Clear Laravel caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run database migrations (optional - comment out if you don't want auto-migrations)
echo "🗄️ Running migrations..."
php artisan migrate --force

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

echo "✅ Deployment completed successfully!"
```

6. **Save and Enable** automatic deployment

### Hostinger Environment Variables

Make sure your `.env` file on Hostinger is properly configured with:
- Database credentials
- Application key
- APP_ENV=production
- APP_DEBUG=false

## 📋 Workflow Details

### Build & Validate (`deploy.yml`)

Triggers on:
- Push to `main` branch
- Manual trigger via GitHub Actions tab

What it does:
- Validates PHP and Node.js setup
- Installs dependencies
- Builds production assets
- Confirms build succeeded

### Tests (`tests.yml`)

Runs automatically on:
- Push to `main` or `develop` branches
- All pull requests

Tests against:
- PHP 8.2
- PHP 8.3

### Code Quality (`code-quality.yml`)

Checks:
- Code style (PHP CS Fixer, ESLint)
- Security vulnerabilities (Composer & NPM audit)
- TODO/FIXME comments

## 🧪 Testing Locally Before Deploy

Before pushing to main:

```bash
# Install dependencies
composer install
npm install

# Build assets
npm run build

# Run tests (if configured)
php artisan test
```

## 🛡️ Hostinger Server Requirements

Ensure your Hostinger account has:
- ✅ Git enabled
- ✅ PHP 8.2+ with required extensions
- ✅ Composer access
- ✅ Node.js 20+ and NPM
- ✅ MySQL database
- ✅ Proper file permissions

## 🐛 Troubleshooting

### Build Fails on GitHub Actions

Check the Actions tab logs for:
- Composer dependency issues
- NPM dependency conflicts
- Build errors in Vite

### Deployment Doesn't Update on Hostinger

1. Check Hostinger Git panel for deployment log
2. Verify webhook is enabled
3. Test Git connection manually
4. Check post-receive script errors

### Permission Errors

```bash
# On Hostinger terminal
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Assets Not Loading

- Clear browser cache
- Check `public/build` directory exists on server
- Verify Vite manifest is generated
- Run `php artisan storage:link` if needed

## 📞 Quick Commands Reference

### On GitHub (via Actions tab)
- Manually trigger build: Actions → Build & Validate → Run workflow

### On Hostinger (via SSH/Terminal)
```bash
# Pull latest code manually
git pull origin main

# Rebuild assets
npm run build

# Clear all caches
php artisan optimize:clear

# View deployment logs
tail -f storage/logs/laravel.log
```

## ✅ Deployment Checklist

- [ ] Code pushed to GitHub
- [ ] GitHub Actions build succeeded (green checkmark)
- [ ] Hostinger webhook triggered deployment
- [ ] Application accessible on live URL
- [ ] No errors in Hostinger deployment log
- [ ] Database migrations ran successfully
- [ ] Frontend assets loading correctly

## 🎯 Next Steps

1. Push your code to `main` branch
2. Monitor GitHub Actions for build success
3. Check Hostinger Git panel for deployment status
4. Test your live application
5. Monitor logs for any issues

---

**Note**: If you need more control over the deployment process, you can customize the post-receive script in Hostinger's Git settings.

