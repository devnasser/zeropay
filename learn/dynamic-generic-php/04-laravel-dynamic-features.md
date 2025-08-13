# Laravel Dynamic Features

## 1. Dynamic Model Relationships

### Dynamic Relationship Loading
```php
trait DynamicRelationships
{
    protected array $dynamicRelations = [];
    
    /**
     * Define a dynamic relationship at runtime
     */
    public function addDynamicRelation(string $name, \Closure $callback): void
    {
        $this->dynamicRelations[$name] = $callback;
    }
    
    /**
     * Handle dynamic relationship calls
     */
    public function __call($method, $parameters)
    {
        if (isset($this->dynamicRelations[$method])) {
            return call_user_func($this->dynamicRelations[$method], $this);
        }
        
        return parent::__call($method, $parameters);
    }
    
    /**
     * Get relationship dynamically
     */
    public function getRelationValue($key)
    {
        if (isset($this->dynamicRelations[$key])) {
            return $this->getRelationshipFromMethod($key);
        }
        
        return parent::getRelationValue($key);
    }
}

// Usage
class Product extends Model
{
    use DynamicRelationships;
    
    public function initializeDynamicRelations()
    {
        // Add relationship based on configuration
        if (config('app.features.product_variants')) {
            $this->addDynamicRelation('variants', function($product) {
                return $product->hasMany(ProductVariant::class);
            });
        }
        
        // Add conditional relationship
        $this->addDynamicRelation('activeReviews', function($product) {
            return $product->hasMany(Review::class)
                ->where('is_approved', true)
                ->where('created_at', '>', now()->subMonths(6));
        });
    }
}
```

### Polymorphic Dynamic Relations
```php
trait PolymorphicDynamicRelations
{
    /**
     * Create dynamic morphTo relationships
     */
    public function dynamicMorphTo(string $name, array $types): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        $relation = $this->morphTo($name);
        
        // Add type constraints
        $relation->constrain($types);
        
        return $relation;
    }
    
    /**
     * Dynamic morph many with conditions
     */
    public function dynamicMorphMany(string $related, string $name, array $conditions = [])
    {
        $relation = $this->morphMany($related, $name);
        
        foreach ($conditions as $field => $value) {
            if (is_callable($value)) {
                $relation->where($value);
            } else {
                $relation->where($field, $value);
            }
        }
        
        return $relation;
    }
}

// Usage
class Comment extends Model
{
    use PolymorphicDynamicRelations;
    
    public function commentable()
    {
        return $this->dynamicMorphTo('commentable', [
            Product::class,
            Article::class,
            Video::class
        ]);
    }
}

class Product extends Model
{
    use PolymorphicDynamicRelations;
    
    public function comments()
    {
        return $this->dynamicMorphMany(Comment::class, 'commentable', [
            'is_approved' => true,
            'is_spam' => false,
            function($query) {
                $query->where('created_at', '>', now()->subYear());
            }
        ]);
    }
}
```

## 2. Dynamic Scopes

### Runtime Scope Builder
```php
trait DynamicScopes
{
    protected static array $dynamicScopes = [];
    
    /**
     * Register a dynamic scope
     */
    public static function addDynamicScope(string $name, \Closure $callback): void
    {
        static::$dynamicScopes[$name] = $callback;
    }
    
    /**
     * Handle dynamic scope calls
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'scope')) {
            $scope = lcfirst(substr($method, 5));
            
            if (isset(static::$dynamicScopes[$scope])) {
                array_unshift($parameters, $this);
                return call_user_func_array(static::$dynamicScopes[$scope], $parameters);
            }
        }
        
        return parent::__call($method, $parameters);
    }
    
    /**
     * Build complex scopes from configuration
     */
    public static function buildScopesFromConfig(array $config): void
    {
        foreach ($config as $name => $conditions) {
            static::addDynamicScope($name, function($query) use ($conditions) {
                foreach ($conditions as $field => $value) {
                    if (is_array($value)) {
                        $query->whereIn($field, $value);
                    } elseif (is_callable($value)) {
                        $value($query);
                    } else {
                        $query->where($field, $value);
                    }
                }
                
                return $query;
            });
        }
    }
}

// Usage
Product::buildScopesFromConfig([
    'available' => [
        'is_active' => true,
        'quantity' => function($query) {
            $query->where('quantity', '>', 0);
        }
    ],
    'featured' => [
        'is_featured' => true,
        'rating' => function($query) {
            $query->where('rating', '>=', 4);
        }
    ]
]);

// Now you can use
$products = Product::available()->featured()->get();
```

