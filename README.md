# Governator

[![CI Status](https://github.com/zenstruck/governator/workflows/CI/badge.svg)](https://github.com/zenstruck/governator/actions?query=workflow%3ACI)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zenstruck/governator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zenstruck/governator/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/zenstruck/governator/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/zenstruck/governator/?branch=master)

A generic *fixed window* rate limiting throttle with an intuitive and fluent API that supports multiple backends.

```php
use Zenstruck\Governator\ThrottleFactory;
use Zenstruck\Governator\Exception\QuotaExceeded;

try {
    $quota = ThrottleFactory::for('redis://localhost') // create using the redis backend
        ->throttle('something') // the "resource" to throttle
        ->allow(10) // the number of "hits" allowed in the "window"
        ->every(60) // the duration of the "window"
        ->acquire() // "acquire" a lock on the throttle, increasing it's "hit" count
    ;

    $quota->hits(); // 1
    $quota->remaining(); // 9
    $quota->resetsIn(); // 60 (seconds)
    $quota->resetsAt(); // \DateTimeInterface (+60 seconds)
} catch (QuotaExceeded $e) {
    // The lock could not be "acquired"
    $e->resetsIn(); // 50 (seconds)
    $e->resetsAt(); // \DateTimeInterface (+50 seconds)
    $e->hits(); // 11
    $e->remaining(); // 0
}
```

## Documentation

1. [Installation](#installation)
2. [Usage](#usage)
    1. [Fluent Throttle Builder](#fluent-throttle-builder)
    2. [ThrottleFactory *factory*](#throttlefactory-factory)
    3. [Resource Prefix](#resource-prefix)
3. [Available Stores](#available-stores)
    1. [Psr6 Cache Store](#psr6-cache-store)
    2. [Psr16 Cache Store](#psr16-cache-store)
    3. [Redis Store](#redis-store)
    4. [Memory Store](#memory-store)
    5. [Unlimited Store](#unlimited-store)
4. [Cookbook](#cookbook)
    1. [Symfony Integration](#symfony-integration)
        1. [Throttle Controller](#throttle-controller)
        2. [QuotaExceeded Exception Subscriber](#quotaexceeded-exception-subscriber)
        3. [Login Throttle](#login-throttle)
        4. [API Request Throttle](#api-request-throttle)
        5. [Message Handler "Funnel" Throttle](#message-handler-funnel-throttle)
5. [Credit](#credit)

## Installation

    $ composer require zenstruck/governator

## Usage

There are several different [rate limiting throttle strategies](https://konghq.com/blog/how-to-design-a-scalable-rate-limiting-algorithm/).
This library uses the *fixed window* strategy. The *window* is defined with a *limit* and a *duration*. The *hits* are
tracked within the *duration*. If a *hit* within the *duration* exceeds the *limit*, it is rejected (exception thrown).

A throttle has the following pieces:

1. **Store**: this is the backend that *persists* the throttle (see [Available Stores](#available-stores)).
2. **Resource**: a `string` representation of the resource you wish to throttle. Typically, a name and something to
distinguish different *throttles* per user like the request IP/username (ie `login-$ip-$username`).
3. **Limit**: the number of *hits* allowed within the *window*.
4. **TTL**: the duration (in seconds) of the *window*.
5. **Quota**: the current *state* of the throttle.

Throttle's are created from a `ThrottleFactory`. Let's create a throttle for the resource "something" that allows
*5 hits* every *10 seconds*:

```php
use Zenstruck\Governator\Store;
use Zenstruck\Governator\ThrottleFactory;

/** @var Store $store */

$throttle = (new ThrottleFactory($store))->create('something', 5, 10); // instance of Zenstruck\Governator\Throttle
```

*Hitting* the throttle returns a `Quota` object with details about the current state of the throttle:

```php
use Zenstruck\Governator\Throttle;

/** @var Throttle $throttle */

$quota = $throttle->hit(); // instance of Zenstruck\Governator\Quota

$quota->hits(); // 1
$quota->remaining(); // 4
$quota->resetsIn(); // 10 (seconds)
$quota->resetsAt(); // \DateTimeInterface (+10 seconds)
$quota->hasBeenExceeded(); // false

sleep(3);

$quota = $throttle->hit();

$quota->hits(); // 2
$quota->remaining(); // 3
$quota->resetsIn(); // 7 (seconds)
$quota->resetsAt(); // \DateTimeInterface (+7 seconds)
```

If *hitting* the throttle exceeds the *limit* within the *window*, a `Quota` is still returned. The `Quota::check()`
method throws a `QuotaExceeded` exception if exceeded:

```php
use Zenstruck\Governator\Exception\QuotaExceeded;
use Zenstruck\Governator\Throttle;

/** @var Throttle $throttle */

try {
    $throttle->hit()->check(); // instance of Quota
    $throttle->hit()->check(); // instance of Quota
    $throttle->hit()->check(); // instance of Quota

    // Continuing from the example above, this "hit" throws the exception as it will cause the limit of
    // 5 to be exceeded (within the 10 second window).
    $throttle->hit()->check();
} catch (QuotaExceeded $e) {
    $e->resetsIn(); // 7 (seconds)
    $e->resetsAt(); // \DateTimeInterface (+7 seconds)
    $e->hits(); // 6
    $e->remaining(); // 0
}
```

You can use the `->aquire()` method to always throw a `QuotaExceeded` exception without calling check:

```php
use Zenstruck\Governator\Throttle;

/** @var Throttle $throttle */

// Continuing from our example above, this will throw a QuotaExceeded exception
$throttle->acquire(); // equivalent to $throttle->hit()->check()
```

The `->acquire()` method can optionally take a *block for* parameter in *seconds*. If, when *hitting* the throttle, it
is exceeded, this is the maximum number of seconds to *block* the process waiting for the throttle to reset. If the time
to reset is greater than the passed seconds, no blocking will occur, and it will throw a `QuotaExceeded` exception
immediately.

```php
use Zenstruck\Governator\Throttle;

/** @var Throttle $throttle */

// Continuing from our example above, this will throw a QuotaExceeded exception immediately
// because the passed block for time is less than the window's TTL of 7 seconds.
$throttle->acquire(5); // throws QuotaExceeded exception

// This will block the process for 7 seconds (the time until the throttle resets) before
// returning a Quota object.
$throttle->acquire(10); // returns Quota (no exception)
```

You can get the status of a throttle (without increasing its "hits") with the `->status()` method.

```php
use Zenstruck\Governator\Throttle;

/** @var Throttle $throttle */

$quota = $throttle->status(); // assumes the throttle is empty

$quota->hits(); // 0

$throttle->hit();
$throttle->hit();

$throttle->status()->hits(); // 2
```

A throttle can be reset early:

```php
use Zenstruck\Governator\Throttle;

/** @var Throttle $throttle */

$throttle->reset();
```

### Fluent Throttle Builder

Throttles can alternatively be created via a fluent interface:

```php
use Zenstruck\Governator\ThrottleFactory;

/** @var ThrottleFactory $factory */

$factory->throttle('something')->allow(5)->every(10)->create(); // instance of Zenstruck\Governator\ThrottleFactory

// easily build resources
$factory->throttle('a', 'b')->with('c', 'd')->allow(5)->every(10)->create(); // resource = "abcd"

// call throttle methods directly
$factory->throttle('something')->allow(5)->every(10)->hit();
$factory->throttle('something')->allow(5)->every(10)->acquire();
$factory->throttle('something')->allow(5)->every(10)->acquire(10);
$factory->throttle('something')->allow(5)->every(10)->status();
$factory->throttle('something')->allow(5)->every(10)->reset();
```

### ThrottleFactory *factory*

Throttle Factory's can be created using the `::for()` named constructor. You can pass an object or string (DSN),
if supported, it will create the factory based on this:

```php
use Zenstruck\Governator\ThrottleFactory;

$factory = ThrottleFactory::for($objectOrDsn);
```

See the [Available Stores](#available-stores) section below for available objects/DSNs.

### Resource Prefix

You can customize the *prefix* for all your throttle *resources* (the default is `throtte_`). The prefix prevents
conflicts with other services using the same backend as your *store*. It also helps when auditing your store's backend.

```php
use Zenstruck\Governator\Store;
use Zenstruck\Governator\ThrottleFactory;

/** @var Store $store */

$factory = new ThrottleFactory($store, 'my-prefix_');

// alternative
$factory = ThrottleFactory::for($objectOrDsn, 'my-prefix-');
```

## Available Stores

### Psr6 Cache Store

```php
use Zenstruck\Governator\Store\Psr6CacheStore;
use Zenstruck\Governator\ThrottleFactory;

/** @var \Psr\Cache\CacheItemPoolInterface $cache */

$factory = new ThrottleFactory(new Psr6CacheStore($cache));

// alternative
$factory = ThrottleFactory::for($cache);
```

### Psr16 Cache Store

```php
use Zenstruck\Governator\Store\Psr16CacheStore;
use Zenstruck\Governator\ThrottleFactory;

/** @var \Psr\SimpleCache\CacheInterface $cache */

$factory = new ThrottleFactory(new Psr16CacheStore($cache));

// alternative
$factory = ThrottleFactory::for($cache);
```

### Redis Store

```php
use Zenstruck\Governator\Store\RedisStore;
use Zenstruck\Governator\ThrottleFactory;

/** @var \Redis|\RedisArray|\RedisCluser|Predis\ClientInterface $redis */

$factory = new ThrottleFactory(new RedisStore($redis));

// alternatives
$factory = ThrottleFactory::for($redis);

// this requires symfony/cache - see: https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#configure-the-connection
// for all DSN options
$factory = ThrottleFactory::for('redis://localhost');
```

### Memory Store

This store maintains the throttle in memory and is reset at the end of the current PHP process.

```php
use Zenstruck\Governator\Store\MemoryStore;
use Zenstruck\Governator\ThrottleFactory;

$factory = new ThrottleFactory(new MemoryStore());

// alternative
$factory = ThrottleFactory::for('memory');
```

### Unlimited Store

This store never allows the throttle to exceed its *quota* regardless of how the throttle was created (useful for
tests).

```php
use Zenstruck\Governator\Store\UnlimitedStore;
use Zenstruck\Governator\ThrottleFactory;

$factory = new ThrottleFactory(new UnlimitedStore());

// alternative
$factory = ThrottleFactory::for('unlimited');
```

## Cookbook

### Symfony Integration

To use Governator in Symfony, register `ThrottleFactory` as a service:

```yaml
# config/services.yaml

# create with a PSR-6 Store
Zenstruck\Governator\ThrottleFactory:
    arguments: ['@cache.app']
    factory: [Zenstruck\Governator\ThrottleFactory, 'for']

# create with a redis store
Zenstruck\Governator\ThrottleFactory:
    arguments: ['@my_redis_client'] # \Redis|\RedisArray|\RedisCluser|Predis\ClientInterface (registered elsewhere)
    factory: [Zenstruck\Governator\ThrottleFactory, 'for']

# create with a redis DSN (see https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#configure-the-connection)
Zenstruck\Governator\ThrottleFactory:
    arguments: ['%env(REDIS_THROTTLE_DSN)%'] # REDIS_THROTTLE_DSN=redis://localhost (in your .env)
    factory: [Zenstruck\Governator\ThrottleFactory, 'for']

# customize the prefix
Zenstruck\Governator\ThrottleFactory:
    arguments: ['@cache.app', 'my-prefix-']
    factory: [Zenstruck\Governator\ThrottleFactory, 'for']
```

#### Throttle Controller

One scenario is to rate limit a specific controller (assumes `ThrottleFactory` is
[registered as a service](#symfony-integration)):

```php
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @Route("/page", name="page")
 */
public function index(Request $request, ThrottleFactory $factory)
{
    // only allow 5 requests every 10 seconds per IP
    $factory->throttle('page', $request->getClientIp())->allow(5)->every(10)->acquire();

    // the above line with throw a QuotaExceeded exception if the limit has been exceeded

    // ...your controller's code as normal...
}
```

If your controller requires authentication, rate limit based on the username:

```php
use Symfony\Component\Security\Core\User\UserInterface;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @Route("/page", name="page")
 */
public function index(UserInterface $user, ThrottleFactory $factory)
{
    // only allow 5 requests every 10 seconds per username
    $factory->throttle('page', $user->getUsername())->allow(5)->every(10)->acquire();

    // the above line with throw a QuotaExceeded exception if the limit has been exceeded

    // ...your controller's code as normal...
}
```

Combine the above two (if your controller allows anonymous and authenticated users):

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @Route("/page", name="page")
 */
public function index(Request $request, ThrottleFactory $factory, UserInterface $user = null)
{
    if ($user) { // authenticated
        // allow 100 requests every 60 seconds per username (authenticated users have a higher rate limit)
        $factory->throttle('page', $user->getUsername())->allow(100)->every(60)->acquire();
    } else { // anonymous
        // allow 5 requests every 10 seconds per IP
        $factory->throttle('page', $request->getClientIp())->allow(5)->every(10)->acquire();
    }

    // ...your controller's code as normal...
}
```

A more advanced setup with multiple quota's:

```php
use Symfony\Component\Security\Core\User\UserInterface;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @Route("/page", name="page")
 */
public function index(UserInterface $user, ThrottleFactory $factory)
{
    // only allow 10 requests every 10 seconds
    $factory->throttle('page', 'short', $user->getUsername())->allow(10)->every(10)->acquire();

    // additionally, only allow 20 requests every 60 seconds
    $factory->throttle('page', 'long', $user->getUsername())->allow(20)->every(60)->acquire();

    // ...your controller's code as normal...
}
```

#### QuotaExceeded Exception Subscriber

When a `QuotaExceeded` exception is thrown by a web request, it is converted to a 500 status code by Symfony. We can
create an exception subscriber to change this behavior:

```php
// src/EventSubscriber/QuotaExceededSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Zenstruck\Governator\Exception\QuotaExceeded;

class QuotaExceededSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof QuotaExceeded) {
            return;
        }

        // convert QuotaExceeded exception to TooManyRequestsHttpException (429 status code)
        // and add some helpful response headers for the user
        $event->setThrowable(new TooManyRequestsHttpException($exception->resetsIn(), null, $exception, 0, [
            'X-RateLimit-Limit' => $exception->limit(),
            'X-RateLimit-Remaining' => $exception->remaining(),
            'X-RateLimit-Reset' => $exception->resetsAt()->getTimestamp(),
        ]));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => 'onKernelException',
        ];
    }
}
```

You can optionally [customize the production error page](https://symfony.com/doc/current/controller/error_pages.html).

#### Login Throttle

A common requirement for web apps is to rate limit login attempts to prevent abuse. There are several points Governator
can help with this. In this example, we'll throttle the attempt in your [Guard Authenticator's](https://symfony.com/doc/current/security/form_login_setup.html)
`getCredentials()` method:

```php
// your Symfony\Component\Security\Guard\AuthenticatorInterface
// assumes ThrottleFactory was injected to the service and is available as $this->throttleFactory

public function getCredentials(Request $request)
{
    $credentials = [
        'email' => $request->request->get('email'),
        'password' => $request->request->get('password'),
        'csrf_token' => $request->request->get('_csrf_token'),
    ];

    // only allow 5 attempts per email/ip a minute
    $this->throttleFactory
        ->throttle('login', $request->getClientIp(), $credentials['email'])
        ->allow(5)
        ->every(60)
        ->acquire() // throws QuotaExceeded if exceeded
    ;

    // ...
}
```

#### API Request Throttle

In this example, we'll rate limit an entire section of your site (`/api/*`). Additionally, we'll provide useful
information on the current state of a user's quota as response headers for the consumer:

```php
// src/App/EventSubscriber/ApiThrottleSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Zenstruck\Governator\Quota;
use Zenstruck\Governator\ThrottleFactory;
use function Symfony\Component\String\s;

class ApiThrottleSubscriber implements EventSubscriberInterface
{
    private ThrottleFactory $factory;
    private ?Quota $quota = null;

    public function __construct(ThrottleFactory $factory)
    {
        $this->factory = $factory;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            // not a "master" request
            return;
        }

        if (!s($event->getRequest()->getPathInfo())->startsWith('/api')) {
            // not an api request
            return;
        }

        $ip = $event->getRequest()->getClientIp();

        // only allow 5 api requests every 20 seconds
        // and set the returned quota for use in the response listener below
        // let QuotaExceeded exceptions bubble up (to the QuotaExceededSubscriber above)
        $this->quota = $this->factory->throttle('api', $ip)->allow(5)->every(20)->acquire();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->quota) {
            // quota was not set for this request
            return;
        }

        // add helpful response headers for the consumer
        $event->getResponse()->headers->add([
            'X-RateLimit-Limit' => $this->quota->limit(),
            'X-RateLimit-Remaining' => $this->quota->remaining(),
            'X-RateLimit-Reset' => $this->quota->resetsAt()->getTimestamp(),
        ]);

        $this->quota = null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
            'kernel.response' => 'onKernelResponse',
        ];
    }
}
```

#### Message Handler "Funnel" Throttle

Imagine a scenario where you want to use a 3rd party service to *geocode* a user's IP when they login. You handle this
with an asynchronous `Login` event using [Symfony Messenger](https://symfony.com/doc/current/messenger.html). The
problem is, this service has a rate limit of 1 request per 5 seconds. You can use Governator to throttle these requests
to a *funnel* that enforces the service's rate limit. Events that exceed this limit are re-queued:

```php
// src/App/MessageHandler/GeocodeLoginHandler.php

namespace App\MessageHandler;

use App\Message\Login;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Zenstruck\Governator\Exception\QuotaExceeded;
use Zenstruck\Governator\ThrottleFactory;

final class GeocodeIpHandler implements MessageHandlerInterface
{
    private ThrottleFactory $throttleFactory;
    private MessageBusInterface $bus;

    public function __construct(ThrottleFactory $throttleFactory, MessageBusInterface $bus)
    {
        $this->throttleFactory = $throttleFactory;
        $this->bus = $bus;
    }

    public function __invoke(Login $message): void
    {
        try {
            // only allow this job to be executed once every 5 seconds
            $this->throttleFactory
                ->throttle('geocoding-service')
                ->allow(1)
                ->every(5)
                ->acquire(2) // block for up to 2 seconds to wait for throttle to be available
            ;
        } catch (QuotaExceeded $e) {
            // rate limit of service exceeded
            // re-queue with delay
            $this->bus->dispatch($message, [new DelayStamp($e->resetsIn() * 1000)]);

            return;
        }

        // the geocoding service is available, do something...
    }
}
```

Because the handler is run in the background, we block the throttle *hit* for up to 2 seconds waiting for the service
to become available.

## Credit

This inspiration for this library's API comes from [Laravel](https://laravel.com/) and
[Symfony's Lock Component](https://symfony.com/doc/current/components/lock.html).
