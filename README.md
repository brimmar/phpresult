# Option and Result

[![Tests](https://github.com/brimmar/phpresult/actions/workflows/tests.yml/badge.svg)](https://github.com/brimmar/phpresult/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/brimmar/phpresult/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/brimmar/phpresult/actions/workflows/static-analysis.yml)
[![License](https://img.shields.io/packagist/l/brimmar/phpresult)](https://packagist.org/packages/brimmar/phpresult)
[![Latest Stable Version](https://img.shields.io/packagist/v/brimmar/phpresult)](https://packagist.org/packages/brimmar/phpresult)
[![Total Downloads](https://img.shields.io/packagist/dt/brimmar/phpresult)](https://packagist.org/packages/brimmar/phpresult)

`brimmar/phpresult` ships two Rust-like types for PHP 8.5.

`Option<T>` is a value that might be missing. `Some` holds the value. `None` holds nothing. Missing means absence.

`Result<T, E>` is success or failure. `Ok` holds `T`. `Err` holds `E`. There is no `Either` type.

Both namespaces live in this package. `Brimmar\PhpOption` is Option. `Brimmar\PhpResult` is Result. Install it once. `brimmar/phpoption` is archived, and this package conflicts with it.

PHP code around you returns `null` and throws. `Option::fromNullable()` and `Result::try()` bring those values in. `unwrapOr(null)` and `unwrapOrThrow()` hand them back. See [Null and exceptions](#null-and-exceptions).

Methods that return an Option or a Result are `#[\NoDiscard]`. Ignoring the return value warns, because that drops a present value or a failure. Cast the call to `void` when you mean to ignore it.

```bash
composer require brimmar/phpresult
```

## Table of contents

1. [Option](#option)
2. [Result](#result)
3. [Using them together](#using-them-together)
4. [Null and exceptions](#null-and-exceptions)
5. [Option methods](#option-methods)
6. [Result methods](#result-methods)
7. [Static analysis](#static-analysis)
8. [Contributing](#contributing)
9. [Security vulnerabilities](#security-vulnerabilities)
10. [License](#license)

## Option

`Option` is the type you return when a lookup can miss.

```php
<?php

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Option;
use Brimmar\PhpOption\Some;

class UserProfile
{
    private array $data = [];

    public function setField(string $field, mixed $value): void
    {
        $this->data[$field] = $value;
    }

    public function getField(string $field): Option
    {
        return isset($this->data[$field]) ? new Some($this->data[$field]) : new None();
    }

    public function getDisplayName(): string
    {
        return $this->getField('display_name')
            ->orElse(fn () => $this->getField('username'))
            ->unwrapOr('Anonymous');
    }

    public function getAge(): Option
    {
        return $this->getField('age')->andThen(function ($age) {
            return is_numeric($age) && $age > 0 && $age < 120
                ? new Some((int) $age)
                : new None();
        });
    }
}

$profile = new UserProfile();
$profile->setField('username', 'johndoe');
$profile->setField('age', '30');

echo $profile->getDisplayName(); // johndoe

echo $profile->getAge()
    ->map(fn ($age) => "User is $age years old")
    ->unwrapOr('Age not provided or invalid'); // User is 30 years old

echo $profile->getField('email')
    ->map(fn ($email) => "Contact: $email")
    ->unwrapOr('No email provided'); // No email provided
```

The same shape replaces optional chaining. Each step returns Option, so a missing user, address, or country stops the chain at `None`.

```php
<?php

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Option;
use Brimmar\PhpOption\Some;

class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $country,
    ) {}
}

class User
{
    public function __construct(
        public string $name,
        private ?Address $address = null,
    ) {}

    public function getAddress(): Option
    {
        return $this->address === null ? new None() : new Some($this->address);
    }
}

class UserRepository
{
    /** @var list<User> */
    private array $users = [];

    public function addUser(User $user): void
    {
        $this->users[] = $user;
    }

    public function findUserByName(string $name): Option
    {
        foreach ($this->users as $user) {
            if ($user->name === $name) {
                return new Some($user);
            }
        }

        return new None();
    }
}

$repo = new UserRepository();
$repo->addUser(new User('Alice', new Address('123 Main St', 'Springfield', 'USA')));
$repo->addUser(new User('Bob'));

function getUserCountry(UserRepository $repo, string $name): string
{
    return $repo->findUserByName($name)
        ->andThen(fn ($user) => $user->getAddress())
        ->map(fn ($address) => $address->country)
        ->unwrapOr('Country not found');
}

echo getUserCountry($repo, 'Alice'); // USA
echo getUserCountry($repo, 'Bob'); // Country not found
echo getUserCountry($repo, 'Charlie'); // Country not found
```

## Result

`Result` is the type you return when the caller has to handle a failure.

```php
<?php

use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;

class UserRegistration
{
    public function __construct(
        private Database $db,
        private EmailService $emailService,
    ) {}

    public function registerUser(string $username, string $email, string $password): Result
    {
        return $this->validateInput($username, $email, $password)
            ->andThen(fn () => $this->checkUserExists($username, $email))
            ->andThen(fn () => $this->hashPassword($password))
            ->andThen(fn ($hashedPassword) => $this->saveUser($username, $email, $hashedPassword))
            ->andThen(fn ($userId) => $this->sendWelcomeEmail($userId, $email));
    }

    private function validateInput(string $username, string $email, string $password): Result
    {
        if (strlen($username) < 3) {
            return new Err('Username must be at least 3 characters long');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Err('Invalid email address');
        }
        if (strlen($password) < 8) {
            return new Err('Password must be at least 8 characters long');
        }

        return new Ok(null);
    }

    private function checkUserExists(string $username, string $email): Result
    {
        $exists = $this->db->query(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [$username, $email],
        )->fetchColumn();

        return $exists ? new Err('Username or email already exists') : new Ok(null);
    }

    private function hashPassword(string $password): Result
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        return $hashedPassword ? new Ok($hashedPassword) : new Err('Failed to hash password');
    }

    private function saveUser(string $username, string $email, string $hashedPassword): Result
    {
        $userId = $this->db->insert(
            'INSERT INTO users (username, email, password) VALUES (?, ?, ?)',
            [$username, $email, $hashedPassword],
        );

        return $userId ? new Ok($userId) : new Err('Failed to save user to database');
    }

    private function sendWelcomeEmail(int $userId, string $email): Result
    {
        $sent = $this->emailService->send($email, 'Welcome to our service!', 'Thank you for registering...');

        return $sent ? new Ok($userId) : new Err('Failed to send welcome email');
    }
}

echo (new UserRegistration($db, $emailService))
    ->registerUser('johndoe', 'john@example.com', 'password123')
    ->match(
        Ok: fn ($value) => "User registered successfully with ID: $value",
        Err: fn ($error) => "Registration failed: $error",
    );
```

`orElse` runs only on `Err`. A cache miss is an `Err`, so the client fetches from the API. A rate-limit `Err` stops the chain before that lookup. The cache answers with `null` and the HTTP client throws. `Option::fromNullable` and `Result::try` turn both into a `Result`.

```php
<?php

use Brimmar\PhpOption\Option;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;

class WeatherApiClient
{
    public function __construct(
        private HttpClient $httpClient,
        private CacheInterface $cache,
        private RateLimiter $rateLimiter,
        private string $apiKey,
    ) {}

    public function getWeatherForecast(string $city): Result
    {
        return $this->checkRateLimit()->andThen(
            fn () => $this->getCachedForecast($city)
                ->orElse(fn () => $this->fetchForecastFromApi($city))
                ->andThen(fn ($forecast) => $this->cacheForecast($city, $forecast)),
        );
    }

    private function checkRateLimit(): Result
    {
        return $this->rateLimiter->isAllowed('weather_api')
            ? new Ok(null)
            : new Err('Rate limit exceeded. Please try again later.');
    }

    private function getCachedForecast(string $city): Result
    {
        return Option::fromNullable($this->cache->get("weather_forecast:$city"))->okOr('Cache miss');
    }

    private function fetchForecastFromApi(string $city): Result
    {
        return Result::try(fn () => $this->httpClient->get('https://api.weather.com/forecast', [
            'query' => ['city' => $city, 'apikey' => $this->apiKey],
        ]))
            ->mapErr(fn (Exception $e) => 'Failed to fetch forecast: '.$e->getMessage())
            ->andThen(fn ($response) => $response->getStatusCode() === 200
                ? new Ok(json_decode($response->getBody(), true))
                : new Err('API request failed with status code: '.$response->getStatusCode()));
    }

    private function cacheForecast(string $city, array $forecast): Result
    {
        $cached = $this->cache->set("weather_forecast:$city", $forecast, 3600);

        return $cached ? new Ok($forecast) : new Err('Failed to cache forecast');
    }
}

echo (new WeatherApiClient($httpClient, $cache, $rateLimiter, 'your-api-key'))
    ->getWeatherForecast('New York')
    ->match(
        Ok: fn ($value) => 'Weather forecast for New York: '.$value['summary'],
        Err: fn ($error) => "Failed to get weather forecast: $error",
    );
```

`or` and `orElse` also express fallbacks that are still failures when every source misses.

```php
<?php

use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;

class ConfigManager
{
    private array $configs = [];

    public function getConfig(string $key): Result
    {
        return $this->getFromEnvironment($key)
            ->or($this->getFromFile($key))
            ->orElse(fn () => $this->getDefaultConfig($key));
    }

    private function getFromEnvironment(string $key): Result
    {
        $value = getenv($key);

        return $value !== false ? new Ok($value) : new Err('Not found in environment');
    }

    private function getFromFile(string $key): Result
    {
        return isset($this->configs[$key])
            ? new Ok($this->configs[$key])
            : new Err('Not found in config file');
    }

    private function getDefaultConfig(string $key): Result
    {
        $defaults = ['timeout' => 30, 'retries' => 3];

        return isset($defaults[$key])
            ? new Ok($defaults[$key])
            : new Err("No default value for $key");
    }

    public function setConfig(string $key, mixed $value): void
    {
        $this->configs[$key] = $value;
    }
}

$manager = new ConfigManager();
$manager->setConfig('database_url', 'mysql://localhost/mydb');

$validDatabase = $manager->getConfig('database_url')
    ->map(fn ($url) => parse_url($url))
    ->isOkAnd(fn ($parsed) => isset($parsed['scheme'], $parsed['host'], $parsed['path']));

echo $validDatabase
    ? 'Valid database configuration found'
    : 'Invalid or missing database configuration';

echo 'Timeout set to: '.$manager->getConfig('timeout')->expect('Timeout configuration is required');
```

## Using them together

Use Option when the honest answer is "nothing there". Use Result when the caller must hear why it failed. Convert at that boundary. The conversions return Option and Result directly.

`okOr` and `okOrElse` turn absence into a failure. `Some` becomes `Ok`. `None` becomes `Err`.

```php
<?php

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Option;
use Brimmar\PhpOption\Some;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;

class Settings
{
    private array $configs = [];

    public function get(string $key): Option
    {
        return isset($this->configs[$key]) ? new Some($this->configs[$key]) : new None();
    }

    public function set(string $key, mixed $value): void
    {
        $this->configs[$key] = $value;
    }

    public function required(string $key): Result
    {
        return $this->get($key)->okOr("Required configuration '$key' is missing");
    }

    public function databaseUrl(): Result
    {
        return $this->required('database_url')->andThen(function ($url) {
            $parsed = parse_url($url);

            return isset($parsed['scheme'], $parsed['host'], $parsed['path'])
                ? new Ok($url)
                : new Err('Invalid database URL format');
        });
    }
}

$settings = new Settings();
$settings->set('database_url', 'mysql://localhost/mydb');
$settings->set('debug', true);

echo $settings->get('debug')->unwrapOr(false) ? 'Debug mode is ON' : 'Debug mode is OFF';

echo $settings->databaseUrl()->match(
    Ok: fn ($url) => "Connected to: $url",
    Err: fn ($error) => "Error: $error",
);

echo $settings->required('api_key')->match(
    Ok: fn ($key) => "API Key: $key",
    Err: fn ($error) => "Error: $error",
); // Error: Required configuration 'api_key' is missing
```

`okOrElse` builds that error lazily, so a `Some` never runs the closure.

```php
$option = new Some(42);
$option->okOrElse(fn () => 'No value')->unwrap(); // 42

$missing = new None();
$missing->okOrElse(fn () => 'No value')->unwrapErr(); // No value
```

`ok` keeps the success and drops the error. `err` keeps the error and drops the success.

```php
(new Ok(42))->ok()->unwrap(); // 42
(new Err('missing'))->ok()->isNone(); // true

(new Ok(42))->err()->isNone(); // true
(new Err('missing'))->err()->unwrap(); // missing
```

`transpose` swaps the nesting.

An Option of a Result becomes a Result of an Option. `Some(Ok(v))` becomes `Ok(Some(v))`. `Some(Err(e))` becomes `Err(e)`. `None` becomes `Ok(None)`.

```php
(new Some(new Ok(42)))->transpose()->unwrap()->unwrap(); // 42
(new Some(new Err('error')))->transpose()->unwrapErr(); // error
(new None())->transpose()->unwrap()->isNone(); // true
```

A Result of an Option becomes an Option of a Result. `Ok(Some(v))` becomes `Some(Ok(v))`. `Ok(None)` becomes `None`. `Err(e)` becomes `Some(Err(e))`.

```php
(new Ok(new Some(42)))->transpose()->unwrap()->unwrap(); // 42
(new Ok(new None()))->transpose()->isNone(); // true
(new Err('error'))->transpose()->unwrap()->unwrapErr(); // error
```

A lookup is Option. An update that requires the row is Result. `okOr` is the handoff.

```php
<?php

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Option;
use Brimmar\PhpOption\Some;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;

class UserService
{
    /** @var array<int, array{name: string, email: string, activities?: list<int>}> */
    private array $users = [];

    public function addUser(int $id, array $user): void
    {
        $this->users[$id] = $user;
    }

    public function findUser(int $id): Option
    {
        return isset($this->users[$id]) ? new Some($this->users[$id]) : new None();
    }

    public function updateUser(int $id, array $data): Result
    {
        return $this->findUser($id)
            ->okOr('User not found')
            ->andThen(fn () => $this->validateUserData($data))
            ->map(fn ($validData) => array_merge($this->users[$id], $validData))
            ->inspect(function ($updatedUser) use ($id) {
                $this->users[$id] = $updatedUser;
            });
    }

    private function validateUserData(array $data): Result
    {
        $errors = array_filter([
            'name' => strlen($data['name'] ?? '') < 2 ? 'Name too short' : null,
            'email' => filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL) ? null : 'Invalid email',
        ]);

        return $errors === [] ? new Ok($data) : new Err($errors);
    }

    public function score(int $id): Option
    {
        return $this->findUser($id)->andThen(function ($user) {
            return isset($user['activities']) ? new Some(array_sum($user['activities'])) : new None();
        });
    }
}

$service = new UserService();
$service->addUser(1, ['name' => 'Alice', 'email' => 'alice@example.com', 'activities' => [2, 3]]);

echo $service->updateUser(1, ['name' => 'Alicia', 'email' => 'alicia@example.com'])->match(
    Ok: fn ($user) => $user['name'],
    Err: fn ($error) => 'Update failed',
); // Alicia

echo $service->findUser(99)
    ->okOr('User not found')
    ->match(
        Ok: fn ($user) => $user['name'],
        Err: fn ($error) => $error,
    ); // User not found

echo $service->score(1)->unwrapOr(0); // 5
```

## Null and exceptions

The PHP code you call returns `null` or throws. Convert on the line that calls it. Convert back on the line that hands a value to code expecting `null` or an exception. Everything between those two lines is Option and Result.

| You have | You want | Call |
| --- | --- | --- |
| `?T` | `Option<T>` | `Option::fromNullable($value)` |
| a call that throws | `Result<T, E>` | `Result::try(fn () => ...)` |
| `Option<T>` | `Result<T, E>` | `->okOr($error)` |
| `Result<T, E>` | `Option<T>` | `->ok()` |
| `Option<T>` or `Result<T, E>` | `?T` | `->unwrapOr(null)` |
| `Result<T, E>` | `T`, or `E` thrown | `->unwrapOrThrow()` |

### Null

`Option::fromNullable()` turns `null` into `None` and every other value into `Some`. `0`, `''` and `false` are values.

```php
$user = Option::fromNullable($users->find($id)); // Option<User>

$name = $user->map(fn (User $user) => $user->name)->unwrapOr('guest');
```

`unwrapOr(null)` goes back to a nullable, for a template or a `??`.

```php
$email = $user->map(fn (User $user) => $user->email)->unwrapOr(null); // ?string
```

`okOr()` turns the missing value into a failure that says why. `okOrElse()` builds that error only for `None`.

```php
$user = Option::fromNullable($users->find($id))->okOr(new UserNotFound($id)); // Result<User, UserNotFound>
```

### Exceptions

`Result::try()` calls a closure and returns its value in `Ok`. A thrown exception becomes the `Err`.

```php
$config = Result::try(fn () => json_decode($json, true, flags: JSON_THROW_ON_ERROR), JsonException::class);
// Result<mixed, JsonException>
```

List the classes to catch after the closure. Any other exception keeps propagating.

```php
Result::try(fn () => $client->get($url), ConnectException::class, TimeoutException::class);
// Result<Response, ConnectException|TimeoutException>
```

With no class, `try` catches `\Exception`. It lets `\Error` through, because a `TypeError` or a method call on `null` is a bug to fix. Pass `\Throwable::class` to catch those as well.

`mapErr()` turns the exception into your own error.

```php
$config = Result::try(fn () => json_decode($json, true, flags: JSON_THROW_ON_ERROR), JsonException::class)
    ->mapErr(fn (JsonException $e) => new InvalidConfig($path, previous: $e));
```

`unwrapOrThrow()` goes the other way. It returns the `Ok` value. On `Err` it throws the error itself when the error is a `\Throwable`. Use it where the surrounding code expects an exception, such as a controller with an exception handler, or a database transaction that rolls back on a throw.

```php
$order = $db->transaction(fn () => $this->placeOrder($cart)->unwrapOrThrow());
```

An error that is not a `\Throwable` is thrown inside a `RuntimeException`, the way `unwrap()` throws it. Map it first to pick the exception.

```php
$user = $this->findUser($id)
    ->mapErr(fn (string $reason) => new NotFoundHttpException($reason))
    ->unwrapOrThrow();
```

`unwrap()` and `expect()` are for the failure that should never happen. When they throw on an `Err` holding a `\Throwable`, that error is the `previous` exception, so its stack trace reaches your log.

### Several steps in one function

`andThen` chains the steps. Each step fails with its own error type, and the Result collects them.

```php
/** @return Result<Receipt, InvalidCart|OutOfStock|PaymentDeclined> */
public function checkout(Cart $cart): Result
{
    return $this->validate($cart)
        ->andThen(fn (Cart $cart) => $this->reserve($cart))
        ->andThen(fn (Reservation $reservation) => $this->charge($reservation));
}
```

A chain gets awkward when the last step needs the values of two earlier ones. `Result::gen()` runs a generator instead. Each step is `yield from $result->step()`. `Ok` hands its value back. The first `Err` stops the generator and becomes the return value, like `?` in Rust. Whatever the generator returns comes back in `Ok`.

```php
/** @return Result<Order, JsonException|UserNotFound|OutOfStock|PaymentDeclined> */
public function checkout(string $payload): Result
{
    return Result::gen(function () use ($payload) {
        $data = yield from Result::try(
            fn () => json_decode($payload, true, flags: JSON_THROW_ON_ERROR),
            JsonException::class,
        )->step();
        $user = yield from Option::fromNullable($this->users->find($data['user_id']))
            ->okOr(new UserNotFound)
            ->step();
        $reservation = yield from $this->reserve($data['items'])->step();
        $receipt = yield from $this->charge($user, $reservation)->step();

        return new Order($user, $reservation, $receipt);
    });
}
```

The first step throws, the second returns `null`, and the last two return a Result. All four read the same way.

PHPStan and your IDE know every type here with no extension. `$user` is a `User`, and the error type of `checkout` is the union of the four steps.

`$user = yield $result;` runs the same way and is shorter. Its value is `mixed` to an IDE, and to PHPStan unless the [Result extension](#result-extension) is installed.

Without a generator, return the `Err` yourself. `isErr()` narrows the variable to an `Err` that fits any success type.

```php
$reservation = $this->reserve($items);
if ($reservation->isErr()) {
    return $reservation;
}

$receipt = $this->charge($user, $reservation->unwrap());
```

## Option methods

#### `Option::fromNullable(mixed $value): Option`

`null` becomes `None`. Every other value becomes `Some`.

```php
Option::fromNullable(null)->isNone(); // true
Option::fromNullable(0)->unwrap(); // 0
```

#### `isSome(): bool`

Returns `true` when the option is `Some`.

```php
(new Some(42))->isSome(); // true
(new None())->isSome(); // false
```

#### `isSomeAnd(callable $fn): bool`

Returns `true` when the option is `Some` and the value matches the predicate.

```php
(new Some(42))->isSomeAnd(fn ($value) => $value > 40); // true
(new Some(42))->isSomeAnd(fn ($value) => $value < 40); // false
(new None())->isSomeAnd(fn ($value) => $value > 0); // false
```

#### `isNone(): bool`

Returns `true` when the option is `None`.

```php
(new Some(42))->isNone(); // false
(new None())->isNone(); // true
```

#### `iter(): Iterator`

Iterates the contained value. `None` yields nothing. An array is one item. Walk the elements with `foreach ($option->unwrapOr([]) as $item)`.

```php
foreach ((new Some(42))->iter() as $value) {
    echo $value; // 42
}
```

#### `unwrap(): mixed`

Returns the contained value. `None` throws `RuntimeException`.

```php
(new Some(42))->unwrap(); // 42
```

#### `expect(string $msg): mixed`

Returns the contained value. `None` throws `RuntimeException` with `$msg`.

```php
(new Some(42))->expect('Value should be present'); // 42
```

#### `flatten(): Option`

Collapses `Option<Option<T>>` to `Option<T>`.

```php
(new Some(new Some(42)))->flatten()->unwrap(); // 42
(new Some(new None()))->flatten()->isNone(); // true
```

#### `unwrapOr(mixed $default): mixed`

Returns the contained value, or `$default` for `None`.

```php
(new Some(42))->unwrapOr(0); // 42
(new None())->unwrapOr(0); // 0
```

#### `unwrapOrElse(callable $default): mixed`

Returns the contained value, or calls `$default` for `None`.

```php
(new Some(42))->unwrapOrElse(fn () => 0); // 42
(new None())->unwrapOrElse(fn () => 0); // 0
```

#### `map(callable $fn): Option`

Applies `$fn` to a `Some` value. `None` stays `None`.

```php
(new Some(42))->map(fn ($x) => $x * 2)->unwrap(); // 84
(new None())->map(fn ($x) => $x * 2)->isNone(); // true
```

#### `mapOr(mixed $default, callable $fn): mixed`

Applies `$fn` to a `Some` value, or returns `$default` for `None`.

```php
(new Some(42))->mapOr(0, fn ($x) => $x * 2); // 84
(new None())->mapOr(0, fn ($x) => $x * 2); // 0
```

#### `mapOrElse(callable $default, callable $fn): mixed`

Applies `$fn` to a `Some` value, or calls `$default` for `None`.

```php
(new Some(42))->mapOrElse(fn () => 0, fn ($x) => $x * 2); // 84
(new None())->mapOrElse(fn () => 0, fn ($x) => $x * 2); // 0
```

#### `inspect(callable $fn): Option`

Calls `$fn` with the contained value when it is `Some`, then returns the same option.

```php
echo (new Some(42))->inspect(function ($x) { echo "Got: $x "; })->unwrap(); // Got: 42 42
```

#### `okOr(mixed $error): Result`

`Some(v)` becomes `Ok(v)`. `None` becomes `Err($error)`.

```php
(new Some(42))->okOr('No value')->unwrap(); // 42
(new None())->okOr('No value')->unwrapErr(); // No value
```

#### `okOrElse(callable $error): Result`

`Some(v)` becomes `Ok(v)`. `None` becomes `Err($error())`. The closure runs only for `None`.

```php
(new Some(42))->okOrElse(fn () => 'No value')->unwrap(); // 42
(new None())->okOrElse(fn () => 'No value')->unwrapErr(); // No value
```

#### `and(Option $opt): Option`

Returns `$opt` when this option is `Some`. `None` stays `None`.

```php
(new Some(42))->and(new Some(10))->unwrap(); // 10
(new None())->and(new Some(10))->isNone(); // true
```

#### `andThen(callable $fn): Option`

Calls `$fn` with the contained value when this option is `Some`. `$fn` must return an Option. `None` stays `None`.

```php
(new Some(42))->andThen(fn ($x) => new Some($x * 2))->unwrap(); // 84
(new None())->andThen(fn ($x) => new Some($x * 2))->isNone(); // true
```

#### `or(Option $opt): Option`

Returns this option when it is `Some`, otherwise `$opt`.

```php
(new Some(42))->or(new Some(10))->unwrap(); // 42
(new None())->or(new Some(10))->unwrap(); // 10
```

#### `orElse(callable $fn): Option`

Returns this option when it is `Some`. `None` calls `$fn`, which must return an Option.

```php
(new Some(42))->orElse(fn () => new Some(10))->unwrap(); // 42
(new None())->orElse(fn () => new Some(10))->unwrap(); // 10
```

#### `transpose(): Result`

Turns `Option<Result<U, E>>` into `Result<Option<U>, E>`.

`Some(Ok(v))` becomes `Ok(Some(v))`. `Some(Err(e))` becomes `Err(e)`. `None` becomes `Ok(None)`.

```php
(new Some(new Ok(42)))->transpose()->unwrap()->unwrap(); // 42
(new Some(new Err('error')))->transpose()->unwrapErr(); // error
(new None())->transpose()->unwrap()->isNone(); // true
```

#### `xor(Option $opt): Option`

Returns `Some` when exactly one of the two options is `Some`. Two `Some` values, or two `None` values, become `None`.

```php
(new Some(42))->xor(new None())->unwrap(); // 42
(new Some(42))->xor(new Some(10))->isNone(); // true
```

#### `zip(Option $other): Option`

Pairs the two contained values. A `None` on either side makes the result `None`.

```php
(new Some(42))->zip(new Some('hello'))->unwrap(); // [42, 'hello']
(new Some(42))->zip(new None())->isNone(); // true
```

#### `zipWith(Option $other, callable $fn): Option`

Pairs the two contained values through `$fn`. A `None` on either side makes the result `None`.

```php
(new Some(42))->zipWith(new Some(10), fn ($a, $b) => $a + $b)->unwrap(); // 52
(new Some(42))->zipWith(new None(), fn ($a, $b) => $a + $b)->isNone(); // true
```

#### `unzip(): array`

Splits `Some([a, b])` into `[Some(a), Some(b)]`. `None` becomes `[None, None]`.

```php
[$a, $b] = (new Some([42, 'hello']))->unzip();
$a->unwrap(); // 42
$b->unwrap(); // hello

[$a, $b] = (new None())->unzip();
$a->isNone(); // true
$b->isNone(); // true
```

#### `match(callable $Some, callable $None): mixed`

Runs `$Some` for a value and `$None` for absence.

```php
(new Some(42))->match(
    Some: fn ($x) => "Value is $x",
    None: fn () => 'No value',
); // Value is 42

(new None())->match(
    Some: fn ($x) => "Value is $x",
    None: fn () => 'No value',
); // No value
```

#### `filter(callable $predicate): Option`

Keeps a `Some` whose value passes `$predicate`. A failing predicate, and `None`, both return `None`.

```php
(new Some(42))->filter(fn ($x) => $x > 40)->unwrap(); // 42
(new Some(42))->filter(fn ($x) => $x < 40)->isNone(); // true
(new None())->filter(fn ($x) => $x > 0)->isNone(); // true
```

## Result methods

#### `Result::try(callable $fn, string $catch = Exception::class, string ...$orCatch): Result`

Calls `$fn` and returns its value in `Ok`. An exception of a listed class becomes `Err`. Any other exception is rethrown. With no class it catches `\Exception` and lets `\Error` through.

```php
Result::try(fn () => intdiv(10, 2))->unwrap(); // 5
Result::try(fn () => json_decode('{', flags: JSON_THROW_ON_ERROR), JsonException::class)->isErr(); // true
Result::try(fn () => intdiv(1, 0), DivisionByZeroError::class)->isErr(); // true
```

#### `Result::gen(callable $fn): Result`

Runs a generator. Each step is a Result, written `yield from $result->step()`. `Ok` hands its value back to the generator. The first `Err` stops the generator and is returned. The generator's return value comes back in `Ok`.

```php
Result::gen(function () {
    $a = yield from (new Ok(40))->step();
    $b = yield from (new Ok(2))->step();

    return $a + $b;
})->unwrap(); // 42

Result::gen(function () {
    $a = yield from (new Ok(40))->step();
    $b = yield from (new Err('no second number'))->step();

    return $a + $b;
})->unwrapErr(); // no second number
```

`yield $result` is a step too. Its value has no type without the Result extension.

#### `isOk(): bool`

Returns `true` when the result is `Ok`.

```php
(new Ok(42))->isOk(); // true
(new Err('error'))->isOk(); // false
```

#### `isOkAnd(callable $fn): bool`

Returns `true` when the result is `Ok` and the value matches the predicate.

```php
(new Ok(42))->isOkAnd(fn ($value) => $value > 40); // true
(new Ok(42))->isOkAnd(fn ($value) => $value < 40); // false
(new Err('error'))->isOkAnd(fn ($value) => true); // false
```

#### `isErr(): bool`

Returns `true` when the result is `Err`.

```php
(new Ok(42))->isErr(); // false
(new Err('error'))->isErr(); // true
```

#### `isErrAnd(callable $fn): bool`

Returns `true` when the result is `Err` and the error matches the predicate.

```php
(new Err('error'))->isErrAnd(fn ($error) => $error === 'error'); // true
(new Err('error'))->isErrAnd(fn ($error) => $error === 'other'); // false
(new Ok(42))->isErrAnd(fn ($error) => true); // false
```

#### `ok(): Option`

`Ok(v)` becomes `Some(v)`. `Err` becomes `None`. The error is dropped.

```php
(new Ok(42))->ok()->unwrap(); // 42
(new Err('error'))->ok()->isNone(); // true
```

#### `err(): Option`

`Err(e)` becomes `Some(e)`. `Ok` becomes `None`. The success value is dropped.

```php
(new Ok(42))->err()->isNone(); // true
(new Err('error'))->err()->unwrap(); // error
```

#### `unwrap(): mixed`

Returns the contained `Ok` value. `Err` throws `RuntimeException`. An error that is a `Throwable` is the `previous` exception.

```php
(new Ok(42))->unwrap(); // 42
```

#### `unwrapOrThrow(): mixed`

Returns the contained `Ok` value. `Err` throws the error itself when the error is a `Throwable`. Any other error is thrown inside a `RuntimeException`, the way `unwrap()` throws it.

```php
(new Ok(42))->unwrapOrThrow(); // 42
(new Err(new DomainException('no user')))->unwrapOrThrow(); // throws that DomainException
```

#### `step(): Generator`

One step of `Result::gen()`. `yield from $result->step()` is the contained `Ok` value. An `Err` ends the generator, and `Result::gen()` returns that `Err`. Outside `Result::gen()` it does nothing useful.

```php
Result::gen(function () {
    $id = yield from (new Ok(7))->step(); // 7

    return $id * 2;
})->unwrap(); // 14
```

#### `unwrapErr(): mixed`

Returns the contained `Err` value. `Ok` throws `RuntimeException`.

```php
(new Err('error'))->unwrapErr(); // error
```

#### `expect(string $msg): mixed`

Returns the contained `Ok` value. `Err` throws `RuntimeException` with `$msg`. An error that is a `Throwable` is the `previous` exception.

```php
(new Ok(42))->expect('Failed to get value'); // 42
```

#### `expectErr(string $msg): mixed`

Returns the contained `Err` value. `Ok` throws `RuntimeException` with `$msg`.

```php
(new Err('error'))->expectErr('Failed to get error'); // error
```

#### `intoOk(): mixed`

Returns the contained `Ok` value. `Err` throws `RuntimeException`.

```php
(new Ok(42))->intoOk(); // 42
```

#### `intoErr(): mixed`

Returns the contained `Err` value. `Ok` throws `RuntimeException`.

```php
(new Err('error'))->intoErr(); // error
```

#### `flatten(): Result`

Collapses `Result<Result<T, E>, E>` to `Result<T, E>`. `Err` is returned unchanged, even when the error is a `Result`.

```php
(new Ok(new Ok(42)))->flatten()->unwrap(); // 42
(new Ok(new Err('inner error')))->flatten()->unwrapErr(); // inner error
(new Err('outer error'))->flatten()->unwrapErr(); // outer error
```

#### `iter(): Iterator`

Iterates the contained `Ok` value. `Err` yields nothing. An array is one item. Walk the elements with `foreach ($result->unwrapOr([]) as $item)`.

```php
foreach ((new Ok(42))->iter() as $value) {
    echo $value; // 42
}
```

#### `unwrapOr(mixed $default): mixed`

Returns the contained `Ok` value, or `$default` for `Err`.

```php
(new Ok(42))->unwrapOr(0); // 42
(new Err('error'))->unwrapOr(0); // 0
```

#### `unwrapOrElse(callable $fn): mixed`

Returns the contained `Ok` value, or calls `$fn` with the error.

```php
(new Ok(42))->unwrapOrElse(fn () => 0); // 42
(new Err('error'))->unwrapOrElse(fn ($error) => strlen($error)); // 5
```

#### `map(callable $fn): Result`

Applies `$fn` to an `Ok` value. `Err` stays `Err`.

```php
(new Ok(42))->map(fn ($value) => $value * 2)->unwrap(); // 84
(new Err('error'))->map(fn ($value) => $value * 2)->unwrapErr(); // error
```

#### `mapErr(callable $fn): Result`

Applies `$fn` to an `Err` value. `Ok` stays `Ok`.

```php
(new Err('error'))->mapErr(fn ($error) => strtoupper($error))->unwrapErr(); // ERROR
(new Ok(42))->mapErr(fn ($error) => strtoupper($error))->unwrap(); // 42
```

#### `mapOr(mixed $default, callable $fn): mixed`

Applies `$fn` to an `Ok` value, or returns `$default` for `Err`.

```php
(new Ok(42))->mapOr(0, fn ($value) => $value * 2); // 84
(new Err('error'))->mapOr(0, fn ($value) => $value * 2); // 0
```

#### `mapOrElse(callable $default, callable $fn): mixed`

Applies `$fn` to an `Ok` value, or `$default` to an `Err` value.

```php
(new Ok(42))->mapOrElse(fn ($error) => strlen($error), fn ($value) => $value * 2); // 84
(new Err('error'))->mapOrElse(fn ($error) => strlen($error), fn ($value) => $value * 2); // 5
```

#### `inspect(callable $fn): Result`

Calls `$fn` with the contained value when it is `Ok`, then returns the same result.

```php
echo (new Ok(42))->inspect(function ($value) { echo "Got value: $value "; })->unwrap(); // Got value: 42 42
```

#### `inspectErr(callable $fn): Result`

Calls `$fn` with the contained error when it is `Err`, then returns the same result.

```php
echo (new Err('error'))->inspectErr(function ($error) { echo "Got error: $error "; })->unwrapErr(); // Got error: error error
```

#### `and(Result $res): Result`

Returns `$res` when this result is `Ok`. An `Err` is returned unchanged.

```php
(new Ok(42))->and(new Ok(10))->unwrap(); // 10
(new Err('error'))->and(new Ok(10))->unwrapErr(); // error
```

#### `andThen(callable $fn): Result`

Calls `$fn` with the contained value when this result is `Ok`. `$fn` must return a Result. An `Err` is returned unchanged.

```php
(new Ok(42))->andThen(fn ($value) => new Ok($value * 2))->unwrap(); // 84
(new Err('error'))->andThen(fn ($value) => new Ok($value * 2))->unwrapErr(); // error
```

#### `or(Result $res): Result`

Returns this result when it is `Ok`, otherwise `$res`.

```php
(new Ok(42))->or(new Ok(10))->unwrap(); // 42
(new Err('error1'))->or(new Ok(10))->unwrap(); // 10
```

#### `orElse(callable $fn): Result`

Returns this result when it is `Ok`. An `Err` calls `$fn` with the error. `$fn` must return a Result.

```php
(new Ok(42))->orElse(fn ($error) => new Ok($error.' handled'))->unwrap(); // 42
(new Err('error'))->orElse(fn ($error) => new Ok($error.' handled'))->unwrap(); // error handled
```

#### `transpose(): Option`

Turns `Result<Option<U>, E>` into `Option<Result<U, E>>`.

`Ok(Some(v))` becomes `Some(Ok(v))`. `Ok(None)` becomes `None`. `Err(e)` becomes `Some(Err(e))`.

```php
(new Ok(new Some(42)))->transpose()->unwrap()->unwrap(); // 42
(new Ok(new None()))->transpose()->isNone(); // true
(new Err('error'))->transpose()->unwrap()->unwrapErr(); // error
```

#### `match(callable $Ok, callable $Err): mixed`

Runs `$Ok` for a success and `$Err` for a failure.

```php
(new Ok(42))->match(
    Ok: fn ($value) => "Success: $value",
    Err: fn ($error) => "Error: $error",
); // Success: 42

(new Err('error'))->match(
    Ok: fn ($value) => "Success: $value",
    Err: fn ($error) => "Error: $error",
); // Error: error
```

## Static analysis

PHPStan reads the generics from this package, with no extension. `Result::try(fn () => ..., JsonException::class)` is a `Result<T, JsonException>`. `andThen` adds the error type of each step. `yield from $result->step()` is the `Ok` type of that Result. `isOk()`, `isErr()`, `isSome()` and `isNone()` narrow both branches. The type parameters are covariant, so a `Result<int, NotFound>` is accepted where `Result<int, NotFound|Invalid>` is declared.

Two PHPStan extensions add the checks below. They are optional, and each one stands on its own. `brimmar/phpstan-rustlike-option-extension` checks Option. `brimmar/phpstan-rustlike-result-extension` checks Result. This version of the package needs their 3.0 releases and PHPStan 2.3. Composer refuses the older ones, because they look for the `Interfaces\Result` and `Interfaces\Option` names and would report nothing. Add the neon file for the one you install.

```bash
composer require --dev brimmar/phpstan-rustlike-option-extension brimmar/phpstan-rustlike-result-extension
```

```neon
includes:
    - vendor/brimmar/phpstan-rustlike-option-extension/extension.neon
    - vendor/brimmar/phpstan-rustlike-result-extension/extension.neon
```

### Option extension

`unwrap()` and `expect()` throw when the value is `None`. PHPStan accepts the call when the receiver is `Some`, including `(new Some(1))->unwrap()` and the lines after `if ($option->isNone()) return`. A bare `Option`, a check on a different variable, and the `isNone()` branch are `option.unsafeMethod`.

```php
function label(Option $option): string
{
    return $option->unwrap(); // option.unsafeMethod
}
```

```php
function label(Option $option): string
{
    if ($option->isNone()) {
        return 'missing';
    }

    return $option->unwrap();
}
```

`isSomeAnd()` narrows the true branch only. `isNone()` narrows to `None<never>`, so `return $option;` in that branch fits a function that returns an `Option` of another type.

`match()` accepts `Some` and `None` in either order. A positional call or a different name is `option.matchNamedArgs`. One argument is `option.matchArgs`.

```php
$option->match(
    None: fn () => 'missing',
    Some: fn ($value) => $value,
);
```

An unused `Option` is `option.unused`. That includes `new Some(1);` and a function of yours. The pipe operator does not hide it.

```php
/** @return Option<int> */
function find(int $id): Option
{
    return new Some($id);
}

find(1); // option.unused

$_ = find(1);
```

Methods that return an `Option` already use `#[\NoDiscard]`. Ignoring one is `method.resultDiscarded`. `(void)` acknowledges that drop. On a function without the attribute, the same cast is `function.inVoidCast`, so assign the call to `$_` instead.

```php
(void) $option->map(fn ($value) => $value);
```

`flatten()`, `transpose()`, and `unzip()` are reported when the contained type is definitely wrong. `option.flatten` wants `Option<Option<T>>`. `option.transpose` wants `Option<Result<T, E>>`. `option.unzip` wants an array. `mixed` and a template type are left alone.

```php
/** @param Option<int> $option */
function collapsed(Option $option): mixed
{
    return $option->flatten(); // option.flatten
}
```

### Result extension

`unwrap()`, `expect()`, and `intoOk()` throw on `Err`. `unwrapErr()`, `expectErr()`, and `intoErr()` throw on `Ok`. The call is `result.unsafeMethodCall` while that side is still possible. An `Ok` value passes the success calls, and an `Err` value passes the failure calls. `isOk()` and `isErr()` narrow a `Result`, so the matching call is accepted in that branch and after an early return. `Result<T, never>` accepts the success calls. `Result<never, E>` accepts the failure calls. The contained type stays intact, so `unwrap()` on `Result<int, string>` is still `int` after `isErr()` returns early.

```php
/** @param Result<int, string> $result */
function id(Result $result): int
{
    return $result->unwrap(); // result.unsafeMethodCall
}
```

```php
/** @param Result<int, string> $result */
function id(Result $result): int
{
    if ($result->isErr()) {
        return 0;
    }

    return $result->unwrap();
}
```

The narrowed types are `Ok<T, never>` and `Err<never, E>`. An `Err` narrowed this way fits a function that returns a `Result` with another success type, so the early return needs no conversion.

```php
/**
 * @param Result<int, string> $id
 * @return Result<User, string>
 */
function load(Result $id): Result
{
    if ($id->isErr()) {
        return $id;
    }

    return findUser($id->unwrap());
}
```

`isOkAnd()` and `isErrAnd()` narrow the true branch only. `unwrapErr()` in the else of `isOkAnd()` is still `result.unsafeMethodCall`.

`unwrapOrThrow()` throws the error itself, so the extension accepts the call while every possible error is a `Throwable`. For any other error type it throws a `RuntimeException` the way `unwrap()` does, and the call is `result.unsafeMethodCall`.

```php
/** @param Result<User, UserNotFound> $result */
function user(Result $result): User
{
    return $result->unwrapOrThrow();
}

/** @param Result<User, string> $result */
function user(Result $result): User
{
    return $result->unwrapOrThrow(); // result.unsafeMethodCall
}
```

The extension also types the short step. Inside the closure given to `Result::gen()`, a plain `yield $result` has the `Ok` type of that Result. PHPStan reads it as `mixed` without the extension. A `yield` in any other generator keeps its own type.

```php
Result::gen(function () {
    $user = yield $this->findUser(1); // User
    $order = yield $this->lastOrder($user); // Order

    return $order->total;
});
```

`map()` and `mapErr()` transform a value. A `throw` inside the callback leaves the Result, and the extension reports it as `result.mapException`. Return an `Err` from `andThen` when the failure belongs in the Result.

```php
$result->map(fn (int $id) => $id > 0 ? $id : throw new RuntimeException('bad id')); // result.mapException

$result->andThen(fn (int $id) => $id > 0 ? new Ok($id) : new Err('bad id'));
```

`match()` accepts `Ok` and `Err` in either order. A positional call or a different name is `result.matchNamedArgs`.

```php
$result->match(
    Err: fn ($error) => $error,
    Ok: fn ($value) => $value,
);
```

`flatten()` and `transpose()` are reported when the contained type is definitely wrong. `result.flatten` wants `Result<Result<T, E>, E>`. `result.transpose` wants `Result<Option<T>, E>`.

```php
/** @param Result<int, string> $result */
function collapsed(Result $result): mixed
{
    return $result->flatten(); // result.flatten
}
```

A dropped `Result` is `result.unused`. `new Ok(1);` counts. `Result<int, never>` does not, because that error cannot exist. `Result<Option<int>, never>` still does, because the option inside may be `None`. `(void)` and `$_` work the same way as they do for Option.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md).

## Security vulnerabilities

Please see [SECURITY.md](SECURITY.md).

## License

This project is licensed under the MIT License. See [LICENSE.md](LICENSE.md).
