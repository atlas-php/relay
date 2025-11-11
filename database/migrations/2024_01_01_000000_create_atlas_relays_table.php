<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atlas_relays', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('request_source')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 32)->default('queued');
            $table->string('mode', 32)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('failure_reason')->nullable();
            $table->boolean('is_retry')->default(false);
            $table->unsignedInteger('retry_seconds')->nullable();
            $table->unsignedInteger('retry_max_attempts')->nullable();
            $table->boolean('is_delay')->default(false);
            $table->unsignedInteger('delay_seconds')->nullable();
            $table->unsignedInteger('timeout_seconds')->nullable();
            $table->unsignedInteger('http_timeout_seconds')->nullable();
            $table->timestamp('retry_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('retry_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atlas_relays');
    }
};
