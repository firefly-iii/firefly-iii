# Firefly III Docker Setup

This document contains Docker-specific setup instructions for Firefly III. For general Firefly III documentation, please refer to the main [README.md](README.md).

## Prerequisites

- Docker and Docker Compose installed
- Git installed
- PowerShell (for Windows environment)

## Setup Instructions

1. Create environment files:
```powershell
# For development
Copy-Item .env.dev.template .env.dev
# For production
Copy-Item .env.prod.template .env.prod
```

2. Edit the environment files:
   - Update `APP_KEY` with a secure random string (you can generate one using `php artisan key:generate`)
   - Update database credentials
   - Update Redis password
   - Update mail settings if needed
   - Update `APP_URL` to match your domain

3. Start the development environment:
```powershell
docker-compose -f docker-compose.dev.yml up -d
```

4. Start the production environment:
```powershell
docker-compose -f docker-compose.prod.yml up -d
```

## Environment Files

The environment files (`.env.dev` and `.env.prod`) contain important configuration settings. Make sure to:

1. Never commit the actual `.env` files to version control
2. Keep the `.env.*.template` files in version control
3. Update all passwords and sensitive information
4. Set appropriate values for your environment

## Services

The setup includes:

- Firefly III application
- MariaDB database
- Redis cache

## Development vs Production

### Development Environment
- Uses `docker-compose.dev.yml`
- Debug mode enabled
- Development-specific settings
- Accessible at `fireflyiii-dev.localhost`

### Production Environment
- Uses `docker-compose.prod.yml`
- Debug mode disabled
- Production-optimized settings
- Accessible at your configured domain

## Traefik Integration

The setup is configured to work with Traefik. The labels in the docker-compose files are set up for Traefik integration, but SSL/TLS configuration should be handled by your Traefik setup.

## Backup

Database backups should be configured separately. The MariaDB data is stored in a Docker volume named `fireflyiii_db` (development) or `fireflyiii_db_prod` (production).

## Maintenance

To update the containers:
```powershell
docker-compose -f docker-compose.dev.yml pull
docker-compose -f docker-compose.dev.yml up -d
```

## Troubleshooting

1. If the application doesn't start:
   - Check the logs: `docker-compose -f docker-compose.dev.yml logs`
   - Verify environment variables
   - Ensure ports are not in use

2. Database connection issues:
   - Verify database credentials in `.env` file
   - Check if the database container is running
   - Ensure the network is properly configured

## Security Notes

1. Always use strong passwords in production
2. Keep your environment files secure
3. Regularly update the containers
4. Monitor the logs for any suspicious activity 