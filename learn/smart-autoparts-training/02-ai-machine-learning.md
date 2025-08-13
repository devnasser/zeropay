# AI & Machine Learning for Smart AutoParts

## 1. Recommendation System

### Collaborative Filtering Engine
```php
namespace App\Services\AI;

class CollaborativeFilteringEngine
{
    protected array $userItemMatrix = [];
    protected array $itemSimilarityMatrix = [];
    
    public function buildUserItemMatrix(): void
    {
        // Build matrix from orders and interactions
        $interactions = DB::select("
            SELECT 
                user_id,
                product_id,
                SUM(interaction_score) as score
            FROM (
                -- Purchases (highest weight)
                SELECT 
                    o.user_id,
                    oi.product_id,
                    5.0 * oi.quantity as interaction_score
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.status = 'completed'
                
                UNION ALL
                
                -- Cart additions
                SELECT 
                    user_id,
                    product_id,
                    2.0 as interaction_score
                FROM cart_items
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                
                UNION ALL
                
                -- Product views
                SELECT 
                    user_id,
                    product_id,
                    0.5 as interaction_score
                FROM product_views
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                UNION ALL
                
                -- Reviews
                SELECT 
                    user_id,
                    product_id,
                    3.0 * (rating / 5.0) as interaction_score
                FROM reviews
            ) as interactions
            GROUP BY user_id, product_id
        ");
        
        // Convert to matrix format
        foreach ($interactions as $interaction) {
            $this->userItemMatrix[$interaction->user_id][$interaction->product_id] = $interaction->score;
        }
    }
    
    public function calculateItemSimilarity(): void
    {
        $products = array_keys(array_merge(...array_values($this->userItemMatrix)));
        
        foreach ($products as $product1) {
            foreach ($products as $product2) {
                if ($product1 != $product2) {
                    $similarity = $this->cosineSimilarity($product1, $product2);
                    
                    if ($similarity > 0.1) { // Threshold for relevance
                        $this->itemSimilarityMatrix[$product1][$product2] = $similarity;
                    }
                }
            }
        }
        
        // Store in Redis for fast access
        foreach ($this->itemSimilarityMatrix as $product => $similarities) {
            Redis::zadd("product:similarities:{$product}", $similarities);
            Redis::expire("product:similarities:{$product}", 86400); // 24 hours
        }
    }
    
    protected function cosineSimilarity($product1, $product2): float
    {
        $users1 = [];
        $users2 = [];
        
        foreach ($this->userItemMatrix as $userId => $products) {
            $users1[$userId] = $products[$product1] ?? 0;
            $users2[$userId] = $products[$product2] ?? 0;
        }
        
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        foreach (array_keys($users1 + $users2) as $user) {
            $score1 = $users1[$user] ?? 0;
            $score2 = $users2[$user] ?? 0;
            
            $dotProduct += $score1 * $score2;
            $magnitude1 += pow($score1, 2);
            $magnitude2 += pow($score2, 2);
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    public function getRecommendations(int $userId, int $limit = 10): array
    {
        $userProducts = $this->userItemMatrix[$userId] ?? [];
        $recommendations = [];
        
        // Get products similar to what user has interacted with
        foreach ($userProducts as $productId => $score) {
            $similarProducts = Redis::zrevrange("product:similarities:{$productId}", 0, 20, 'WITHSCORES');
            
            foreach ($similarProducts as $similarProductId => $similarity) {
                // Skip if user already interacted with this product
                if (isset($userProducts[$similarProductId])) {
                    continue;
                }
                
                // Calculate recommendation score
                $recommendationScore = $score * $similarity;
                
                if (!isset($recommendations[$similarProductId])) {
                    $recommendations[$similarProductId] = 0;
                }
                
                $recommendations[$similarProductId] += $recommendationScore;
            }
        }
        
        // Sort by score and return top N
        arsort($recommendations);
        
        return array_slice(array_keys($recommendations), 0, $limit);
    }
}
```

