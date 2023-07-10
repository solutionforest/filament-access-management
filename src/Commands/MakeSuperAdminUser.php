<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Support\Commands\Concerns\CanValidateInput;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;

class MakeSuperAdminUser extends Command
{
    use CanValidateInput;

    protected $signature = 'make:super-admin-user
                            {--name= : The name of the user}
                            {--email= : A valid and unique email address}
                            {--password= : The password for the user (min. 8 characters)}';

    public $description = 'Create a super admin user';

    protected array $options;

    public function handle(): int
    {
        $this->options = $this->options();

        $user = $this->createUser();

        $this->assignRole($user);

        $this->sendSuccessMessage($user);

        return static::SUCCESS;
    }

    protected function createUser(): Authenticatable
    {
        $email = $this->validateInput(fn () => $this->options['email'] ?? $this->ask('Email address'), 'email', ['required', 'email'], fn () => $this->options['email'] = null);

        return static::getUserModel()::where('email', $email)->first() ?:
            static::getUserModel()::create($this->getUserData([
                'email' => $email,
                'name' => $this->validateInput(fn () => $this->options['name'] ?? $this->ask('Name'), 'name', ['required'], fn () => $this->options['name'] = null),
                'password' => Hash::make($this->validateInput(fn () => $this->options['password'] ?? $this->secret('Password'), 'password', ['required', 'min:8'], fn () => $this->options['password'] = null)),
            ]));
    }

    protected function assignRole(Authenticatable $user): void
    {
        $role = FilamentAuthenticate::createAdminRole();

        $user->assignRole($role->name);
    }

    protected function getUserModel(): string
    {
        return Utils::getUserModel();
    }

    protected function sendSuccessMessage(Authenticatable $user): void
    {
        $this->info('User ' . ($user->getAttribute('email') ?? $user->getAttribute('username')) . ' Created !');
    }
}
