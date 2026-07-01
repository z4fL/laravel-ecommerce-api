<?php

namespace App\Console\Commands;

use App\Enum\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('user:create-admin')]
#[Description('Create a new administrator account')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Create Admin Account ===');
        $this->newLine();

        $name = $this->ask('Name: ', 'admin');
        $email = $this->ask('Email: ', 'zaaaafl654@gmail.com');

        $password = $this->secret('Password'); // default = password
        $passwordConfirmation = $this->secret('Confirm Password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->newLine();
            $this->error('Validation failed.');

            foreach ($validator->errors()->all() as $error) {
                $this->line("• {$error}");
            }

            return self::FAILURE;
        }

        if (! $this->confirm('Create this administrator?', true)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::ADMIN,
        ]);

        $this->newLine();
        $this->info('Administrator created successfully.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $name],
                ['Email', $email],
                ['Role', UserRole::ADMIN->value],
            ]
        );

        return self::SUCCESS;
    }
}