### Conditional Scope Application
```php
class ScopeBuilder
{
    protected $query;
    protected array $conditions = [];
    
    public function __construct($query)
    {
        $this->query = $query;
    }
    
    public function when(string $scope, $condition = true): self
    {
        $this->conditions[] = [
            'scope' => $scope,
            'condition' => $condition
        ];
        
        return $this;
    }
    
    public function apply(): \Illuminate\Database\Eloquent\Builder
    {
        foreach ($this->conditions as $item) {
            if (value($item['condition'])) {
                $this->query->{$item['scope']}();
            }
        }
        
        return $this->query;
    }
}

// Extension for models
trait ConditionalScopes
{
    public function scopeWithConditions($query): ScopeBuilder
    {
        return new ScopeBuilder($query);
    }
}

// Usage
$products = Product::withConditions()
    ->when('active', auth()->check())
    ->when('inStock', request('in_stock'))
    ->when('featured', request('featured'))
    ->apply()
    ->get();
```

## 3. Dynamic Validation Rules

### Runtime Validation Builder
```php
class DynamicValidator
{
    protected array $rules = [];
    protected array $messages = [];
    protected array $attributes = [];
    
    public function field(string $field): FieldRuleBuilder
    {
        return new FieldRuleBuilder($this, $field);
    }
    
    public function addRule(string $field, $rules): self
    {
        $this->rules[$field] = $rules;
        return $this;
    }
    
    public function addMessage(string $key, string $message): self
    {
        $this->messages[$key] = $message;
        return $this;
    }
    
    public function when($condition, callable $callback): self
    {
        if (value($condition)) {
            $callback($this);
        }
        
        return $this;
    }
    
    public function merge(array $rules): self
    {
        $this->rules = array_merge($this->rules, $rules);
        return $this;
    }
    
    public function validate(array $data): array
    {
        return \Validator::make($data, $this->rules, $this->messages, $this->attributes)
            ->validate();
    }
    
    public function getRules(): array
    {
        return $this->rules;
    }
}

class FieldRuleBuilder
{
    protected DynamicValidator $validator;
    protected string $field;
    protected array $rules = [];
    
    public function __construct(DynamicValidator $validator, string $field)
    {
        $this->validator = $validator;
        $this->field = $field;
    }
    
    public function required(bool $condition = true): self
    {
        if ($condition) {
            $this->rules[] = 'required';
        }
        return $this;
    }
    
    public function string(): self
    {
        $this->rules[] = 'string';
        return $this;
    }
    
    public function email(): self
    {
        $this->rules[] = 'email';
        return $this;
    }
    
    public function min(int $value): self
    {
        $this->rules[] = "min:$value";
        return $this;
    }
    
    public function max(int $value): self
    {
        $this->rules[] = "max:$value";
        return $this;
    }
    
    public function unique(string $table, string $column = null, $except = null): self
    {
        $rule = "unique:$table";
        if ($column) $rule .= ",$column";
        if ($except) $rule .= ",$except";
        
        $this->rules[] = $rule;
        return $this;
    }
    
    public function custom($rule): self
    {
        if (is_array($rule)) {
            $this->rules = array_merge($this->rules, $rule);
        } else {
            $this->rules[] = $rule;
        }
        return $this;
    }
    
    public function message(string $message): self
    {
        $lastRule = end($this->rules);
        $this->validator->addMessage("$this->field.$lastRule", $message);
        return $this;
    }
    
    public function build(): DynamicValidator
    {
        $this->validator->addRule($this->field, $this->rules);
        return $this->validator;
    }
}

// Usage
$validator = new DynamicValidator();

$validator
    ->field('name')
        ->required()
        ->string()
        ->max(255)
        ->message('Name is too long')
        ->build()
    ->field('email')
        ->required(request('require_email'))
        ->email()
        ->unique('users', 'email', auth()->id())
        ->build()
    ->when(request('is_company'), function($v) {
        $v->field('company_name')->required()->string()->build()
          ->field('tax_number')->required()->string()->build();
    });

$validated = $validator->validate($request->all());
```

