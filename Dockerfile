FROM node:18.20-alpine3.20 AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY slides/ slides/
RUN npx asciidoctor-revealjs slides/index.adoc

FROM nginx:1.27-alpine3.21
COPY --from=build /app/node_modules/reveal.js/dist /usr/share/nginx/html/node_modules/reveal.js/dist
COPY --from=build /app/node_modules/reveal.js/plugin /usr/share/nginx/html/node_modules/reveal.js/plugin
COPY --from=build /app/slides/highlight/highlight.min.js /usr/share/nginx/html/highlight/highlight.min.js
COPY --from=build /app/slides/index.html /usr/share/nginx/html/index.html
COPY slides/images/ /usr/share/nginx/html/images/
COPY slides/css/ /usr/share/nginx/html/css/
