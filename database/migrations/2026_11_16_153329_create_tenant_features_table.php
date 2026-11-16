<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::create('tenant_features', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');

            $table->string('feature_key', 100);

            $table->boolean('is_enabled')->default(false);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'feature_key'],
                'tenant_feature_unique'
            );

            $table->index('tenant_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_features');
    }
};
