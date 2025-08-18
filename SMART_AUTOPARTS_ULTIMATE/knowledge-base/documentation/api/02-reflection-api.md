# Reflection API في PHP و Laravel

## 1. أساسيات Reflection

### ReflectionClass
```php
class DynamicAnalyzer {
    public function analyzeClass(string $className): array {
        $reflection = new \ReflectionClass($className);
        
        return [
            'name' => $reflection->getName(),
            'namespace' => $reflection->getNamespaceName(),
            'is_abstract' => $reflection->isAbstract(),
            'is_interface' => $reflection->isInterface(),
            'is_trait' => $reflection->isTrait(),
            'parent' => $reflection->getParentClass()?->getName(),
            'interfaces' => array_map(fn($i) => $i->getName(), $reflection->getInterfaces()),
            'traits' => array_map(fn($t) => $t->getName(), $reflection->getTraits()),
            'properties' => $this->getProperties($reflection),
            'methods' => $this->getMethods($reflection),
            'constants' => $reflection->getConstants()
        ];
    }
    
    private function getProperties(\ReflectionClass $reflection): array {
        $properties = [];
        
        foreach ($reflection->getProperties() as $property) {
            $properties[] = [
                'name' => $property->getName(),
                'type' => $property->getType()?->getName(),
                'visibility' => $this->getVisibility($property),
                'is_static' => $property->isStatic(),
                'default' => $property->isInitialized() ? $property->getDefaultValue() : null
            ];
        }
        
        return $properties;
    }
    
    private function getMethods(\ReflectionClass $reflection): array {
        $methods = [];
        
        foreach ($reflection->getMethods() as $method) {
            $methods[] = [
                'name' => $method->getName(),
                'visibility' => $this->getVisibility($method),
                'is_static' => $method->isStatic(),
                'is_abstract' => $method->isAbstract(),
                'parameters' => $this->getParameters($method),
                'return_type' => $method->getReturnType()?->getName()
            ];
        }
        
        return $methods;
    }
    
    private function getVisibility($reflector): string {
        if ($reflector->isPublic()) return 'public';
        if ($reflector->isProtected()) return 'protected';
        if ($reflector->isPrivate()) return 'private';
        return 'unknown';
    }
    
    private function getParameters(\ReflectionMethod $method): array {
        $parameters = [];
        
        foreach ($method->getParameters() as $param) {
            $parameters[] = [
                'name' => $param->getName(),
                'type' => $param->getType()?->getName(),
                'is_optional' => $param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                'is_variadic' => $param->isVariadic()
            ];
        }
        
        return $parameters;
    }
}
```

### Dynamic Object Creation
```php
class DynamicFactory {
    private array $bindings = [];
    
    public function bind(string $abstract, $concrete): void {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function make(string $class, array $parameters = []) {
        // Check bindings
        if (isset($this->bindings[$class])) {
            $class = $this->bindings[$class];
        }
        
        $reflection = new \ReflectionClass($class);
        
        // إذا كان الصنف لا يحتوي على constructor
        if (!$reflection->getConstructor()) {
            return $reflection->newInstance();
        }
        
        $constructor = $reflection->getConstructor();
        $dependencies = [];
        
        foreach ($constructor->getParameters() as $param) {
            $dependencies[] = $this->resolveDependency($param, $parameters);
        }
        
        return $reflection->newInstanceArgs($dependencies);
    }
    
    private function resolveDependency(\ReflectionParameter $param, array $parameters) {
        $name = $param->getName();
        
        // Check if parameter was provided
        if (isset($parameters[$name])) {
            return $parameters[$name];
        }
        
        // Try to resolve type
        $type = $param->getType();
        if ($type && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }
        
        // Use default value if available
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }
        
        throw new \Exception("Cannot resolve parameter {$name}");
    }
}
```

## 2. Dynamic Method Invocation

### Method Invoker
```php
class DynamicInvoker {
    public function invoke($object, string $method, array $args = []) {
        $reflection = new \ReflectionMethod($object, $method);
        
        // Make private/protected methods accessible
        if (!$reflection->isPublic()) {
            $reflection->setAccessible(true);
        }
        
        // Resolve parameters
        $parameters = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            
            if (isset($args[$name])) {
                $parameters[] = $args[$name];
            } elseif (isset($args[$param->getPosition()])) {
                $parameters[] = $args[$param->getPosition()];
            } elseif ($param->isDefaultValueAvailable()) {
                $parameters[] = $param->getDefaultValue();
            } else {
                throw new \Exception("Missing required parameter: {$name}");
            }
        }
        
        return $reflection->invokeArgs($object, $parameters);
    }
    
    public function invokeStatic(string $class, string $method, array $args = []) {
        $reflection = new \ReflectionMethod($class, $method);
        
        if (!$reflection->isStatic()) {
            throw new \Exception("Method {$method} is not static");
        }
        
        return $reflection->invokeArgs(null, $args);
    }
}
```

