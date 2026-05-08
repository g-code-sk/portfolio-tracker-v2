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
        Schema::table('securities', function (Blueprint $table): void {
            $table->decimal('current_price', 20, 10)->nullable()->after('isin');
            $table->string('current_price_currency', 3)->nullable()->after('current_price');
            $table->dateTime('current_price_updated_at')->nullable()->after('current_price_currency');
            $table->foreignId('current_data_provider_id')
                ->nullable()
                ->after('current_price_updated_at')
                ->constrained('security_data_providers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('securities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_data_provider_id');
            $table->dropColumn([
                'current_price',
                'current_price_currency',
                'current_price_updated_at',
            ]);
        });
    }
};
