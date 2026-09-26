<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'Bakı', 'Sumqayıt', 'Gəncə', 'Mingəçevir', 'Şirvan', 'Naxçıvan',
            'Şəki', 'Quba', 'Qəbələ', 'Lənkəran', 'Şamaxı', 'Xaçmaz',
            'Qusar', 'Zaqatala', 'Balakən', 'Qax', 'İsmayıllı', 'Göyçay',
            'Ağdaş', 'Bərdə', 'Yevlax', 'Ağcabədi', 'Beyləqan', 'İmişli',
            'Saatlı', 'Sabirabad', 'Salyan', 'Neftçala', 'Masallı', 'Astara',
            'Lerik', 'Cəlilabad', 'Biləsuvar', 'Ağsu', 'Kürdəmir', 'Ucar',
            'Tovuz', 'Qazax', 'Ağstafa', 'Şəmkir', 'Samux', 'Goranboy',
            'Naftalan', 'Tərtər', 'Şuşa', 'Xankəndi', 'Laçın', 'Kəlbəcər',
            'Füzuli', 'Cəbrayıl', 'Zəngilan', 'Qubadlı', 'Ordubad', 'Culfa',
        ];

        foreach ($cities as $name) {
            City::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
