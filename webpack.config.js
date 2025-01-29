const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const TerserPlugin = require('terser-webpack-plugin');

const isDevelopment = process.env.NODE_ENV === 'development';

module.exports = {
  ...defaultConfig,
  mode: isDevelopment ? 'development' : 'production',
  devtool: isDevelopment ? 'eval-source-map' : 'source-map',
  entry: {
    app: [
      path.resolve(__dirname, './assets/src/main.tsx'),
      path.resolve(__dirname, './dashboard/styles/main.css')
    ]
  },
  output: {
    path: path.resolve(__dirname, 'assets/build'),
    filename: isDevelopment ? '[name].js' : '[name].[contenthash].js',
    chunkFilename: isDevelopment ? '[id].js' : '[id].[contenthash].js',
    publicPath: '/wp-content/themes/client-side-dashboard/assets/build/'
  },
  resolve: {
    ...defaultConfig.resolve,
    extensions: ['.ts', '.tsx', '.js', '.jsx', '.css'],
    alias: {
      '@dashboard': path.resolve(__dirname, 'dashboard'),
      '@dashboard-styles': path.resolve(__dirname, 'dashboard/styles'),
      '@styles': path.resolve(__dirname, 'dashboard/styles'),
      '@features': path.resolve(__dirname, 'features')
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
              transpileOnly: isDevelopment,
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
      filename: isDevelopment ? '[name].css' : '[name].[contenthash].css'
    }),
    new WebpackManifestPlugin({
      publicPath: ''
    })
  ],
  optimization: {
    ...defaultConfig.optimization,
    minimize: !isDevelopment,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          compress: {
            drop_console: !isDevelopment
          }
        }
      })
    ]
  }
}; 