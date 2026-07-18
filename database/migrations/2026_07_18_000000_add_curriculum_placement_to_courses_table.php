<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('year_level')->default(1)->after('description');
            $table->unsignedTinyInteger('semester_number')->default(1)->after('year_level');
            $table->index(['program_id', 'year_level', 'semester_number']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['program_id', 'year_level', 'semester_number']);
            $table->dropColumn(['year_level', 'semester_number']);
        });
    }
};
