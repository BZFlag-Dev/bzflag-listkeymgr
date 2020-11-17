<?php

return [
    'protocol' => (!empty($_SERVER['HTTPS'] ?? '') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') == 'https')? 'https' : 'http',
    'hostname' => 'my.bzflag.org',
    'baseURI' => '/listkeys/',
    'checkip' => true // Set to false for development
];
