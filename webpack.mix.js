let mix = require('laravel-mix')

mix.setPublicPath('public')
    .js('resources/js/field.js', 'dist/js')
    .sass('resources/sass/field.scss', 'dist/css')
    .webpackConfig({
        resolve: {
            symlinks: false
        }
    })
