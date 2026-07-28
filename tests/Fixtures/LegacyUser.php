<?php

namespace SolutionForest\FilamentAccessManagement\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SolutionForest\FilamentAccessManagement\Concerns\FilamentUser as FilamentUserTrait;

/**
 * Represents an app that upgraded from Filament v3 but still uses the ORIGINAL
 * (now deprecated) FilamentUser trait on its User model. Proves backward
 * compatibility: such a model must keep working on Filament v5.
 */
class LegacyUser extends Authenticatable implements FilamentUser
{
    use FilamentUserTrait;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
