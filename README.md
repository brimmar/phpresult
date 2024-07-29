# PHP Result Type Documentation

This documentation covers the implementation of a Rust-like Result Type for PHP. The Result type is used for returning and propagating errors. It has two variants: `Ok`, representing success and containing a value, and `Err`, representing error and containing an error value.

## Table of Contents

1. [Result Interface](#result-interface)
2. [Usage](#usage)
3. [Methods](#methods)
4. [Complementary Packages](#complementary-packages)
5. [Static Analysis](#static-analysis)
6. [Contributing](#contributing)
7. [Security Vulnerabilities](#security-vulnerabilities)
8. [License](#License)

## Result Interface

The `Result` interface defines the contract for both `Ok` and `Err` classes.

```php
<?php

namespace Brimmar\PhpResult\Interfaces;

/**
 * @template T
 * @template E
 */
interface Result
{
    // ... (methods will be documented below)
}
```

## Usage

#### First Example

```php
<?php
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Interfaces\Result;

class UserRegistration
{
    private $db;
    private $emailService;

    public function __construct(Database $db, EmailService $emailService)
    {
        $this->db = $db;
        $this->emailService = $emailService;
    }

    public function registerUser(string $username, string $email, string $password): Result
    {
        return $this->validateInput($username, $email, $password)
            ->andThen(fn() => $this->checkUserExists($username, $email))
            ->andThen(fn() => $this->hashPassword($password))
            ->andThen(fn($hashedPassword) => $this->saveUser($username, $email, $hashedPassword))
            ->andThen(fn($userId) => $this->sendWelcomeEmail($userId, $email));
    }

    private function validateInput(string $username, string $email, string $password): Result
    {
        if (strlen($username) < 3) {
            return new Err("Username must be at least 3 characters long");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Err("Invalid email address");
        }
        if (strlen($password) < 8) {
            return new Err("Password must be at least 8 characters long");
        }
        return new Ok(null);
    }

    private function checkUserExists(string $username, string $email): Result
    {
        $exists = $this->db->query("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email])->fetchColumn();
        return $exists ? new Err("Username or email already exists") : new Ok(null);
    }

    private function hashPassword(string $password): Result
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $hashedPassword ? new Ok($hashedPassword) : new Err("Failed to hash password");
    }

    private function saveUser(string $username, string $email, string $hashedPassword): Result
    {
        $userId = $this->db->insert("INSERT INTO users (username, email, password) VALUES (?, ?, ?)", [$username, $email, $hashedPassword]);
        return $userId ? new Ok($userId) : new Err("Failed to save user to database");
    }

    private function sendWelcomeEmail(int $userId, string $email): Result
    {
        $sent = $this->emailService->send($email, "Welcome to our service!", "Thank you for registering...");
        return $sent ? new Ok($userId) : new Err("Failed to send welcome email");
    }
}

$registration = new UserRegistration($db, $emailService);
$result = $registration->registerUser("johndoe", "john@example.com", "password123")->match(
    Ok: fn($value) => echo "User registered successfully with ID: $value",
    Err: fn($error) => echo "Registration failed: $error",
);
```

#### Second Example

```php
<?php
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Interfaces\Result;

class WeatherApiClient
{
    private $httpClient;
    private $cache;
    private $rateLimiter;
    private $apiKey;

    public function __construct(HttpClient $httpClient, CacheInterface $cache, RateLimiter $rateLimiter, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->rateLimiter = $rateLimiter;
        $this->apiKey = $apiKey;
    }

    public function getWeatherForecast(string $city): Result
    {
        return $this->checkRateLimit()
            ->andThen(fn() => $this->getCachedForecast($city))
            ->orElse(fn() => $this->fetchForecastFromApi($city))
            ->andThen(fn($forecast) => $this->cacheForecast($city, $forecast));
    }

    private function checkRateLimit(): Result
    {
        return $this->rateLimiter->isAllowed('weather_api')
            ? new Ok(null)
            : new Err("Rate limit exceeded. Please try again later.");
    }

    private function getCachedForecast(string $city): Result
    {
        $cachedForecast = $this->cache->get("weather_forecast:$city");
        return $cachedForecast ? new Ok($cachedForecast) : new Err("Cache miss");
    }

    private function fetchForecastFromApi(string $city): Result
    {
        try {
            $response = $this->httpClient->get("https://api.weather.com/forecast", [
                'query' => ['city' => $city, 'apikey' => $this->apiKey]
            ]);

            if ($response->getStatusCode() !== 200) {
                return new Err("API request failed with status code: " . $response->getStatusCode());
            }

            $forecast = json_decode($response->getBody(), true);
            return new Ok($forecast);
        } catch (\Exception $e) {
            return new Err("Failed to fetch forecast: " . $e->getMessage());
        }
    }

    private function cacheForecast(string $city, array $forecast): Result
    {
        $cached = $this->cache->set("weather_forecast:$city", $forecast, 3600); // Cache for 1 hour
        return $cached ? new Ok($forecast) : new Err("Failed to cache forecast");
    }
}

$weatherClient = new WeatherApiClient($httpClient, $cache, $rateLimiter, 'your-api-key');
$result = $weatherClient->getWeatherForecast("New York")->match(
    Ok: fn($value) => echo "Weather forecast for New York: $value['summary']",
    Err: fn($error) => echo "Failed to get weather forecast: $error",
);;
```

#### Third Example

```php
<?php
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Interfaces\Result;

class ConfigManager
{
    private $configs = [];

    public function getConfig(string $key): Result
    {
        return $this->getFromEnvironment($key)
            ->or($this->getFromFile($key))
            ->orElse(fn() => $this->getDefaultConfig($key));
    }

    private function getFromEnvironment(string $key): Result
    {
        $value = getenv($key);
        return $value !== false ? new Ok($value) : new Err("Not found in environment");
    }

    private function getFromFile(string $key): Result
    {
        return isset($this->configs[$key])
            ? new Ok($this->configs[$key])
            : new Err("Not found in config file");
    }

    private function getDefaultConfig(string $key): Result
    {
        $defaults = ['timeout' => 30, 'retries' => 3];
        return isset($defaults[$key])
            ? new Ok($defaults[$key])
            : new Err("No default value for $key");
    }

    public function setConfig(string $key, $value): void
    {
        $this->configs[$key] = $value;
    }
}

$manager = new ConfigManager();
$manager->setConfig('database_url', 'mysql://localhost/mydb');

$dbConfig = $manager->getConfig('database_url')
    ->map(fn($url) => parse_url($url))
    ->isOkAnd(fn($parsed) => isset($parsed['scheme'], $parsed['host'], $parsed['path']));

if ($dbConfig) {
    echo "Valid database configuration found";
} else {
    echo "Invalid or missing database configuration";
}

$timeout = $manager->getConfig('timeout')
    ->expect("Timeout configuration is required");

echo "Timeout set to: $timeout";
```

#### Fourth Example

```php
<?php
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Interfaces\Result;
use Brimmar\PhpOption\Some;
use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Interfaces\Option;

class UserService
{
    private $users = [];

    public function findUser(int $id): Option
    {
        return isset($this->users[$id]) ? new Some($this->users[$id]) : new None();
    }

    public function updateUser(int $id, array $data): Result
    {
        return $this->findUser($id)
            ->ok()
            ->mapErr(fn() => "User not found")
            ->andThen(fn($user) => $this->validateUserData($data))
            ->map(fn($validData) => array_merge($this->users[$id], $validData))
            ->inspect(fn($updatedUser) => $this->users[$id] = $updatedUser);
    }

    private function validateUserData(array $data): Result
    {
        $errors = array_filter([
            'name' => strlen($data['name'] ?? '') < 2 ? 'Name too short' : null,
            'email' => filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL) ? null : 'Invalid email',
        ]);

        return empty($errors) ? new Ok($data) : new Err($errors);
    }

    public function getUserStats(): array
    {
        return array_map(
            fn($user) => $this->calculateUserScore($user)->unwrapOr(0),
            $this->users
        );
    }

    private function calculateUserScore(array $user): Option
    {
        return isset($user['activities'])
            ? new Some(array_sum($user['activities']))
            : new None();
    }
}

$service = new UserService();

// Simulate adding a user
$service->users[1] = ['name' => 'Alice', 'email' => 'alice@example.com'];

$updateResult = $service->updateUser(1, ['name' => 'Alicia'])
    ->transpose();

$name = $updateResult
    ->iter()
    ->current()['name'] ?? 'Unknown';

echo "Updated name: $name";

$stats = $service->getUserStats();
echo "User stats: " . implode(', ', $stats);
```

### Methods

#### `isOk(): bool`

Returns `true` if the result is `Ok`.

Example:

```php
$result = new Ok(42);
echo $result->isOk(); // Output: true

$result = new Err("error");
echo $result->isOk(); // Output: false
```

#### `isOkAnd(callable $fn): bool`

Returns `true` if the result is `Ok` and the value inside of it matches a predicate.

Example:

```php
$result = new Ok(42);
echo $result->isOkAnd(fn($value) => $value > 40); // Output: true
echo $result->isOkAnd(fn($value) => $value < 40); // Output: false

$result = new Err("error");
echo $result->isOkAnd(fn($value) => true); // Output: false
```

#### `isErr(): bool`

Returns `true` if the result is `Err`.

Example:

```php
$result = new Ok(42);
echo $result->isErr(); // Output: false

$result = new Err("error");
echo $result->isErr(); // Output: true
```

#### `isErrAnd(callable $fn): bool`

Returns `true` if the result is `Err` and the value inside of it matches a predicate.

Example:

```php
$result = new Err("error");
echo $result->isErrAnd(fn($error) => $error === "error"); // Output: true
echo $result->isErrAnd(fn($error) => $error === "other"); // Output: false

$result = new Ok(42);
echo $result->isErrAnd(fn($error) => true); // Output: false
```

#### `ok(?string $className): mixed`

Converts from `Result<T, E>` to `Option<T>`.

Example:

```php
$result = new Ok(42);
$option = $result->ok('\Brimmar\PhpOption\Some');
echo $option->unwrap(); // Output: 42

$result = new Err("error");
$option = $result->ok('\Brimmar\PhpOption\None');
echo $option->isNone(); // Output: true
```

#### `err(?string $className): mixed`

Converts from `Result<T, E>` to `Option<E>`.

Example:

```php
$result = new Ok(42);
$option = $result->err('\Brimmar\PhpOption\None');
echo $option->isNone(); // Output: true

$result = new Err("error");
$option = $result->err('\Brimmar\PhpOption\Some');
echo $option->unwrap(); // Output: "error"
```

#### `unwrap(): mixed`

Returns the contained `Ok` value. Throws an exception if the value is an `Err`.

Example:

```php
$result = new Ok(42);
echo $result->unwrap(); // Output: 42

$result = new Err("error");
$result->unwrap(); // Throws RuntimeException
```

#### `expect(string $msg): mixed`

Returns the contained `Ok` value. Throws an exception with a provided message if the value is an `Err`.

Example:

```php
$result = new Ok(42);
echo $result->expect("Failed to get value"); // Output: 42

$result = new Err("error");
$result->expect("Failed to get value"); // Throws RuntimeException with message "Failed to get value: error"
```

#### `expectErr(string $msg): mixed`

Returns the contained `Err` value. Throws an exception with a provided message if the value is an `Ok`.

Example:

```php
$result = new Err("error");
echo $result->expectErr("Failed to get error"); // Output: "error"

$result = new Ok(42);
$result->expectErr("Failed to get error"); // Throws RuntimeException with message "Failed to get error: 42"
```

#### `flatten(): Result`

Converts from `Result<Result<T, E>, E>` to `Result<T, E>`.

Example:

```php
$result = new Ok(new Ok(42));
$flattened = $result->flatten();
echo $flattened->unwrap(); // Output: 42

$result = new Ok(new Err("inner error"));
$flattened = $result->flatten();
echo $flattened->unwrapErr(); // Output: "inner error"

$result = new Err("outer error");
$flattened = $result->flatten();
echo $flattened->unwrapErr(); // Output: "outer error"
```

#### `intoErr(): mixed`

Returns the contained `Err` value. Throws an exception if the value is an `Ok`.

Example:

```php
$result = new Err("error");
echo $result->intoErr(); // Output: "error"

$result = new Ok(42);
$result->intoErr(); // Throws RuntimeException
```

#### `intoOk(): mixed`

Returns the contained `Ok` value. Throws an exception if the value is an `Err`.

Example:

```php
$result = new Ok(42);
echo $result->intoOk(); // Output: 42

$result = new Err("error");
$result->intoOk(); // Throws RuntimeException
```

#### `iter(): Iterator`

Returns an iterator over the possibly contained value.

Example:

```php
$result = new Ok(42);
foreach ($result->iter() as $value) {
    echo $value; // Output: 42
}

$result = new Err("error");
foreach ($result->iter() as $value) {
    echo "This won't be executed";
}
```

#### `unwrapOr(mixed $default): mixed`

Returns the contained `Ok` value or a provided default.

Example:

```php
$result = new Ok(42);
echo $result->unwrapOr(0); // Output: 42

$result = new Err("error");
echo $result->unwrapOr(0); // Output: 0
```

#### `unwrapOrElse(callable $fn): mixed`

Returns the contained `Ok` value or computes it from a closure.

Example:

```php
$result = new Ok(42);
echo $result->unwrapOrElse(fn() => 0); // Output: 42

$result = new Err("error");
echo $result->unwrapOrElse(fn($error) => strlen($error)); // Output: 5
```

#### `map(callable $fn): Result`

Maps a `Result<T, E>` to `Result<U, E>` by applying a function to a contained `Ok` value, leaving an `Err` value untouched.

Example:

```php
$result = new Ok(42);
$mapped = $result->map(fn($value) => $value * 2);
echo $mapped->unwrap(); // Output: 84

$result = new Err("error");
$mapped = $result->map(fn($value) => $value * 2);
echo $mapped->unwrapErr(); // Output: "error"
```

#### `mapErr(callable $fn): Result`

Maps a `Result<T, E>` to `Result<T, F>` by applying a function to a contained `Err` value, leaving an `Ok` value untouched.

Example:

```php
$result = new Err("error");
$mapped = $result->mapErr(fn($error) => strtoupper($error));
echo $mapped->unwrapErr(); // Output: "ERROR"

$result = new Ok(42);
$mapped = $result->mapErr(fn($error) => strtoupper($error));
echo $mapped->unwrap(); // Output: 42
```

#### `mapOr(mixed $default, callable $fn): mixed`

Returns the provided default (if `Err`), or applies a function to the contained value (if `Ok`).

Example:

```php
$result = new Ok(42);
echo $result->mapOr(0, fn($value) => $value * 2); // Output: 84

$result = new Err("error");
echo $result->mapOr(0, fn($value) => $value * 2); // Output: 0
```

#### `mapOrElse(callable $default, callable $fn): mixed`

Maps a `Result<T, E>` to `U` by applying fallback function `default` to a contained `Err` value, or function `fn` to a contained `Ok` value.

Example:

```php
$result = new Ok(42);
echo $result->mapOrElse(
    fn($error) => strlen($error),
    fn($value) => $value * 2
); // Output: 84

$result = new Err("error");
echo $result->mapOrElse(
    fn($error) => strlen($error),
    fn($value) => $value * 2
); // Output: 5
```

#### `inspect(callable $fn): self`

Calls the provided closure with a reference to the contained value (if `Ok`).

Example:

```php
$result = new Ok(42);
$result->inspect(function($value) {
    echo "Got value: $value";
}); // Output: Got value: 42

$result = new Err("error");
$result->inspect(function($value) {
    echo "This won't be executed";
});
```

#### `inspectErr(callable $fn): self`

Calls the provided closure with a reference to the contained error (if `Err`).

Example:

```php
$result = new Err("error");
$result->inspectErr(function($error) {
    echo "Got error: $error";
}); // Output: Got error: error

$result = new Ok(42);
$result->inspectErr(function($error) {
    echo "This won't be executed";
});
```

#### `and(Result $res): Result`

Returns `res` if the result is `Ok`, otherwise returns the `Err` value of `self`.

Example:

```php
$result1 = new Ok(42);
$result2 = new Ok(10);
$combined = $result1->and($result2);
echo $combined->unwrap(); // Output: 10

$result1 = new Err("error");
$result2 = new Ok(10);
$combined = $result1->and($result2);
echo $combined->unwrapErr(); // Output: "error"
```

#### `andThen(callable $fn): Result`

Calls `fn` if the result is `Ok`, otherwise returns the `Err` value of `self`.

Example:

```php
$result = new Ok(42);
$chained = $result->andThen(fn($value) => new Ok($value * 2));
echo $chained->unwrap(); // Output: 84

$result = new Err("error");
$chained = $result->andThen(fn($value) => new Ok($value * 2));
echo $chained->unwrapErr(); // Output: "error"
```

#### `or(Result $res): Result`

Returns `self` if it is `Ok`, otherwise returns `res`.

Example:

```php
$result1 = new Ok(42);
$result2 = new Ok(10);
$combined = $result1->or($result2);
echo $combined->unwrap(); // Output: 42

$result1 = new Err("error1");
$result2 = new Ok(10);
$combined = $result1->or($result2);
echo $combined->unwrap(); // Output: 10
```

#### `orElse(callable $fn): Result`

Calls `fn` if the result is `Err`, otherwise returns the `Ok` value of `self`.

Example:

```php
$result = new Ok(42);
$chained = $result->orElse(fn($error) => new Ok($error . " handled"));
echo $chained->unwrap(); // Output: 42

$result = new Err("error");
$chained = $result->orElse(fn($error) => new Ok($error . " handled"));
echo $chained->unwrap(); // Output: "error handled"
```

#### `transpose(?string $noneClassName, ?string $someClassName): mixed`

Transposes a `Result` of an `Option` into an `Option` of a `Result`.

Example:

```php
$result = new Ok(new Some(42));
$transposed = $result->transpose('\Brimmar\PhpOption\None', '\Brimmar\PhpOption\Some');
echo $transposed->unwrap()->unwrap(); // Output: 42

$result = new Ok(new None());
$transposed = $result->transpose('\Brimmar\PhpOption\None', '\Brimmar\PhpOption\Some');
echo $transposed->isNone(); // Output: true

$result = new Err("error");
$transposed = $result->transpose('\Brimmar\PhpOption\None', '\Brimmar\PhpOption\Some');
echo $transposed->unwrap()->unwrapErr(); // Output: "error"
```

#### `match(callable $Ok, callable $Err): mixed`

Matches the result and returns a value based on the provided patterns.

Example:

```php
$result = new Ok(42);
$value = $result->match(
    Ok: fn($value) => "Success: $value",
    Err: fn($error) => "Error: $error"
);
echo $value; // Output: "Success: 42"

$result = new Err("error");
$value = $result->match(
    Ok: fn($value) => "Success: $value",
    Err: fn($error) => "Error: $error"
);
echo $value; // Output: "Error: error"
```

## Complementary Packages

This package works well with the PHP Option Type package, which implements the Option Type. Some methods in this package, such as transpose, depend on the Option Type implementation.

[PhpOption](https://github.com/brimmar/phpoption/)

## Static Analysis

We recommend using PHPStan for static code analysis. This package includes custom PHPStan rules to enhance type checking for Result types. To enable these rules, add the following to your PHPStan configuration:

```sh
composer require brimmar/phpstan-rustlike-result-extension --dev
```

```neon
// phpstan.neon
includes:
    - vendor/brimmar/phpstan-rustlike-result-extension/extension.neon
```

## Contributing

Please see CONTRIBUTING.md for details.

## Security Vulnerabilities

Pleas review our security policy on how to report security vulnerabilities.

## License

Please see LICENSE.md for more information.
