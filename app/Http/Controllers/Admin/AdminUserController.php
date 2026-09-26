<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Adminlərin idarəsi — yalnız baş admin üçün (EnsureSuperAdmin). */
class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $admins = Admin::query()
            ->when($request->input('q'), fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
            ))
            ->withCount('activityLogs')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.admins.index', [
            'admins'  => $admins,
            'filters' => $request->only('q'),
        ]);
    }

    public function create()
    {
        return view('admin.admins.form', ['admin' => new Admin(['role' => 'moderator', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->whereNull('deleted_at')],
            'password'  => ['required', 'confirmed', Password::min(10)],
            'role'      => ['required', Rule::in(Admin::ROLES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $admin = DB::transaction(function () use ($data) {
            $admin = Admin::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role'      => $data['role'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            AdminActivityLog::record('admin.created', $admin, [
                'email' => $admin->email,
                'role'  => $admin->role,
            ]);

            return $admin;
        });

        return redirect()->route('admin.admins.index')
            ->with('success', "«{$admin->name}» admin kimi əlavə edildi.");
    }

    public function edit(Admin $admin)
    {
        return view('admin.admins.form', compact('admin'));
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)->whereNull('deleted_at')],
            'password'  => ['nullable', 'confirmed', Password::min(10)],
            'role'      => ['required', Rule::in(Admin::ROLES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isSelf = $admin->id === Auth::guard('admin')->id();

        // Baş admin özünü aşağı salıb və ya söndürüb paneldən qıfıllana bilməz.
        if ($isSelf && ($data['role'] !== 'super_admin' || ! ($data['is_active'] ?? false))) {
            return back()->withErrors([
                'role' => 'Öz hesabınızın rolunu və ya aktivliyini dəyişə bilməzsiniz.',
            ])->withInput();
        }

        $this->guardLastSuperAdmin($admin, $data['role'], (bool) ($data['is_active'] ?? false));

        DB::transaction(function () use ($admin, $data) {
            $changes = [];

            foreach (['name', 'email', 'role'] as $field) {
                if ($admin->{$field} !== $data[$field]) {
                    $changes[$field] = ['from' => $admin->{$field}, 'to' => $data[$field]];
                }
            }

            $admin->fill([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'role'      => $data['role'],
                'is_active' => $data['is_active'] ?? false,
            ]);

            if (! empty($data['password'])) {
                $admin->password = $data['password'];
                $changes['password'] = 'dəyişdirildi';
            }

            $admin->save();

            AdminActivityLog::record('admin.updated', $admin, $changes);
        });

        return redirect()->route('admin.admins.index')
            ->with('success', "«{$admin->name}» yeniləndi.");
    }

    public function destroy(Admin $admin)
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->withErrors(['admin' => 'Öz hesabınızı silə bilməzsiniz.']);
        }

        $this->guardLastSuperAdmin($admin, null, false);

        DB::transaction(function () use ($admin) {
            AdminActivityLog::record('admin.deleted', $admin, ['email' => $admin->email]);
            $admin->delete();
        });

        return back()->with('success', "«{$admin->name}» silindi.");
    }

    /**
     * Sonuncu aktiv baş admin getsə, panelə heç kim admin əlavə edə bilməz —
     * bərpa yalnız baza üzərindən mümkün olardı.
     */
    private function guardLastSuperAdmin(Admin $admin, ?string $newRole, bool $stayingActive): void
    {
        $losingPower = $admin->isSuperAdmin() && ($newRole !== 'super_admin' || ! $stayingActive);

        if (! $losingPower) {
            return;
        }

        $others = Admin::where('id', '!=', $admin->id)
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->exists();

        if (! $others) {
            throw ValidationException::withMessages([
                'role' => 'Sistemdə ən azı bir aktiv baş admin qalmalıdır.',
            ]);
        }
    }
}
