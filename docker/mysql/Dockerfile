FROM mysql:8.4

COPY docker/mysql/formforge.cnf /etc/mysql/conf.d/formforge.cnf

HEALTHCHECK --interval=5s --timeout=5s --retries=12 \
    CMD ["mysqladmin", "ping", "--host=127.0.0.1", "--silent"]
