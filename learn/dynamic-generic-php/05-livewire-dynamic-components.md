# Livewire Dynamic Components

## 1. Dynamic Component Loading

### Component Factory
```php
namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;

class DynamicComponentFactory extends Component
{
    public string $componentType;
    public array $componentData = [];
    protected array $componentMap = [
        'form' => Forms\DynamicForm::class,
        'table' => Tables\DynamicTable::class,
        'chart' => Charts\DynamicChart::class,
        'widget' => Widgets\DynamicWidget::class,
    ];
    
    public function mount(string $type, array $data = [])
    {
        $this->componentType = $type;
        $this->componentData = $data;
    }
    
    public function render()
    {
        $componentClass = $this->componentMap[$this->componentType] ?? null;
        
        if (!$componentClass) {
            throw new \Exception("Unknown component type: {$this->componentType}");
        }
        
        return view('livewire.dynamic-component-factory', [
            'componentClass' => $componentClass
        ]);
    }
}

// View: livewire/dynamic-component-factory.blade.php
<div>
    @livewire($componentClass, $componentData, key($componentType . '-' . uniqid()))
</div>

// Usage
<livewire:dynamic-component-factory 
    type="form" 
    :data="['fields' => $formFields]" 
/>
```

### Dynamic Form Component
```php
namespace App\Livewire\Forms;

use Livewire\Component;

class DynamicForm extends Component
{
    public array $fields = [];
    public array $values = [];
    public array $rules = [];
    public array $messages = [];
    
    public function mount(array $fields = [])
    {
        $this->fields = $fields;
        $this->buildRules();
        $this->initializeValues();
    }
    
    protected function buildRules()
    {
        foreach ($this->fields as $field) {
            if (isset($field['rules'])) {
                $this->rules["values.{$field['name']}"] = $field['rules'];
            }
            
            if (isset($field['messages'])) {
                foreach ($field['messages'] as $rule => $message) {
                    $this->messages["values.{$field['name']}.{$rule}"] = $message;
                }
            }
        }
    }
    
    protected function initializeValues()
    {
        foreach ($this->fields as $field) {
            $this->values[$field['name']] = $field['default'] ?? null;
        }
    }
    
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        
        // Emit events for field changes
        if (str_starts_with($propertyName, 'values.')) {
            $fieldName = str_replace('values.', '', $propertyName);
            $this->dispatch('field-updated', field: $fieldName, value: data_get($this->values, $fieldName));
        }
    }
    
    public function addField(array $field)
    {
        $this->fields[] = $field;
        $this->values[$field['name']] = $field['default'] ?? null;
        
        if (isset($field['rules'])) {
            $this->rules["values.{$field['name']}"] = $field['rules'];
        }
    }
    
    public function removeField(string $name)
    {
        $this->fields = array_filter($this->fields, fn($field) => $field['name'] !== $name);
        unset($this->values[$name]);
        unset($this->rules["values.{$name}"]);
    }
    
    public function submit()
    {
        $validated = $this->validate();
        
        $this->dispatch('form-submitted', data: $validated['values']);
    }
    
    public function render()
    {
        return view('livewire.forms.dynamic-form');
    }
}

// View: livewire/forms/dynamic-form.blade.php
<form wire:submit="submit">
    @foreach($fields as $field)
        <div class="mb-4">
            @switch($field['type'])
                @case('text')
                @case('email')
                @case('password')
                    <label for="{{ $field['name'] }}" class="block mb-2">
                        {{ $field['label'] ?? $field['name'] }}
                    </label>
                    <input 
                        type="{{ $field['type'] }}" 
                        id="{{ $field['name'] }}"
                        wire:model.live="values.{{ $field['name'] }}"
                        class="w-full px-3 py-2 border rounded"
                        {{ $field['attributes'] ?? '' }}
                    >
                    @break
                    
                @case('select')
                    <label for="{{ $field['name'] }}" class="block mb-2">
                        {{ $field['label'] ?? $field['name'] }}
                    </label>
                    <select 
                        id="{{ $field['name'] }}"
                        wire:model.live="values.{{ $field['name'] }}"
                        class="w-full px-3 py-2 border rounded"
                    >
                        <option value="">Select...</option>
                        @foreach($field['options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @break
                    
                @case('textarea')
                    <label for="{{ $field['name'] }}" class="block mb-2">
                        {{ $field['label'] ?? $field['name'] }}
                    </label>
                    <textarea 
                        id="{{ $field['name'] }}"
                        wire:model.live="values.{{ $field['name'] }}"
                        class="w-full px-3 py-2 border rounded"
                        rows="{{ $field['rows'] ?? 3 }}"
                    ></textarea>
                    @break
                    
                @case('checkbox')
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model.live="values.{{ $field['name'] }}"
                            class="mr-2"
                        >
                        {{ $field['label'] ?? $field['name'] }}
                    </label>
                    @break
            @endswitch
            
            @error("values.{$field['name']}")
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
    @endforeach
    
    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">
        Submit
    </button>
</form>
```

