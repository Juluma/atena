var gulp = require('gulp');
var plugins = require("gulp-load-plugins")({
	pattern: ['gulp-*', 'gulp.*', 'main-bower-files'],
	replaceString: /\bgulp[\-.]/
});

gulp.task('styles', function () {
	gulp.src('css/sass/*.scss')
		.pipe(plugins.plumber())
		.pipe(plugins.sass())
		.pipe(plugins.autoprefixer("last 2 version", "> 1%", "ie 8", "ie 7"))
		.pipe(plugins.minifyCss())
		.pipe(gulp.dest('css'));
		// .pipe(plugins.livereload());
		// .pipe(plugins.notify({ message: 'Styles task complete' }));
});

gulp.task('scripts', function () {

	var jsFiles = [
		'bower_components/modernizr/modernizr.js',
		'bower_components/jquery/dist/jquery.js',
		'bower_components/foundation/js/foundation/foundation.js',
		// 'bower_components/foundation/js/foundation/foundation.topbar.js',
		// 'bower_components/foundation/js/foundation/foundation.orbit.js',
		'bower_components/fastclick/lib/fastclick.js',
		'bower_components/magnific-popup/dist/jquery.magnific-popup.js',
		'bower_components/foundation/js/foundation/foundation.offcanvas.js',
		'js/to-be-minified/*',
		'js/vendor/to-be-minified/*'
	];

	gulp.src(jsFiles)
		.pipe(plugins.filter(['*.js']))
		.pipe(plugins.plumber())
		.pipe(plugins.concat("scripts.min.js"))
		.pipe(plugins.uglify())
		.pipe(gulp.dest('js'));
		// .pipe(plugins.livereload())
		// .pipe(notify({ message: 'Scripts task complete' }));
});

gulp.task('watch', function () {
	// plugins.livereload.listen();
	gulp.watch('css/sass/**/*.scss', ['styles']);
	gulp.watch('js/to-be-minified/**/*.js', ['scripts']);
});

gulp.task('default', ['styles', 'scripts', 'watch']);
