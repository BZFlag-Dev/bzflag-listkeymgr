<?php

return [
    'protocol' => !empty($_SERVER['HTTPS'] ?? '')? 'https' : 'http',
    'hostname' => 'my.bzflag.org',
    'baseURI' => '/listkeymgr/',
    'checkip' => true // Set to false for development
];
