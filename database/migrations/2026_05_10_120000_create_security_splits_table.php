<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_splits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('security_id')->constrained()->cascadeOnDelete();
            $table->foreignId('security_data_provider_id')->constrained('security_data_providers')->cascadeOnDelete();
            $table->date('effective_on');
            $table->unsignedInteger('ratio_numerator')->nullable();
            $table->unsignedInteger('ratio_denominator')->nullable();
            $table->string('raw_ratio')->nullable();
            $table->timestamps();

            $table->unique(['security_id', 'security_data_provider_id', 'effective_on'], 'security_splits_security_provider_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_splits');
    }
};
