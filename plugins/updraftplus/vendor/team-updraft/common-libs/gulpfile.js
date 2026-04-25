/* ==Requirements to build the gulp js file== */
const gulp = require('gulp');
const runSequence = require('gulp4-run-sequence');
const uglify = require('gulp-uglify');
const uglifycss = require('gulp-uglifycss');
const rename = require('gulp-rename');
const postcss = require('gulp-postcss');
const sourcemaps = require('gulp-sourcemaps');
const gutil = require('gulp-util');
const autoprefixer = require('autoprefixer');
const clean = require('gulp-clean');
const plumber = require('gulp-plumber');
const sass = require('gulp-sass')(require('sass'));
const notify = require('gulp-notify')
const modernizr = require('gulp-modernizr');
const webpack = require('webpack');
const webpackStream = require('webpack-stream');

/*== Specify Paths for various UpdraftCentral Files ==*/

// set clean paths
const cleanPaths = [
	'updraft-theme/*',
	'composer.lock',
	'package-lock.json'
];

const handlebarsPaths = [
	'node_modules/handlebars/dist/handlebars.js',
	'node_modules/handlebars/dist/handlebars.min.js',
	'node_modules/handlebars/dist/handlebars.runtime.js',
	'node_modules/handlebars/dist/handlebars.runtime.min.js',
	'node_modules/handlebars/LICENSE'
];

const jsWatchPaths = [
	'src/updraft-theme/**/*.js',
];

const jsPaths = {
	'theme': './src/updraft-theme/theme.js',
};

const phpPaths = [
	'src/updraft-theme/*.php'
];

const svgsPaths = [
	'src/updraft-theme/**/*.svg'
];

var fontPaths = [
	'src/updraft-theme/**/*.woff2',
	'src/updraft-theme/**/*.ttf'
];

const scssWatchPaths = [
	'src/updraft-theme/**/*.scss',
];

const scssPaths = [
	'src/updraft-theme/theme.scss',
	'src/updraft-theme/theme-colors.scss',
];

const htmlPaths = [
	'src/updraft-theme/**/*.html'
];

/* ==Start Gulp Process== */
gulp.copy = function(src,dest) {
	return gulp.src(src, {base:"."})
	.pipe(plumber(reportError))
	.pipe(gulp.dest(dest));
};

/* ==Images=== */
gulp.task('svgs_move', function(){
	return gulp.src(svgsPaths)
	.pipe(plumber(reportError))
	.pipe(gulp.dest('updraft-theme'));
})

/* ==Fonts=== */
gulp.task('fonts_move', function(){
	return gulp.src(fontPaths)
	.pipe(plumber(reportError))
	.pipe(gulp.dest('updraft-theme'));
});

/* ==Sorting HTML=== */
gulp.task('html_move', function(){
	return gulp.src(htmlPaths)
	.pipe(plumber(reportError))
	.pipe(gulp.dest('updraft-theme'));
});

/* ===Sorting SCSS=== */
gulp.task('scss_compile', function(){
	return gulp.src(scssPaths)
	.pipe(plumber(reportError))
	.pipe(sourcemaps.init())
	.pipe(sass({silenceDeprecations: ['legacy-js-api', 'mixed-decls', 'color-functions', 'global-builtin', 'import']}))
	.pipe(postcss([ autoprefixer({ browsers: ['last 2 versions'] }) ]))
	.pipe(plumber(reportError))
	.pipe(gulp.dest('updraft-theme'))
	.pipe(uglifycss({'maxLineLen': 0, 'uglyComments': true}).on('error', console.error))
	.pipe(rename({suffix: '.min'}))
	.pipe(sourcemaps.write('.'))
	.pipe(plumber.stop())
	.pipe(gulp.dest('updraft-theme'));
})

/* ===Sorting JS=== */
gulp.task('js_compile', function(){
	return webpackStream({
            mode: 'production',
			entry: jsPaths,
            output: {
                filename: '[name].js',
            },
        }, webpack)
        .pipe(gulp.dest('updraft-theme'));
});

