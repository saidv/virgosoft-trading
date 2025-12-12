# ==========================================
# Stage 1: Base (Common dependencies)
# ==========================================
FROM node:20-alpine as base
WORKDIR /app
COPY frontend/package*.json ./

# ==========================================
# Stage 2: Development (Hot-reloading)
# ==========================================
FROM base as dev
# Install all dependencies (including devDependencies)
RUN npm install
# Copy source code
COPY frontend/ .
# Expose Vite port
EXPOSE 5173
# Run dev server
CMD ["npm", "run", "dev", "--", "--host"]

# ==========================================
# Stage 3: Builder (Compiles for Production)
# ==========================================
FROM base as builder
# Install dependencies (clean install)
RUN npm install
# Copy source code
COPY frontend/ .
# Build the application (outputs to /app/dist)
RUN npm run build

# ==========================================
# Stage 4: Production (Nginx Server)
# ==========================================
FROM nginx:stable-alpine as production
# Copy built assets from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html
# Copy custom Nginx config
COPY infra/nginx/frontend/nginx.conf /etc/nginx/conf.d/default.conf
# Expose HTTP port
EXPOSE 80
# Start Nginx
CMD ["nginx", "-g", "daemon off;"]
