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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->json('name'); // Multi-language
            $table->json('description')->nullable();
            $table->json('specifications')->nullable(); // Technical specs
            $table->string('brand')->nullable()->index();
            $table->string('model')->nullable();
            $table->string('year_from')->nullable();
            $table->string('year_to')->nullable();
            $table->json('compatible_cars')->nullable(); // List of compatible car models
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('min_quantity')->default(1);
            $table->string('unit')->default('piece'); // piece, set, box
            $table->decimal('weight', 8, 3)->nullable(); // in kg
            $table->json('dimensions')->nullable(); // {"length": 10, "width": 5, "height": 3}
            $table->json('images')->nullable(); // Array of image paths
            $table->string('video_url')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('sales_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(true);
            $table->boolean('is_used')->default(false);
            $table->boolean('is_original')->default(true);
            $table->enum('condition', ['new', 'used', 'refurbished'])->default('new');
            $table->string('warranty_period')->nullable();
            $table->json('tags')->nullable();
            $table->json('seo')->nullable(); // SEO metadata
            $table->timestamps();
            
            $table->index(['shop_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index(['brand', 'model']);
            $table->index(['price', 'is_active']);
            $table->index(['rating', 'sales_count']);
            // Note: SQLite doesn't support fullText, will implement search using Laravel Scout
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
