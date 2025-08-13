# Generic Programming في PHP و Laravel

## 1. Generic Collections

### Type-Safe Collection
```php
/**
 * @template T
 */
class GenericCollection implements \IteratorAggregate, \Countable
{
    /** @var array<T> */
    private array $items = [];
    
    /** @var class-string<T> */
    private string $type;
    
    /**
     * @param class-string<T> $type
     * @param array<T> $items
     */
    public function __construct(string $type, array $items = [])
    {
        $this->type = $type;
        foreach ($items as $item) {
            $this->add($item);
        }
    }
    
    /**
     * @param T $item
     * @return self<T>
     */
    public function add($item): self
    {
        if (!$item instanceof $this->type) {
            throw new \InvalidArgumentException(
                "Item must be instance of {$this->type}"
            );
        }
        
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * @return T|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }
    
    /**
     * @param callable(T): bool $callback
     * @return self<T>
     */
    public function filter(callable $callback): self
    {
        return new self($this->type, array_filter($this->items, $callback));
    }
    
    /**
     * @template U
     * @param callable(T): U $callback
     * @return array<U>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }
    
    /**
     * @param callable(T, T): int $callback
     * @return self<T>
     */
    public function sort(callable $callback): self
    {
        $items = $this->items;
        usort($items, $callback);
        return new self($this->type, $items);
    }
    
    /**
     * @return \Iterator<T>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->items);
    }
    
    public function count(): int
    {
        return count($this->items);
    }
    
    /**
     * @return array<T>
     */
    public function toArray(): array
    {
        return $this->items;
    }
}

// Usage
/** @var GenericCollection<User> $users */
$users = new GenericCollection(User::class);
$users->add(new User(['name' => 'John']));

$activeUsers = $users->filter(fn(User $user) => $user->is_active);
$userNames = $users->map(fn(User $user) => $user->name);
```

### Generic Repository
```php
/**
 * @template T of \Illuminate\Database\Eloquent\Model
 */
abstract class GenericRepository
{
    /** @var class-string<T> */
    protected string $model;
    
    /**
     * @return T
     */
    protected function newInstance()
    {
        return new $this->model;
    }
    
    /**
     * @param array<string, mixed> $attributes
     * @return T
     */
    public function create(array $attributes)
    {
        return $this->model::create($attributes);
    }
    
    /**
     * @param int|string $id
     * @return T|null
     */
    public function find($id)
    {
        return $this->model::find($id);
    }
    
    /**
     * @param int|string $id
     * @return T
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id)
    {
        return $this->model::findOrFail($id);
    }
    
    /**
     * @param array<string, mixed> $criteria
     * @return GenericCollection<T>
     */
    public function findBy(array $criteria): GenericCollection
    {
        $query = $this->model::query();
        
        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }
        
        return new GenericCollection($this->model, $query->get()->all());
    }
    
    /**
     * @param T $model
     * @param array<string, mixed> $attributes
     * @return T
     */
    public function update($model, array $attributes)
    {
        $model->update($attributes);
        return $model->fresh();
    }
    
    /**
     * @param T $model
     * @return bool
     */
    public function delete($model): bool
    {
        return $model->delete();
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Builder<T>
     */
    public function query()
    {
        return $this->model::query();
    }
}

/**
 * @extends GenericRepository<User>
 */
class UserRepository extends GenericRepository
{
    protected string $model = User::class;
    
    /**
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model::where('email', $email)->first();
    }
}
```

## 2. Generic Data Transfer Objects

### Base DTO
```php
/**
 * @template T
 */
abstract class GenericDTO
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data = [])
    {
        $this->validate();
    }
    
    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(): array;
    
    protected function validate(): void
    {
        $validator = \Validator::make($this->data, $this->rules());
        
        if ($validator->fails()) {
            throw new \InvalidArgumentException(
                $validator->errors()->first()
            );
        }
    }
    
    /**
     * @template K of key-of<T>
     * @param K $key
     * @return T[K]|null
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }
    
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
    
    /**
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static($data);
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return static
     */
    public static function fromRequest(\Illuminate\Http\Request $request): static
    {
        return new static($request->validated());
    }
}

class CreateUserDTO extends GenericDTO
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8'
        ];
    }
    
    public function getName(): string
    {
        return $this->get('name');
    }
    
    public function getEmail(): string
    {
        return $this->get('email');
    }
    
    public function getPassword(): string
    {
        return $this->get('password');
    }
}
```

