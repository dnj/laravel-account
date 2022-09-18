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
        Schema::create('accounts', function (Blueprint $table) {
            $floatScale = $this->getFloatScale();

            $table->id();
            $table->string('title', 255);
            $table->foreignId('user_id')->nullable();
            $table->foreignId('currency_id');
            $table->timestamps();
            $table->decimal('balance', 10 + $floatScale, $floatScale);
            $table->boolean('can_send');
            $table->boolean('can_receive');
            $table->json('meta')->nullable();
            $table->unsignedTinyInteger("status")->index();

            $table->foreign('currency_id')
                ->references('id')
                ->on($this->getCurrencyTable());
            
            $userTable = $this->getUserTable();
            if ($userTable) {
                $table->foreign("user_id")
                    ->references("id")
                    ->on($userTable);
            } else {
                $table->index("user_id");
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }

};
