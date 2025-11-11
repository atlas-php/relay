<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('atlas-relay.tables.relay_log_archives', 'atlas_relay_log_archives');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('relay_id')->index();
            $table->string('stage', 64);
            $table->string('action', 64);
            $table->string('status', 32);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('archived_at')->nullable();
        });
    }

    public function down(): void
    {
        $tableName = config('atlas-relay.tables.relay_log_archives', 'atlas_relay_log_archives');

        Schema::dropIfExists($tableName);
    }
};