### Generic Response
```php
/**
 * @template T
 */
class GenericResponse
{
    /**
     * @param T $data
     * @param int $status
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private mixed $data,
        private int $status = 200,
        private array $meta = []
    ) {}
    
    /**
     * @return T
     */
    public function getData()
    {
        return $this->data;
    }
    
    public function getStatus(): int
    {
        return $this->status;
    }
    
    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
    
    /**
     * @param array<string, mixed> $meta
     * @return self<T>
     */
    public function withMeta(array $meta): self
    {
        return new self($this->data, $this->status, array_merge($this->meta, $meta));
    }
    
    /**
     * @param int $status
     * @return self<T>
     */
    public function withStatus(int $status): self
    {
        return new self($this->data, $status, $this->meta);
    }
    
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function toJsonResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'data' => $this->data,
            'meta' => $this->meta
        ], $this->status);
    }
    
    /**
     * @template U
     * @param T $data
     * @return self<T>
     */
    public static function success($data, array $meta = []): self
    {
        return new self($data, 200, $meta);
    }
    
    /**
     * @param string $message
     * @return self<array{message: string}>
     */
    public static function error(string $message, int $status = 400): self
    {
        return new self(['message' => $message], $status);
    }
}
```

## 3. Generic Service Layer

### Base Service
```php
/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TRepository of GenericRepository<TModel>
 */
abstract class GenericService
{
    /**
     * @param TRepository $repository
     */
    public function __construct(protected GenericRepository $repository)
    {
    }
    
    /**
     * @param array<string, mixed> $data
     * @return GenericResponse<TModel>
     */
    public function create(array $data): GenericResponse
    {
        try {
            $model = $this->repository->create($this->prepareData($data));
            return GenericResponse::success($model);
        } catch (\Exception $e) {
            return GenericResponse::error($e->getMessage(), 422);
        }
    }
    
    /**
     * @param int|string $id
     * @return GenericResponse<TModel>
     */
    public function find($id): GenericResponse
    {
        $model = $this->repository->find($id);
        
        if (!$model) {
            return GenericResponse::error('Resource not found', 404);
        }
        
        return GenericResponse::success($model);
    }
    
    /**
     * @param array<string, mixed> $criteria
     * @return GenericResponse<GenericCollection<TModel>>
     */
    public function search(array $criteria): GenericResponse
    {
        $results = $this->repository->findBy($criteria);
        return GenericResponse::success($results);
    }
    
    /**
     * @param int|string $id
     * @param array<string, mixed> $data
     * @return GenericResponse<TModel>
     */
    public function update($id, array $data): GenericResponse
    {
        $model = $this->repository->find($id);
        
        if (!$model) {
            return GenericResponse::error('Resource not found', 404);
        }
        
        try {
            $updated = $this->repository->update($model, $this->prepareData($data));
            return GenericResponse::success($updated);
        } catch (\Exception $e) {
            return GenericResponse::error($e->getMessage(), 422);
        }
    }
    
    /**
     * @param int|string $id
     * @return GenericResponse<array{message: string}>
     */
    public function delete($id): GenericResponse
    {
        $model = $this->repository->find($id);
        
        if (!$model) {
            return GenericResponse::error('Resource not found', 404);
        }
        
        if ($this->repository->delete($model)) {
            return GenericResponse::success(['message' => 'Resource deleted successfully']);
        }
        
        return GenericResponse::error('Failed to delete resource', 500);
    }
    
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function prepareData(array $data): array
    {
        return $data;
    }
}

/**
 * @extends GenericService<User, UserRepository>
 */
class UserService extends GenericService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
    
    protected function prepareData(array $data): array
    {
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        return $data;
    }
    
    /**
     * @param string $email
     * @return GenericResponse<User>
     */
    public function findByEmail(string $email): GenericResponse
    {
        $user = $this->repository->findByEmail($email);
        
        if (!$user) {
            return GenericResponse::error('User not found', 404);
        }
        
        return GenericResponse::success($user);
    }
}
```

## 4. Generic Traits

### Filterable Trait
```php
/**
 * @template T
 */
trait Filterable
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<T> $query
     * @param array<string, mixed> $filters
     * @return \Illuminate\Database\Eloquent\Builder<T>
     */
    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if (method_exists($this, $method = 'filter' . Str::studly($key))) {
                $query = $this->$method($query, $value);
            } elseif ($this->isFillable($key)) {
                $query->where($key, $value);
            }
        }
        
        return $query;
    }
    
    /**
     * @param \Illuminate\Database\Eloquent\Builder<T> $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder<T>
     */
    protected function filterSearch($query, string $value)
    {
        $searchable = $this->searchable ?? ['name'];
        
        return $query->where(function ($q) use ($value, $searchable) {
            foreach ($searchable as $field) {
                $q->orWhere($field, 'LIKE', "%{$value}%");
            }
        });
    }
    
    /**
     * @param \Illuminate\Database\Eloquent\Builder<T> $query
     * @param array<string, string> $value
     * @return \Illuminate\Database\Eloquent\Builder<T>
     */
    protected function filterDateRange($query, array $value)
    {
        if (isset($value['from'])) {
            $query->whereDate('created_at', '>=', $value['from']);
        }
        
        if (isset($value['to'])) {
            $query->whereDate('created_at', '<=', $value['to']);
        }
        
        return $query;
    }
}
```

