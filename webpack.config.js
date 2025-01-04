const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

module.exports = {
  ...defaultConfig,
  entry: {
    app: ['./assets/src/main.tsx', './dashboard/styles/main.css']
  },
  output: {
    path: path.resolve(__dirname, 'assets/build'),
    filename: '[name].[contenthash].js',
    chunkFilename: '[id].[contenthash].js',
    publicPath: '/wp-content/themes/athlete-dashboard-child/assets/build/'
  },
  resolve: {
    ...defaultConfig.resolve,
    extensions: ['.ts', '.tsx', '.js', '.jsx', '.css'],
    alias: {
      '@dashboard-styles': path.resolve(__dirname, 'dashboard/styles'),
      '@styles': path.resolve(__dirname, 'dashboard/styles')
    }
  },
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: [
          {
            loader: 'ts-loader',
            options: {
              transpileOnly: true,
              compilerOptions: {
                jsx: 'react-jsx'
              }
            }
          }
        ],
        exclude: /node_modules/
      },
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              importLoaders: 1
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  ['postcss-import', {
                    path: [
                      path.resolve(__dirname, 'dashboard/styles'),
                      path.resolve(__dirname, 'features')
                    ]
                  }],
                  ['postcss-preset-env', {
                    stage: 3,
                    features: {
                      'nesting-rules': true
                    }
                  }]
                ]
              }
            }
          }
        ]
      }
    ]
  },
  plugins: [
    ...defaultConfig.plugins,
    new MiniCssExtractPlugin({
      filename: 'app.[contenthash].css'
    }),
    new WebpackManifestPlugin({
      publicPath: ''
    })
  ],
  optimization: {
    ...defaultConfig.optimization,
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        defaultVendors: {
          test: /[\\/]node_modules[\\/]/,
          priority: -10,
          reuseExistingChunk: true
        }
      }
    }
  }
}; 