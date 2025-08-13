<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Composite indexes for better query performance
        
        // Orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['shop_id', 'status', 'created_at'], 'idx_shop_status_date');
            $table->index(['user_id', 'status', 'created_at'], 'idx_user_status_date');
            $table->index(['payment_status', 'created_at'], 'idx_payment_date');
        });
        
        // Products table
        Schema::table('products', function (Blueprint $table) {
            $table->index(['shop_id', 'is_active', 'created_at'], 'idx_shop_active_date');
            $table->index(['category_id', 'is_active', 'rating'], 'idx_category_active_rating');
            $table->index(['brand', 'model', 'year_from', 'year_to'], 'idx_vehicle_compatibility');
            $table->index(['is_featured', 'is_active', 'created_at'], 'idx_featured_active_date');
        });
        
        // Order items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'product_id'], 'idx_order_product');
            $table->index(['product_id', 'created_at'], 'idx_product_date');
        });
        
        // Reviews table
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'rating', 'created_at'], 'idx_product_rating_date');
            $table->index(['shop_id', 'rating', 'created_at'], 'idx_shop_rating_date');
            $table->index(['user_id', 'created_at'], 'idx_user_date');
        });
        
        // Vendors table
        Schema::table('vendors', function (Blueprint $table) {
            $table->index(['status', 'subscription_plan'], 'idx_status_plan');
            $table->index(['subscription_expires_at'], 'idx_subscription_expiry');
        });
        
        // Add partial indexes for SQLite (commenting out as SQLite doesn't support)
        // DB::statement('CREATE INDEX idx_active_products ON products(shop_id) WHERE is_active = 1');
        // DB::statement('CREATE INDEX idx_pending_orders ON orders(shop_id) WHERE status = "pending"');
        
        // Optimize existing queries with hints
        DB::statement('ANALYZE');
    }
    
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_shop_status_date');
            $table->dropIndex('idx_user_status_date');
            $table->dropIndex('idx_payment_date');
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_shop_active_date');
            $table->dropIndex('idx_category_active_rating');
            $table->dropIndex('idx_vehicle_compatibility');
            $table->dropIndex('idx_featured_active_date');
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_product');
            $table->dropIndex('idx_product_date');
        });
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_product_rating_date');
            $table->dropIndex('idx_shop_rating_date');
            $table->dropIndex('idx_user_date');
        });
        
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('idx_status_plan');
            $table->dropIndex('idx_subscription_expiry');
        });
    }
};