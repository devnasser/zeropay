<?php

namespace Tests\Performance;

use Tests\TestCase;

class LoadTest extends TestCase
{
    public function test_homepage_load_time()
    {
        $start = microtime(true);
        $response = $this->get('/');
        $end = microtime(true);
        
        $loadTime = ($end - $start) * 1000; // milliseconds
        
        $response->assertStatus(200);
        $this->assertLessThan(1000, $loadTime); // Less than 1 second
    }
    
    public function test_api_response_time()
    {
        $start = microtime(true);
        $response = $this->get('/api/products');
        $end = microtime(true);
        
        $responseTime = ($end - $start) * 1000;
        
        $this->assertLessThan(200, $responseTime); // Less than 200ms
    }
}