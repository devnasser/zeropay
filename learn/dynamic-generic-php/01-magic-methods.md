# Magic Methods في PHP و Laravel

## 1. Magic Methods الأساسية

### __construct() و __destruct()
```php
class DynamicModel {
    private array $attributes = [];
    
    public function __construct(array $attributes = []) {
        $this->attributes = $attributes;
    }
    
    public function __destruct() {
        // تنظيف الموارد
    }
}
```

### __get() و __set()
```php
class DynamicEntity {
    private array $data = [];
    private array $computed = [];
    
    public function __get(string $name) {
        // Check computed properties first
        if (isset($this->computed[$name])) {
            return $this->computed[$name]();
        }
        
        // Check data array
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        
        // Check for getter method
        $getter = 'get' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        
        throw new \Exception("Property {$name} does not exist");
    }
    
    public function __set(string $name, $value): void {
        // Check for setter method
        $setter = 'set' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }
        
        $this->data[$name] = $value;
    }
}
```

### __call() و __callStatic()
```php
class DynamicRepository {
    public function __call(string $method, array $arguments) {
        // Dynamic finder methods
        if (str_starts_with($method, 'findBy')) {
            $field = Str::snake(substr($method, 6));
            return $this->findByField($field, $arguments[0] ?? null);
        }
        
        // Dynamic scopes
        if (str_starts_with($method, 'where')) {
            $field = Str::snake(substr($method, 5));
            return $this->where($field, $arguments[0] ?? null);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist");
    }
    
    public static function __callStatic(string $method, array $arguments) {
        return (new static)->$method(...$arguments);
    }
}
```

### __isset() و __unset()
```php
class DynamicConfig {
    private array $config = [];
    
    public function __isset(string $name): bool {
        return isset($this->config[$name]) || 
               method_exists($this, 'get' . Str::studly($name));
    }
    
    public function __unset(string $name): void {
        unset($this->config[$name]);
    }
}
```

### __toString() و __invoke()
```php
class DynamicResponse {
    private $content;
    
    public function __toString(): string {
        return json_encode($this->content);
    }
    
    public function __invoke(...$args) {
        return response()->json($this->content, ...$args);
    }
}
```

### __clone() و __sleep() و __wakeup()
```php
class DynamicModel {
    private $connection;
    private array $data = [];
    
    public function __clone() {
        // Deep copy للبيانات
        $this->data = array_map(function($item) {
            return is_object($item) ? clone $item : $item;
        }, $this->data);
    }
    
    public function __sleep(): array {
        // حفظ البيانات فقط، ليس الاتصال
        return ['data'];
    }
    
    public function __wakeup(): void {
        // إعادة إنشاء الاتصال
        $this->connection = DB::connection();
    }
}
```

## 2. تطبيقات Laravel المتقدمة

### Dynamic Eloquent Attributes
```php
trait DynamicAttributes {
    protected array $dynamicAttributes = [];
    
    public function addDynamicAttribute(string $name, \Closure $callback): void {
        $this->dynamicAttributes[$name] = $callback;
    }
    
    public function __get($key) {
        if (isset($this->dynamicAttributes[$key])) {
            return call_user_func($this->dynamicAttributes[$key], $this);
        }
        
        return parent::__get($key);
    }
}

// الاستخدام
$user->addDynamicAttribute('full_address', function($user) {
    return "{$user->address}, {$user->city}, {$user->country}";
});
```

### Magic Query Builder
```php
class MagicQueryBuilder {
    private $query;
    
    public function __construct($model) {
        $this->query = $model::query();
    }
    
    public function __call($method, $arguments) {
        // تحويل الأسماء الوصفية إلى استعلامات
        $patterns = [
            '/^findAll(.+)By(.+)$/' => function($matches, $args) {
                $model = Str::singular($matches[1]);
                $field = Str::snake($matches[2]);
                return $this->query->where($field, $args[0])->get();
            },
            '/^count(.+)Where(.+)Equals(.+)$/' => function($matches, $args) {
                $field = Str::snake($matches[2]);
                $value = $matches[3];
                return $this->query->where($field, $value)->count();
            }
        ];
        
        foreach ($patterns as $pattern => $handler) {
            if (preg_match($pattern, $method, $matches)) {
                return $handler($matches, $arguments);
            }
        }
        
        return $this->query->$method(...$arguments);
    }
}
```

## 3. أفضل الممارسات

### 1. التوثيق الواضح
```php
/**
 * @method static User findByEmail(string $email)
 * @method static Collection whereActive()
 * @property-read string $full_name
 */
class User extends Model {
    // ...
}
```

### 2. التحقق من الأداء
```php
public function __get($name) {
    // استخدام cache للخصائص المحسوبة الثقيلة
    return Cache::remember("model.{$this->id}.{$name}", 300, function() use ($name) {
        return $this->computeAttribute($name);
    });
}
```

### 3. معالجة الأخطاء
```php
public function __call($method, $arguments) {
    try {
        return $this->handleDynamicMethod($method, $arguments);
    } catch (\Exception $e) {
        Log::error("Dynamic method call failed", [
            'method' => $method,
            'arguments' => $arguments,
            'error' => $e->getMessage()
        ]);
        throw new \BadMethodCallException(
            "Method {$method} does not exist or failed to execute"
        );
    }
}
```

## 4. أمثلة متقدمة

### Dynamic Form Builder
```php
class DynamicForm {
    private array $fields = [];
    
    public function __call($method, $arguments) {
        // text(), email(), password(), etc.
        if (in_array($method, ['text', 'email', 'password', 'textarea'])) {
            $name = $arguments[0];
            $options = $arguments[1] ?? [];
            
            $this->fields[] = [
                'type' => $method,
                'name' => $name,
                'options' => $options
            ];
            
            return $this;
        }
        
        throw new \BadMethodCallException("Unknown field type: {$method}");
    }
    
    public function __toString(): string {
        return view('forms.dynamic', ['fields' => $this->fields])->render();
    }
}

// الاستخدام
$form = (new DynamicForm)
    ->text('name', ['required' => true])
    ->email('email')
    ->password('password')
    ->textarea('bio', ['rows' => 5]);
```

### Dynamic API Client
```php
class DynamicApiClient {
    private string $baseUrl;
    private array $endpoints = [];
    
    public function __get($service) {
        return new class($this->baseUrl, $service) {
            private $baseUrl;
            private $service;
            
            public function __construct($baseUrl, $service) {
                $this->baseUrl = $baseUrl;
                $this->service = $service;
            }
            
            public function __call($method, $arguments) {
                $httpMethod = 'GET';
                $action = $method;
                
                if (str_starts_with($method, 'create')) {
                    $httpMethod = 'POST';
                } elseif (str_starts_with($method, 'update')) {
                    $httpMethod = 'PUT';
                } elseif (str_starts_with($method, 'delete')) {
                    $httpMethod = 'DELETE';
                }
                
                $endpoint = "{$this->baseUrl}/{$this->service}/{$action}";
                
                return Http::$httpMethod($endpoint, $arguments[0] ?? []);
            }
        };
    }
}

// الاستخدام
$api = new DynamicApiClient('https://api.example.com');
$api->users->create(['name' => 'John']);
$api->products->getAll();
$api->orders->updateStatus(['status' => 'shipped']);
```