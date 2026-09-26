<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Panelə ilk baş admini əlavə edir.
 *
 * Şifrə fayla yazılmır — ya `ADMIN_PASSWORD` mühit dəyişənindən götürülür,
 * ya da təsadüfi yaradılıb bir dəfə ekrana çıxarılır. Beləliklə seeder
 * repodə qalsa da, içində gizli məlumat olmur.
 *
 * Nümunə:
 *   php artisan db:seed --class=AdminSeeder
 *   ADMIN_EMAIL=... ADMIN_PASSWORD=... php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'mr.ealiyev@gmail.com');
        $name  = env('ADMIN_NAME', 'Rəşad Əliyev');

        $givenPassword = env('ADMIN_PASSWORD');

        $admin = Admin::withTrashed()->where('email', $email)->first();

        if ($admin) {
            // Mövcud hesabın şifrəsi yalnız açıq şəkildə istənəndə dəyişir —
            // seeder-in təkrar işə düşməsi girişi sındırmamalıdır.
            $admin->restore();

            $admin->fill([
                'name'      => $name,
                'role'      => 'super_admin',
                'is_active' => true,
            ]);

            if ($givenPassword) {
                $admin->password = $givenPassword;
            }

            $admin->save();

            $this->command->info("Mövcud admin yeniləndi: {$email}");

            if (! $givenPassword) {
                $this->command->line('Şifrə dəyişdirilmədi (ADMIN_PASSWORD verilməyib).');
            }

            return;
        }

        $password = $givenPassword ?: Str::password(16, symbols: false);

        Admin::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => $password,
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $this->command->info("Baş admin yaradıldı: {$email}");

        if (! $givenPassword) {
            $this->command->warn("Şifrə: {$password}");
            $this->command->line('Bu şifrə yalnız indi göstərilir — ilk girişdən sonra dəyişin.');
        }
    }
}
