<?php

use De\Idrinth\Yaml\Yaml;
use Gitonomy\Git\Admin;
use Gitonomy\Git\Repository;
use Webmozart\Glob\Glob;

require_once (__DIR__ . '/../vendor/autoload.php');

$headers = apache_request_headers();

if (!isset($headers['X-GitHub-Delivery'])) {
    header('Content-Type: text/plain', true, 400);
    die();
}
if (!isset($headers['X-GitHub-Event']) || $headers['X-GitHub-Event'] !== 'push') {
    header('Content-Type: text/plain', true, 400);
    die();
}
    var_dump($_SERVER['REMOTE_ADDR'], $_POST);
foreach (Yaml::decodeFromFile(dirname(__DIR__) . '/config.yml') as $repository) {
    if ($repository['project'] === $_POST['repository']['ssh_url']) {
        if ($repository['source'] !== $_SERVER['REMOTE_ADDR']) {
            header('Content-Type: text/plain', true, 403);
            die();
        }
        $path = dirname(__DIR__) . '/cache/' .$_POST['repository']['full_name'];
        if (!is_dir($path)) {
            mkdir($path, 0700, true);
            $repository = Admin::cloneRepository($path, $repository['project']);
            $repository->setDescription('Secrets cache');
        } else {
            $repository = new Repository($path);
            $repository->run('pull');
        }
        foreach ($repository['files'] as $file) {
            foreach (Glob::glob($path . '/' . $file['from']) as $f) {
                var_dump($f);
                //copy($f, $file['to-path'] . '/' . $f);
            }
        }
        header('Content-Type: text/plain', true, 200);
        die();
    }
}
header('Content-Type: text/plain', true, 404);
die();