### Property Accessor
```php
class DynamicPropertyAccessor {
    public function get($object, string $property) {
        $reflection = new \ReflectionProperty($object, $property);
        
        if (!$reflection->isPublic()) {
            $reflection->setAccessible(true);
        }
        
        return $reflection->getValue($object);
    }
    
    public function set($object, string $property, $value): void {
        $reflection = new \ReflectionProperty($object, $property);
        
        if (!$reflection->isPublic()) {
            $reflection->setAccessible(true);
        }
        
        $reflection->setValue($object, $value);
    }
    
    public function getAll($object): array {
        $reflection = new \ReflectionClass($object);
        $properties = [];
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $properties[$property->getName()] = $property->getValue($object);
        }
        
        return $properties;
    }
}
```

## 3. تطبيقات Laravel المتقدمة

### Dynamic Model Inspector
```php
class ModelInspector {
    public function inspect(string $modelClass): array {
        $reflection = new \ReflectionClass($modelClass);
        $instance = $reflection->newInstanceWithoutConstructor();
        
        return [
            'table' => $instance->getTable(),
            'fillable' => $instance->getFillable(),
            'guarded' => $instance->getGuarded(),
            'hidden' => $instance->getHidden(),
            'casts' => $instance->getCasts(),
            'dates' => $instance->getDates(),
            'relations' => $this->getRelations($reflection),
            'scopes' => $this->getScopes($reflection),
            'attributes' => $this->getAccessors($reflection)
        ];
    }
    
    private function getRelations(\ReflectionClass $reflection): array {
        $relations = [];
        
        foreach ($reflection->getMethods() as $method) {
            if ($method->class !== $reflection->getName()) continue;
            if ($method->getNumberOfParameters() > 0) continue;
            
            $returnType = $method->getReturnType();
            if (!$returnType) continue;
            
            $typeName = $returnType->getName();
            
            $relationTypes = [
                'Illuminate\Database\Eloquent\Relations\HasOne',
                'Illuminate\Database\Eloquent\Relations\HasMany',
                'Illuminate\Database\Eloquent\Relations\BelongsTo',
                'Illuminate\Database\Eloquent\Relations\BelongsToMany',
                'Illuminate\Database\Eloquent\Relations\MorphTo',
                'Illuminate\Database\Eloquent\Relations\MorphOne',
                'Illuminate\Database\Eloquent\Relations\MorphMany',
            ];
            
            if (in_array($typeName, $relationTypes)) {
                $relations[] = [
                    'name' => $method->getName(),
                    'type' => class_basename($typeName)
                ];
            }
        }
        
        return $relations;
    }
    
    private function getScopes(\ReflectionClass $reflection): array {
        $scopes = [];
        
        foreach ($reflection->getMethods() as $method) {
            if (str_starts_with($method->getName(), 'scope')) {
                $scopeName = lcfirst(substr($method->getName(), 5));
                $scopes[] = $scopeName;
            }
        }
        
        return $scopes;
    }
    
    private function getAccessors(\ReflectionClass $reflection): array {
        $accessors = [];
        
        foreach ($reflection->getMethods() as $method) {
            if (preg_match('/^get(.+)Attribute$/', $method->getName(), $matches)) {
                $accessors[] = Str::snake($matches[1]);
            }
        }
        
        return $accessors;
    }
}
```

### Dynamic Service Container
```php
class DynamicContainer {
    private array $bindings = [];
    private array $instances = [];
    
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }
    
    public function singleton(string $abstract, $concrete = null): void {
        $this->bind($abstract, $concrete, true);
    }
    
    public function make(string $abstract, array $parameters = []) {
        // Check for singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        
        // If concrete is a closure, execute it
        if ($concrete instanceof \Closure) {
            $object = $concrete($this, $parameters);
        } else {
            $object = $this->build($concrete, $parameters);
        }
        
        // Store singleton
        if (isset($this->bindings[$abstract]['singleton']) && 
            $this->bindings[$abstract]['singleton']) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    private function build(string $concrete, array $parameters = []) {
        $reflection = new \ReflectionClass($concrete);
        
        if (!$reflection->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }
        
        $constructor = $reflection->getConstructor();
        
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        $dependencies = array_map(
            fn($param) => $this->resolveDependency($param, $parameters),
            $constructor->getParameters()
        );
        
        return $reflection->newInstanceArgs($dependencies);
    }
    
    private function resolveDependency(\ReflectionParameter $parameter, array $parameters) {
        $name = $parameter->getName();
        
        if (isset($parameters[$name])) {
            return $parameters[$name];
        }
        
        $type = $parameter->getType();
        
        if ($type && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }
        
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        
        throw new \Exception("Unable to resolve dependency {$name}");
    }
}
```

