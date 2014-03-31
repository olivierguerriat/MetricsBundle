# MetricsBundle

A [statsd](https://github.com/etsy/statsd) client for Symfony 2.

## Installation

1. Add it to your `composer.json` file:

    `"require": { "guerriat/metricsbundle": "dev-master" }`

2. Update your vendors using composer:

    `$ php composer.phar update`

3. Register it in `app/AppKernel.php` by adding the following line to the `$bundles` array:

    `new Guerriat\MetricsBundle\GuerriatMetricsBundle(),`

4. Configure it at will, following the syntax detailed in "Configuration" and "Usage" sections.

## Configuration

This bundle's configuration has two main sections: servers & clients.

    guerriat_metrics:
        servers:
        clients:

### Server

You can setup multiple `statsd` servers and give them a name. For each one, you can specify the host (defaults to `127.0.0.1`), the port (defaults to `8125`) and the maximum size of UDP payload in bytes (defaults to `512`). Metrics are sent together (as much as possible by datagram) at the end of the page generation.

Here's an sample configuration file for two servers:

    guerriat_metrics:
        servers:
            default:
                host: 127.0.0.1
                port: 8125
            funky:
                host: funky.guerriat.be
                port: 54321
                udp_max_size: 1024

### Clients

A client is named and can be assigned to all servers or a subset of them (and that's the whole point of clients). This is done by specifying an array of servers name for the `servers` property. If you want to use all servers, you can set the array `['all']` or simply omit the `servers` property.

When using multiple servers, this bundle makes sure that a given key will always be sent to the same server, avoiding aggregation trouble at the statsd level.

A client can also have other sections such as `events`, `collectors` and `monolog`, as explained in Usage.

Here's a sample configuration file featuring three servers and two clients:

    guerriat_metrics:
        servers:
            srv1:
                host: 127.0.0.1
            srv2:
                host: statsd1.guerriat.be
            srv3:
                host: statsd2.guerriat.be
        clients:
            default:
                servers: ['all']
            no_srv2:
                servers: ['srv1', 'srv3']

#### Key prefix

You can set a key prefix for a given client:

    client_name:
        prefix: app_name.env_name

It will be used for all metrics sent by the client (including metrics sent via events, collectors and monolog).

## Metric types

Every metric is identified by a key. It can only contains alphanumerical characters, hyphens, underscores and dots.

### Counter

A counter that you can increment and decrement. You can also sample it, in order not to overload the network. By example, if the sampling rate is 0.1, the message will be sent only once every ten calls. The data is automatically corrected in order for graphite to give correct values. Thus, you don't need to worry about data consistency when changing the sampling rate.

(note that decrementing is not supported by the current version of Etsy's implementation of statsd, due to a bug)

### Timer

A timer is a time in millisecond. Derived values (such as mean, percentile…) are automatically calculated for analytics purposes.

### Gauge

A gauge contains a arbitrary value.

### Set

A set is a count of unique numbers among the different numbers sent. By example, you can send the user id in order to count the number of active user.

## Usage

There is 4 ways to send metrics :

* directly in the controller
* by binding on events
* by using a collector service
* via Monolog

### In the controller

In the controller, you can send metrics using the `default` client thanks to the `guerriat_metrics` service. For other clients, the service is named `guerriat_metrics.client_name`.

    // Counting
    $this->get('guerriat_metrics')->increment('key');
    $this->get('guerriat_metrics')->decrement('key');
    
    // … with a sampling rate
    $this->get('guerriat_metrics')->increment('key', 0.5);
    
    // … with a custom value
    $this->get('guerriat_metrics')->counter('key', 4);
    
    // … with both
    $this->get('guerriat_metrics')->counter('key', 4, 0.5);
    
    // Timing
    $start = microtime(true);
    slow_function();
    $this->get('guerriat_metrics')->timer('key', (microtime(true) - $start) * 1000);
    
    // … or give a callable
    $this->get('guerriat_metrics')->timing('key', 'slow_function');
    
    // Gauge
    $this->get('guerriat_metrics')->gauge('key', 42);
    
    // Set
    $this->get('guerriat_metrics')->set('key', 6);

### Binding on events

You can setup events to be listened directly in the config file. For each clients, there is an `events` section, specifying which metrics should be sent. All types are available: `counter` (with `increment` and `decrement` shortcuts), `timer`, `gauge` and `set`.

The configuration is done like this:

    event_name:
        type: app.key

Or like this, if you want to specify which method is called to get the metric value (except for `increment` and `decrement`):

    event_name:
        type:
            key: app.key
            method: getValue

By default, `timer` will call `getDuration()` while `counter`, `gauge` and `set` will call `getValue()`.

Here's an excerpt of a sample configuration file:

    client_name:
        events:
            guerriat.app.visit:
                increment: guerriat.guest
            guerriat.app.long_event:
                timer: guerriat.long_event.timer
                counter: guerriat.long_event.processed_items
                gauge:
                    key: guerriat.long_event.result
                    method: getResult
            guerriat.app.login:
                decrement: guerriat.guest
                increment: guerriat.known_user
                timer: 
                    key: guerriat.password_hash.timer
                    method: getHashDuration
                set: 
                    key: guerriat.connected_users
                    method: getUserId

#### Trigger an event from a controller

    $event = new \Symfony\Component\EventDispatcher\Event();
    $this->get('event_dispatcher')->dispatch('event_name', $event);

### Using a `MetricCollector`

A `MetricCollector` is a class extending `MetricCollector`, having at least a method which is called on kernel.response and whose signature is:

    public function collect($client, $key, $request, $response, $exception, $master);

* `$client` is a reference to the `Client` service and you can use it as you would from the controller
* `$key` is the key specified in the config file
* `$request` is the `Request` object
* `$response` is the `Response` object
* `$exception` is the `Exception` object
* `$master` is a boolean indicating whether it is the master request

You have to activate a `MetricCollector` for a specific client via the config file, specifying the service name of the collector and the key you want to use, as you can see in this excerpt of a sample configuration file:

    client_name:
        collectors:
            sample_bundle.collector.url: guerriat.request.url
            guerriat_metrics.collector.time: guerriat.request.time

A few `MetricCollector`s are included in this bundle:

* `guerriat_metrics.collector.time` collects the request time (with route name, in a timer)
* `guerriat_metrics.collector.exception` collects the exception (with code, in a counter)
* `guerriat_metrics.collector.memory` collects PHP memory usage (in KB, in a gauge)
* `guerriat_metrics.collector.hit` collects hits (with route name, in a counter)
* `guerriat_metrics.collector.response` collects responses status code (with request route name, in a counter)

### Monolog integration

This bundle have a Monolog handler which can send a increment on every log above a set level. As for the events and collectors, this is configured by client, as you can see in this self-explanatory excerpt:

    client_name:
        monolog:
            enable: true
            prefix: 'log'             ## key prefix
            level: 'warning'          ## minimum level
            formatter:
                context_logging: true ## if you want additional packets for context, default is false.
                extra_logging: true   ## if you want additional packets for extra, default is false.
                words: 3              ## the number of the word in the stats key, default is 2.

However, you also must tell Monolog about our handler by adding this to your config file:

    monolog:
        handlers:
            guerriat_metrics:
                type: service
                id: guerriat_metrics.monolog.handler

Note that the prefix will be added to the client's prefix. The resulting key will have the format `client_prefix.monolog_prefix.channel.level.first_words`.

#### Log from a controller

    $logger = $this->get('logger');
    $logger->error('An error occurred');
    $logger->warning('Something strange took place');
    $logger->info('Something happened');
    


### Key format helper

If you need to format a string to a valid key, you can use this simple helper:

    use Guerriat\MetricsBundle\Metric\KeyFormatter;
    KeyFormatter::format("L'éclair au chocolat"); // returns "Leclair-au-chocolat"

You also can specify a maximum number of words:

    KeyFormatter::format("L'éclair au chocolat", 2); // returns "Leclair-au"

Or a maximum number of characters:

    KeyFormatter::format("L'éclair au chocolat", false, 5); // returns "Lecla"

You also can customize the separator:

    KeyFormatter::format("L'éclair au chocolat", false, false, '.'); // returns "Leclair.au.chocolat"

Or do all this at once:

    KeyFormatter::format("L'éclair au chocolat", 3, 15, '.'); // returns "Leclair.au.choc"

## Sample config.yml

    guerriat_metrics:
        servers:
            srv1:
                host: 127.0.0.1              ## default is 127.0.0.1
                port: 8125                   ## default is 8125
            srv2:
                host: statsd.guerriat.be
            beta:
                host: statsd.guerriat.be
                port: 8127
                udp_max_size: 1024           ## default is 512
        clients:
            default:
                prefix: guerriat.prod
                servers: ['srv1', 'srv2']    ## default is ['all']
                events:
                    guerriat.app.visit:
                        increment: visit.guest
                    guerriat.app.long_event:
                        timer: long_event.timer
                        counter: long_event.processed_items
                        gauge:
                            key: long_event.result
                            method: getResult
                    guerriat.app.login:
                        decrement: visit.guest
                        increment: visit.known_user
                        timer: 
                            key: password_hash.timer
                            method: getHashDuration
                        set: 
                            key: connected_users
                            method: getUserId
                collectors:
                    sample_bundle.collector.url: request.url
                    guerriat_metrics.collector.time: request.time
                    guerriat_metrics.collector.exception: request.exception
                    guerriat_metrics.collector.memory: request.memory
                    guerriat_metrics.collector.hit: request.hit
                    guerriat_metrics.collector.response: response
                monolog:
                    enable: true
                    prefix: 'log'             ## key prefix
                    level: 'warning'          ## minimum level
            test:
                prefix: guerriat.test
                servers: ['test']
                monolog:
                    enable: true
                    prefix: 'log'
                    level: 'debug'
                    formatter:
                        context_logging: true ## if you want additional packets for context, default is false.
                        extra_logging: true   ## if you want additional packets for extra, default is false.
                        words: 3              ## the number of the word in the stats key, default is 2.
    
    
    monolog:
        handlers:
            guerriat_metrics:
                type: service
                id: guerriat_metrics.monolog.handler

## Unit testing

[![Build Status](https://travis-ci.org/olivierguerriat/MetricsBundle.png?branch=master)](https://travis-ci.org/olivierguerriat/MetricsBundle)

    $ php composer.phar install
    $ phpunit --coverage-html Tests/coverage

## Credits

Written by [Olivier Guerriat](http://guerriat.be) and greatly inspired by those two bundles:

* [liuggio/StatsDClientBundle](https://github.com/liuggio/StatsDClientBundle/)
* [M6Web/StatsdBundle](https://github.com/M6Web/StatsdBundle/)
