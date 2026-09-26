<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Sərnişin tələb elanları.
 |
 | İndiyə qədər bazarda inventarı yalnız sürücü yaradırdı: boş axtarış nəticəsi
 | alan sərnişinin edə biləcəyi heç nə yox idi və çıxıb gedirdi. Bu cədvəl
 | həmin anı tutur. Bir sətir eyni anda iki iş görür:
 |
 |   1. sürücüyə göstəriləcək **adlı-ünvanlı tələbdir** — "cümə 18:00, Bakı→Şəki,
 |      2 yer" sətri "43 nəfər axtarıb" statistikasından qat-qat inandırıcıdır;
 |   2. uyğun elan yaranan kimi sərnişini geri çağıran bildirişin **ünvanıdır**.
 |
 | `flexible_days` matçı bir gündən geniş edir: rayona gedən adam üçün cümə və
 | şənbə çox vaxt fərq etmir, amma dəqiq tarix tələb etsək matç sayı kəskin
 | azalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('to_city_id')->constrained('cities')->cascadeOnDelete();

            $table->date('wanted_date');

            // 0 = yalnız həmin gün. 1–3 = ±gün. Matç pəncərəsini genişləndirir.
            $table->unsignedTinyInteger('flexible_days')->default(0);

            $table->unsignedTinyInteger('seats')->default(1);
            $table->string('note', 300)->nullable();

            // open → sürücülərə görünür və matç axtarılır
            // fulfilled → sərnişin uyğun səfəri bron edib
            // cancelled → sərnişin özü bağlayıb
            // expired → tarix keçib (planlaşdırılmış əmr bağlayır)
            $table->string('status', 12)->default('open');

            // Sonuncu göstərilən uyğun səfər — bildirişin deep-link hədəfi.
            $table->foreignId('matched_ride_id')->nullable()
                ->constrained('rides')->nullOnDelete();

            // Sərnişinə ən son nə vaxt "səfər tapıldı" deyildiyi. Hər yeni uyğun
            // elanda push getsə, bir marşrutda beş elan verən sürücü sərnişinin
            // telefonunu beş dəfə oyadardı — `RideMatchService` bu sütuna baxıb
            // eyni tələb üçün gündə bir dəfədən çox xəbər vermir.
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();

            // Eyni adam eyni marşrut və tarix üçün ikinci sətir yarada bilməz —
            // əks halda sürücünün siyahısı təkrarlardan ibarət olardı.
            $table->unique(['user_id', 'from_city_id', 'to_city_id', 'wanted_date'], 'ride_requests_unique_route');

            // Sürücünün "mənim marşrutumda tələb" sorğusu bu indeksə düşür.
            $table->index(['from_city_id', 'to_city_id', 'wanted_date', 'status'], 'ride_requests_route_idx');

            // Elan yaradılanda matç axtarışı və istifadəçinin öz siyahısı.
            $table->index(['status', 'wanted_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_requests');
    }
};
