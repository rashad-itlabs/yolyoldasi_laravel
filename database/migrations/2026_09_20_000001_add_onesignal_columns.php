<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | Push OneSignal üzərindən gedir. İki dəyişiklik lazımdır:
 |
 | 1. `device_tokens.token` artıq FCM tokeni deyil — OneSignal subscription
 |    id-sidir. Formasına baxıb ayırd etmək mümkün deyil, ona görə `provider`
 |    sütunu hansı olduğunu yazır. Köhnə sətirlər üçün 'fcm' saxlanılır.
 |
 | 2. Əsas ünvanlama cihaz yox, hesab üzrədir: tətbiq girişdən sonra
 |    `OneSignal.login(user_id)` çağırır və server "42 nömrəli istifadəçiyə
 |    göndər" deyə bilir. Bu, cihaz siyahısından etibarlıdır — token
 |    dəyişəndə, POST alınmayanda və ya tətbiq yenidən quraşdırılanda cihaz
 |    siyahıdan düşür, alias isə qalır. `users.onesignal_external_id` həmin
 |    alias-ın qeydə alındığını göstərir; boşdursa, subscription id-lərə
 |    qayıdırıq.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Enum yox, string: üçüncü bir provayder əlavə edilsə miqrasiya
            // yox, sadəcə yeni dəyər lazım olsun.
            $table->string('provider', 16)->default('onesignal')->after('platform');

            // Cihazın hansı hesabla OneSignal-a login olduğu. `users`-dakı
            // sütunla eyni dəyər olur, amma burada cihaz səviyyəsindədir —
            // köhnə hesabla qalmış cihazı görmək üçün.
            $table->string('external_id', 64)->nullable()->after('provider');

            $table->index(['provider', 'user_id']);
        });

        // Mövcud sətirlər OneSignal-dan əvvəl yazılıb.
        DB::table('device_tokens')->update(['provider' => 'fcm']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('onesignal_external_id', 64)->nullable()->unique()->after('language_code');
            $table->timestamp('onesignal_linked_at')->nullable()->after('onesignal_external_id');
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropIndex(['provider', 'user_id']);
            $table->dropColumn(['provider', 'external_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onesignal_external_id', 'onesignal_linked_at']);
        });
    }
};
