FROM node:22-alpine

WORKDIR /app

# Install dependencies first for better layer caching
COPY package*.json ./
RUN npm ci --omit=dev

# Copy service source code
COPY . .

# Expose internal port
EXPOSE 3000

CMD ["node", "server.js"]