## 2. Dynamic Table Component

### Advanced Dynamic Table
```php
namespace App\Livewire\Tables;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class DynamicTable extends Component
{
    use WithPagination;
    
    public string $model;
    public array $columns = [];
    public array $filters = [];
    public array $actions = [];
    public string $search = '';
    public string $sortField = 'id';
    public string $sortDirection = 'asc';
    public int $perPage = 10;
    public array $selected = [];
    public bool $selectAll = false;
    
    public array $defaultColumns = [
        'id' => ['label' => 'ID', 'sortable' => true],
        'created_at' => ['label' => 'Created', 'sortable' => true, 'format' => 'date']
    ];
    
    public function mount(string $model, array $columns = [], array $filters = [], array $actions = [])
    {
        $this->model = $model;
        $this->columns = array_merge($this->defaultColumns, $columns);
        $this->filters = $filters;
        $this->actions = $actions;
    }
    
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getFilteredQuery()->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }
    
    public function updatedSelected()
    {
        $this->selectAll = false;
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function executeAction($action, $id = null)
    {
        $actionConfig = $this->actions[$action] ?? null;
        
        if (!$actionConfig) return;
        
        if ($actionConfig['type'] === 'bulk' && empty($this->selected)) {
            session()->flash('error', 'No items selected');
            return;
        }
        
        if ($actionConfig['confirm'] ?? false) {
            $this->dispatch('confirm-action', [
                'action' => $action,
                'id' => $id,
                'message' => $actionConfig['confirm']
            ]);
            return;
        }
        
        $this->performAction($action, $id);
    }
    
    public function performAction($action, $id = null)
    {
        $actionConfig = $this->actions[$action];
        
        if ($actionConfig['type'] === 'single' && $id) {
            $model = $this->model::find($id);
            if (isset($actionConfig['callback'])) {
                call_user_func($actionConfig['callback'], $model);
            }
        } elseif ($actionConfig['type'] === 'bulk') {
            $models = $this->model::whereIn('id', $this->selected)->get();
            foreach ($models as $model) {
                if (isset($actionConfig['callback'])) {
                    call_user_func($actionConfig['callback'], $model);
                }
            }
            $this->selected = [];
            $this->selectAll = false;
        }
        
        session()->flash('success', $actionConfig['success'] ?? 'Action completed');
    }
    
    protected function getFilteredQuery(): Builder
    {
        $query = $this->model::query();
        
        // Apply search
        if ($this->search) {
            $query->where(function($q) {
                foreach ($this->columns as $field => $config) {
                    if ($config['searchable'] ?? true) {
                        $q->orWhere($field, 'like', '%' . $this->search . '%');
                    }
                }
            });
        }
        
        // Apply filters
        foreach ($this->filters as $field => $value) {
            if (!empty($value)) {
                if (is_array($value) && isset($value['from']) && isset($value['to'])) {
                    $query->whereBetween($field, [$value['from'], $value['to']]);
                } elseif (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }
        
        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query;
    }
    
    public function render()
    {
        return view('livewire.tables.dynamic-table', [
            'items' => $this->getFilteredQuery()->paginate($this->perPage)
        ]);
    }
}
```

## 3. Dynamic Actions & Events

