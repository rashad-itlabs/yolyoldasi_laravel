<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | Böyümə funksiyalarının tələb etdiyi sütunlar. Hamısı bir miqrasiyadadır,
 | çünki tək-tək heç biri müstəqil məna daşımır və bir deploy-da qalxır.
 |
 |  rides.women_only      — yalnız qadın sərnişin qəbul edən səfər
 |  rides.boosted_until   — axtarışda yuxarı çıxma müddəti (dəvət mükafatı)
 |  driver_profiles.boost_until — sürücünün qazandığı boost ehtiyatı
 |  users.referral_code   — dəvət kodu (hesab yaranan kimi doldurulur)
 |  users.referred_by     — kimin dəvəti ilə gəldiyi
 |  user_stats.driver_requests_total / decided_count / decision_minutes
 |                        — "adətən 2 saat ərzində cavab verir" statistikası
 |  cities.lat / lng      — məsafə və təklif olunan qiymət üçün
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hər blok öz sütununun yoxluğunu yoxlayır. Səbəb konkretdir: sütunlar
        // əlavə olunandan sonra aşağıdakı geri-doldurma addımı sınsa, miqrasiya
        // `migrations` cədvəlinə yazılmır və növbəti cəhd sütunları **yenidən**
        // əlavə etməyə çalışıb "duplicate column" verir. Yarımçıq qalmış
        // miqrasiyanı əl ilə təmizləmək istismarda ən pis işdir.
        Schema::table('rides', function (Blueprint $table) {
            if (! Schema::hasColumn('rides', 'women_only')) {
                // Qadın sərnişinlər üçün ən böyük maneə tanımadığı kişi sürücü
                // ilə tək yola çıxmaqdır. Sütun sürücünün öz seçimidir;
                // qadağanı `BookingController` tətbiq edir.
                $table->boolean('women_only')->default(false)->after('instant_booking');
            }

            if (! Schema::hasColumn('rides', 'boosted_until')) {
                // Elanın verildiyi anda sürücünün boost ehtiyatından götürülür.
                // Sürücü profilində yox, elanda saxlanılır ki, sıralama tək
                // cədvəl üzərindən getsin — hər axtarışda `driver_profiles`-a
                // join çox baha başa gələrdi.
                $table->timestamp('boosted_until')->nullable()->after('women_only');

                $table->index(['status', 'boosted_until']);
            }
        });

        Schema::table('driver_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('driver_profiles', 'boost_until')) {
                // Dəvətlərdən yığılan ehtiyat. Yeni elan veriləndə `rides`-a
                // köçürülür; bitənə qədər hər elan boost alır.
                $table->timestamp('boost_until')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 12)->nullable()->unique()->after('language_code');
            }

            if (! Schema::hasColumn('users', 'referred_by')) {
                // Dəvət edən hesab silinsə də dəvət olunan qalır.
                $table->foreignId('referred_by')->nullable()->after('referral_code')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'referred_at')) {
                $table->timestamp('referred_at')->nullable()->after('referred_by');
            }

            if (! Schema::hasColumn('users', 'referral_rewarded_at')) {
                // Dəvət mükafatı qeydiyyatda yox, dəvət olunan **ilk real
                // səfəri** edəndə verilir — yoxsa bir adam on nömrə ilə
                // qeydiyyatdan keçib özünə boost yığardı. Bu sütun mükafatın
                // bir dəfə verildiyini qeyd edir.
                $table->timestamp('referral_rewarded_at')->nullable()->after('referred_at');
            }
        });

        Schema::table('user_stats', function (Blueprint $table) {
            // Cavab statistikası. Üç sütun saxlanılır, iki rəqəm hesablanır:
            // cavab faizi = decided / requests, orta vaxt = minutes / decided.
            // Hazır faizi saxlamaq olmazdı — hər yeni sorğuda bütün tarixçəni
            // yenidən oxumaq lazım gələrdi.
            if (! Schema::hasColumn('user_stats', 'driver_requests_total')) {
                $table->unsignedInteger('driver_requests_total')->default(0);
            }

            if (! Schema::hasColumn('user_stats', 'driver_decided_count')) {
                $table->unsignedInteger('driver_decided_count')->default(0);
            }

            if (! Schema::hasColumn('user_stats', 'driver_decision_minutes')) {
                $table->unsignedBigInteger('driver_decision_minutes')->default(0);
            }
        });

        Schema::table('cities', function (Blueprint $table) {
            // Məsafə hesabı üçün. Tətbiqdə `az_cities.dart`-da eyni data var;
            // serverə köçürülür ki, qiymət təklifi klientin cədvəlindən asılı
            // qalmasın və köhnə tətbiq versiyaları da düzgün rəqəm alsın.
            if (! Schema::hasColumn('cities', 'lat')) {
                $table->decimal('lat', 9, 6)->nullable();
            }

            if (! Schema::hasColumn('cities', 'lng')) {
                $table->decimal('lng', 9, 6)->nullable();
            }
        });

        // Mövcud hesablara dəvət kodu. Yeni hesablar `User::booted()`-da alır.
        foreach (DB::table('users')->whereNull('referral_code')->pluck('id') as $id) {
            DB::table('users')->where('id', $id)->update([
                'referral_code' => \App\Models\User::generateReferralCode(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropIndex(['status', 'boosted_until']);
            $table->dropColumn(['women_only', 'boosted_until']);
        });

        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn('boost_until');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'referral_code', 'referred_by', 'referred_at', 'referral_rewarded_at',
            ]);
        });

        Schema::table('user_stats', function (Blueprint $table) {
            $table->dropColumn([
                'driver_requests_total',
                'driver_decided_count',
                'driver_decision_minutes',
            ]);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