## 4. أدوات تحليل الكود

### Code Analyzer
```php
class CodeAnalyzer {
    public function analyze(string $file): array {
        $code = file_get_contents($file);
        $tokens = token_get_all($code);
        
        $analysis = [
            'classes' => [],
            'functions' => [],
            'traits' => [],
            'interfaces' => [],
            'namespaces' => []
        ];
        
        for ($i = 0; $i < count($tokens); $i++) {
            if (!is_array($tokens[$i])) continue;
            
            switch ($tokens[$i][0]) {
                case T_NAMESPACE:
                    $analysis['namespaces'][] = $this->getNextIdentifier($tokens, $i);
                    break;
                    
                case T_CLASS:
                    $className = $this->getNextIdentifier($tokens, $i);
                    $analysis['classes'][] = $this->analyzeClass($className);
                    break;
                    
                case T_FUNCTION:
                    $analysis['functions'][] = $this->getNextIdentifier($tokens, $i);
                    break;
                    
                case T_TRAIT:
                    $analysis['traits'][] = $this->getNextIdentifier($tokens, $i);
                    break;
                    
                case T_INTERFACE:
                    $analysis['interfaces'][] = $this->getNextIdentifier($tokens, $i);
                    break;
            }
        }
        
        return $analysis;
    }
    
    private function getNextIdentifier(array $tokens, int &$index): string {
        while (++$index < count($tokens)) {
            if (is_array($tokens[$index]) && $tokens[$index][0] === T_STRING) {
                return $tokens[$index][1];
            }
        }
        return '';
    }
    
    private function analyzeClass(string $className): array {
        try {
            $reflection = new \ReflectionClass($className);
            
            return [
                'name' => $className,
                'methods_count' => count($reflection->getMethods()),
                'properties_count' => count($reflection->getProperties()),
                'is_final' => $reflection->isFinal(),
                'is_abstract' => $reflection->isAbstract()
            ];
        } catch (\Exception $e) {
            return ['name' => $className, 'error' => $e->getMessage()];
        }
    }
}
```

## 5. أمثلة عملية

### Dynamic API Documentation Generator
```php
class ApiDocGenerator {
    public function generate(string $controllerClass): array {
        $reflection = new \ReflectionClass($controllerClass);
        $docs = [];
        
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $controllerClass) continue;
            
            $docs[] = [
                'endpoint' => $this->getEndpoint($method),
                'method' => $this->getHttpMethod($method),
                'parameters' => $this->getParameters($method),
                'response' => $this->getResponse($method),
                'description' => $this->getDescription($method)
            ];
        }
        
        return $docs;
    }
    
    private function getEndpoint(\ReflectionMethod $method): string {
        // Extract from route annotations or naming convention
        $name = $method->getName();
        $controller = Str::kebab(str_replace('Controller', '', $method->class));
        
        return "/{$controller}/{$name}";
    }
    
    private function getHttpMethod(\ReflectionMethod $method): string {
        $name = $method->getName();
        
        if (str_starts_with($name, 'get') || $name === 'index' || $name === 'show') {
            return 'GET';
        } elseif (str_starts_with($name, 'post') || $name === 'store') {
            return 'POST';
        } elseif (str_starts_with($name, 'put') || $name === 'update') {
            return 'PUT';
        } elseif (str_starts_with($name, 'delete') || $name === 'destroy') {
            return 'DELETE';
        }
        
        return 'GET';
    }
    
    private function getParameters(\ReflectionMethod $method): array {
        $params = [];
        
        foreach ($method->getParameters() as $param) {
            if ($param->getType()?->getName() === 'Illuminate\Http\Request') {
                continue;
            }
            
            $params[] = [
                'name' => $param->getName(),
                'type' => $param->getType()?->getName() ?? 'mixed',
                'required' => !$param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            ];
        }
        
        return $params;
    }
    
    private function getResponse(\ReflectionMethod $method): array {
        $returnType = $method->getReturnType();
        
        if (!$returnType) {
            return ['type' => 'mixed'];
        }
        
        return [
            'type' => $returnType->getName(),
            'nullable' => $returnType->allowsNull()
        ];
    }
    
    private function getDescription(\ReflectionMethod $method): string {
        $docComment = $method->getDocComment();
        
        if (preg_match('/@description\s+(.+)/', $docComment, $matches)) {
            return trim($matches[1]);
        }
        
        return '';
    }
}
```