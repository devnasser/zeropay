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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['customer', 'shop_owner', 'technician', 'driver', 'admin'])->default('customer')->after('email');
            $table->string('phone')->unique()->nullable()->after('type');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female'])->nullable()->after('whatsapp');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('national_id')->nullable()->after('birth_date');
            $table->string('avatar')->nullable()->after('national_id');
            $table->enum('preferred_language', ['ar', 'en', 'ur', 'fr', 'fa'])->default('ar')->after('avatar');
            $table->boolean('enable_voice_interface')->default(false)->after('preferred_language');
            $table->boolean('is_active')->default(true)->after('enable_voice_interface');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->timestamp('phone_verified_at')->nullable()->after('is_verified');
            $table->timestamp('last_login_at')->nullable()->after('phone_verified_at');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->json('settings')->nullable()->after('last_login_ip');
            $table->decimal('wallet_balance', 10, 2)->default(0)->after('settings');
            $table->integer('loyalty_points')->default(0)->after('wallet_balance');
            
            $table->index(['type', 'is_active']);
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'phone', 'whatsapp', 'gender', 'birth_date',
                'national_id', 'avatar', 'preferred_language', 'enable_voice_interface',
                'is_active', 'is_verified', 'phone_verified_at', 'last_login_at',
                'last_login_ip', 'settings', 'wallet_balance', 'loyalty_points'
            ]);
        });
    }
};
