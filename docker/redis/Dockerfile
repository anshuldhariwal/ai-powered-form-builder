FROM redis:8-alpine

COPY docker/redis/redis.conf /usr/local/etc/redis/redis.conf

HEALTHCHECK --interval=5s --timeout=3s --retries=5 \
    CMD ["redis-cli", "ping"]

CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]
