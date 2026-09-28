<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | Oxunmuş söhbətlərin `newMessage` bildirişləri oxunmuş olur.
 |
 | İndiyə qədər `POST /conversations/{id}/read` yalnız söhbətin sayğacını
 | sıfırlayırdı, bildiriş sətirlərinə toxunmurdu. Nəticədə mesajları çoxdan
 | oxumuş istifadəçinin zəngində hələ də həmin mesajların rəqəmi görünür.
 | `MessageService::markRead()` artıq bildirişləri də oxunmuş edir; bu
 | miqrasiya yalnız o düzəlişdən əvvəl yığılmış qalığı təmizləyir.
 |
 | Bildiriş hansı mesaja aid olduğunu saxlamır, ona görə meyar vaxtdır:
 | bildiriş iştirakçının söhbəti son oxuduğu andan (`last_read_at`) gec deyilsə,
 | mesajı oxunub. `read_at` də həmin an olur. Söhbəti heç açmamış iştirakçının
 | (`last_read_at` null) bildirişləri olduğu kimi qalır.
 |
 | Vaxt saniyə dəqiqliyindədir. Oxunmadan sonra, amma eyni saniyədə gəlmiş
 | mesaj `last_read_at`-a bərabər görünür — onu oxunmuş saymamaq üçün bərabərlik
 | yalnız söhbətdə oxunmamış mesaj qalmayanda (`unread_count = 0`) qəbul olunur.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE notifications n
            JOIN conversation_participants cp
              ON cp.conversation_id = n.conversation_id
             AND cp.user_id = n.user_id
            SET n.read_at = cp.last_read_at
            WHERE n.type = 'newMessage'
              AND n.read_at IS NULL
              AND cp.last_read_at IS NOT NULL
              AND (
                n.created_at < cp.last_read_at
                OR (n.created_at = cp.last_read_at AND cp.unread_count = 0)
              )
            SQL);
    }

    public function down(): void
    {
        // Geri qaytarılmır: hansı sətirlərin miqrasiyadan əvvəl oxunmamış
        // olduğu heç yerdə saxlanmır. Yeni `markRead()` də `read_at`-a eyni
        // `last_read_at` dəyərini yazır, ona görə miqrasiyanın işarələdiyi
        // sətirləri sonradan ayırmaq olmur. Oxunmuş mesajın bildirişini
        // yenidən oxunmamış etməyin də mənası yoxdur.
    }
};
