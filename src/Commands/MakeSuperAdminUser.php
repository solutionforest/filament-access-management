<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Exception;
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

    /** @var array<array-key, mixed> */
    protected array $options;

    public function handle(): int
    {
        $this->options = $this->options();

        $user = $this->createUser();

        $this->assignRole($user);

        $this->sendSuccessMessage($user);

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    protected function getUserCheckKeys(): array
    {
        return [
            'email' => $this->validateInput(fn () => $this->options['email'] ??
                $this->ask('Email address'), 'email', array_merge(['required', 'email'], $this->option('force') ? [] : ['unique:'.$this->getUserModel()]),
                fn () => $this->options['email'] = null),
        ];
    }

    /**
     * @return array<string, string>
     */
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
        $model = static::getUserModel();

        if ($this->option('force')) {
            $query = $model::query();

            foreach ($checkKeys as $key => $value) {
                $query = $query->where($key, $value);
            }

            $user = $query->first();

            if (! $user instanceof Authenticatable) {
                throw new Exception('Unable to find the user to update.');
            }

            return $user;
        }

        $data = array_merge($checkKeys, $this->getUserData());

        return $model::create($data);
    }

    protected function assignRole(Authenticatable $user): void
    {
        $role = FilamentAuthenticate::createAdminRole();

        if (! method_exists($user, 'assignRole')) {
            return;
        }

        $user->assignRole($role->getAttribute('name'));
    }

    /**
     * @return class-string<Authenticatable>
     */
    protected function getUserModel(): string
    {
        return Utils::getUserModel();
    }

    protected function sendSuccessMessage(Authenticatable $user): void
    {
        $identifier = $user->getAttribute('email') ?? $user->getAttribute('username');
        $identifier = is_scalar($identifier) ? (string) $identifier : '';

        $this->info('User '.$identifier.' Created !');
    }
}