### Content-Based Filtering
```php
class ContentBasedFilteringEngine
{
    protected array $productFeatures = [];
    protected TfIdfVectorizer $vectorizer;
    
    public function extractProductFeatures(Product $product): array
    {
        $features = [];
        
        // Basic attributes
        $features['category'] = $product->category->slug;
        $features['brand'] = strtolower($product->brand);
        $features['price_range'] = $this->getPriceRange($product->price);
        
        // Text features from name and description
        $textFeatures = $this->extractTextFeatures(
            $product->name . ' ' . $product->description
        );
        $features = array_merge($features, $textFeatures);
        
        // Technical specifications
        foreach ($product->specifications as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $features["spec_{$key}"] = $this->normalizeValue($value);
            }
        }
        
        // Compatibility features
        if ($product->compatibility) {
            $features['compatible_makes'] = implode(' ', array_unique(
                array_column($product->compatibility, 'make')
            ));
            $features['compatible_models'] = implode(' ', array_unique(
                array_column($product->compatibility, 'model')
            ));
        }
        
        return $features;
    }
    
    public function buildFeatureVectors(): void
    {
        $products = Product::active()->get();
        $documents = [];
        
        foreach ($products as $product) {
            $features = $this->extractProductFeatures($product);
            $document = implode(' ', array_values($features));
            $documents[$product->id] = $document;
        }
        
        // Create TF-IDF vectors
        $this->vectorizer = new TfIdfVectorizer();
        $vectors = $this->vectorizer->fitTransform($documents);
        
        // Store vectors in Redis
        foreach ($vectors as $productId => $vector) {
            Redis::hset('product:vectors', $productId, serialize($vector));
        }
    }
    
    public function findSimilarProducts(Product $product, int $limit = 10): array
    {
        $targetVector = unserialize(Redis::hget('product:vectors', $product->id));
        
        if (!$targetVector) {
            return [];
        }
        
        $similarities = [];
        
        // Calculate similarity with all other products
        $allVectors = Redis::hgetall('product:vectors');
        
        foreach ($allVectors as $productId => $vectorData) {
            if ($productId == $product->id) continue;
            
            $vector = unserialize($vectorData);
            $similarity = $this->cosineSimilarity($targetVector, $vector);
            
            if ($similarity > 0.3) { // Threshold
                $similarities[$productId] = $similarity;
            }
        }
        
        arsort($similarities);
        
        return array_slice(array_keys($similarities), 0, $limit);
    }
    
    protected function extractTextFeatures(string $text): array
    {
        // Remove Arabic/English stop words
        $stopWords = array_merge(
            ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for'],
            ['في', 'من', 'إلى', 'على', 'عن', 'مع', 'هذا', 'هذه', 'ذلك']
        );
        
        // Tokenize and clean
        $words = preg_split('/\s+/', strtolower($text));
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        // Extract n-grams
        $features = [];
        
        // Unigrams
        foreach ($words as $word) {
            $features["word_{$word}"] = 1;
        }
        
        // Bigrams
        for ($i = 0; $i < count($words) - 1; $i++) {
            $bigram = $words[$i] . '_' . $words[$i + 1];
            $features["bigram_{$bigram}"] = 1;
        }
        
        return $features;
    }
}
```

