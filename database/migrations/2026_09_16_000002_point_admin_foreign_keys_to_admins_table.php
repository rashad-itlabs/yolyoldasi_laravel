<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin paneli ayrıca `admins` cədvəli ilə işləyir, ona görə admini göstərən
 * üç sütun artıq `users`-a yox, `admins`-ə baxmalıdır.
 *
 * Üç sütunun hamısı bu miqrasiya yazılanda boş idi (heç bir sənəd
 * yoxlanılmamış, heç bir şikayət bağlanmamışdı), ona görə məlumat köçürmə
 * lazım deyil — yalnız açar dəyişir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropForeign('admin_activity_logs_admin_id_foreign');
            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
        });

        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropForeign('driver_profiles_reviewed_by_foreign');
            $table->foreign('reviewed_by')->references('id')->on('admins')->nullOnDelete();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign('reports_resolved_by_foreign');
            $table->foreign('resolved_by')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
