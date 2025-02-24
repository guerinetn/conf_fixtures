'use strict';

/** Imports Gulp */
const gulp = require('gulp');
const del = require('del');
const path = require('path');
const fs = require('fs');
const browserSync = require('browser-sync').create();
var exec = require('gulp-exec');

/** Imports Reveal */
const map = require('map-stream');
var asciidoctor = require('@asciidoctor/core')()
var asciidoctorRevealjs = require('@asciidoctor/reveal.js')
asciidoctorRevealjs.register();

/** Définition des constantes */

// Dossier des sources à builder
const srcDir = 'slides';
// Dossier de sortie du build
let outBaseDir = 'public';
let outDir = `${outBaseDir}`;
// Dossier de sortie du build des présentations
let prezOutDir = `${outDir}`;

// Constantes des extensions à prendre en compte pour les différents items du build
const adocIndexFiles = [`${srcDir}/**/index.adoc`, `${srcDir}/**/index-*.adoc`];
const adocWatchExtensions = [`${srcDir}/**/*.adoc`];
const mediasExtensions = [`${srcDir}/**/*.{svg,png,jpg,gif,webp}`];
const mermaidWatchExtensions = [`${srcDir}/**/*.mmd`];
const cssExtensions = [`${srcDir}/**/*.css`];
const jsExtensions = [`${srcDir}/**/*.js`];
const themesExtensions = [`themes/**/*.*`];
const extReplace = require('gulp-ext-replace');

gulp.task('convert', () =>
    gulp.src(adocIndexFiles)
        .pipe(convertAdocToHtml())
        .pipe(extReplace('.html'))
        .pipe(gulp.dest(prezOutDir))


);

gulp.task('copy-and-generate-mermaid-png', () =>
  gulp.src(mermaidWatchExtensions)
    .pipe(gulp.dest(prezOutDir))
    .pipe(exec('mmdc -i <%= file.path %> -o <%= file.path.replace("mmd", options.ext) %>', { ext: 'png' }))
);

gulp.task('dependencies', (done) => {
      gulp.src('node_modules/reveal.js/{css,js,lib,plugin}/**/*.*')
          .pipe(gulp.dest(`${prezOutDir}/node_modules/reveal.js`));
      gulp.src('node_modules/font-awesome/{css,fonts}/*.*')
          .pipe(gulp.dest(`${prezOutDir}/../themes/font-awesome`));
      done();
});



gulp.task('copy-medias', () =>
  gulp.src(mediasExtensions).pipe(gulp.dest(prezOutDir))
);

gulp.task('copy-css', () =>
  gulp.src(cssExtensions).pipe(gulp.dest(prezOutDir))
);

gulp.task('copy-js', () =>
  gulp.src(jsExtensions).pipe(gulp.dest(prezOutDir))
);

gulp.task('copy-themes', () =>
  gulp.src(themesExtensions).pipe(gulp.dest(`${outDir}/themes/`))
);

gulp.task('serveAndWatch', () => {
    browserSync.init({
        server: {
          baseDir: `./${outBaseDir}/`
        },
        directory: true,
        notify: false,
        port: 3000
    });

    function browserSyncReload(cb) {
        browserSync.reload();
        cb();
    }

    gulp.watch(adocWatchExtensions, gulp.series('convert', browserSyncReload));
    gulp.watch(mediasExtensions, gulp.series('copy-medias', browserSyncReload));
    gulp.watch(cssExtensions, gulp.series('copy-css', browserSyncReload));
    gulp.watch(themesExtensions, gulp.series('copy-themes', browserSyncReload));
    gulp.watch(jsExtensions, gulp.series('copy-js', browserSyncReload));
    gulp.watch(mermaidWatchExtensions, gulp.series('copy-and-generate-mermaid-png', browserSyncReload));
});


gulp.task('clean', () => del(outDir, { dot: true }));


// Build production files, the default task
gulp.task('default', gulp.series(
        'clean',
        'convert',
        gulp.parallel('dependencies', 'copy-css', 'copy-js', 'copy-medias', 'copy-themes', 'copy-and-generate-mermaid-png')
    )
);


gulp.task('prepare', prepare);

function prepare(cb) {
    outDir = `${outDir}`
    prezOutDir = `${outDir}`
    cb();
}

// Build dev files
gulp.task('serve', gulp.series(
    'prepare',
    'default',
    'serveAndWatch')
);

function convertAdocToHtml() {

  const attributes = {
      'revealjsdir': `/node_modules/reveal.js@`,
      'runtimePrezDir': `${prezOutDir}`
  };
  const options = {
    safe: 'safe',
    backend: 'revealjs',
    attributes: attributes,
    to_file: false,
    header_footer: true
  };

  return map((file, next) => {
    console.log(`Compilation en html de ${file.path}`);
    const newContent = asciidoctor.convertFile(file.path, options);
    file.contents = new Buffer(newContent);
    next(null, file);
  });
};
