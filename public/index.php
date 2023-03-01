<?php

use De\Idrinth\Yaml\Yaml;

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
$post = json_decode(file_get_contents('php://input'),true);
foreach (Yaml::decodeFromFile(dirname(__DIR__) . '/config.yml') as $repository) {
    if ($repository['project'] === $post['repository']['ssh_url']) {
        if ($repository['source'] !== $_SERVER['REMOTE_ADDR']) {
            header('Content-Type: text/plain', true, 403);
            die();
        }
        $path = dirname(__DIR__) . '/cache/' . $post['repository']['full_name'];
        file_put_contents(
            dirname(__DIR__) . '/cache/todo',
            "{$post['repository']['full_name']}|{$repository['project']}\n",
            FILE_APPEND
        );
        header('Content-Type: text/plain', true, 200);
        die();
    }
}
header('Content-Type: text/plain', true, 404);
die();