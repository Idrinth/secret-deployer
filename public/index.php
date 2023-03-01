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
$post = json_decode(file_get_contents('php://input'),true);
$key = ['-c', 'core.sshCommand="/usr/bin/ssh -i ' . dirname(__DIR__) . '/private.key"'];
foreach (Yaml::decodeFromFile(dirname(__DIR__) . '/config.yml') as $repository) {
    if ($repository['project'] === $post['repository']['ssh_url']) {
        if ($repository['source'] !== $_SERVER['REMOTE_ADDR']) {
            header('Content-Type: text/plain', true, 403);
            die();
        }
        $path = dirname(__DIR__) . '/cache/' . $post['repository']['full_name'];
        $output = [];
        if (!is_dir($path)) {
            mkdir($path, 0700, true);
            exec(
                'git clone -c core.sshCommand="/usr/bin/ssh -i ' . dirname(__DIR__) . '/private.key" ' . $repository['project']. ' ' . $path,
                $output
            );
            var_dump('git clone -c core.sshCommand="/usr/bin/ssh -i ' . dirname(__DIR__) . '/private.key" ' . $repository['project']. ' ' . $path);
        } else {
            exec('cd ' . $path . ' && git pull', $output);
        }
        foreach ($repository['files'] as $file) {
            foreach (Glob::glob($path . '/' . $file['from']) as $f) {
                copy($f, $file['to-path'] . '/' . $f);
            }
        }
        header('Content-Type: text/plain', true, 200);
        echo implode("\n", $output);
        die();
    }
}
header('Content-Type: text/plain', true, 404);
die();