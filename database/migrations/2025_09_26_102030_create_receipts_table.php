<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('receipt_id')->index(); // אפשר גם unique ביחד עם user_id
            $table->string('merchant')->nullable()->index();
            $table->decimal('total_amount', 18, 8)->nullable();
            $table->string('currency', 8)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('vat_amount', 18, 8)->nullable();
            $table->string('s3_key')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('transaction_group_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id','receipt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
