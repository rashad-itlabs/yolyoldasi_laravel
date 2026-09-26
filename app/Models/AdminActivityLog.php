<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminActivityLog extends Model
{
    /** Cədvəldə yalnız created_at var. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id', 'action', 'subject_type', 'subject_id', 'meta', 'ip',
    ];

    protected $casts = ['meta' => 'array'];

    /**
     * Panelin bütün dəyişdirici əməliyyatları buradan keçir — hansı adminin
     * nə etdiyi başqa yerdə saxlanmır, ona görə log yazısı əməliyyatla eyni
     * tranzaksiyada çağırılmalıdır.
     */
    public static function record(string $action, ?Model $subject = null, array $meta = []): self
    {
        return static::create([
            'admin_id'     => Auth::guard('admin')->id(),
            'action'       => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'meta'         => $meta ?: null,
            'ip'           => request()->ip(),
        ]);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /** `App\Models\Ride` → `Ride` — siyahıda qısa göstərmək üçün. */
    public function subjectLabel(): ?string
    {
        return $this->subject_type ? class_basename($this->subject_type) : null;
    }
}
