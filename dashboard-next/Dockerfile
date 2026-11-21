FROM node:24-alpine AS dependencies

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci


FROM node:24-alpine AS builder

WORKDIR /app

ENV NEXT_TELEMETRY_DISABLED=1
ENV DATABASE_URL=mysql://build:build@127.0.0.1:3306/build

COPY --from=dependencies /app/node_modules ./node_modules
COPY . .

RUN npx prisma generate \
    && npm run build


FROM node:24-alpine AS runtime

RUN apk add --no-cache ca-certificates wget \
    && wget -q https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem \
       -O /etc/ssl/certs/rds-global-bundle.pem

WORKDIR /app

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ENV PORT=3000
ENV HOSTNAME=0.0.0.0
ENV DB_SSL_CA_PATH=/etc/ssl/certs/rds-global-bundle.pem

RUN addgroup --system --gid 1001 nodejs \
    && adduser --system --uid 1001 nextjs

COPY --from=builder --chown=nextjs:nodejs /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

USER nextjs

EXPOSE 3000

CMD ["node", "server.js"]
