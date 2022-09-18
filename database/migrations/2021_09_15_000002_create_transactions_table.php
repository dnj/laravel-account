<?php

use dnj\Account\ModelHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ModelHelpers;

    public function up(): void
    {
        Schema::create('accounts_transactions', function (Blueprint $table) {
            $floatScale = $this->getFloatScale();

            $table->id();
            $table->foreignId('from_id');
            $table->foreignId('to_id');
            $table->decimal('amount', 10 + $floatScale, $floatScale);
            $table->timestamps();
            $table->json('meta')->nullable();

            $table->foreign('from_id')
                ->references('id')
                ->on("accounts")
                ->onDelete('cascade');

            $table->foreign('to_id')
                ->references('id')
                ->on("accounts")
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_transactions');
    }

};
