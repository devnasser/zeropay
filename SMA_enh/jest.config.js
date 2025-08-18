module.exports = {
  projects: [
    {
      displayName: 'api-gateway',
      testMatch: ['<rootDir>/core/api-gateway/**/*.test.{js,ts}'],
      testEnvironment: 'node',
    },
    {
      displayName: 'microservices',
      testMatch: ['<rootDir>/microservices/**/*.test.{js,ts,php}'],
      testEnvironment: 'node',
    },
    {
      displayName: 'web-app',
      testMatch: ['<rootDir>/apps/web/**/*.test.{js,ts,tsx}'],
      testEnvironment: 'jsdom',
    },
    {
      displayName: 'mobile-app',
      testMatch: ['<rootDir>/apps/mobile/**/*.test.{js,ts,tsx}'],
      preset: 'jest-expo',
    },
  ],
  coverageThreshold: {
    global: {
      branches: 80,
      functions: 80,
      lines: 80,
      statements: 80,
    },
  },
};
