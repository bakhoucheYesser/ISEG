const Encore = require('@symfony/webpack-encore');
const path = require('path');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            plugins: [
                require('@tailwindcss/postcss')
            ]
        };
    })
    .addAliases({
        '@symfony/stimulus-bridge/controllers.json': path.resolve(__dirname, 'assets/controllers.json')
    })
    .enableStimulusBridge('./assets/controllers.json')

    // Configuration pour corriger le problème de manifeste
    .configureManifestPlugin((options) => {
        options.writeToFileEmit = true;
    });

module.exports = Encore.getWebpackConfig();
