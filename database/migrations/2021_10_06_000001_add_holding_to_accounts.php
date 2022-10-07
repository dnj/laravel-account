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
        Schema::table('accounts', function (Blueprint $table) {
            $floatScale = $this->getFloatScale();
            $table->decimal('holding', 10 + $floatScale, $floatScale)
                ->after("balance")
                ->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn("holding");
        });
    }

};
