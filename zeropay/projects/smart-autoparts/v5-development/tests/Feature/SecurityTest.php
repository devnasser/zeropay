<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_set()
    {
        $response = $this->get('/');
        
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy');
        $response->assertHeaderMissing('X-Powered-By');
    }
    
    public function test_sql_injection_protection()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        $response = $this->get('/products?search=' . $maliciousInput);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => 1]); // Table still exists
    }
    
    public function test_xss_protection()
    {
        $user = User::factory()->create();
        $xssPayload = '<script>alert("XSS")</script>';
        
        $response = $this->actingAs($user)->post('/products', [
            'name' => $xssPayload,
            'description' => $xssPayload
        ]);
        
        $response->assertDontSee('<script>', false);
    }
    
    public function test_csrf_protection()
    {
        $user = User::factory()->create();
        
        // Without CSRF token
        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => 1,
            'quantity' => 1
        ], ['Accept' => 'application/json']);
        
        $response->assertStatus(419); // CSRF token mismatch
    }
    
    public function test_rate_limiting()
    {
        // Test API rate limiting
        for ($i = 0; $i < 65; $i++) {
            $response = $this->post('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }
        
        $response->assertStatus(429); // Too Many Requests
    }
    
    public function test_password_hashing()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123')
        ]);
        
        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(password_verify('secret123', $user->password));
    }
    
    public function test_secure_file_upload()
    {
        $user = User::factory()->create();
        
        // Test malicious file upload
        $file = \Illuminate\Http\UploadedFile::fake()->create('malicious.php', 100);
        
        $response = $this->actingAs($user)->post('/upload', [
            'file' => $file
        ]);
        
        // Should reject PHP files
        $response->assertSessionHasErrors('file');
    }
    
    public function test_authentication_security()
    {
        $user = User::factory()->create();
        
        // Test session fixation protection
        $this->get('/login');
        $oldSessionId = session()->getId();
        
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);
        
        $this->assertNotEquals($oldSessionId, session()->getId());
    }
}