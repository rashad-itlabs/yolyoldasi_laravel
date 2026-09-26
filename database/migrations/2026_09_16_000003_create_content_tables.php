<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sayt məzmununu blade fayllarından bazaya köçürür: əlaqə məlumatları,
 * hüquqi səhifələr və FAQ artıq admin panelindən idarə olunur.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Açar-dəyər: əlaqə e-poçtları, ünvan, sosial linklər.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->string('group', 40)->default('general');
            $table->timestamp('updated_at')->nullable();

            $table->index('group');
        });

        // Hüquqi və məlumat səhifələri: terms, privacy, about.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();

            $table->string('title');
            $table->text('lead')->nullable();
            $table->longText('body')->nullable();

            $table->string('meta_description', 300)->nullable();

            // Səhifədə göstərilən "Son yenilənmə" tarixi — avtomatik
            // updated_at-dan ayrıdır, çünki kiçik düzəliş tarixi sıfırlamamalıdır.
            $table->date('content_updated_at')->nullable();

            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 300);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('site_settings');
    }
};
