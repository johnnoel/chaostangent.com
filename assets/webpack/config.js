'use strict';

const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const SvgSpritemapPlugin = require('svg-spritemap-webpack-plugin');

module.exports = {
    entry: path.resolve(__dirname, '../ts/main.ts'),
    output: {
        filename: '[name].[contenthash].js',
        chunkFilename: '[id].[contenthash].js',
        path: path.resolve(__dirname, '../../public/assets/'),
        publicPath: '/assets/'
    },
    resolve: {
        extensions: [ '.ts', '.js', '.json' ],
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                loader: 'ts-loader'
            },
            {
                test: /\.css$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            importLoaders: 1,
                        },
                    },
                    {
                        loader: 'postcss-loader',
                        options: {
                            postcssOptions: {
                                plugins: [
                                    'postcss-import',
                                    'postcss-preset-env',
                                ],
                            },
                        },
                    },
                ],
            },
        ],
    },
    plugins: [
        new SvgSpritemapPlugin([
            'assets/icons/**/*.svg',
        ], {
            output: {
                filename: 'icons.[contenthash].svg',
                svgo: true,
                chunk: {
                    name: 'icons',
                },
            },
            sprite: {
                prefix: 'icon-',
                generate: {
                    title: false,
                },
            },
        }),
        new CleanWebpackPlugin(),
        new MiniCssExtractPlugin({
            filename: '[name].[contenthash].css',
            chunkFilename: '[id].[contenthash].css',
            ignoreOrder: false,
        }),
        new WebpackManifestPlugin(),
    ],
};
