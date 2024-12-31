const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  entry: {
    main: './assets/src/main.tsx'
  },
  resolve: {
    ...defaultConfig.resolve,
    alias: {
      '@dashboard': path.resolve(__dirname, 'dashboard'),
      '@assets': path.resolve(__dirname, 'assets/src')
    }
  }
}; 