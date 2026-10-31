<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A message sent from the Contact Us form. */
class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'message', 'is_read'];

    protected $casts = ['is_read' => 'boolean'];

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->phone);

        return strlen($digits) >= 7 ? 'https://wa.me/' . $digits : null;
    }
}