### Hybrid Recommendation System
```php
class HybridRecommendationEngine
{
    protected CollaborativeFilteringEngine $collaborative;
    protected ContentBasedFilteringEngine $contentBased;
    protected UserProfileAnalyzer $profileAnalyzer;
    
    public function getPersonalizedRecommendations(User $user, array $context = []): array
    {
        $recommendations = [];
        
        // 1. Collaborative filtering recommendations
        $collaborativeRecs = $this->collaborative->getRecommendations($user->id, 50);
        foreach ($collaborativeRecs as $index => $productId) {
            $recommendations[$productId] = [
                'score' => 1 - ($index / 50), // Normalize to 0-1
                'source' => 'collaborative'
            ];
        }
        
        // 2. Content-based recommendations from recent interactions
        $recentProducts = $this->getUserRecentProducts($user, 5);
        foreach ($recentProducts as $product) {
            $similar = $this->contentBased->findSimilarProducts($product, 20);
            
            foreach ($similar as $index => $productId) {
                if (!isset($recommendations[$productId])) {
                    $recommendations[$productId] = ['score' => 0, 'source' => []];
                }
                
                $recommendations[$productId]['score'] += (1 - ($index / 20)) * 0.8;
                $recommendations[$productId]['source'][] = 'content';
            }
        }
        
        // 3. Context-aware adjustments
        if (!empty($context)) {
            $this->applyContextualFilters($recommendations, $context);
        }
        
        // 4. User profile preferences
        $profileBoosts = $this->profileAnalyzer->getPreferenceBoosts($user);
        $this->applyProfileBoosts($recommendations, $profileBoosts);
        
        // 5. Business rules and diversity
        $this->applyBusinessRules($recommendations);
        $this->ensureDiversity($recommendations);
        
        // Sort by final score
        uasort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Return top recommendations with metadata
        return $this->prepareRecommendations(array_slice($recommendations, 0, 20, true));
    }
    
    protected function applyContextualFilters(&$recommendations, $context): void
    {
        // Vehicle-specific filtering
        if (isset($context['vehicle'])) {
            $compatibleProducts = $this->getCompatibleProducts($context['vehicle']);
            
            foreach ($recommendations as $productId => &$rec) {
                if (in_array($productId, $compatibleProducts)) {
                    $rec['score'] *= 1.5; // Boost compatible products
                } else {
                    $rec['score'] *= 0.3; // Penalize incompatible
                }
            }
        }
        
        // Location-based adjustments
        if (isset($context['location'])) {
            $localInventory = $this->getLocalInventory($context['location']);
            
            foreach ($recommendations as $productId => &$rec) {
                if (in_array($productId, $localInventory)) {
                    $rec['score'] *= 1.2; // Boost locally available
                }
            }
        }
        
        // Seasonal adjustments
        $seasonalBoosts = $this->getSeasonalBoosts();
        foreach ($recommendations as $productId => &$rec) {
            if (isset($seasonalBoosts[$productId])) {
                $rec['score'] *= $seasonalBoosts[$productId];
            }
        }
    }
    
    protected function ensureDiversity(&$recommendations): void
    {
        $categories = [];
        $brands = [];
        $finalRecs = [];
        
        foreach ($recommendations as $productId => $rec) {
            $product = Product::find($productId);
            
            if (!$product) continue;
            
            $categoryCount = $categories[$product->category_id] ?? 0;
            $brandCount = $brands[$product->brand] ?? 0;
            
            // Limit same category/brand
            if ($categoryCount >= 3 || $brandCount >= 2) {
                $rec['score'] *= 0.5; // Penalize over-representation
            }
            
            $categories[$product->category_id] = $categoryCount + 1;
            $brands[$product->brand] = $brandCount + 1;
            
            $finalRecs[$productId] = $rec;
        }
        
        $recommendations = $finalRecs;
    }
}
```

## 2. Natural Language Processing

