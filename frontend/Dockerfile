# Multi-stage build for Vue 3 + Vite frontend

# Stage 1: Build production assets
FROM node:22-alpine AS build
WORKDIR /app

# Copy dependency definitions
COPY package*.json ./
RUN npm ci

# Copy application source
COPY . .

# Set default API URL build arg (uses /api for Nginx reverse proxy to backend)
ARG VITE_API_BASE_URL=/api
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL

# Build SPA
RUN npm run build

# Stage 2: Serve using Nginx
FROM nginx:1.27-alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
