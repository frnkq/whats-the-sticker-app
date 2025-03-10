<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappSticker extends Model
{
    protected $fillable = [
        'sticker_id',
        'context_id',
        'from',
    ];
}
/*
 document.set({
    id: messageId,
    stickerId,
    from,
    tags
  });
*/