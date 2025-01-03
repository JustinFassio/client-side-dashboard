const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Log resolved paths for verification
console.log('Alias paths:', {
  styles: path.resolve(__dirname, 'dashboard/styles'),
  dashboard: path.resolve(__dirname, 'dashboard'),
  assets: path.resolve(__dirname, 'assets/src')
});

module.exports = {
  ...defaultConfig,
  entry: {
    main: './assets/src/main.tsx'
  },
  output: {
    path: path.resolve(__dirname, 'assets/build'),
    filename: '[name].js',
    chunkFilename: '[name].js',
    publicPath: '/wp-content/themes/athlete-dashboard-child/assets/build/'
  },
  resolve: {
    ...defaultConfig.resolve,
    extensions: ['.ts', '.tsx', '.js', '.jsx', '.css'],
    alias: {
      '@dashboard': path.resolve(__dirname, 'dashboard'),
      '@assets': path.resolve(__dirname, 'assets/src'),
      '@styles': path.resolve(__dirname, 'dashboard/styles')
    }
  },
  module: {
    ...defaultConfig.module,
    rules: [
      ...defaultConfig.module.rules.filter(rule => !rule.test?.toString().includes('.css')),
      {
        test: /\.tsx?$/,
        use: [
          {
            loader: 'ts-loader',
            options: {
              transpileOnly: true,
              configFile: path.resolve(__dirname, 'tsconfig.json')
            }
          }
        ],
        exclude: /node_modules/
      },
      {
        test: /\.css$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              publicPath: '../'
            }
          },
          {
            loader: 'css-loader',
            options: {
              importLoaders: 1,
              url: false,
              import: true,
              modules: {
                auto: true,
                localIdentName: '[name]__[local]--[hash:base64:5]'
              }
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  ['postcss-import', {
                    path: [
                      path.resolve(__dirname),
                      path.resolve(__dirname, 'dashboard/styles'),
                      path.resolve(__dirname, 'features'),
                      path.resolve(__dirname, 'dashboard/components')
                    ],
                    resolve: (id) => {
                      // Handle @styles alias
                      if (id.startsWith('@styles/')) {
                        return path.resolve(__dirname, 'dashboard/styles', id.slice(8));
                      }
                      // Handle @dashboard alias
                      if (id.startsWith('@dashboard/')) {
                        return path.resolve(__dirname, 'dashboard', id.slice(10));
                      }
                      return id;
                    }
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
      filename: '[name].css',
      chunkFilename: '[name].css',
      ignoreOrder: true
    })
  ],
  optimization: {
    ...defaultConfig.optimization,
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        styles: {
          name: 'styles',
          test: /\.css$/,
          chunks: 'all',
          enforce: true
        }
      }
    }
  }
}; 