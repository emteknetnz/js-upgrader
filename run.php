<?php

$vendors = [
    'silverstripe',
    'colymba',
    'dnadesign',
    'symbiote',
    'tractorcow',
    'bringyourownideas',
];

function getMajorVersion($version)
{
    return explode('.', $version)[0];
}

$keys = [];
$depsMinorUpdate = [];
$depsMajorUpdate = [];
$devDepsMinorUpdate = [];
$devDepsMajorUpdate = [];

$npmjs = [];
foreach ($vendors as $vendor) {
    $path = __DIR__ . '/../../' . $vendor;
    $subdirs = scandir($path);
    foreach ($subdirs as $subdir) {
        if ($subdir == '.' || $subdir == '..' || !is_dir("$path/$subdir")) {
            continue;
        }
        $file = "$path/$subdir/package.json";
        if (!file_exists($file)) {
            continue;
        }
        $key = "$vendor/$subdir";
        $keys[] = $key;
        $depsMajorUpdate[$key] = [];
        $depsMinorUpdate[$key] = [];
        $devDepsMajorUpdate[$key] = [];
        $devDepsMinorUpdate[$key] = [];
        $contents = file_get_contents($file);
        $json = json_decode($contents, true);
        foreach (['dependencies', 'devDependencies'] as $node) {
            if (!isset($json[$node])) {
                continue;
            }
            foreach ($json[$node] as $name => $version) {
                $packageJsonVersion = ltrim($version, '^');
                $packageJsonMajorVersion = getMajorVersion($packageJsonVersion);
                if (!isset($npmjs[$name])) {
                    $remote = "https://registry.npmjs.org/$name";
                    echo "Fetching from $remote\n";
                    $c = file_get_contents($remote);
                    $npmjs[$name] = json_decode($c, true)['dist-tags']['latest'];
                }
                $npmVersion = $npmjs[$name];
                $npmMajorVersion = getMajorVersion($npmVersion);
                if ($packageJsonMajorVersion != $npmMajorVersion) {
                    if ($node == 'dependencies') {
                        $depsMajorUpdate[$name] = $npmVersion;
                    } else {
                        $devDepsMajorUpdate[$name] = $npmVersion;
                    }
                } else {
                    if ($packageJsonVersion != $npmVersion) {
                        if ($node == 'dependencies') {
                            $depsMinorUpdate[$name] = $npmVersion;
                        } else {
                            $devDepsMinorUpdate[$name] = $npmVersion;
                        }
                    }
                }
            }
        }
    }
}

ob_start();
foreach ($keys as $key) {
    echo "\n\n$key\n";
    $deps = $depsMajorUpdate[$key];
    if (count($deps)) {
        echo "Major update required for $key\n";
        foreach ($deps as $name => $version) {
            echo "  $name: $version\n";
        }
    }
    $deps = $depsMinorUpdate[$key];
    if (count($deps)) {
        echo "Minor update required for $key\n";
        foreach ($deps as $name => $version) {
            echo "  $name: $version\n";
        }
    }
    $deps = $devDepsMajorUpdate[$key];
    if (count($deps)) {
        echo "Major update required for $key (dev)\n";
        foreach ($deps as $name => $version) {
            echo "  $name: $version\n";
        }
    }
    $deps = $devDepsMinorUpdate[$key];
    if (count($deps)) {
        echo "Minor update required for $key (dev)\n";
        foreach ($deps as $name => $version) {
            echo "  $name: $version\n";
        }
    }
}
$output = ob_get_clean();
file_put_contents('output.txt', $output);
echo "Wrote to output.txt";
