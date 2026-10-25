<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Postal codes are rows of their town: no listing, no form of their own, and the rows go with the
 * town whichever way it is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LOC_postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code');

            // No cascade here on purpose: the town's model hooks decide when the codes are taken
            // away, never a foreign key that runs before the guards have had their turn.
            $table->foreignId('town_id')->constrained('LOC_towns');
            $table->index(['town_id']);

            addCommonColumns($table);
            addAuditColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LOC_postal_codes');
    }
};
