# BBB Laravel Application - Docker Deployment

Docker containerized setup untuk aplikasi Laravel BBB dengan MySQL, Redis, dan Nginx.

## 📋 Prerequisites

-   Docker Desktop
-   Docker Compose
-   Git (untuk clone repository)

## 🚀 Quick Start

### 1. Clone dan Setup

```bash
git clone <your-repo-url>
cd bbb
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Edit .env file sesuai kebutuhan
# Terutama database credentials dan JWT settings
```

### 3. Deploy dengan Script Otomatis

```bash
# Untuk Windows (Git Bash)
./deploy.sh

# Atau manual dengan docker-compose
docker-compose up --build -d
```

## 🏗️ Arsitektur Container

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Nginx Proxy   │    │  Laravel App    │    │     MySQL       │
│   Port: 80      │◄───┤   Port: 8000    │◄───┤   Port: 3307    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                ▲
                                │
                       ┌─────────────────┐
                       │     Redis       │
                       │   Port: 6380    │
                       └─────────────────┘
```

## 🔧 Services

| Service             | Port | Description                           |
| ------------------- | ---- | ------------------------------------- |
| **app**             | 8000 | Laravel Application (PHP-FPM + Nginx) |
| **nginx**           | 80   | Load Balancer & Reverse Proxy         |
| **db**              | 3307 | MySQL Database                        |
| **redis**           | 6380 | Cache & Session Store                 |
| **phpmyadmin**      | 8080 | Database Management                   |
| **redis-commander** | 8081 | Redis Management                      |

## 📁 Docker Files Structure

```
docker/
├── nginx/
│   ├── nginx.conf          # Main Nginx config
│   ├── default.conf        # Laravel virtual host
│   └── production.conf     # Production load balancer
├── mysql/
│   └── my.cnf             # MySQL optimization
├── php.ini                # PHP production settings
├── php-fpm.conf          # FPM process management
└── supervisord.conf      # Process supervisor
```

## 🚀 Deployment Commands

### Manual Deployment

```bash
# Build dan start semua services
docker-compose up --build -d

# Jalankan migrations
docker-compose exec app php artisan migrate --force

# Optimize Laravel
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Set permissions
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chown -R www-data:www-data bootstrap/cache
```

### Development Mode

```bash
# Start dengan logs visible
docker-compose up --build

# Rebuild specific service
docker-compose up --build app

# Scale aplikasi (multiple instances)
docker-compose up --scale app=3 -d
```

## 🔍 Monitoring & Debugging

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f db
docker-compose logs -f nginx
```

### Access Containers

```bash
# Laravel app container
docker-compose exec app bash

# MySQL container
docker-compose exec db mysql -u root -p

# Redis container
docker-compose exec redis redis-cli
```

### Health Check

```bash
# Check service status
docker-compose ps

# Health endpoint
curl http://localhost/health
```

## ⚙️ Configuration

### Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db_bbb
DB_USERNAME=root
DB_PASSWORD=secret123

# Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

# JWT
JWT_SECRET=your-secret-key
JWT_TTL=1440
JWT_REFRESH_TTL=43200
```

### Docker Compose Override

Buat file `docker-compose.override.yml` untuk custom configuration:

```yaml
version: "3.8"
services:
    app:
        environment:
            - APP_DEBUG=true
        volumes:
            - ./:/var/www/html
```

## 🔒 Security Features

-   **Rate Limiting**: API endpoints dengan rate limiting
-   **Security Headers**: X-Frame-Options, X-XSS-Protection, dll
-   **SSL Ready**: HTTPS configuration tersedia
-   **JWT Auto-refresh**: Token refresh otomatis
-   **Database Security**: MySQL native authentication

## 📊 Performance Optimization

### PHP-FPM Settings

-   Dynamic process management
-   OPCache enabled
-   Memory limit optimization

### MySQL Optimization

-   InnoDB buffer pool tuning
-   Connection pooling
-   Query cache enabled

### Nginx Optimization

-   Gzip compression
-   Static file caching
-   Load balancing ready

## 🛠️ Maintenance Commands

### Update Application

```bash
# Pull latest changes
git pull origin main

# Rebuild dan restart
docker-compose up --build -d

# Run migrations
docker-compose exec app php artisan migrate --force
```

### Backup Database

```bash
# Backup ke file
docker-compose exec db mysqldump -u root -p db_bbb > backup.sql

# Restore dari backup
docker-compose exec -T db mysql -u root -p db_bbb < backup.sql
```

### Clear Cache

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

## 🚨 Troubleshooting

### Common Issues

1. **Permission Errors**

    ```bash
    docker-compose exec app chown -R www-data:www-data storage
    docker-compose exec app chmod -R 775 storage
    ```

2. **Database Connection Failed**

    ```bash
    # Check database is running
    docker-compose ps db

    # Check logs
    docker-compose logs db
    ```

3. **Port Already in Use**

    ```bash
    # Change ports in docker-compose.yml
    ports:
      - "8001:80"  # Instead of 8000:80
    ```

4. **Memory Issues**
    ```bash
    # Increase Docker memory limit
    # Docker Desktop > Settings > Resources > Memory
    ```

## 🌐 Production Deployment

### SSL Certificate Setup

1. Place certificates in `docker/nginx/ssl/`
2. Uncomment SSL configuration in `production.conf`
3. Update domain name in configuration

### Environment Production

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
```

### Monitoring Setup

-   Add health check endpoints
-   Setup logging aggregation
-   Configure alerting system

## 📞 Support

Jika mengalami masalah deployment:

1. Check logs: `docker-compose logs -f`
2. Verify permissions: `ls -la storage/`
3. Test database connection: `docker-compose exec app php artisan tinker`
4. Check environment: `docker-compose exec app php artisan config:show`

---

**Happy Deploying! 🚀**