### Action Manager
```php
trait DynamicActions
{
    protected array $dynamicActions = [];
    protected array $actionListeners = [];
    
    public function registerAction(string $name, callable $handler, array $options = []): void
    {
        $this->dynamicActions[$name] = [
            'handler' => $handler,
            'options' => $options
        ];
        
        if ($options['listen'] ?? false) {
            $this->actionListeners[] = $name;
        }
    }
    
    public function executeAction(string $action, ...$parameters)
    {
        if (!isset($this->dynamicActions[$action])) {
            throw new \Exception("Action {$action} not registered");
        }
        
        $config = $this->dynamicActions[$action];
        
        // Check permissions
        if (isset($config['options']['can'])) {
            if (!auth()->user()->can($config['options']['can'])) {
                $this->dispatch('unauthorized', action: $action);
                return;
            }
        }
        
        // Execute pre-handlers
        if (isset($config['options']['before'])) {
            call_user_func($config['options']['before'], ...$parameters);
        }
        
        // Execute main handler
        $result = call_user_func($config['handler'], ...$parameters);
        
        // Execute post-handlers
        if (isset($config['options']['after'])) {
            call_user_func($config['options']['after'], $result, ...$parameters);
        }
        
        // Emit events
        if ($config['options']['emit'] ?? true) {
            $this->dispatch($action . '-completed', result: $result);
        }
        
        return $result;
    }
    
    public function getListeners()
    {
        $listeners = parent::getListeners();
        
        foreach ($this->actionListeners as $action) {
            $listeners[$action] = 'executeAction';
        }
        
        return $listeners;
    }
}

// Usage
class DynamicDashboard extends Component
{
    use DynamicActions;
    
    public function mount()
    {
        $this->registerAction('refresh-stats', function() {
            $this->stats = $this->calculateStats();
        }, [
            'listen' => true,
            'emit' => true
        ]);
        
        $this->registerAction('export-data', function($format) {
            return $this->exportData($format);
        }, [
            'can' => 'export-data',
            'before' => fn() => Log::info('Export started'),
            'after' => fn($result) => Log::info('Export completed', ['result' => $result])
        ]);
    }
}
```

## 4. Computed Properties

### Dynamic Computed Properties
```php
trait DynamicComputedProperties
{
    protected array $computedProperties = [];
    protected array $computedCache = [];
    protected array $computedDependencies = [];
    
    public function computed(string $name, callable $callback, array $dependencies = []): void
    {
        $this->computedProperties[$name] = $callback;
        $this->computedDependencies[$name] = $dependencies;
    }
    
    public function __get($property)
    {
        if (isset($this->computedProperties[$property])) {
            return $this->getComputedProperty($property);
        }
        
        return parent::__get($property);
    }
    
    protected function getComputedProperty(string $name)
    {
        // Check if any dependency has changed
        $shouldRecalculate = false;
        
        if (isset($this->computedDependencies[$name])) {
            foreach ($this->computedDependencies[$name] as $dep) {
                if ($this->hasUpdated($dep)) {
                    $shouldRecalculate = true;
                    break;
                }
            }
        }
        
        // Return cached value if dependencies haven't changed
        if (!$shouldRecalculate && isset($this->computedCache[$name])) {
            return $this->computedCache[$name];
        }
        
        // Recalculate
        $value = call_user_func($this->computedProperties[$name], $this);
        $this->computedCache[$name] = $value;
        
        return $value;
    }
    
    public function updated($propertyName)
    {
        // Clear computed cache for properties that depend on this
        foreach ($this->computedDependencies as $computed => $deps) {
            if (in_array($propertyName, $deps)) {
                unset($this->computedCache[$computed]);
            }
        }
        
        parent::updated($propertyName);
    }
}

// Usage
class ProductManager extends Component
{
    use DynamicComputedProperties;
    
    public $products = [];
    public $filter = '';
    public $sortBy = 'name';
    
    public function mount()
    {
        $this->computed('filteredProducts', function() {
            return collect($this->products)
                ->filter(function($product) {
                    return empty($this->filter) || 
                           stripos($product['name'], $this->filter) !== false;
                })
                ->sortBy($this->sortBy)
                ->values()
                ->all();
        }, ['products', 'filter', 'sortBy']);
        
        $this->computed('totalValue', function() {
            return collect($this->filteredProducts)
                ->sum('price');
        }, ['filteredProducts']);
        
        $this->computed('statistics', function() {
            $filtered = collect($this->filteredProducts);
            
            return [
                'count' => $filtered->count(),
                'total' => $filtered->sum('price'),
                'average' => $filtered->avg('price'),
                'min' => $filtered->min('price'),
                'max' => $filtered->max('price')
            ];
        }, ['filteredProducts']);
    }
}
```

