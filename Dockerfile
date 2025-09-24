# הצמד תגית יציבה במקום latest
FROM fireflyiii/core:version-6.2.21

WORKDIR /var/www/html

# מעתיקים רק את קבצי האפליקציה ששינית (לא vendor)
# אם נוח לך, אפשר גם COPY . . כל עוד ב-.dockerignore אתה חוסם vendor/node_modules
COPY routes/ routes/
COPY resources/ resources/
COPY app/ app/
COPY public/ public/
COPY config/ config/
# אם לא שינית את composer.json/lock - אל תעתיק אותם בכלל

# לא מריצים שום artisan ולא composer בזמן ה-build
# ה-entrypoint המקורי של התמונה ירים nginx+php-fpm
