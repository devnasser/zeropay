import { describe, it, expect, beforeEach, afterEach } from '@jest/globals';
import { AuthService } from '../src/services/AuthService';
import { UserRepository } from '../src/repositories/UserRepository';
import { JWTService } from '../src/services/JWTService';

jest.mock('../src/repositories/UserRepository');
jest.mock('../src/services/JWTService');

describe('AuthService', () => {
  let authService: AuthService;
  let userRepository: jest.Mocked<UserRepository>;
  let jwtService: jest.Mocked<JWTService>;

  beforeEach(() => {
    userRepository = new UserRepository() as jest.Mocked<UserRepository>;
    jwtService = new JWTService() as jest.Mocked<JWTService>;
    authService = new AuthService(userRepository, jwtService);
  });

  describe('register', () => {
    it('should register a new user successfully', async () => {
      const userData = {
        name: 'أحمد محمد',
        email: 'ahmad@example.com',
        password: 'securePassword123',
        phone: '+966501234567',
        type: 'customer',
      };

      userRepository.findByEmail.mockResolvedValue(null);
      userRepository.create.mockResolvedValue({
        id: '123',
        ...userData,
        password: 'hashedPassword',
      });

      jwtService.generateAccessToken.mockReturnValue('accessToken');
      jwtService.generateRefreshToken.mockReturnValue('refreshToken');

      const result = await authService.register(userData);

      expect(result).toHaveProperty('user');
      expect(result).toHaveProperty('tokens');
      expect(result.user.email).toBe(userData.email);
      expect(userRepository.create).toHaveBeenCalledWith(
        expect.objectContaining({
          ...userData,
          password: expect.any(String),
        })
      );
    });

    it('should throw error if email already exists', async () => {
      userRepository.findByEmail.mockResolvedValue({ id: '123' });

      await expect(
        authService.register({
          name: 'Test',
          email: 'existing@example.com',
          password: 'password',
          phone: '+966501234567',
          type: 'customer',
        })
      ).rejects.toThrow('Email already exists');
    });
  });

  describe('login', () => {
    it('should login user with valid credentials', async () => {
      const user = {
        id: '123',
        email: 'user@example.com',
        password: 'hashedPassword',
        two_factor_enabled: false,
      };

      userRepository.findByEmail.mockResolvedValue(user);
      // Mock password verification
      jest.spyOn(authService as any, 'verifyPassword').mockResolvedValue(true);

      jwtService.generateAccessToken.mockReturnValue('accessToken');
      jwtService.generateRefreshToken.mockReturnValue('refreshToken');

      const result = await authService.login('user@example.com', 'password');

      expect(result).toHaveProperty('user');
      expect(result).toHaveProperty('tokens');
    });

    it('should require 2FA if enabled', async () => {
      const user = {
        id: '123',
        email: 'user@example.com',
        password: 'hashedPassword',
        two_factor_enabled: true,
      };

      userRepository.findByEmail.mockResolvedValue(user);
      jest.spyOn(authService as any, 'verifyPassword').mockResolvedValue(true);

      const result = await authService.login('user@example.com', 'password');

      expect(result).toHaveProperty('requires_2fa', true);
      expect(result).toHaveProperty('user_id', '123');
      expect(result).not.toHaveProperty('tokens');
    });
  });
});
