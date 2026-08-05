FROM node:24-bookworm-slim AS node-base

ARG APP_UID=1000
ARG APP_GID=1000

WORKDIR /var/www/html

RUN groupmod --gid "${APP_GID}" node \
    && usermod --uid "${APP_UID}" --gid "${APP_GID}" node

COPY docker/node/frontend-init.sh /usr/local/bin/formforge-frontend-init

RUN chmod 0755 /usr/local/bin/formforge-frontend-init

FROM node-base AS frontend-init

USER root

ENTRYPOINT ["formforge-frontend-init"]

FROM node-base AS development

USER node

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
