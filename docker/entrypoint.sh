#!/usr/bin/env sh
set -e

cd /var/www/html

# אם אין .env - נעתיק מהדוגמה
if [ ! -f .env ]; then
  cp .env.example .env 2>/dev/null || true
fi

# אם APP_KEY חסר/ריק - נייצר מפתח בלי artisan
if ! grep -q "^APP_KEY=" .env || [ -z "$(grep '^APP_KEY=' .env | cut -d= -f2-)" ]; then
  NEWKEY=$(php -r 'echo "base64:".base64_encode(random_bytes(32));')
  # אם יש APP_KEY שורה – נחליף; אחרת נוסיף
  if grep -q "^APP_KEY=" .env; then
    # החלפה בטוחה בלי GNU sed תלויות
    awk -v K="$NEWKEY" 'BEGIN{FS=OFS="="} /^APP_KEY=/{$2=K} !/^APP_KEY=/{print} /^APP_KEY=/{print; next}' .env >/tmp/.env.new 2>/dev/null || true
    if [ -s /tmp/.env.new ]; then mv /tmp/.env.new .env; else sed -i "s#^APP_KEY=.*#APP_KEY=$NEWKEY#" .env 2>/dev/null || echo "APP_KEY=$NEWKEY" >> .env; fi
  else
    echo "APP_KEY=$NEWKEY" >> .env
  fi
fi

# אל תריץ כאן php artisan optimize:clear/config:clear/route:clear/view:clear
# הן עלולות להדליק את ה-Exception Handler בזמן שאין auth/DB זמין.

# אם תרצה להריץ migrate אחרי שה-DB מוכן, תוכל לעשות זאת ידנית:
# docker exec -it <container> php artisan migrate --force

# הרץ את הפקודה המקורית (CMD)
exec "$@"
