# دليل API - Smart AutoParts

## نقاط النهاية الرئيسية

### 🔐 المصادقة
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/user
```

### 📦 المنتجات
```
GET    /api/products
GET    /api/products/{id}
POST   /api/products
PUT    /api/products/{id}
DELETE /api/products/{id}
GET    /api/products/search
GET    /api/products/recommendations
```

### 🛒 السلة والطلبات
```
GET    /api/cart
POST   /api/cart/add
PUT    /api/cart/update
DELETE /api/cart/remove
POST   /api/checkout
GET    /api/orders
GET    /api/orders/{id}
```

### 🏪 المتاجر
```
GET    /api/shops
GET    /api/shops/{id}
POST   /api/shops
PUT    /api/shops/{id}
GET    /api/shops/{id}/products
```

## أمثلة الاستخدام

### تسجيل مستخدم جديد
```bash
curl -X POST https://api.smartautoparts.sa/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد محمد",
    "email": "ahmad@example.com",
    "password": "password123",
    "phone": "+966501234567",
    "type": "customer"
  }'
```

### البحث عن منتجات
```bash
curl -X GET "https://api.smartautoparts.sa/api/products/search?q=فلتر&category=engine&brand=toyota"
```

### الحصول على توصيات
```bash
curl -X GET "https://api.smartautoparts.sa/api/products/recommendations" \
  -H "Authorization: Bearer {token}"
```
