'use strict';

const common = require('./config');
const { merge } = require('webpack-merge');

module.exports = merge(common, {
    mode: 'development',
    devtool: 'inline-source-map',
});
