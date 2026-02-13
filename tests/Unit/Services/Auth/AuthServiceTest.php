<?php

use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->authService = $this->app->make(AuthService::class);
});

it('secures password by hashing when needed', function () {
    config(['auth.api.password_uncompromised_check' => false]);

    $password = 'ValidPassword123!';
    Hash::shouldReceive('needsRehash')->andReturn(true);
    Hash::shouldReceive('make')->with($password)->andReturn('hashed_password');

    $securePassword = (fn (string $value) => $this->securePassword($value))
        ->bindTo($this->authService, $this->authService);

    expect($securePassword($password))->toBe('hashed_password');
});

it('returns already hashed password when rehash not needed', function () {
    config(['auth.api.password_uncompromised_check' => false]);

    $hashedPassword = '$2y$10$already_hashed_password';
    Hash::shouldReceive('needsRehash')->with($hashedPassword)->andReturn(false);

    $securePassword = (fn (string $value) => $this->securePassword($value))
        ->bindTo($this->authService, $this->authService);

    expect($securePassword($hashedPassword))->toBe($hashedPassword);
});

it('throws when password is compromised', function () {
    config(['auth.api.password_uncompromised_check' => true]);

    $securePassword = (fn (string $value) => $this->securePassword($value))
        ->bindTo($this->authService, $this->authService);

    expect(fn () => $securePassword('password123'))
        ->toThrow(ValidationException::class);
});

it('skips uncompromised validation when disabled', function () {
    config(['auth.api.password_uncompromised_check' => false]);

    Hash::shouldReceive('needsRehash')->andReturn(true);
    Hash::shouldReceive('make')->andReturn('hashed');

    $securePassword = (fn (string $value) => $this->securePassword($value))
        ->bindTo($this->authService, $this->authService);

    expect($securePassword('anypassword'))->toBe('hashed');
});
