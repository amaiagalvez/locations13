<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LOC_neighbourhoods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ine_code')->nullable();
            $table->foreignId('town_id')->constrained('LOC_towns');

            addCommonColumns($table);
            addAuditColumns($table);
        });

        // The name is unique inside the town, not globally: two towns may both have an "Old town".
        addUniqueActiveColumnIndex('LOC_neighbourhoods', 'name', ['town_id']);
    }

    public function down(): void
    {
        Schema::dropIfExists('LOC_neighbourhoods');
    }
};
