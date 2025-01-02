const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  entry: {
    main: './assets/src/main.tsx'
  },
  output: {
    path: path.resolve(__dirname, 'assets/build'),
    filename: '[name].js'
  },
  resolve: {
    ...defaultConfig.resolve,
    extensions: ['.ts', '.tsx', '.js', '.jsx'],
    alias: {
      '@dashboard': path.resolve(__dirname, 'dashboard'),
      '@assets': path.resolve(__dirname, 'assets/src')
    }
  },
  module: {
    ...defaultConfig.module,
    rules: [
      ...defaultConfig.module.rules,
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
      }
    ]
  }
}; 