### Cacheable Trait
```php
/**
 * @template T
 */
trait Cacheable
{
    /**
     * @return string
     */
    protected function getCacheKey(): string
    {
        return sprintf('%s:%s', $this->getTable(), $this->getKey());
    }
    
    /**
     * @return int
     */
    protected function getCacheTTL(): int
    {
        return property_exists($this, 'cacheTTL') ? $this->cacheTTL : 3600;
    }
    
    /**
     * @template TValue
     * @param string $key
     * @param callable(): TValue $callback
     * @return TValue
     */
    protected function rememberCache(string $key, callable $callback)
    {
        $cacheKey = $this->getCacheKey() . ':' . $key;
        
        return Cache::remember($cacheKey, $this->getCacheTTL(), $callback);
    }
    
    /**
     * @return void
     */
    protected function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
    }
    
    protected static function bootCacheable(): void
    {
        static::saved(function ($model) {
            $model->clearCache();
        });
        
        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
```

## 5. Generic Validators

### Base Validator
```php
/**
 * @template T
 */
abstract class GenericValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
    }
    
    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(): array;
    
    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }
    
    /**
     * @return array<string, string>
     */
    protected function attributes(): array
    {
        return [];
    }
    
    /**
     * @throws \Illuminate\Validation\ValidationException
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        $validator = \Validator::make(
            $this->data,
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );
        
        return $validator->validate();
    }
    
    /**
     * @return bool
     */
    public function isValid(): bool
    {
        $validator = \Validator::make($this->data, $this->rules());
        return !$validator->fails();
    }
    
    /**
     * @return array<string, array<string>>
     */
    public function errors(): array
    {
        $validator = \Validator::make($this->data, $this->rules());
        $validator->fails();
        
        return $validator->errors()->toArray();
    }
    
    /**
     * @param array<string, mixed> $data
     * @return static
     */
    public static function make(array $data): static
    {
        return new static($data);
    }
}

class CreateProductValidator extends GenericValidator
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0'
        ];
    }
    
    protected function messages(): array
    {
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'price.required' => 'سعر المنتج مطلوب',
            'price.min' => 'السعر يجب أن يكون أكبر من صفر'
        ];
    }
}
```

## 6. أمثلة متقدمة

### Generic Pipeline
```php
/**
 * @template TPassable
 * @template TResult
 */
class GenericPipeline
{
    /** @var TPassable */
    private $passable;
    
    /** @var array<callable(TPassable): TPassable> */
    private array $pipes = [];
    
    /**
     * @param TPassable $passable
     * @return self<TPassable, TResult>
     */
    public function send($passable): self
    {
        $this->passable = $passable;
        return $this;
    }
    
    /**
     * @param array<callable(TPassable): TPassable> $pipes
     * @return self<TPassable, TResult>
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }
    
    /**
     * @param callable(TPassable): TResult $destination
     * @return TResult
     */
    public function then(callable $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );
        
        return $pipeline($this->passable);
    }
    
    /**
     * @return callable
     */
    private function carry(): callable
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                return $pipe($passable, $stack);
            };
        };
    }
}

// Usage
$result = (new GenericPipeline())
    ->send($userData)
    ->through([
        ValidateUserData::class,
        NormalizeUserData::class,
        HashPassword::class,
    ])
    ->then(fn($data) => User::create($data));
```

### Generic Event System
```php
/**
 * @template TEvent
 */
class GenericEventDispatcher
{
    /** @var array<class-string<TEvent>, array<callable(TEvent): void>> */
    private array $listeners = [];
    
    /**
     * @param class-string<TEvent> $event
     * @param callable(TEvent): void $listener
     * @return void
     */
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }
    
    /**
     * @param TEvent $event
     * @return void
     */
    public function dispatch($event): void
    {
        $eventClass = get_class($event);
        
        if (!isset($this->listeners[$eventClass])) {
            return;
        }
        
        foreach ($this->listeners[$eventClass] as $listener) {
            $listener($event);
        }
    }
    
    /**
     * @param class-string<TEvent> $event
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && count($this->listeners[$event]) > 0;
    }
}

// Event classes
class UserCreated
{
    public function __construct(public User $user) {}
}

class OrderPlaced
{
    public function __construct(public Order $order) {}
}

// Usage
$dispatcher = new GenericEventDispatcher();

$dispatcher->listen(UserCreated::class, function (UserCreated $event) {
    Mail::to($event->user)->send(new WelcomeMail());
});

$dispatcher->listen(OrderPlaced::class, function (OrderPlaced $event) {
    $event->order->shop->notify(new NewOrderNotification($event->order));
});

$dispatcher->dispatch(new UserCreated($user));
$dispatcher->dispatch(new OrderPlaced($order));
```