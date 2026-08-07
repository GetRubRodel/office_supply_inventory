<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;

/**
 * Resolves the currently authenticated user through the Guard contract.
 *
 * This exists because the global `auth()` helper is typed as
 * `AuthFactory|Guard`, and `AuthFactory` does not declare `user()`,
 * `check()`, or `id()`. Using the Guard contract directly keeps every
 * call site statically type-safe and free of undefined-method errors.
 */
final class CurrentUser
{
    /**
     * Get the currently authenticated user for the default guard, or null.
     */
    public static function get(): ?User
    {
        $user = self::guard()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Determine whether a user is currently authenticated.
     */
    public static function check(): bool
    {
        return self::get() !== null;
    }

    /**
     * Get the authenticated user's primary key, or null.
     */
    public static function id(): ?int
    {
        $user = self::get();

        return $user !== null ? (int) $user->getKey() : null;
    }

    private static function guard(): Guard
    {
        /** @var Factory $factory */
        $factory = app(Factory::class);

        return $factory->guard();
    }
}
