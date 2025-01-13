module.exports = {
	testEnvironment: 'jsdom',
	setupFilesAfterEnv: ['<rootDir>/jest.setup.js'],
	moduleNameMapper: {
		'\\.(css|less|scss|sass)$': 'identity-obj-proxy',
		'^@/(.*)$': '<rootDir>/$1'
	},
	transform: {
		'^.+\\.(ts|tsx)$': ['ts-jest', {
			tsconfig: 'tsconfig.json'
		}]
	},
	testMatch: [
		'**/__tests__/**/*.(test|spec).(ts|tsx)'
	],
	moduleFileExtensions: ['ts', 'tsx', 'js', 'jsx', 'json', 'node'],
	testEnvironmentOptions: {
		url: 'http://localhost'
	},
	globals: {
		'ts-jest': {
			isolatedModules: true
		}
	},
	verbose: true
}; 