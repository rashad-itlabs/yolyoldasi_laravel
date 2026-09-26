<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kompozit açarlı cədvəl (conversation_id + user_id).
 * Eloquent kompozit primary key-i dəstəkləmir, ona görə bu modeldə
 * $model->save() ÇAĞIRMA — yeniləmələri həmişə sorğu qurucusu ilə et:
 *   ConversationParticipant::where(...)->increment('unread_count')
 */
class ConversationParticipant extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $keyType = null;

    protected $fillable = ['conversation_id', 'user_id', 'unread_count', 'last_read_at'];

    protected $casts = ['last_read_at' => 'datetime'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
