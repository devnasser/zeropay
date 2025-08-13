<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Inventory Reservations table
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->string('reference');
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['reference', 'expires_at']);
            $table->index('product_id');
        });
        
        // Inventory Logs table
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('type', 50); // sold, reserved, returned, adjustment, etc.
            $table->integer('quantity'); // Can be negative
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'type']);
            $table->index('created_at');
        });
        
        // API Keys table
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('key');
            $table->index(['is_active', 'expires_at']);
        });
        
        // Performance Metrics table
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric', 100);
            $table->float('value');
            $table->json('tags')->nullable();
            $table->timestamp('recorded_at');
            $table->index(['metric', 'recorded_at']);
            $table->index('recorded_at');
        });
        
        // Add columns to products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'available_quantity')) {
                $table->integer('available_quantity')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('products', 'views_count')) {
                $table->integer('views_count')->default(0);
            }
            if (!Schema::hasColumn('products', 'cost')) {
                $table->decimal('cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
                $table->index('is_featured');
            }
            if (!Schema::hasColumn('products', 'priority')) {
                $table->integer('priority')->default(0);
            }
            
            // Add composite indexes
            $table->index(['shop_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index(['brand', 'model']);
        });
        
        // Add columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'processed_at')) {
                $table->timestamp('processed_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'failure_reason')) {
                $table->text('failure_reason')->nullable();
            }
            if (!Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }
            if (!Schema::hasColumn('orders', 'tracking_code')) {
                $table->string('tracking_code')->nullable()->unique();
            }
            if (!Schema::hasColumn('orders', 'estimated_delivery_date')) {
                $table->date('estimated_delivery_date')->nullable();
            }
            
            // Add indexes
            $table->index(['user_id', 'status']);
            $table->index(['shop_id', 'created_at']);
            $table->index('payment_status');
        });
        
        // Push tokens table
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token');
            $table->string('platform', 20); // ios, android, web
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'token']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('inventory_reservations');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['available_quantity', 'views_count', 'cost', 'is_featured', 'priority']);
            $table->dropIndex(['shop_id', 'is_active']);
            $table->dropIndex(['category_id', 'is_active']);
            $table->dropIndex(['brand', 'model']);
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['processed_at', 'failure_reason', 'payment_reference', 'tracking_code', 'estimated_delivery_date']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['shop_id', 'created_at']);
            $table->dropIndex(['payment_status']);
        });
    }
};