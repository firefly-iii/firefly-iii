# Firefly III Docker Setup

This document contains Docker-specific setup instructions for Firefly III. For general Firefly III documentation, please refer to the main [README.md](README.md).

## Prerequisites

- Docker and Docker Compose installed
- Git installed
- PowerShell (for Windows environment)

## Environment Variables

### Development Environment (.env.dev)
```ini
# Firefly III Configuration
APP_ENV=testing
APP_DEBUG=true
APP_KEY=TestTestTestTestTestTestTestTest
APP_URL=http://fireflyiii-dev.localhost
APP_TIMEZONE=UTC
APP_LOCALE=en_US
APP_LOG_ENV=notice
QUERY_PARSER_IMPLEMENTATION=new

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=fireflyiii
DB_USERNAME=firefly
DB_PASSWORD=firefly_password

# Redis Configuration
REDIS_HOST=cache
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=log
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="Firefly III"

# Cache and Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
LOG_CHANNEL=stack
LOG_LEVEL=debug
MODEL_CACHE_ENABLED=false

# Security
AUTHENTICATION_GUARD=web
AUTHENTICATION_EMAIL_VERIFICATION=false
AUTHENTICATION_MUST_VERIFY_EMAIL=false
AUTHENTICATION_MUST_VERIFY_EMAIL_NEW_USER=false

# Firefly III Specific
FIREFLY_III_LAYOUT=v1
FIREFLY_III_AUTO_UPDATE_CHECK=false
FIREFLY_III_SINGLE_USER_MODE=false
FIREFLY_III_IS_DEMO_SITE=false
FIREFLY_III_ALLOW_REGISTRATION=true
FIREFLY_III_ALLOW_PASSWORD_RESET=true
FIREFLY_III_ALLOW_SOCIAL_LOGIN=false
FIREFLY_III_ALLOW_OAUTH_ACCESS_TOKEN=true
FIREFLY_III_ALLOW_OAUTH_REFRESH_TOKEN=true
FIREFLY_III_ALLOW_OAUTH_PUBLIC_CLIENTS=true
FIREFLY_III_ALLOW_OAUTH_PRIVATE_CLIENTS=true
FIREFLY_III_ALLOW_OAUTH_AUTHORIZATION_CODE=true
FIREFLY_III_ALLOW_OAUTH_CLIENT_CREDENTIALS=true
FIREFLY_III_ALLOW_OAUTH_PASSWORD_GRANT=true
FIREFLY_III_ALLOW_OAUTH_IMPLICIT_GRANT=true
FIREFLY_III_ALLOW_OAUTH_REFRESH_TOKEN_GRANT=true
FIREFLY_III_ALLOW_OAUTH_AUTHORIZATION_CODE_GRANT=true
FIREFLY_III_ALLOW_OAUTH_CLIENT_CREDENTIALS_GRANT=true

# Feature Flags
USE_RUNNING_BALANCE=false
ENABLE_EXTERNAL_MAP=false
DISABLE_FRAME_HEADER=false
DISABLE_CSP_HEADER=false
ALLOW_WEBHOOKS=false
SEND_ERROR_MESSAGE=true
```

### Production Environment (.env.prod)
```ini
# Firefly III Configuration
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://fireflyiii.yourdomain.com
APP_TIMEZONE=UTC
APP_LOCALE=en_US

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=fireflyiii
DB_USERNAME=firefly
DB_PASSWORD=change_this_to_a_secure_password

# Redis Configuration
REDIS_HOST=cache
REDIS_PASSWORD=change_this_to_a_secure_password
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="Firefly III"

# Cache and Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
LOG_CHANNEL=stack
LOG_LEVEL=error

# Security
AUTHENTICATION_GUARD=web
AUTHENTICATION_EMAIL_VERIFICATION=true
AUTHENTICATION_MUST_VERIFY_EMAIL=true
AUTHENTICATION_MUST_VERIFY_EMAIL_NEW_USER=true

# Firefly III Specific
FIREFLY_III_LAYOUT=v1
FIREFLY_III_AUTO_UPDATE_CHECK=true
FIREFLY_III_SINGLE_USER_MODE=false
FIREFLY_III_IS_DEMO_SITE=false
FIREFLY_III_ALLOW_REGISTRATION=false
FIREFLY_III_ALLOW_PASSWORD_RESET=true
FIREFLY_III_ALLOW_SOCIAL_LOGIN=false
FIREFLY_III_ALLOW_OAUTH_ACCESS_TOKEN=true
FIREFLY_III_ALLOW_OAUTH_REFRESH_TOKEN=true
FIREFLY_III_ALLOW_OAUTH_PUBLIC_CLIENTS=true
FIREFLY_III_ALLOW_OAUTH_PRIVATE_CLIENTS=true
FIREFLY_III_ALLOW_OAUTH_AUTHORIZATION_CODE=true
FIREFLY_III_ALLOW_OAUTH_CLIENT_CREDENTIALS=true
FIREFLY_III_ALLOW_OAUTH_PASSWORD_GRANT=true
FIREFLY_III_ALLOW_OAUTH_IMPLICIT_GRANT=true
FIREFLY_III_ALLOW_OAUTH_REFRESH_TOKEN_GRANT=true
FIREFLY_III_ALLOW_OAUTH_AUTHORIZATION_CODE_GRANT=true
FIREFLY_III_ALLOW_OAUTH_CLIENT_CREDENTIALS_GRANT=true
```

## Setup Instructions

1. Create environment files:
```powershell
# For development
Copy-Item .env.dev.template .env.dev
# For production
Copy-Item .env.prod.template .env.prod
```

2. Edit the environment files:
   - Copy the appropriate template above into your `.env.dev` or `.env.prod` file
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