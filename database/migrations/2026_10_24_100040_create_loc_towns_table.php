<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LOC_towns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ine_code')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('LOC_districts');

            addCommonColumns($table);
            addAuditColumns($table);
        });

        addUniqueActiveNameIndex('LOC_towns');
    }

    public function down(): void
    {
        Schema::dropIfExists('LOC_towns');
    }
};
