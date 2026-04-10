FROM nginx:1.25-alpine

# Remove default config
RUN rm /etc/nginx/conf.d/default.conf

# Copy our config
COPY default.conf /etc/nginx/conf.d/default.conf

# Non-root user
RUN addgroup -g 1001 fulcrum \
    && adduser \
    -u 1001 \
    -G fulcrum \
    -s /bin/sh \
    -D fulcrum \
    && chown -R fulcrum:fulcrum /var/cache/nginx \
    && chown -R fulcrum:fulcrum /var/log/nginx \
    && touch /var/run/nginx.pid \
    && chown fulcrum:fulcrum /var/run/nginx.pid

USER fulcrum

EXPOSE 80