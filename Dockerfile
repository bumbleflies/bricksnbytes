# Build stage
FROM node:24-alpine AS builder

WORKDIR /build

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source
COPY . .

# Build Astro
RUN npm run build

# Mailer dependencies (contact form)
FROM node:24-alpine AS mailer
WORKDIR /mailer
COPY mailer/package*.json ./
RUN npm ci --omit=dev
COPY mailer/server.mjs mailer/validate.mjs ./

# Runtime stage
FROM nginx:alpine

# Node runtime for the contact-form mailer, started by the entrypoint hook below
RUN apk add --no-cache nodejs
COPY --from=mailer /mailer /opt/mailer
COPY --chmod=755 docker/40-start-mailer.sh /docker-entrypoint.d/40-start-mailer.sh

# Copy built site from builder
COPY --from=builder /build/dist /usr/share/nginx/html

# Copy nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Health check
HEALTHCHECK --interval=10s --timeout=5s --start-period=10s --retries=5 \
  CMD wget --quiet --tries=1 --spider --user-agent="Docker-HealthCheck-AstroBeta" http://localhost/health || exit 1

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
