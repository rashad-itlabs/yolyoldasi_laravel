<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | Admin bildirişinin mətni 240 simvoldan 1000-ə qalxır.
 |
 | Bildiriş siyahısı mətni artıq kəsmir, ona görə uzun elan yazmağın mənası
 | var. Sütun VARCHAR(500) idi — 1000 simvola sığmır, TEXT olur.
 |
 | Birbaşa MODIFY, `change()` yox: bu layihənin digər sütun dəyişiklikləri də
 | belədir (bax: 2026_09_22_000004).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE push_messages MODIFY content TEXT NOT NULL');
    }

    public function down(): void
    {
        // Geri daralmadan əvvəl uzun mətnlər kəsilir, yoxsa MODIFY xəta verər.
        DB::statement('UPDATE push_messages SET content = LEFT(content, 500)');
        DB::statement('ALTER TABLE push_messages MODIFY content VARCHAR(500) NOT NULL');
    }
};