## 5. Dynamic Forms with Wire Models

### Advanced Wire Model Binding
```php
class DynamicModelBinding extends Component
{
    public array $models = [];
    protected array $modelConfig = [];
    
    public function mountDynamicModelBinding(array $config)
    {
        $this->modelConfig = $config;
        
        foreach ($config as $key => $settings) {
            $this->models[$key] = $settings['default'] ?? null;
        }
    }
    
    public function updatedModels($value, $key)
    {
        $parts = explode('.', $key);
        $modelKey = $parts[0];
        
        if (!isset($this->modelConfig[$modelKey])) {
            return;
        }
        
        $config = $this->modelConfig[$modelKey];
        
        // Run validators
        if (isset($config['validate'])) {
            $validator = Validator::make(
                [$modelKey => $value],
                [$modelKey => $config['validate']]
            );
            
            if ($validator->fails()) {
                $this->addError("models.{$key}", $validator->errors()->first());
                return;
            }
        }
        
        // Run transformers
        if (isset($config['transform'])) {
            $value = call_user_func($config['transform'], $value);
            data_set($this->models, $key, $value);
        }
        
        // Run side effects
        if (isset($config['onChange'])) {
            call_user_func($config['onChange'], $value, $this);
        }
        
        // Emit events
        if ($config['emit'] ?? false) {
            $this->dispatch('model-updated', model: $modelKey, value: $value);
        }
    }
    
    public function getDynamicRules(): array
    {
        $rules = [];
        
        foreach ($this->modelConfig as $key => $config) {
            if (isset($config['validate'])) {
                $rules["models.{$key}"] = $config['validate'];
            }
        }
        
        return $rules;
    }
}

// Usage
class AdvancedForm extends DynamicModelBinding
{
    public function mount()
    {
        $this->mountDynamicModelBinding([
            'price' => [
                'default' => 0,
                'validate' => 'numeric|min:0',
                'transform' => fn($value) => round($value, 2),
                'onChange' => fn($value) => $this->calculateTotal(),
                'emit' => true
            ],
            'quantity' => [
                'default' => 1,
                'validate' => 'integer|min:1',
                'onChange' => fn($value) => $this->calculateTotal()
            ],
            'discount' => [
                'default' => 0,
                'validate' => 'numeric|min:0|max:100',
                'transform' => fn($value) => min(100, max(0, $value))
            ]
        ]);
    }
    
    public function calculateTotal()
    {
        $price = $this->models['price'] ?? 0;
        $quantity = $this->models['quantity'] ?? 1;
        $discount = $this->models['discount'] ?? 0;
        
        $this->models['total'] = ($price * $quantity) * (1 - $discount / 100);
    }
}
```

## 6. Component Morphing

### Dynamic Component Morphing
```php
trait ComponentMorphing
{
    protected string $currentMorph = 'default';
    protected array $morphStates = [];
    
    public function morph(string $state, array $data = []): void
    {
        $this->currentMorph = $state;
        
        if (isset($this->morphStates[$state])) {
            foreach ($this->morphStates[$state] as $property => $value) {
                if (is_callable($value)) {
                    $this->$property = call_user_func($value, $this, $data);
                } else {
                    $this->$property = $value;
                }
            }
        }
        
        $this->dispatch('component-morphed', state: $state);
    }
    
    public function defineMorphState(string $name, array $state): void
    {
        $this->morphStates[$name] = $state;
    }
    
    public function getMorphView(): string
    {
        $baseView = $this->getRenderView();
        $morphView = str_replace('.blade.php', "-{$this->currentMorph}.blade.php", $baseView);
        
        if (view()->exists($morphView)) {
            return $morphView;
        }
        
        return $baseView;
    }
    
    public function render()
    {
        return view($this->getMorphView());
    }
}

// Usage
class MorphingCard extends Component
{
    use ComponentMorphing;
    
    public $title = '';
    public $content = '';
    public $actions = [];
    
    public function mount()
    {
        $this->defineMorphState('loading', [
            'title' => 'Loading...',
            'content' => '',
            'actions' => []
        ]);
        
        $this->defineMorphState('error', [
            'title' => 'Error',
            'content' => fn($component, $data) => $data['message'] ?? 'An error occurred',
            'actions' => [
                ['label' => 'Retry', 'action' => 'retry'],
                ['label' => 'Cancel', 'action' => 'cancel']
            ]
        ]);
        
        $this->defineMorphState('success', [
            'title' => 'Success',
            'content' => fn($component, $data) => $data['message'] ?? 'Operation completed',
            'actions' => [
                ['label' => 'Continue', 'action' => 'continue']
            ]
        ]);
    }
    
    public function loadData()
    {
        $this->morph('loading');
        
        try {
            // Load data...
            $this->morph('success', ['message' => 'Data loaded successfully']);
        } catch (\Exception $e) {
            $this->morph('error', ['message' => $e->getMessage()]);
        }
    }
}
```

