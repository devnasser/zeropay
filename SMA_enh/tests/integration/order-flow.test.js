const request = require('supertest');
const { apiGateway } = require('../../core/api-gateway/src/app');

describe('Order Flow Integration Test', () => {
  let authToken;
  let userId;
  let productId;
  let orderId;

  beforeAll(async () => {
    // Register user
    const registerResponse = await request(apiGateway)
      .post('/api/auth/register')
      .send({
        name: 'Test User',
        email: 'test@example.com',
        password: 'password123',
        phone: '+966501234567',
        type: 'customer'
      });

    authToken = registerResponse.body.tokens.access_token;
    userId = registerResponse.body.user.id;

    // Get a product
    const productsResponse = await request(apiGateway)
      .get('/api/products')
      .set('Authorization', `Bearer ${authToken}`);

    productId = productsResponse.body.data[0].id;
  });

  test('Complete order flow', async () => {
    // 1. Add to cart
    const cartResponse = await request(apiGateway)
      .post('/api/cart/add')
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        productId,
        quantity: 2
      });

    expect(cartResponse.status).toBe(200);
    expect(cartResponse.body.cart.items).toHaveLength(1);

    // 2. Create order
    const orderResponse = await request(apiGateway)
      .post('/api/orders')
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        shippingAddress: {
          street: '123 Main St',
          city: 'Riyadh',
          country: 'SA',
          postalCode: '11111'
        },
        paymentMethod: 'mada'
      });

    expect(orderResponse.status).toBe(201);
    expect(orderResponse.body.order).toHaveProperty('orderNumber');
    orderId = orderResponse.body.order.id;

    // 3. Process payment
    const paymentResponse = await request(apiGateway)
      .post(`/api/payments/process`)
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        orderId,
        amount: orderResponse.body.order.total,
        method: 'mada',
        cardDetails: {
          // Test card details
          number: '4111111111111111',
          expiry: '12/25',
          cvv: '123'
        }
      });

    expect(paymentResponse.status).toBe(200);
    expect(paymentResponse.body.success).toBe(true);

    // 4. Check order status
    const statusResponse = await request(apiGateway)
      .get(`/api/orders/${orderId}`)
      .set('Authorization', `Bearer ${authToken}`);

    expect(statusResponse.status).toBe(200);
    expect(statusResponse.body.order.status).toBe('confirmed');
    expect(statusResponse.body.order.paymentStatus).toBe('paid');
  });

  afterAll(async () => {
    // Cleanup
    await request(apiGateway)
      .delete(`/api/users/${userId}`)
      .set('Authorization', `Bearer ${authToken}`);
  });
});
