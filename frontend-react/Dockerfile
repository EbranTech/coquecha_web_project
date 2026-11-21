FROM node:24-alpine AS builder

WORKDIR /app

ARG VITE_API_URL=http://localhost:8080/api/v1
ARG VITE_LOGIN_URL=http://localhost:8080/login
ARG VITE_ADMIN_URL=http://localhost:8080/admin
ARG VITE_INDICATORS_URL=http://localhost:3000

ENV VITE_API_URL=$VITE_API_URL
ENV VITE_LOGIN_URL=$VITE_LOGIN_URL
ENV VITE_ADMIN_URL=$VITE_ADMIN_URL
ENV VITE_INDICATORS_URL=$VITE_INDICATORS_URL

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


FROM nginx:1.27-alpine AS runtime

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80
