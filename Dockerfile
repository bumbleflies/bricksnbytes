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

# Runtime stage
FROM nginx:alpine

# Copy built site from builder
COPY --from=builder /build/dist /usr/share/nginx/html

# Copy nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD wget --quiet --tries=1 --spider --user-agent="Docker-HealthCheck-AstroBeta" http://localhost/ || exit 1

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