### Arabic/English Search Engine
```php
namespace App\Services\NLP;

class MultilingualSearchEngine
{
    protected ArabicAnalyzer $arabicAnalyzer;
    protected EnglishAnalyzer $englishAnalyzer;
    protected SearchIndexer $indexer;
    
    public function search(string $query, array $filters = []): array
    {
        // Detect language
        $language = $this->detectLanguage($query);
        
        // Process query based on language
        $processedQuery = match($language) {
            'ar' => $this->arabicAnalyzer->process($query),
            'en' => $this->englishAnalyzer->process($query),
            default => $this->processMultilingual($query)
        };
        
        // Extract entities
        $entities = $this->extractEntities($processedQuery);
        
        // Build search query
        $searchQuery = $this->buildSearchQuery($processedQuery, $entities, $filters);
        
        // Execute search
        $results = $this->executeSearch($searchQuery);
        
        // Re-rank results using ML
        return $this->reRankResults($results, $query, $entities);
    }
    
    protected function detectLanguage(string $text): string
    {
        $arabicPattern = '/[\x{0600}-\x{06FF}]/u';
        $arabicCount = preg_match_all($arabicPattern, $text);
        $totalChars = mb_strlen($text);
        
        if ($arabicCount / $totalChars > 0.3) {
            return 'ar';
        }
        
        return 'en';
    }
    
    protected function extractEntities(array $tokens): array
    {
        $entities = [
            'brands' => [],
            'models' => [],
            'parts' => [],
            'years' => [],
            'specifications' => []
        ];
        
        // Brand detection
        $knownBrands = Cache::remember('known_brands', 3600, function() {
            return Brand::pluck('name')->map('strtolower')->toArray();
        });
        
        foreach ($tokens as $token) {
            if (in_array(strtolower($token), $knownBrands)) {
                $entities['brands'][] = $token;
            }
            
            // Year detection
            if (preg_match('/^(19|20)\d{2}$/', $token)) {
                $entities['years'][] = $token;
            }
            
            // Part number detection
            if (preg_match('/^[A-Z0-9]{4,}[-]?[A-Z0-9]*$/', $token)) {
                $entities['parts'][] = $token;
            }
        }
        
        return $entities;
    }
    
    protected function buildSearchQuery(array $tokens, array $entities, array $filters): array
    {
        $must = [];
        $should = [];
        $filter = [];
        
        // Full text search
        $searchText = implode(' ', $tokens);
        $must[] = [
            'multi_match' => [
                'query' => $searchText,
                'fields' => [
                    'name.ar^3',
                    'name.en^3',
                    'description.ar',
                    'description.en',
                    'brand^2',
                    'sku^4',
                    'oem_numbers^5'
                ],
                'type' => 'best_fields',
                'fuzziness' => 'AUTO'
            ]
        ];
        
        // Entity boosting
        if (!empty($entities['brands'])) {
            $should[] = [
                'terms' => [
                    'brand' => $entities['brands'],
                    'boost' => 2.0
                ]
            ];
        }
        
        if (!empty($entities['years'])) {
            $should[] = [
                'range' => [
                    'compatibility.year_from' => ['lte' => min($entities['years'])],
                    'compatibility.year_to' => ['gte' => max($entities['years'])]
                ]
            ];
        }
        
        // Apply filters
        if (!empty($filters['category'])) {
            $filter[] = ['term' => ['category_id' => $filters['category']]];
        }
        
        if (!empty($filters['price_range'])) {
            $filter[] = [
                'range' => [
                    'price' => [
                        'gte' => $filters['price_range']['min'],
                        'lte' => $filters['price_range']['max']
                    ]
                ]
            ];
        }
        
        return [
            'bool' => [
                'must' => $must,
                'should' => $should,
                'filter' => $filter
            ]
        ];
    }
    
    protected function reRankResults(array $results, string $originalQuery, array $entities): array
    {
        $reRanked = [];
        
        foreach ($results as $result) {
            $score = $result['_score'];
            
            // Boost exact matches
            if (stripos($result['_source']['name'], $originalQuery) !== false) {
                $score *= 1.5;
            }
            
            // Boost if all entities match
            $entityMatches = 0;
            foreach ($entities as $type => $values) {
                foreach ($values as $value) {
                    if ($this->entityMatches($result['_source'], $type, $value)) {
                        $entityMatches++;
                    }
                }
            }
            
            if ($entityMatches == count(array_merge(...array_values($entities)))) {
                $score *= 1.3;
            }
            
            // User behavior scoring
            $behaviorScore = $this->getUserBehaviorScore($result['_source']['id']);
            $score *= (1 + $behaviorScore);
            
            $reRanked[] = array_merge($result, ['final_score' => $score]);
        }
        
        usort($reRanked, fn($a, $b) => $b['final_score'] <=> $a['final_score']);
        
        return $reRanked;
    }
}

class ArabicAnalyzer
{
    protected array $stopWords = [
        'في', 'من', 'إلى', 'على', 'هذا', 'هذه', 'ذلك', 'التي', 'الذي',
        'كان', 'كانت', 'هو', 'هي', 'مع', 'عن', 'بعد', 'قبل'
    ];
    
    public function process(string $text): array
    {
        // Normalize Arabic text
        $normalized = $this->normalize($text);
        
        // Tokenize
        $tokens = $this->tokenize($normalized);
        
        // Remove stop words
        $tokens = array_filter($tokens, fn($token) => !in_array($token, $this->stopWords));
        
        // Stem words
        $stemmed = array_map([$this, 'stem'], $tokens);
        
        // Expand with synonyms
        $expanded = $this->expandWithSynonyms($stemmed);
        
        return array_unique($expanded);
    }
    
    protected function normalize(string $text): string
    {
        // Remove diacritics
        $text = preg_replace('/[\x{064B}-\x{065F}]/u', '', $text);
        
        // Normalize hamza
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        
        // Normalize taa marbouta
        $text = str_replace('ة', 'ه', $text);
        
        // Normalize alef maksura
        $text = str_replace('ى', 'ي', $text);
        
        return $text;
    }
    
    protected function stem(string $word): string
    {
        // Simple Arabic stemming rules
        $prefixes = ['ال', 'و', 'ف', 'ب', 'ك', 'ل'];
        $suffixes = ['ها', 'ان', 'ات', 'ون', 'ين', 'تم', 'كم', 'هن'];
        
        // Remove prefixes
        foreach ($prefixes as $prefix) {
            if (str_starts_with($word, $prefix)) {
                $word = substr($word, strlen($prefix));
                break;
            }
        }
        
        // Remove suffixes
        foreach ($suffixes as $suffix) {
            if (str_ends_with($word, $suffix) && strlen($word) - strlen($suffix) >= 2) {
                $word = substr($word, 0, -strlen($suffix));
                break;
            }
        }
        
        return $word;
    }
    
    protected function expandWithSynonyms(array $terms): array
    {
        $synonyms = [
            'سياره' => ['عربيه', 'مركبه'],
            'قطع' => ['اجزاء', 'قطع غيار'],
            'محرك' => ['موتور', 'ماكينه'],
            'زيت' => ['زيوت', 'دهن'],
            'فرامل' => ['بريك', 'مكابح'],
            'اطار' => ['كاوتش', 'عجله', 'تاير'],
            'بطاريه' => ['بطاريات', 'مركم']
        ];
        
        $expanded = $terms;
        
        foreach ($terms as $term) {
            if (isset($synonyms[$term])) {
                $expanded = array_merge($expanded, $synonyms[$term]);
            }
        }
        
        return $expanded;
    }
}
```

