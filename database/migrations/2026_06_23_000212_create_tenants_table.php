<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#000000');
            $table->string('accent_color')->default('#ffffff');
            $table->string('chatbot_name')->default('AI Assistant');
            $table->string('greeting_message')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->json('escalation_keywords')->nullable();
            $table->enum('plan', ['starter', 'growth', 'pro'])->default('starter');
            $table->enum('status', ['trial', 'active', 'suspended'])->default('trial');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
