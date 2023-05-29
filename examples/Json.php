<?php

use verfriemelt\pp\Parser\JSON\Json;

require __DIR__ . '/../vendor/autoload.php';


    $composerFile = file_get_contents(__DIR__ . "/../composer.json")?:throw new \RuntimeException;

    var_dump(Json::parse('"a"'));



