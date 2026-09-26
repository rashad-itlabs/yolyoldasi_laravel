<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin panelindən göndərilmiş bir bildiriş — göndəriş tarixçəsinin sətri.
 *
 * Alıcıların özləri `notifications` cədvəlindədir (`push_message_id` ilə
 * bağlıdır); burada yalnız nəyin, kimə və hansı nəticə ilə göndərildiyi durur.
 */
class PushMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id', 'type', 'target', 'user_id',
        'heading', 'content', 'recipients', 'delivered', 'error',
    ];

    protected $casts = ['delivered' => 'boolean'];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /** Yalnız tək istifadəçiyə göndərişdə dolu olur. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