### Context-Aware Validation
```php
class ContextualValidator
{
    protected array $contexts = [];
    protected string $currentContext = 'default';
    
    public function defineContext(string $name, array $rules): self
    {
        $this->contexts[$name] = $rules;
        return $this;
    }
    
    public function useContext(string $name): self
    {
        $this->currentContext = $name;
        return $this;
    }
    
    public function getRules(): array
    {
        return $this->contexts[$this->currentContext] ?? [];
    }
    
    public function combineContexts(array $contexts): array
    {
        $rules = [];
        
        foreach ($contexts as $context) {
            if (isset($this->contexts[$context])) {
                $rules = array_merge_recursive($rules, $this->contexts[$context]);
            }
        }
        
        return $rules;
    }
}

// Usage in Form Request
class DynamicFormRequest extends FormRequest
{
    protected ContextualValidator $validator;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->validator = new ContextualValidator();
        
        $this->validator
            ->defineContext('create', [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|confirmed'
            ])
            ->defineContext('update', [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $this->user?->id,
                'password' => 'sometimes|string|min:8|confirmed'
            ])
            ->defineContext('admin', [
                'role' => 'required|in:admin,moderator,user',
                'permissions' => 'array',
                'permissions.*' => 'string|exists:permissions,name'
            ]);
    }
    
    public function rules(): array
    {
        $context = $this->route()->getActionMethod();
        
        if (auth()->user()?->isAdmin()) {
            return $this->validator->combineContexts([$context, 'admin']);
        }
        
        return $this->validator->useContext($context)->getRules();
    }
}
```

## 4. Dynamic Middleware

### Runtime Middleware Registration
```php
class DynamicMiddlewareManager
{
    protected array $middlewares = [];
    protected $router;
    
    public function __construct($router)
    {
        $this->router = $router;
    }
    
    public function register(string $name, $middleware): self
    {
        if (is_string($middleware)) {
            $this->middlewares[$name] = $middleware;
        } elseif (is_callable($middleware)) {
            $this->middlewares[$name] = new class($middleware) {
                protected $handler;
                
                public function __construct($handler)
                {
                    $this->handler = $handler;
                }
                
                public function handle($request, $next)
                {
                    return call_user_func($this->handler, $request, $next);
                }
            };
        }
        
        $this->router->aliasMiddleware($name, $this->middlewares[$name]);
        
        return $this;
    }
    
    public function group(array $middlewares, \Closure $routes): void
    {
        $this->router->middlewareGroup('dynamic', $middlewares);
        $this->router->middleware('dynamic')->group($routes);
    }
    
    public function applyToRoute(string $route, array $middlewares): void
    {
        $this->router->middleware($middlewares)->name($route);
    }
}

// Usage in service provider
$middlewareManager = new DynamicMiddlewareManager($this->app['router']);

$middlewareManager
    ->register('check-subscription', function($request, $next) {
        if (!auth()->user()?->hasActiveSubscription()) {
            return redirect()->route('subscription.expired');
        }
        return $next($request);
    })
    ->register('log-access', function($request, $next) {
        Log::info('Route accessed', [
            'user' => auth()->id(),
            'route' => $request->route()->getName(),
            'ip' => $request->ip()
        ]);
        return $next($request);
    });

// Apply to routes
$middlewareManager->group(['check-subscription', 'log-access'], function() {
    Route::get('/premium/dashboard', [PremiumController::class, 'dashboard']);
    Route::get('/premium/reports', [PremiumController::class, 'reports']);
});
```

### Conditional Middleware
```php
class ConditionalMiddleware
{
    protected array $conditions = [];
    
    public function when($condition, $middleware): self
    {
        $this->conditions[] = [
            'condition' => $condition,
            'middleware' => $middleware
        ];
        
        return $this;
    }
    
    public function handle($request, $next)
    {
        $middlewaresToRun = [];
        
        foreach ($this->conditions as $item) {
            if (value($item['condition'])) {
                $middlewaresToRun[] = $item['middleware'];
            }
        }
        
        return (new Pipeline(app()))
            ->send($request)
            ->through($middlewaresToRun)
            ->then($next);
    }
}

// Usage
Route::middleware([
    new ConditionalMiddleware()
        ->when(config('app.maintenance_mode'), MaintenanceMode::class)
        ->when(config('app.require_2fa'), TwoFactorAuth::class)
        ->when(request()->is('api/*'), 'throttle:api')
])->group(function() {
    // Routes
});
```

## 5. Macro System

