<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'frith:admin
                            {--name= : The person\'s name}
                            {--email= : Their email address}
                            {--role= : owner or editor}
                            {--password= : Skips the prompt. Lands in your shell history, so prefer the prompt}';

    protected $description = 'Create an account for the admin panel';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email address', required: true);

        if (User::query()->where('email', $email)->exists()) {
            $this->error("There is already an account for {$email}.");

            return self::FAILURE;
        }

        $role = $this->option('role') ?: select(
            label: 'What can they do?',
            options: collect(UserRole::cases())
                ->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label().' — '.$r->description()])
                ->all(),
            default: UserRole::Editor->value,
        );

        $role = UserRole::tryFrom($role);

        if (! $role) {
            $this->error('Role must be either "owner" or "editor".');

            return self::FAILURE;
        }

        $plain = $this->option('password') ?: password('Password', required: true);

        $validator = Validator::make(
            ['email' => $email, 'password' => $plain, 'name' => $name],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:254'],
                'password' => ['required', Password::min(12)->uncompromised()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($plain),
            'role' => $role,
        ]);

        $this->newLine();
        $this->info("Created {$role->label()} account for {$email}.");
        $this->line('  '.$role->description());
        $this->line('  Sign in at '.rtrim(config('app.url'), '/').'/admin');

        return self::SUCCESS;
    }
}