### Intelligent Chatbot
```php
class IntelligentChatbot
{
    protected NLUEngine $nlu;
    protected DialogManager $dialogManager;
    protected ResponseGenerator $responseGenerator;
    
    public function processMessage(string $message, string $sessionId): array
    {
        // 1. Natural Language Understanding
        $intent = $this->nlu->extractIntent($message);
        $entities = $this->nlu->extractEntities($message);
        
        // 2. Context Management
        $context = $this->dialogManager->getContext($sessionId);
        $context = $this->dialogManager->updateContext($context, $intent, $entities);
        
        // 3. Action Determination
        $action = $this->determineAction($intent, $entities, $context);
        
        // 4. Execute Action
        $result = $this->executeAction($action, $entities, $context);
        
        // 5. Generate Response
        $response = $this->responseGenerator->generate($result, $context);
        
        // 6. Update Context
        $this->dialogManager->saveContext($sessionId, $context);
        
        return [
            'response' => $response,
            'intent' => $intent,
            'entities' => $entities,
            'suggestions' => $this->getSuggestions($context)
        ];
    }
    
    protected function determineAction(array $intent, array $entities, array $context): string
    {
        $intentName = $intent['name'];
        $confidence = $intent['confidence'];
        
        // High confidence intents
        if ($confidence > 0.8) {
            return match($intentName) {
                'search_product' => 'searchProducts',
                'check_compatibility' => 'checkCompatibility',
                'track_order' => 'trackOrder',
                'get_price' => 'getPrice',
                'technical_support' => 'provideTechnicalSupport',
                default => 'clarifyIntent'
            };
        }
        
        // Low confidence - try to clarify
        return 'clarifyIntent';
    }
    
    protected function executeAction(string $action, array $entities, array $context): array
    {
        return match($action) {
            'searchProducts' => $this->searchProducts($entities),
            'checkCompatibility' => $this->checkCompatibility($entities, $context),
            'trackOrder' => $this->trackOrder($entities),
            'getPrice' => $this->getPrice($entities),
            'provideTechnicalSupport' => $this->provideTechnicalSupport($entities, $context),
            default => $this->handleUnknown($entities, $context)
        };
    }
    
    protected function checkCompatibility(array $entities, array $context): array
    {
        if (!isset($entities['product']) || !isset($entities['vehicle'])) {
            return [
                'type' => 'clarification_needed',
                'missing' => array_diff(['product', 'vehicle'], array_keys($entities))
            ];
        }
        
        $product = Product::where('name', 'like', '%' . $entities['product'] . '%')->first();
        
        if (!$product) {
            return [
                'type' => 'product_not_found',
                'query' => $entities['product']
            ];
        }
        
        $vehicle = $entities['vehicle'];
        $compatibility = DB::table('product_vehicle_compatibility')
            ->where('product_id', $product->id)
            ->where('make', $vehicle['make'] ?? null)
            ->where('model', $vehicle['model'] ?? null)
            ->where('year_from', '<=', $vehicle['year'] ?? 9999)
            ->where('year_to', '>=', $vehicle['year'] ?? 0)
            ->first();
        
        return [
            'type' => 'compatibility_result',
            'compatible' => $compatibility !== null,
            'product' => $product,
            'vehicle' => $vehicle,
            'confidence' => $compatibility->confidence_score ?? 0,
            'notes' => $compatibility->notes ?? null
        ];
    }
}

class NLUEngine
{
    protected IntentClassifier $intentClassifier;
    protected EntityExtractor $entityExtractor;
    
    public function extractIntent(string $message): array
    {
        // Preprocess message
        $processed = $this->preprocess($message);
        
        // Use ML model for intent classification
        $intents = $this->intentClassifier->classify($processed);
        
        // Return top intent with confidence
        return [
            'name' => $intents[0]['intent'],
            'confidence' => $intents[0]['confidence'],
            'alternatives' => array_slice($intents, 1, 2)
        ];
    }
    
    public function extractEntities(string $message): array
    {
        $entities = [];
        
        // Product entities
        $products = $this->extractProductEntities($message);
        if (!empty($products)) {
            $entities['product'] = $products[0];
        }
        
        // Vehicle entities
        $vehicle = $this->extractVehicleInfo($message);
        if (!empty($vehicle)) {
            $entities['vehicle'] = $vehicle;
        }
        
        // Order number
        if (preg_match('/\b[A-Z]{2}\d{8}\b/', $message, $matches)) {
            $entities['order_number'] = $matches[0];
        }
        
        // Quantity
        if (preg_match('/(\d+)\s*(قطع|piece|pcs|items?)/i', $message, $matches)) {
            $entities['quantity'] = intval($matches[1]);
        }
        
        return $entities;
    }
    
    protected function extractVehicleInfo(string $message): ?array
    {
        $patterns = [
            'make_model_year' => '/(\w+)\s+(\w+)\s+(19|20)\d{2}/',
            'year_make_model' => '/(19|20)\d{2}\s+(\w+)\s+(\w+)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return $this->parseVehicleMatch($matches);
            }
        }
        
        return null;
    }
}
```

