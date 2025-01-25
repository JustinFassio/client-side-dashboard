module.exports = {
	preset: 'ts-jest',
	testEnvironment: 'jsdom',
	setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],
	moduleNameMapper: {
		'\\.(css|less|scss|sass)$': 'identity-obj-proxy',
		'\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$': '<rootDir>/__mocks__/fileMock.js',
		'^msw/node$': '<rootDir>/node_modules/msw/lib/node/index.js',
		'^msw/browser$': '<rootDir>/node_modules/msw/lib/browser/index.js'
	},
	transform: {
		'^.+\\.tsx?$': ['ts-jest', {
			tsconfig: {
				jsx: 'react'
			}
		}]
	},
	testPathIgnorePatterns: ['/node_modules/', '/dist/'],
	globals: {
		'ts-jest': {
			isolatedModules: true
		}
	}
}; 