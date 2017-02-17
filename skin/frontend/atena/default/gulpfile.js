re('gulp'),
  compass      = require('gulp-compass'),
  sass         = require('gulp-sass'),
  minifycss    = require('gulp-minify-css'),
  uglify       = require('gulp-uglify'),
  // rename          = require('gulp-rename'),
  rimraf       = require('gulp-rimraf'),
  concat       = require('gulp-concat'),
  notify       = require('gulp-notify'),
  cache        = require('gulp-cache'),
  livereload   = require('gulp-livereload'),
  // plumber      = require('gulp-plumber'),
  path         = require('path');

var config = {

  // If you do not have the live reload extension installed,
  // set this to true. We will include the script for you,
  // just to aid with development.
  appendLiveReload: true,
  // Should CSS & JS be compressed?
  minifyCss: true,
  uglifyJS: true

}

// css
gulp.task('css', function() {
  var stream = gulp
    .src(['scss/styles.scss'])
    .pipe(compass({
        config_file: 'config.rb',
        css: '../css',
        sass: 'scss',
        image: '../images'
    }))
    .pipe(gulp.dest('../css'));

  if (config.minifyCss === true) {
    stream.pipe(minifycss({keepSpecialComments: '0'}));
  }

  return stream
    .pipe(gulp.dest('../css'))
    //.pipe(notify({ message: 'Successfully compiled LESS' }));
});
