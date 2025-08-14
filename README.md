# Warp Core PHP Microframework

Warp Core is a microframework with a focus on being small, fast, flexible, robust, and on following best practice.

* 🐁 **Small**: We aim to keep Warp Core as small as possible.  Excluding things like interfaces, tests, examples and other non-executable code, Warp Core doesn't exceed more than a couple of dozen classes.  The entire framework is focused on providing:
  * Request handling
  * Routing
  * Response dispatching
  * Middleware
  * Error handling
  * Nothing else!
* 🔤 **Simple**: We don't think software development should be rocket science.  A small codebase is a codebase that is easy to understand and work with.
* 🚀 **Fast**: Keeping it lean keeps it fast.  We try to avoid expensive operations such as string manipulation with regex if there is something simpler we can use to achieve the same goal 
* 🪄 **Flexible**: Warp Core embraces the philosophy of loose coupling and design by contract.  As such, almost every aspect of the framework can be supplemented or replaced with your own implementation.  It also supports middleware, providing you many ways to build a rich application on a simple core
* 🚜 **Robust**: Great effort has been put into keeping Warp Core from blowing up in your face.  Every effort has been made to prevent or catch every conceivable error, and there is a comprehensive suite of tests included to validate that everything does what it should
* 🤖 **Modern**: We try to keep up with modern PHP features and industry best practices in the codebase, both for the sake of security and developer convenience

## Requirements

Warp Core has the following requirements for installation in a production environment:

* PHP 8.3 or higher
* [HTTP Support](https://github.com/gordonmcvey/httpsupport) library

### For development

In a development environment, we also require the following libraries: 

* PHPUnit 12
* PHP Code Sniffer
* PHPStan
* PHP Lint

## Installation

* add Warp Core to the `repositories` section of your `composer.json` file

```json
{
  "repositories": [
    {
      "type": "github",
      "url": "git@github.com:gordonmcvey/warp-core-php.git"
    }
  ]
}
```
* Run composer

```bash
composer require gordonmcvey/warp-core-php
```

## Simple Example

Assuming you have a controller called `Hello`, and that you want all incoming requests to route to the `Hello` controller regardless of path, request body, etc.

```php
require_once '/vendor/autoload.php';

(new FrontController(
    new CallStackFactory(),
    new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
    new ResponseSender(),
))->bootstrap(
    new Bootstrap(
        new Router(new SingleControllerStrategy(Hello::class)),
        new ControllerFactory(),
    ),
    Request::fromSuperGlobals(),
);
```

There are a variety of additional examples provided in the `examples/` directory.  A collection of skeleton apps has also been made available in the [Warp Core example apps](https://github.com/gordonmcvey/warp-core-example-app) repo