/* ====Sorting PHP======= */
gulp.task('php_move', function(){
	return gulp.src(phpPaths)
	.pipe(plumber(reportError))
	.pipe(gulp.dest('updraft-theme'));
})

//Modernizer settings
const modernizerSettings = { 
	"options": [
	"domPrefixes",
	"prefixes",
	"addTest",
	"atRule",
	"hasEvent",
	"mq",
	"prefixed",
	"prefixedCSS",
	"prefixedCSSValue",
	"testAllProps",
	"testProp",
	"testStyles",
	"html5printshiv",
	"setClasses"
	],
	"tests": [
	"eventlistener",
	"lastchild",
	"inputtypes"
	],
	"excludeTests": [
	"supports"
	],
	"uglify": false
};

//normal version
gulp.task('modernizr', function() {
  return gulp.src('./src/updraft-theme/js/*.js')
  	.pipe(plumber(reportError))
    .pipe(modernizr('modernizr-custom.js', modernizerSettings))
    .pipe(gulp.dest('updraft-theme/js/modernizr/'))
});

//minified version
gulp.task('modernizr_min', function() {

  return gulp.src('./src/updraft-theme/js/*.js')
  	.pipe(plumber(reportError))
    .pipe(modernizr('modernizr-custom.min.js', modernizerSettings))
    .pipe(uglify().on('error', console.error))
    .pipe(gulp.dest('updraft-theme/js/modernizr/'))
});

gulp.task('handlebar', function(){
	return gulp.src(handlebarsPaths)
	.pipe(plumber(reportError))
	.pipe(gulp.dest('updraft-theme/handlebar-library'));
});

/*== Clean theme build ==*/
gulp.task('clean', function(){
	return gulp.src(cleanPaths, { allowEmpty: true })
	.pipe(plumber(reportError))
	.pipe(clean({force:true}));
})

/*== Building the files ==*/
gulp.task('build', function(done){
	runSequence('clean',
				['js_compile'],
				['php_move'],
				['html_move', 'scss_compile', 'svgs_move', 'fonts_move'],
				['handlebar'],
				done
	);
});

/*== These tasks are ran to build gulp files ==*/

// ran with gulp build
gulp.task('default', function(){
	runSequence('clean', 'build', 'watch');
});

gulp.task('php', function(done){
	runSequence('php_move', done);
});

gulp.task('js', function(done) {
	runSequence('js_compile', done);
});

gulp.task('scss', function(done) {
	runSequence('scss_compile', done);
});

gulp.task('html', function(done){
	runSequence('html_move', done);
});

gulp.task('watch', function(){
	gulp.watch(scssWatchPaths, gulp.series(['scss']));
	gulp.watch(jsWatchPaths, gulp.series(['js']));
	gulp.watch(phpPaths, gulp.series(['php']));
	gulp.watch('src/updraft-theme/**/*.handlebars.html', gulp.series(['html']));
});

// Setup pretty error handling
var reportError = function (error) {
    var lineNumber = (error.lineNumber) ? 'LINE ' + error.lineNumber + ' -- ' : '';
    var report = '';
    var chalk = gutil.colors.white.bgRed;

    // Shows a pop when errors
    notify({
        title: 'Task Failed [' + error.plugin + ']',
		message: lineNumber + 'See console.',
        sound: 'Sosumi' // See: https://github.com/mikaelbr/node-notifier#all-notification-options-with-their-defaults
    }).write(error);

    report += chalk('GULP TASK:') + ' [' + error.plugin + ']\n';
    report += chalk('PROB:') + ' ' + error.message + '\n';
    if (error.lineNumber) { report += chalk('LINE:') + ' ' + error.lineNumber + '\n'; }
    if (error.fileName)   { report += chalk('FILE:') + ' ' + error.fileName + '\n'; }
    console.error(report);
    // console.log(error);
    process.exit(1);
}
