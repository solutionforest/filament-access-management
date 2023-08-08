<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use SolutionForest\FilamentAccessManagement\Concerns\Commands\CanValidateInput;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class MakeSuperAdminUser extends Command
{
    use CanValidateInput;

    protected $signature = 'make:super-admin-user
                            {--name= : The name of the user}
                            {--email= : A valid and unique email address}
                            {--password= : The password for the user (min. 8 characters)}
                            {--force : Force updating existing user}';

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

    protected function getUserCheckKeys(): array {
        return [
            'email' => $this->validateInput(fn () => 
                $this->options['email'] ?? 
                $this->ask('Email address'), 'email', array_merge(['required', 'email'], $this->option('force') ? [] : ['unique:'.$this->getUserModel()]), 
                fn () => $this->options['email'] = null),
        ];
    }

    protected function getUserData(): array
    {
        return [
            'name' => $this->validateInput(fn () => $this->options['name'] ?? $this->ask('Name'), 'name', ['required'], fn () => $this->options['name'] = null),
            'password' => Hash::make($this->validateInput(fn () => $this->options['password'] ?? $this->secret('Password'), 'password', ['required', 'min:8'], fn () => $this->options['password'] = null)),
        ];
    }

    protected function createUser(): Authenticatable
    {
        $checkKeys = $this->getUserCheckKeys();

        if ($this->option('force')) {
            $query = static::getUserModel()::query();
            foreach ($checkKeys as $key => $value) {
                $query = $query->where($key, $value);
            }
            return $query->first();
        }

        $data = array_merge($checkKeys, $this->getUserData());
        return static::getUserModel()::create($data);
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
        $this->info('User '.($user->getAttribute('email') ?? $user->getAttribute('username')).' Created !');
    }
}
