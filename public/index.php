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
                $output,
                $status
            );
        } else {
            exec('cd ' . $path . ' && git pull', $output, $status);
        }
        if ($status !== 0) {
            header('Content-Type: text/plain', true, 500);
            echo implode("\n", $output);
            die();
        }
        $status = 200;
        foreach ($repository['files'] as $file) {
            foreach (Glob::glob($path . '/' . $file['from']) as $f) {
                $t = $file['to-dir'] . '/' . $file['from'];
                if (copy($f, $t)) {
                    $output[] = "copied $f to $t sucessfully";
                } else {
                    $output[] = "copying $f to $t failed";
                    $status = 500;
                }
            }
        }
        header('Content-Type: text/plain', true, $status);
        echo implode("\n", $output);
        foreach ($output as $line) {
            error_log($line);
        }
        die();
    }
}
header('Content-Type: text/plain', true, 404);
die();