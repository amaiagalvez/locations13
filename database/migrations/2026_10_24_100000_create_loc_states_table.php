<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LOC_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ine_code')->nullable();

            addCommonColumns($table);
            addAuditColumns($table);
        });

        addUniqueActiveNameIndex('LOC_states');
        addUniqueActiveColumnIndex('LOC_states', 'ine_code');
    }

    public function down(): void
    {
        Schema::dropIfExists('LOC_states');
    }
};
