const path = require('path');
const webpack = require('webpack');
const ManifestPlugin = require('webpack-manifest-plugin');
require('graceful-fs').gracefulify(require('fs'));

let ASSET_PATH = '';
module.exports = env => {

    let DOMAIN = JSON.stringify('http://localhost/tanamesa');
    if (env.NODE_ENV === 'app') {
        DOMAIN = JSON.stringify('https://tanamesa.app.br/painel');
        ASSET_PATH = '';
    }

    return {
        entry: {
            main: './view/assets/js/init.js',
        },
        output: {
            filename: "[name].[contenthash].js",
            path: path.resolve(__dirname, 'view/assets/dist'),
            publicPath: ASSET_PATH + '/view/assets/dist/',
        },
        plugins: [
            new webpack.DefinePlugin({
                DOMAIN,
            }),
            new webpack.ProvidePlugin({
                $: 'jquery',
                jQuery: 'jquery'
            }),
            new ManifestPlugin(),
        ],
        devtool: false,
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /nodemodules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: ['@babel/preset-env']
                        }
                    }
                },
            ]
        },
        resolve: {
            extensions: ['.js'],
        },
        optimization: {
            minimize: true,
        },
    }
}