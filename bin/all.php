<?php

use De\Idrinth\Yaml\Yaml;

require_once (__DIR__ . '/../vendor/autoload.php');

foreach (Yaml::decodeFromFile(dirname(__DIR__) . '/config.yml') as $repository) {
    $project = preg_replace('/^git@.*?:(.*)\.git$/', '$1', $repository['project']);
    file_put_contents(dirname(__DIR__) . '/cache/todo', "$path|{$repository['project']}\n", FILE_APPEND);
}