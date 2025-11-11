<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('atlas-relay.tables.relay_logs', 'atlas_relay_logs');

        Schema::create($tableName, function (Blueprint $table): void {
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
        $tableName = config('atlas-relay.tables.relay_logs', 'atlas_relay_logs');

        Schema::dropIfExists($tableName);
    }
};
