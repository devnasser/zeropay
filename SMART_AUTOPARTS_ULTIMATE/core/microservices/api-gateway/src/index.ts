import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import rateLimit from 'express-rate-limit';
import { createProxyMiddleware } from 'http-proxy-middleware';
import { logger } from './utils/logger';
import { authMiddleware } from './middleware/auth';
import { errorHandler } from './middleware/error';

const app = express();
const PORT = process.env.PORT || 3000;

// Security middlewares
app.use(helmet());
app.use(cors());
app.use(express.json());

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100 // limit each IP to 100 requests per windowMs
});
app.use('/api', limiter);

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'healthy', timestamp: new Date().toISOString() });
});

// Service proxies
const services = {
  '/api/auth': 'http://auth-service:3001',
  '/api/products': 'http://product-service:3002',
  '/api/orders': 'http://order-service:3003',
  '/api/payments': 'http://payment-service:3004',
  '/api/ai': 'http://ai-service:3005'
};

Object.entries(services).forEach(([path, target]) => {
  app.use(path, authMiddleware, createProxyMiddleware({
    target,
    changeOrigin: true,
    onError: (err, req, res) => {
      logger.error(`Proxy error: ${err.message}`);
      res.status(502).json({ error: 'Service unavailable' });
    }
  }));
});

// GraphQL endpoint
app.use('/graphql', authMiddleware, createProxyMiddleware({
  target: 'http://graphql-service:4000',
  changeOrigin: true,
  ws: true
}));

// Error handling
app.use(errorHandler);

app.listen(PORT, () => {
  logger.info(`⚔️ API Gateway running on port ${PORT}`);
});
