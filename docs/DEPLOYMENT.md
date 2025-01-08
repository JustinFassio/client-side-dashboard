# Deployment Guide

## Table of Contents
- [Prerequisites](#prerequisites)
- [Environment Setup](#environment-setup)
- [Build Process](#build-process)
- [Deployment Process](#deployment-process)
- [Post-Deployment](#post-deployment)
- [Rollback Procedures](#rollback-procedures)

## Prerequisites

### Server Requirements
- PHP 8.0+
- MySQL 5.7+
- Node.js 16+ (for build process)
- WordPress 6.0+
- SSH access
- Composer
- WP-CLI

### Access Requirements
- SSH keys configured
- Database credentials
- WordPress admin access
- Deployment keys/tokens

## Environment Setup

1. **Configure Environment Variables**
   ```bash
   # Copy environment template
   cp .env.example .env.production

   # Update production values
   vim .env.production
   ```

2. **Server Configuration**
   ```nginx
   # Nginx configuration
   server {
       listen 80;
       server_name your-domain.com;
       root /var/www/html;

       location / {
           try_files $uri $uri/ /index.php?$args;
       }

       location ~ \.php$ {
           include fastcgi_params;
           fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
       }
   }
   ```

## Build Process

1. **Install Dependencies**
   ```bash
   # Install PHP dependencies
   composer install --no-dev --optimize-autoloader

   # Install Node dependencies
   npm ci

   # Build production assets
   npm run build
   ```

2. **Optimize Assets**
   ```bash
   # Optimize images
   npm run optimize-images

   # Generate asset manifest
   npm run generate-manifest
   ```

## Deployment Process

### Manual Deployment

1. **Backup Current State**
   ```bash
   # Backup database
   wp db export backup.sql

   # Backup uploads
   tar -czf uploads-backup.tar.gz wp-content/uploads/
   ```

2. **Deploy Code**
   ```bash
   # Pull latest changes
   git pull origin main

   # Install dependencies
   composer install --no-dev
   npm ci
   npm run build

   # Clear caches
   wp cache flush
   ```

### Automated Deployment (GitHub Actions)

```yaml
name: Deploy to Production
on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
          
      - name: Setup Node
        uses: actions/setup-node@v2
        with:
          node-version: '16'
          
      - name: Install Dependencies
        run: |
          composer install --no-dev
          npm ci
          
      - name: Build Assets
        run: npm run build
        
      - name: Deploy
        uses: deployphp/action@master
        with:
          private-key: ${{ secrets.SSH_PRIVATE_KEY }}
          deployment-file: deploy.php
```

## Post-Deployment

1. **Verify Deployment**
   - Check application status
   - Run smoke tests
   - Monitor error logs
   - Verify database migrations

2. **Cache Management**
   ```bash
   # Clear WordPress cache
   wp cache flush

   # Clear object cache
   wp redis flush

   # Clear CDN cache
   wp cdn purge
   ```

3. **Performance Checks**
   - Run performance tests
   - Check load times
   - Monitor server resources
   - Verify CDN integration

## Rollback Procedures

### Quick Rollback

1. **Revert Code**
   ```bash
   # Revert to previous commit
   git revert HEAD

   # Or checkout specific tag
   git checkout v1.0.0
   ```

2. **Restore Database**
   ```bash
   # Import backup
   wp db import backup.sql
   ```

### Full Rollback

1. **Stop Services**
   ```bash
   # Enable maintenance mode
   wp maintenance-mode activate
   ```

2. **Restore Backups**
   ```bash
   # Restore code
   git checkout v1.0.0

   # Restore database
   wp db import backup.sql

   # Restore uploads
   tar -xzf uploads-backup.tar.gz
   ```

3. **Restart Services**
   ```bash
   # Clear caches
   wp cache flush

   # Disable maintenance mode
   wp maintenance-mode deactivate
   ```

## Monitoring

1. **Error Tracking**
   - Monitor WordPress debug log
   - Check PHP error logs
   - Monitor JavaScript console errors

2. **Performance Monitoring**
   - Server resource usage
   - Database performance
   - API response times
   - Frontend load times

3. **Security Monitoring**
   - Failed login attempts
   - API rate limiting
   - File integrity checks
   - Security plugin alerts

## Need Help?

- Check deployment logs
- Review error logs
- Contact DevOps team
- Consult documentation
``` 