### Dynamic Macros
```php
trait DynamicMacros
{
    protected static array $dynamicMacros = [];
    
    public static function addMacro(string $name, \Closure $macro): void
    {
        static::$dynamicMacros[$name] = $macro;
        static::macro($name, $macro);
    }
    
    public static function removeMacro(string $name): void
    {
        unset(static::$dynamicMacros[$name]);
        // Note: Laravel doesn't provide a way to remove macros
    }
    
    public static function hasMacro(string $name): bool
    {
        return isset(static::$dynamicMacros[$name]) || parent::hasMacro($name);
    }
    
    public static function loadMacrosFromConfig(array $config): void
    {
        foreach ($config as $name => $macro) {
            if (is_callable($macro)) {
                static::addMacro($name, $macro);
            }
        }
    }
}

// Collection Macros
Collection::macro('whereLike', function ($key, $value) {
    return $this->filter(function ($item) use ($key, $value) {
        return stripos(data_get($item, $key), $value) !== false;
    });
});

Collection::macro('whereNotLike', function ($key, $value) {
    return $this->reject(function ($item) use ($key, $value) {
        return stripos(data_get($item, $key), $value) !== false;
    });
});

// Query Builder Macros
\Illuminate\Database\Query\Builder::macro('whereLike', function ($column, $value) {
    return $this->where($column, 'LIKE', '%' . $value . '%');
});

\Illuminate\Database\Query\Builder::macro('orderByNullsLast', function ($column, $direction = 'asc') {
    $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
    return $this->orderByRaw("$column IS NULL, $column $direction");
});

// Response Macros
Response::macro('success', function ($data, $message = 'Success', $code = 200) {
    return response()->json([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], $code);
});

Response::macro('error', function ($message = 'Error', $code = 400, $errors = []) {
    return response()->json([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $code);
});
```

## 6. Dynamic Service Binding

### Runtime Service Registration
```php
class DynamicServiceProvider extends ServiceProvider
{
    protected array $services = [];
    
    public function registerDynamic(string $abstract, $concrete = null, bool $singleton = false): void
    {
        $this->services[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => $singleton
        ];
        
        if ($singleton) {
            $this->app->singleton($abstract, $concrete);
        } else {
            $this->app->bind($abstract, $concrete);
        }
    }
    
    public function registerFromConfig(array $config): void
    {
        foreach ($config as $abstract => $settings) {
            $concrete = $settings['concrete'] ?? $abstract;
            $singleton = $settings['singleton'] ?? false;
            
            $this->registerDynamic($abstract, $concrete, $singleton);
        }
    }
    
    public function extendService(string $abstract, \Closure $extend): void
    {
        $this->app->extend($abstract, $extend);
    }
    
    public function replaceService(string $abstract, $concrete): void
    {
        // Unbind if exists
        unset($this->app->bindings[$abstract]);
        unset($this->app->instances[$abstract]);
        
        // Rebind
        $this->app->bind($abstract, $concrete);
    }
}

// Usage
$provider = new DynamicServiceProvider($app);

// Register services based on environment
if (app()->environment('production')) {
    $provider->registerDynamic(PaymentGateway::class, StripeGateway::class, true);
} else {
    $provider->registerDynamic(PaymentGateway::class, MockGateway::class, true);
}

// Register from configuration
$provider->registerFromConfig([
    CacheInterface::class => [
        'concrete' => config('cache.default') === 'redis' ? RedisCache::class : FileCache::class,
        'singleton' => true
    ],
    SearchInterface::class => [
        'concrete' => config('search.driver') === 'elasticsearch' ? ElasticSearch::class : DatabaseSearch::class,
        'singleton' => false
    ]
]);

// Extend existing service
$provider->extendService(UserRepository::class, function($repository, $app) {
    $repository->setCacheDriver($app->make(CacheInterface::class));
    return $repository;
});
```

## 7. Dynamic Eloquent Attributes

### Virtual Attributes
```php
trait VirtualAttributes
{
    protected array $virtualAttributes = [];
    
    public function addVirtualAttribute(string $name, $value): void
    {
        if (is_callable($value)) {
            $this->virtualAttributes[$name] = $value;
        } else {
            $this->virtualAttributes[$name] = fn() => $value;
        }
    }
    
    public function getAttribute($key)
    {
        if (isset($this->virtualAttributes[$key])) {
            return call_user_func($this->virtualAttributes[$key], $this);
        }
        
        return parent::getAttribute($key);
    }
    
    public function toArray()
    {
        $array = parent::toArray();
        
        foreach ($this->virtualAttributes as $key => $value) {
            $array[$key] = call_user_func($value, $this);
        }
        
        return $array;
    }
}

// Usage
$user = User::find(1);

$user->addVirtualAttribute('age', function($user) {
    return $user->date_of_birth->age;
});

$user->addVirtualAttribute('full_name', function($user) {
    return "{$user->first_name} {$user->last_name}";
});

$user->addVirtualAttribute('subscription_status', function($user) {
    if (!$user->subscription) return 'none';
    if ($user->subscription->isExpired()) return 'expired';
    if ($user->subscription->isTrialing()) return 'trial';
    return 'active';
});

// Now you can access
echo $user->age; // Calculated age
echo $user->full_name; // Combined name
echo $user->subscription_status; // Dynamic status
```