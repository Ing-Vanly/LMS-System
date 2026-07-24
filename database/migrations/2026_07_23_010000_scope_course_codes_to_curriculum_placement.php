<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique([
                'program_id',
                'code',
                'year_level',
                'semester_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique([
                'program_id',
                'code',
                'year_level',
                'semester_number',
            ]);
            $table->unique('code');
        });
    }
};
