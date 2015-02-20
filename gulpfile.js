var gulp     = require( 'gulp' ),
    phpcs    = require( 'gulp-phpcs' ),
    sys      = require( 'sys' ),
    exec     = require( 'child_process' ).exec;

var config = {
    paths: {
        src: './src/',
        tests: './tests/',
        vendor: './vendor/'
    }
};

gulp.task( 'default', [ 'watch' ]);

// Validate files using PHP Code Sniffer
gulp.task( 'phpcs', function()
{
    return gulp.src( config.paths.src + '**' )
        .pipe( phpcs({
            bin             : config.paths.vendor + 'bin/phpcs',
            standard        : 'PSR2',
            warningSeverity : 0
        }))
        // Log all problems that was found
        .pipe( phpcs.reporter( 'log' ) );
} );

gulp.task( 'phpunit', function()
{
    exec( config.paths.vendor + 'bin/phpunit', function(error, stdout) {
        sys.puts(stdout);
    });
});

gulp.task( 'watch', [ 'phpcs', 'phpunit' ], function()
{
    gulp.watch( config.paths.src + '**/*', [ 'phpcs', 'phpunit' ] );
    gulp.watch( config.paths.tests + '**/*', [ 'phpunit' ] );
});
