<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | Şəhər koordinatları.
 |
 | Mənbə tətbiqdəki `lib/core/constants/az_cities.dart` cədvəlidir — indiyə
 | qədər məsafə və təklif olunan qiymət yalnız klientdə hesablanırdı. Serverə
 | köçürülməsinin səbəbi budur: qiymət təklifi artıq real marşrut tarixçəsindən
 | çıxarılır və köhnə tətbiq versiyaları da eyni rəqəmi almalıdır.
 |
 | Uyğunlaşdırma **ada görədir**, id-yə görə yox: `cities` cədvəlinin id-ləri
 | serverin öz ardıcıllığıdır və Dart cədvəli ilə üst-üstə düşmür. Adı
 | tapılmayan sətir toxunulmadan qalır (koordinatı null olur) — bu, sistemin
 | heç bir yerini sındırmır, sadəcə həmin marşrutda məsafəyə görə təklif işləmir.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (self::COORDINATES as [$name, $lat, $lng]) {
            DB::table('cities')
                ->where('name', $name)
                ->update(['lat' => $lat, 'lng' => $lng]);
        }
    }

    public function down(): void
    {
        DB::table('cities')->update(['lat' => null, 'lng' => null]);
    }

    /** @var array<int, array{0: string, 1: float, 2: float}> */
    private const COORDINATES = [
        ['Bakı', 40.4093, 49.8671],
        ['Gəncə', 40.6828, 46.3606],
        ['Sumqayıt', 40.5897, 49.6686],
        ['Mingəçevir', 40.7700, 47.0489],
        ['Lənkəran', 38.7529, 48.8475],
        ['Şəki', 41.1919, 47.1706],
        ['Qəbələ', 40.9814, 47.8481],
        ['Quba', 41.3606, 48.5128],
        ['Naxçıvan', 39.2090, 45.4122],
        ['Şirvan', 39.9308, 48.9292],
        ['Yevlax', 40.6172, 47.1500],
        ['Şamaxı', 40.6319, 48.6417],
        ['Xırdalan', 40.4536, 49.7553],
        ['Ağdam', 39.9910, 46.9295],
        ['Ağdaş', 40.6500, 47.4747],
        ['Ağcabədi', 40.0531, 47.4597],
        ['Ağstafa', 41.1194, 45.4536],
        ['Ağsu', 40.5717, 48.4022],
        ['Astara', 38.4558, 48.8747],
        ['Babək', 39.1522, 45.4472],
        ['Balakən', 41.7264, 46.4053],
        ['Bərdə', 40.3744, 47.1264],
        ['Beyləqan', 39.7728, 47.6156],
        ['Biləsuvar', 39.4597, 48.5497],
        ['Cəbrayıl', 39.3989, 47.0258],
        ['Cəlilabad', 39.2058, 48.5072],
        ['Culfa', 38.9542, 45.6294],
        ['Daşkəsən', 40.5203, 46.0781],
        ['Füzuli', 39.5994, 47.1436],
        ['Gədəbəy', 40.5700, 45.8156],
        ['Goranboy', 40.6103, 46.7889],
        ['Göyçay', 40.6531, 47.7403],
        ['Göygöl', 40.5872, 46.3197],
        ['Hacıqabul', 40.0361, 48.9203],
        ['İmişli', 39.8697, 48.0611],
        ['İsmayıllı', 40.7864, 48.1531],
        ['Kəlbəcər', 40.1053, 46.0364],
        ['Kəngərli', 39.3894, 45.1728],
        ['Kürdəmir', 40.3494, 48.1644],
        ['Qax', 41.4206, 46.9297],
        ['Qazax', 41.0922, 45.3661],
        ['Qobustan', 40.5333, 48.9264],
        ['Qubadlı', 39.3444, 46.5797],
        ['Qusar', 41.4272, 48.4306],
        ['Laçın', 39.6383, 46.5464],
        ['Lerik', 38.7744, 48.4153],
        ['Masallı', 39.0342, 48.6597],
        ['Naftalan', 40.5072, 46.8233],
        ['Neftçala', 39.3778, 49.2419],
        ['Oğuz', 41.0714, 47.4636],
        ['Ordubad', 38.9053, 46.0231],
        ['Saatlı', 39.9308, 48.3653],
        ['Sabirabad', 40.0089, 48.4694],
        ['Salyan', 39.5964, 48.9794],
        ['Samux', 40.7614, 46.4083],
        ['Sədərək', 39.7108, 44.8833],
        ['Siyəzən', 41.0781, 49.1114],
        ['Şabran', 41.2211, 48.9908],
        ['Şahbuz', 39.4058, 45.5708],
        ['Şəmkir', 40.8294, 46.0186],
        ['Şərur', 39.5539, 44.9847],
        ['Şuşa', 39.7597, 46.7494],
        ['Tərtər', 40.3444, 46.9350],
        ['Tovuz', 40.9925, 45.6167],
        ['Ucar', 40.5081, 47.6494],
        ['Xaçmaz', 41.4642, 48.8022],
        ['Xızı', 40.9106, 49.0728],
        ['Yardımlı', 38.9061, 48.2472],
        ['Zaqatala', 41.6317, 46.6433],
        ['Zəngilan', 39.0869, 46.6522],
        ['Zərdab', 40.2172, 47.7108],
    ];
};
