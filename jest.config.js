export default {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/tests/frontend'],
  testMatch: [
    '**/__tests__/**/*.{js,jsx}',
    '**/*.{spec,test}.{js,jsx}'
  ],
  transform: {
    '^.+\\.jsx?$': 'babel-jest',
  },
  moduleNameMapper: {
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    '^@/(.*)$': '<rootDir>/resources/js/$1',
    '^@inertiajs/react$': '<rootDir>/node_modules/@inertiajs/react/dist/index.js',
  },
  setupFilesAfterEnv: ['<rootDir>/tests/frontend/setup.js'],
  collectCoverageFrom: [
    'resources/js/**/*.{js,jsx}',
    '!resources/js/**/*.d.ts',
    '!resources/js/**/*.stories.{js,jsx}',
  ],
  coveragePathIgnorePatterns: [
    '/node_modules/',
    '/vendor/',
  ],
  testPathIgnorePatterns: [
    '/node_modules/',
    '/vendor/',
    '/tests/e2e/',
  ],
};
