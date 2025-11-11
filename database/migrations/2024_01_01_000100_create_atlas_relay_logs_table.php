<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atlas_relay_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('relay_id')->index();
            $table->string('stage', 64);
            $table->string('action', 64);
            $table->string('status', 32);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atlas_relay_logs');
    }
};
