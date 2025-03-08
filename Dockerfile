FROM nginx:alpine

COPY node_modules/reveal.js /usr/share/nginx/html/node_modules/reveal.js
COPY slides/highlight/highlight.min.js /usr/share/nginx/html/highlight/highlight.min.js