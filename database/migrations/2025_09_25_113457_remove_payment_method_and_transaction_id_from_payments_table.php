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
        Schema::table('payments', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex('payments_transaction_id_index');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Then drop the columns
            $table->dropColumn(['payment_method', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->default('credit_card');
            $table->string('transaction_id')->nullable();
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->index('transaction_id');
        });
    }
};