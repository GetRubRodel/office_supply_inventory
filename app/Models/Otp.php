<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'identifier',
        'token',
        'type',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public static function generate(string $identifier, string $type = 'email', int $digits = 6, int $expiryMinutes = 10): self
    {
        // Invalidate any previous unused OTPs for this identifier
        static::where('identifier', $identifier)->whereNull('used_at')->update(['used_at' => now()]);

        return static::create([
            'identifier' => $identifier,
            'token' => str_pad((string) random_int(0, 999999), $digits, '0', STR_PAD_LEFT),
            'type' => $type,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    public static function verify(string $identifier, string $token): bool
    {
        $otp = static::where('identifier', $identifier)
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }
}