## 7. Real-time Validation

### Dynamic Validation System
```php
trait DynamicValidation
{
    protected array $dynamicRules = [];
    protected array $conditionalRules = [];
    protected array $asyncValidators = [];
    
    public function addRule(string $field, $rules, $condition = null): void
    {
        if ($condition === null) {
            $this->dynamicRules[$field] = $rules;
        } else {
            $this->conditionalRules[$field][] = [
                'rules' => $rules,
                'condition' => $condition
            ];
        }
    }
    
    public function addAsyncValidator(string $field, callable $validator): void
    {
        $this->asyncValidators[$field] = $validator;
    }
    
    public function getRules(): array
    {
        $rules = parent::getRules() ?? [];
        
        // Add dynamic rules
        $rules = array_merge($rules, $this->dynamicRules);
        
        // Add conditional rules
        foreach ($this->conditionalRules as $field => $conditions) {
            foreach ($conditions as $condition) {
                if (value($condition['condition'])) {
                    $rules[$field] = $condition['rules'];
                }
            }
        }
        
        return $rules;
    }
    
    public function updated($propertyName)
    {
        parent::updated($propertyName);
        
        // Run async validators
        if (isset($this->asyncValidators[$propertyName])) {
            $this->validateAsync($propertyName);
        }
    }
    
    protected function validateAsync(string $field): void
    {
        $validator = $this->asyncValidators[$field];
        $value = data_get($this, $field);
        
        // Show loading state
        $this->dispatch('validation-pending', field: $field);
        
        try {
            $result = call_user_func($validator, $value, $this);
            
            if ($result === true) {
                $this->clearValidation($field);
                $this->dispatch('validation-passed', field: $field);
            } else {
                $this->addError($field, $result ?: 'Validation failed');
                $this->dispatch('validation-failed', field: $field);
            }
        } catch (\Exception $e) {
            $this->addError($field, 'Validation error: ' . $e->getMessage());
            $this->dispatch('validation-error', field: $field);
        }
    }
}

// Usage
class UserRegistration extends Component
{
    use DynamicValidation;
    
    public $username = '';
    public $email = '';
    public $accountType = 'personal';
    
    public function mount()
    {
        // Basic rules
        $this->addRule('username', 'required|min:3|max:20');
        $this->addRule('email', 'required|email');
        
        // Conditional rules
        $this->addRule('company_name', 'required|min:3', 
            fn() => $this->accountType === 'business'
        );
        
        $this->addRule('tax_id', 'required|regex:/^[0-9]{10}$/', 
            fn() => $this->accountType === 'business'
        );
        
        // Async validators
        $this->addAsyncValidator('username', function($value) {
            // Simulate API call
            sleep(1);
            
            $exists = User::where('username', $value)->exists();
            
            return $exists ? 'Username already taken' : true;
        });
        
        $this->addAsyncValidator('email', function($value) {
            // Check if email is disposable
            $response = Http::get("https://api.example.com/verify-email/{$value}");
            
            if ($response->json('is_disposable')) {
                return 'Disposable emails are not allowed';
            }
            
            return true;
        });
    }
}
```