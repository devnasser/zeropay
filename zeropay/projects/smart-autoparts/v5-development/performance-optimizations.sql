-- Performance Optimization Indexes
-- Orders table
CREATE INDEX idx_orders_user_status_date ON orders(user_id, status, created_at);
CREATE INDEX idx_orders_shop_payment ON orders(shop_id, payment_status);
CREATE INDEX idx_orders_date_total ON orders(created_at, total);

-- Products table  
CREATE INDEX idx_products_shop_cat_active ON products(shop_id, category_id, is_active);
CREATE INDEX idx_products_price_rating ON products(price, rating) WHERE is_active = 1;
CREATE INDEX idx_products_brand_model ON products(brand, model);

-- Cart table
CREATE INDEX idx_cart_session ON carts(session_id) WHERE user_id IS NULL;
CREATE INDEX idx_cart_user_product ON carts(user_id, product_id);

-- Reviews table
CREATE INDEX idx_reviews_product_rating ON reviews(product_id, rating, created_at);
CREATE INDEX idx_reviews_verified ON reviews(is_verified, created_at);

-- Vendors table
CREATE INDEX idx_vendors_status_plan ON vendors(status, subscription_plan);
CREATE INDEX idx_vendors_sales ON vendors(total_sales DESC);
