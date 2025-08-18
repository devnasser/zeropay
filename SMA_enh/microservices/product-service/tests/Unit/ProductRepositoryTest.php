<?php

namespace Tests\Unit;

use Tests\TestCase;
use ProductService\Repositories\ProductRepository;
use ProductService\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;
    protected $elasticsearchMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->elasticsearchMock = Mockery::mock('Elasticsearch\Client');
        $this->repository = new ProductRepository();
        $this->repository->setElasticsearchClient($this->elasticsearchMock);
    }

    public function test_search_products_with_filters()
    {
        // Arrange
        $searchQuery = 'فلتر زيت';
        $filters = [
            'category' => 'filters',
            'brand' => 'bosch',
            'price_min' => 50,
            'price_max' => 200,
            'in_stock' => true
        ];

        $expectedResponse = [
            'hits' => [
                'total' => ['value' => 10],
                'hits' => [
                    [
                        '_source' => [
                            'id' => '1',
                            'name' => ['ar' => 'فلتر زيت بوش'],
                            'price' => 75
                        ]
                    ]
                ]
            ],
            'aggregations' => [
                'categories' => ['buckets' => []],
                'brands' => ['buckets' => []],
                'price_ranges' => ['buckets' => []]
            ]
        ];

        $this->elasticsearchMock
            ->shouldReceive('search')
            ->once()
            ->with(Mockery::on(function ($params) use ($searchQuery) {
                return $params['index'] === 'products' &&
                       str_contains($params['body']['query']['bool']['must'][0]['multi_match']['query'], $searchQuery);
            }))
            ->andReturn($expectedResponse);

        // Act
        $result = $this->repository->searchProducts($searchQuery, $filters);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('aggregations', $result);
        $this->assertEquals(10, $result['total']);
    }

    public function test_get_recommendations_uses_cache()
    {
        // Arrange
        $userId = '123';
        $context = ['category' => 'brakes'];
        $cacheKey = "recommendations:$userId:" . md5(json_encode($context));
        
        $expectedProducts = Product::factory()->count(5)->create();

        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, 300, Mockery::any())
            ->andReturn($expectedProducts);

        // Act
        $result = $this->repository->getRecommendations($userId, $context);

        // Assert
        $this->assertCount(5, $result);
        $this->assertInstanceOf(Product::class, $result->first());
    }

    public function test_dynamic_pricing_calculation()
    {
        // Arrange
        $product = Product::factory()->create([
            'price' => 100,
            'cost' => 60,
            'quantity' => 5, // Low stock
            'view_count' => 1000, // High demand
        ]);

        $pricingServiceMock = Mockery::mock('ProductService\Services\PricingService');
        $pricingServiceMock
            ->shouldReceive('calculateDynamicPrice')
            ->with($product)
            ->andReturn(120); // Increased price due to high demand and low stock

        $this->app->instance('PricingService', $pricingServiceMock);

        // Act
        $dynamicPrice = $product->getDynamicPrice();

        // Assert
        $this->assertEquals(120, $dynamicPrice);
        $this->assertGreaterThan($product->price, $dynamicPrice);
    }
}