## 3. Predictive Analytics

### Demand Forecasting
```php
namespace App\Services\Analytics;

class DemandForecastingEngine
{
    protected TimeSeriesAnalyzer $timeSeriesAnalyzer;
    protected SeasonalityDetector $seasonalityDetector;
    
    public function forecastDemand(Product $product, int $daysAhead = 30): array
    {
        // Get historical data
        $historicalData = $this->getHistoricalDemand($product, 365);
        
        // Detect seasonality
        $seasonality = $this->seasonalityDetector->detect($historicalData);
        
        // Prepare features
        $features = $this->prepareFeatures($product, $historicalData);
        
        // Apply different models
        $forecasts = [
            'arima' => $this->forecastWithARIMA($historicalData, $daysAhead),
            'prophet' => $this->forecastWithProphet($historicalData, $daysAhead),
            'lstm' => $this->forecastWithLSTM($features, $daysAhead),
            'ensemble' => null
        ];
        
        // Ensemble prediction
        $forecasts['ensemble'] = $this->ensembleForecast($forecasts);
        
        // Add confidence intervals
        $finalForecast = $this->addConfidenceIntervals($forecasts['ensemble']);
        
        // Apply business constraints
        return $this->applyBusinessConstraints($finalForecast, $product);
    }
    
    protected function prepareFeatures(Product $product, array $historicalData): array
    {
        $features = [];
        
        foreach ($historicalData as $date => $demand) {
            $dateObj = new DateTime($date);
            
            $features[] = [
                'demand' => $demand,
                'day_of_week' => $dateObj->format('N'),
                'day_of_month' => $dateObj->format('j'),
                'month' => $dateObj->format('n'),
                'quarter' => ceil($dateObj->format('n') / 3),
                'is_weekend' => in_array($dateObj->format('N'), [5, 6]),
                'is_holiday' => $this->isHoliday($date),
                'price' => $this->getHistoricalPrice($product, $date),
                'competitor_price' => $this->getCompetitorPrice($product, $date),
                'marketing_spend' => $this->getMarketingSpend($product, $date),
                'stock_level' => $this->getStockLevel($product, $date),
                'weather_temp' => $this->getWeatherData($date)['temperature'] ?? null,
                'economic_index' => $this->getEconomicIndex($date)
            ];
        }
        
        return $features;
    }
    
    public function detectAnomalies(Product $product): array
    {
        $recentDemand = $this->getHistoricalDemand($product, 30);
        $forecast = $this->forecastDemand($product, 1);
        
        $anomalies = [];
        
        foreach ($recentDemand as $date => $actual) {
            $expected = $forecast[$date] ?? null;
            
            if ($expected) {
                $deviation = abs($actual - $expected) / $expected;
                
                if ($deviation > 0.5) { // 50% deviation threshold
                    $anomalies[] = [
                        'date' => $date,
                        'actual' => $actual,
                        'expected' => $expected,
                        'deviation' => $deviation,
                        'possible_causes' => $this->analyzeCauses($product, $date, $deviation)
                    ];
                }
            }
        }
        
        return $anomalies;
    }
    
    protected function analyzeCauses(Product $product, string $date, float $deviation): array
    {
        $causes = [];
        
        // Check for price changes
        $priceChange = $this->checkPriceChange($product, $date);
        if (abs($priceChange) > 0.1) {
            $causes[] = "Price change of " . ($priceChange * 100) . "%";
        }
        
        // Check for stock outs
        if ($this->hadStockOut($product, $date)) {
            $causes[] = "Stock out occurred";
        }
        
        // Check for promotional activities
        if ($this->hadPromotion($product, $date)) {
            $causes[] = "Promotional campaign active";
        }
        
        // Check for competitor activities
        $competitorActivity = $this->checkCompetitorActivity($product, $date);
        if ($competitorActivity) {
            $causes[] = "Competitor activity: " . $competitorActivity;
        }
        
        return $causes;
    }
}

class InventoryOptimizer
{
    protected DemandForecastingEngine $forecaster;
    protected SupplierAnalyzer $supplierAnalyzer;
    
    public function optimizeInventory(Shop $shop): array
    {
        $recommendations = [];
        
        foreach ($shop->products as $product) {
            // Forecast demand
            $forecast = $this->forecaster->forecastDemand($product, 30);
            
            // Calculate optimal stock levels
            $optimalLevels = $this->calculateOptimalLevels($product, $forecast);
            
            // Check current levels
            $currentLevel = $product->quantity;
            
            // Generate recommendations
            if ($currentLevel < $optimalLevels['reorder_point']) {
                $recommendations[] = [
                    'action' => 'reorder',
                    'product' => $product,
                    'quantity' => $optimalLevels['order_quantity'],
                    'urgency' => $this->calculateUrgency($currentLevel, $forecast),
                    'supplier' => $this->supplierAnalyzer->getBestSupplier($product),
                    'estimated_cost' => $optimalLevels['order_quantity'] * $product->cost
                ];
            } elseif ($currentLevel > $optimalLevels['max_stock']) {
                $recommendations[] = [
                    'action' => 'reduce',
                    'product' => $product,
                    'quantity' => $currentLevel - $optimalLevels['max_stock'],
                    'reason' => 'Excess inventory',
                    'suggested_actions' => $this->suggestReductionActions($product)
                ];
            }
        }
        
        return $recommendations;
    }
    
    protected function calculateOptimalLevels(Product $product, array $forecast): array
    {
        // Average daily demand
        $avgDemand = array_sum($forecast) / count($forecast);
        
        // Lead time in days
        $leadTime = $this->supplierAnalyzer->getAverageLeadTime($product);
        
        // Safety stock calculation
        $demandStdDev = $this->calculateStandardDeviation(array_values($forecast));
        $leadTimeStdDev = $this->supplierAnalyzer->getLeadTimeVariability($product);
        
        // Z-score for 95% service level
        $zScore = 1.65;
        
        $safetyStock = $zScore * sqrt(
            pow($leadTime * $demandStdDev, 2) + 
            pow($avgDemand * $leadTimeStdDev, 2)
        );
        
        // Reorder point
        $reorderPoint = ($avgDemand * $leadTime) + $safetyStock;
        
        // Economic Order Quantity (EOQ)
        $holdingCost = $product->cost * 0.25; // 25% annual holding cost
        $orderCost = 50; // Fixed order cost
        $annualDemand = $avgDemand * 365;
        
        $eoq = sqrt((2 * $annualDemand * $orderCost) / $holdingCost);
        
        return [
            'safety_stock' => ceil($safetyStock),
            'reorder_point' => ceil($reorderPoint),
            'order_quantity' => ceil($eoq),
            'max_stock' => ceil($reorderPoint + $eoq),
            'avg_daily_demand' => $avgDemand
        ];
    }
}
```

## Lessons Learned - Iteration 2

### Key Insights:
1. **Hybrid Approaches Work Best**: Combining collaborative and content-based filtering provides better recommendations
2. **Language Complexity**: Arabic NLP requires special handling for morphology and dialects
3. **Real-time Requirements**: Caching and pre-computation essential for responsive AI features
4. **Data Quality Matters**: AI models only as good as the data - need robust data validation
5. **Explainability**: Users want to understand why products are recommended

### Implementation Challenges:
- Arabic text normalization and stemming complexity
- Balancing recommendation accuracy vs diversity
- Managing computational resources for real-time predictions
- Handling cold-start problem for new users/products
- Integrating multiple AI services without latency issues

### Best Practices:
- Use Redis for caching AI predictions and feature vectors
- Implement fallback mechanisms when AI services fail
- A/B test different recommendation algorithms
- Monitor prediction accuracy and adjust models regularly
- Provide manual override options for AI decisions

### Performance Optimizations:
- Pre-compute recommendation scores during off-peak hours
- Use approximate algorithms for similarity calculations
- Implement request batching for AI services
- Cache NLP processing results for common queries
- Use GPU acceleration for deep learning models where applicable