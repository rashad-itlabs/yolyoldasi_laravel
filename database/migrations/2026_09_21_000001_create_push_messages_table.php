<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | Admin panelindən əl ilə göndərilən bildirişlər.
 |
 | İki yerə yazılır: `push_messages` — adminin göndəriş tarixçəsi (bir sətir =
 | bir göndəriş), `notifications` — hər alıcı üçün ayrı sətir ki, elan tətbiqin
 | bildiriş siyahısında da görünsün. İkincisi üçün `type` enum-una iki yeni
 | dəyər əlavə olunur.
 */
return new class extends Migration
{
    /** Enum-un əvvəlki dəyərləri — geri qayıtma üçün. */
    private const OLD_TYPES = [
        'bookingRequested', 'bookingConfirmed', 'bookingRejected', 'bookingCancelled',
        'rideReminder', 'rideCancelled', 'newMessage', 'reviewRequest',
        'documentsApproved', 'documentsRejected',
    ];

    private const NEW_TYPES = ['adminMessage', 'adminMarketing'];

    public function up(): void
    {
        Schema::create('push_messages', function (Blueprint $table) {
            $table->id();

            // Admin silinsə də göndəriş tarixçəsi qalmalıdır.
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();

            // Enum yox, string: yeni növ əlavə etmək miqrasiya tələb etməsin.
            $table->string('type', 32);
            $table->string('target', 8); // all | user

            // Yalnız `target=user` olanda dolur.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('heading');
            $table->string('content', 500);

            // Göndəriş anındakı alıcı sayı. Sonradan hesablamaq olmaz:
            // istifadəçi tənzimləməsini dəyişə, hesabını silə bilər.
            $table->unsignedInteger('recipients')->default(0);
            $table->boolean('delivered')->default(false);
            $table->string('error')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
        });

        // `enum` sütununu Laravel-in `change()` metodu MySQL-də etibarlı
        // dəyişmir — birbaşa MODIFY yazırıq.
        DB::statement($this->enumSql(array_merge(self::OLD_TYPES, self::NEW_TYPES)));

        Schema::table('notifications', function (Blueprint $table) {
            // Hansı göndərişdən gəldiyi — panel oxunma sayını buradan hesablayır.
            $table->foreignId('push_message_id')->nullable()->after('type')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['push_message_id']);
            $table->dropColumn('push_message_id');
        });

        // Enum daralmadan əvvəl yeni dəyərli sətirlər getməlidir, yoxsa
        // MODIFY onları boş sətrə çevirir.
        DB::table('notifications')->whereIn('type', self::NEW_TYPES)->delete();

        DB::statement($this->enumSql(self::OLD_TYPES));

        Schema::dropIfExists('push_messages');
    }

    private function enumSql(array $types): string
    {
        $values = implode(', ', array_map(fn ($type) => "'".$type."'", $types));

        return "ALTER TABLE notifications MODIFY type ENUM({$values}) NOT NULL";
    }
};
