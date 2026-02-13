<?php

use App\Models\User;
use App\Services\Auth\RateLimitService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->baseUrl = '/api/v1/auth';
});

it('validates registration input', function () {
    // Attempt to register with invalid data
    $response = $this->postJson("{$this->baseUrl}/register", [
        'name' => '',
        'email' => 'invalid-email',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
})->group('authentication');

it('registers a user with valid input', function () {
    Notification::fake();

    $response = $this->postJson("{$this->baseUrl}/register", [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'SecUreP@ssw0rd',
        'password_confirmation' => 'SecUreP@ssw0rd',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => __('verification.verification_sent'),
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'testuser@example.com',
    ]);

    $user = User::whereEmail('testuser@example.com')->first();

    expect(Hash::check('SecUreP@ssw0rd', $user->password))->toBeTrue();

    Notification::assertSentTo($user, VerifyEmail::class);
})->group('authentication');

it('does not create duplicate user on registration', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson("{$this->baseUrl}/register", [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'SecUreP@ssw0rd',
        'password_confirmation' => 'SecUreP@ssw0rd',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => __('verification.verification_sent'),
        ]);

    expect(User::whereEmail('test@example.com')->count())
        ->toBe(1);
})->group('authentication');

it('validates login input', function () {
    $response = $this->postJson("{$this->baseUrl}/login", [
        'email' => 'invalid-email',
        'password' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
})->group('authentication');

it('fails login for non-existing user', function () {
    $response = $this->postJson("{$this->baseUrl}/login", [
        'email' => 'missing@example.com',
        'password' => 'whatever',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false])
        ->assertJsonValidationErrors(['email']);
})->group('authentication');

it('logs in a verified user successfully', function () {
    $user = \App\Models\User::factory()->create([
        'password' => bcrypt('SecUreP@ssw0rd'),
        'email_verified_at' => now(),
    ]);

    // Attempt login
    $response = $this->postJson("{$this->baseUrl}/login", [
        'email' => $user->email,
        'password' => 'SecUreP@ssw0rd',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data' => ['token']]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
})->group('authentication');

it('fails login with incorrect password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('SecUreP@ssw0rd'),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson("{$this->baseUrl}/login", [
        'email' => $user->email,
        'password' => 'WrongP@ssw0rd',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false])
        ->assertJsonValidationErrors(['email']);

})->group('authentication');

it('fails login for unverified user', function () {
    $user = User::factory()->create([
        'password' => bcrypt('SecUreP@ssw0rd'),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson("{$this->baseUrl}/login", [
        'email' => $user->email,
        'password' => 'SecUreP@ssw0rd',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false])
        ->assertJsonValidationErrors(['email_verification']);

})->group('authentication');

it('throttles login attempts more than allowed', function () {
    // Set rate limit configuration for testing
    Config::set('auth.api.rate_limit_attempts', 2);
    Config::set('auth.api.rate_limit_decay', 60);

    $service = new RateLimitService;
    $request = Request::create('/login', 'POST', ['email' => 'testuser@example.com']);

    // Mock the IP address for consistent testing
    $request->server('REMOTE_ADDR', '127.0.0.1');

    // Simulate attempts within the limit
    $service->throttle('login', $request);
    $service->throttle('login', $request);

    // Expect exception on the next attempt
    expect(fn () => $service->throttle('login', $request))->toThrow(ValidationException::class);
})->group('authentication');

it('logs out an authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;
    $tokenModel = $user->tokens()->latest()->first();

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $tokenModel->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("{$this->baseUrl}/logout");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenModel->id,
    ]);
})->group('authentication');

it('throttles registration attempts more than allowed', function () {
    Config::set('auth.api.rate_limit_attempts.register', 2);
    Config::set('auth.api.rate_limit_decay.register', 60);

    $payload = [
        'name' => 'Test User',
        'email' => 'throttle-register@example.com',
        'password' => 'SecUreP@ssw0rd',
        'password_confirmation' => 'SecUreP@ssw0rd',
    ];

    $ip = '127.0.0.2';
    $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("{$this->baseUrl}/register", $payload);
    $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("{$this->baseUrl}/register", $payload);

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("{$this->baseUrl}/register", $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
})->group('authentication');

it('resends verification email for unverified user', function () {
    Notification::fake();
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->postJson("{$this->baseUrl}/resend-verification", [
        'email' => $user->email,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Success']);
    Notification::assertSentTo($user, VerifyEmail::class);
})->group('verification');

it('throttles resend verification requests more than allowed', function () {
    Config::set('auth.api.rate_limit_attempts.resend-verification', 2);
    Config::set('auth.api.rate_limit_decay.resend-verification', 60);

    Notification::fake();
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $ip = '127.0.0.3';
    $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("{$this->baseUrl}/resend-verification", ['email' => $user->email]);
    $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("{$this->baseUrl}/resend-verification", ['email' => $user->email]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("{$this->baseUrl}/resend-verification", ['email' => $user->email]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
})->group('verification');

it('verifies valid signed email link', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // Use FULL signed URL with query params (?signature=...)
    $response = $this->getJson($signedUrl);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
})->group('verification');

it('throttles verification attempts more than allowed', function () {
    Config::set('auth.api.rate_limit_attempts.verify-email', 2);
    Config::set('auth.api.rate_limit_decay.verify-email', 60);

    $user = User::factory()->create(['email_verified_at' => null]);
    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'wronghash']
    );

    $ip = '127.0.0.4';
    $this->withServerVariables(['REMOTE_ADDR' => $ip])->getJson($signedUrl);
    $this->withServerVariables(['REMOTE_ADDR' => $ip])->getJson($signedUrl);

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->getJson($signedUrl);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
})->group('verification');

it('fails verification with invalid hash', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'wronghash']
    );

    $response = $this->getJson($signedUrl);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
})->group('verification');

it('fails verification for already verified user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->getJson($signedUrl);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
})->group('verification');

it('fails verification with invalid user id', function () {
    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => 999, 'hash' => sha1('missing@example.com')]
    );

    $response = $this->getJson($signedUrl);

    $response->assertStatus(404);
})->group('verification');
