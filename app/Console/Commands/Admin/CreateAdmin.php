<?php

namespace App\Console\Commands\Admin;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Panelə ilk girişi açan əmr — adminlər yalnız panel daxilindən yaradıla
 * bildiyi üçün birinci hesab konsoldan verilməlidir.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
                            {--name= : Ad Soyad}
                            {--email= : E-poçt}
                            {--password= : Şifrə (verilməsə soruşulur)}
                            {--role=super_admin : super_admin və ya moderator}';

    protected $description = 'Admin panelinə yeni istifadəçi əlavə edir';

    public function handle(): int
    {
        $name     = $this->option('name')     ?: $this->ask('Ad Soyad');
        $email    = $this->option('email')    ?: $this->ask('E-poçt');
        $password = $this->option('password') ?: $this->secret('Şifrə (ən azı 10 simvol)');
        $role     = $this->option('role');

        $validator = Validator::make(compact('name', 'email', 'password', 'role'), [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->whereNull('deleted_at')],
            'password' => ['required', Password::min(10)],
            'role'     => ['required', Rule::in(Admin::ROLES)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = Admin::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => $password,
            'role'      => $role,
            'is_active' => true,
        ]);

        $this->info("Admin #{$admin->id} yaradıldı: {$admin->email} ({$admin->roleLabel()})");
        $this->line('Giriş: '.url('/admin/login'));

        return self::SUCCESS;
    }
}
