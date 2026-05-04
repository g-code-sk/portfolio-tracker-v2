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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('security_id')->constrained('securities');
            $table->foreignId('type_id')->constrained('security_types');
            $table->string('external_transaction_id');
            $table->decimal('number_of_shares', 20, 10);
            $table->decimal('price_per_share', 20, 10);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->timestamps();

            $table->unique(['external_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
