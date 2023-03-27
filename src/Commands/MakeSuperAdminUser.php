<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Filament\Support\Commands\Concerns\CanValidateInput;
use Illuminate\Console\Command;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;

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

    protected function getUserData(): array
    {
        return [
            'name' => $this->validateInput(fn () => $this->options['name'] ?? $this->ask('Name'), 'name', ['required'], fn () => $this->options['name'] = null),
            'email' => $this->validateInput(fn () => $this->options['email'] ?? $this->ask('Email address'), 'email', ['required', 'email', 'unique:' . $this->getUserModel()], fn () => $this->options['email'] = null),
            'password' => Hash::make($this->validateInput(fn () => $this->options['password'] ?? $this->secret('Password'), 'password', ['required', 'min:8'], fn () => $this->options['password'] = null)),
        ];
    }

    protected function createUser(): Authenticatable
    {
        return static::getUserModel()::create($this->getUserData());
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
        $this->info('User '. ($user->getAttribute('email') ?? $user->getAttribute('username')) . ' Created !');
    }
}
