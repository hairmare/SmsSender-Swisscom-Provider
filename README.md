
# SwisscomProvider for [SmsSender](https://github.com/Carpe-Hora/SmsSender)

This was merged into [SmsSender](https://github.com/Carpe-Hora/SmsSender) in [#17](https://github.com/Carpe-Hora/SmsSender/pull/17). This repository is only here for historical reasons. You should use SmsSender directly it you want to send SMS through the Swisscom OneAPI gateway.

Send SMS using the [SMS Messaging API](https://developer.swisscom.com/documentation/api/sms-messaging-api) from [developer.swisscom.com](http://developer.swisscom.com).

````php
<?php

require_once __DIR__.'/vendor/autoload.php';

$client_id = null; // replace with your API-key

$adapter = new \SmsSender\HttpAdapter\CurlHttpAdapter();
$sender  = new \SmsSender\SmsSender();
$sender->registerProviders(array(
        new \SmsSender\Provider\SwisscomProvider(
            $adapter,
            $client_id
        )
));

$to = '0791234567';
$from = '0791234567';
$result = $sender->send($to, 'Hello World', $from);
````
