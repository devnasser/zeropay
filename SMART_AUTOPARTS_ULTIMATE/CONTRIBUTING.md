# 🤝 دليل المساهمة - Contributing Guide

شكراً لاهتمامك بالمساهمة في Smart AutoParts Ultimate! 

## 📋 قبل البدء

### مدونة السلوك
يرجى قراءة [مدونة السلوك](CODE_OF_CONDUCT.md) الخاصة بنا. نتوقع من جميع المساهمين الالتزام بها.

### المتطلبات
- Git
- Docker & Docker Compose
- محرر كود يدعم EditorConfig
- معرفة بـ Laravel/PHP أو Node.js/TypeScript

## 🚀 كيفية المساهمة

### 1. Fork & Clone
```bash
# Fork المشروع من GitHub
# ثم clone للجهاز المحلي
git clone https://github.com/YOUR_USERNAME/smart-autoparts-ultimate.git
cd smart-autoparts-ultimate
```

### 2. إعداد البيئة
```bash
# تشغيل سكريبت الإعداد
make setup

# أو يدوياً
cp .env.example .env
docker-compose up -d
```

### 3. إنشاء فرع جديد
```bash
# للميزات الجديدة
git checkout -b feature/amazing-feature

# لإصلاح الأخطاء
git checkout -b fix/bug-description

# للتحسينات
git checkout -b enhance/enhancement-description
```

### 4. قم بالتغييرات
- اتبع معايير الكود
- أضف اختبارات لكودك
- حدث التوثيق إذا لزم الأمر

### 5. اختبر التغييرات
```bash
# تشغيل الاختبارات
make test

# تشغيل linting
make lint

# التحقق من الأمان
make security-check
```

### 6. Commit التغييرات
نستخدم [Conventional Commits](https://www.conventionalcommits.org/):

```bash
# للميزات
git commit -m "feat: add user authentication via OAuth2"

# للإصلاحات
git commit -m "fix: resolve memory leak in product search"

# للتوثيق
git commit -m "docs: update API documentation"

# للأداء
git commit -m "perf: optimize database queries"

# للتنسيق
git commit -m "style: format code according to PSR-12"

# لإعادة الهيكلة
git commit -m "refactor: extract payment logic to service"

# للاختبارات
git commit -m "test: add unit tests for cart service"

# للصيانة
git commit -m "chore: update dependencies"
```

### 7. Push & Pull Request
```bash
git push origin feature/amazing-feature
```

ثم افتح Pull Request عبر GitHub.

## 📝 معايير الكود

### PHP (Laravel)
- نتبع [PSR-12](https://www.php-fig.org/psr/psr-12/)
- استخدم Type Hints
- وثق الدوال المعقدة

```php
/**
 * Calculate product discount based on user type and quantity
 * 
 * @param Product $product
 * @param User $user
 * @param int $quantity
 * @return float
 */
public function calculateDiscount(Product $product, User $user, int $quantity): float
{
    // Implementation
}
```

### JavaScript/TypeScript
- نتبع [Airbnb Style Guide](https://github.com/airbnb/javascript)
- استخدم TypeScript للملفات الجديدة
- تجنب `any` type

```typescript
interface ProductProps {
  id: string;
  name: string;
  price: number;
  onAddToCart: (productId: string) => void;
}

export const ProductCard: React.FC<ProductProps> = ({ 
  id, 
  name, 
  price, 
  onAddToCart 
}) => {
  // Implementation
};
```

### قواعد البيانات
- استخدم Migrations للتغييرات
- لا تعدل migrations قديمة
- أضف indexes للأداء

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('sku')->unique();
    $table->string('name');
    $table->index(['name', 'sku']); // Composite index
    $table->timestamps();
});
```

## 🧪 الاختبارات

### Unit Tests
```php
public function test_user_can_add_product_to_cart()
{
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100]);
    
    $this->actingAs($user)
         ->post('/api/cart', ['product_id' => $product->id])
         ->assertStatus(201)
         ->assertJson(['total' => 100]);
}
```

### Integration Tests
```javascript
describe('Product Search', () => {
  it('should return filtered products', async () => {
    const response = await request(app)
      .get('/api/products/search')
      .query({ q: 'oil filter' })
      .expect(200);
    
    expect(response.body.data).toHaveLength(10);
    expect(response.body.data[0]).toHaveProperty('name');
  });
});
```

## 📚 التوثيق

### API Documentation
استخدم تعليقات OpenAPI/Swagger:

```php
/**
 * @OA\Get(
 *     path="/api/products",
 *     summary="Get list of products",
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     )
 * )
 */
```

### Code Documentation
- وثق الدوال المعقدة
- اشرح "لماذا" وليس فقط "ماذا"
- استخدم أمثلة عند الحاجة

## 🔄 عملية المراجعة

### ما نبحث عنه:
1. **الوظيفة**: هل يعمل الكود كما هو متوقع؟
2. **الاختبارات**: هل توجد اختبارات كافية؟
3. **الأداء**: هل هناك تأثير على الأداء؟
4. **الأمان**: هل تم اتباع أفضل ممارسات الأمان؟
5. **التوثيق**: هل التوثيق محدث؟

### وقت المراجعة
- عادة خلال 48 ساعة
- للتغييرات الكبيرة قد تستغرق أسبوع

## 🏷️ Labels

| Label | الوصف |
|-------|-------|
| `good first issue` | مناسب للمبتدئين |
| `help wanted` | نحتاج مساعدة |
| `bug` | خطأ يحتاج إصلاح |
| `enhancement` | ميزة جديدة |
| `documentation` | تحسينات التوثيق |
| `performance` | تحسينات الأداء |
| `security` | مشاكل أمنية |

## 💬 التواصل

### القنوات
- GitHub Issues للأخطاء والميزات
- GitHub Discussions للنقاشات
- Email: dev.na@outlook.com للأمور الخاصة

### اللغات
- العربية والإنجليزية مقبولتان
- الكود والتعليقات بالإنجليزية
- التوثيق يمكن أن يكون بالعربية

## 🎁 المكافآت

نقدر مساهماتك! المساهمون النشطون:
- يُضافون لقائمة المساهمين
- يحصلون على شارة خاصة
- يُدعون للمشاركة في القرارات التقنية

## ❓ الأسئلة الشائعة

### س: لا أعرف من أين أبدأ؟
ج: ابحث عن issues بـ label `good first issue`

### س: وجدت خطأ، ماذا أفعل؟
ج: افتح issue جديد مع وصف تفصيلي

### س: لدي فكرة لميزة جديدة؟
ج: افتح issue للنقاش قبل البدء بالتطوير

### س: كود المراجعة سلبي، ماذا أفعل؟
ج: لا تأخذ الأمر شخصياً، نهدف لأفضل جودة

---

شكراً لمساهمتك في جعل Smart AutoParts Ultimate أفضل! 🎉

⚔️ **نمط الأسطورة - معاً نبني الأفضل** ⚔️