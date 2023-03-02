<?php

use De\Idrinth\Yaml\Yaml;
use Webmozart\Glob\Glob;

require_once (__DIR__ . '/../vendor/autoload.php');

if (!is_file(dirname(__DIR__) . '/cache/todo')) {
    die(0);
}

$name = dirname(__DIR__) . '/cache/todo.' . microtime(true);
rename(dirname(__DIR__) . '/cache/todo', $name);
$moveables = [];
foreach (Yaml::decodeFromFile(dirname(__DIR__) . '/config.yml') as $repository) {
    $moveables[$repository['project']] = $repository['files'];
}
foreach (array_unique(explode("\n", file_get_contents($name))) as $line) {
    if (!empty($line)) {
        list($path, $source) = explode('|', $line);
        $path = __DIR__ . '/../cache/' . $path;
        if (!is_dir($path)) {
            mkdir($path, 0700, true);
            exec(
                'git clone -c core.sshCommand="/usr/bin/ssh -i ' . dirname(__DIR__) . '/private.key" ' . $source. ' ' . $path,
                $output,
                $status
            );
            echo "Cloning to $path.\n";
            foreach ($output as $out) {
                echo "  $out";
            }
            $output = [];
            exec(
                'cd ' . $path . ' && git config --local core.sshCommand "/usr/bin/ssh -i ' . dirname(__DIR__) . '/private.key"',
                $output,
                $status
            );
            echo "Registering key to $path.\n";
            foreach ($output as $out) {
                echo "  $out";
            }
            echo "  Status $status\n";
        } else {
            exec(
                'cd ' . $path . ' && git pull',
                $output,
                $status
            );
            echo "Pulling to $path.\n";
            foreach ($output as $out) {
                echo "  $out";
            }
            echo "  Status $status\n";
        }
        $path = realpath($path);
        foreach ($moveables[$source] as $glob) {
            foreach (Glob::glob($path . '/' . $glob['from']) as $file) {
                $file = preg_replace('/^' . preg_quote($path, '/') . '/', '', realpath($file));
                echo "  moving $file to {$glob['to-dir']}\n";
                $dir = dirname($glob['to-dir'] . '/' . $file);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($glob['to-dir'] . '/' . $file, file_get_contents($path . '/' . $file));
            }
        }
    }
}
unlink($name);