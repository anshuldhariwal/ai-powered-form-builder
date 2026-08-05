FROM nginx:1.28-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

HEALTHCHECK --interval=5s --timeout=3s --retries=12 \
    CMD wget --quiet --output-document=- http://127.0.0.1/health > /dev/null || exit 1
