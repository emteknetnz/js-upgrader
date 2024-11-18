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
    $a = explode('.', $version);
    if ($a[0] == '0') {
        return $a[0] . '.' . $a[1];
    }
    return $a[0];
}

$keys = [];
$depsMinorUpdate = [];
$depsMajorUpdate = [];
$devDepsMinorUpdate = [];
$devDepsMajorUpdate = [];
$existingDep = [];

$dir = __DIR__;
if (!file_exists("$dir/.cache")) {
    mkdir("$dir/.cache");
}

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
                $existingDep[$key][$name] = $packageJsonVersion;
                $packageJsonMajorVersion = getMajorVersion($packageJsonVersion);
                if (!isset($npmjs[$name])) {
                    $ename = str_replace(['@','/'], ['_AT_','_FS_'], $name);
                    if (!file_exists("$dir/.cache/$ename")) {
                        $remote = "https://registry.npmjs.org/$name";
                        echo "Fetching from $remote\n";
                        $c = file_get_contents($remote);
                        file_put_contents("$dir/.cache/$ename", $c);
                    } else {
                        echo "Reading from .cache/$ename\n";
                        $c = file_get_contents("$dir/.cache/$ename");
                    }
                    $npmjs[$name] = json_decode($c, true)['dist-tags']['latest'];
                }
                $npmVersion = $npmjs[$name];
                $npmMajorVersion = getMajorVersion($npmVersion);
                if ($packageJsonMajorVersion != $npmMajorVersion) {
                    if ($node == 'dependencies') {
                        $depsMajorUpdat[$key][$name] = $npmVersion;
                    } else {
                        $devDepsMajorUpdate[$key][$name] = $npmVersion;
                    }
                } else {
                    if ($packageJsonVersion != $npmVersion) {
                        if ($node == 'dependencies') {
                            $depsMinorUpdate[$key][$name] = $npmVersion;
                        } else {
                            $devDepsMinorUpdate[$key][$name] = $npmVersion;
                        }
                    }
                }
            }
        }
    }
}

$needsMinor = [];
$needsMajor = [];

ob_start();
foreach ($keys as $key) {
    echo "\n\n# $key\n";
    echo "\nMinor update required for $key\n";
    foreach ($depsMinorUpdate[$key] as $name => $version) {
        echo "  $name => $version\n";
        $needsMinor[$key] = true;
    }
    // echo "\nMinor update required for $key (dev)\n";
    foreach ($devDepsMinorUpdate[$key] as $name => $version) {
        echo "  $name => $version\n";
        $needsMinor[$key] = true;
    }
    echo "\nMajor update required for $key\n";
    foreach ($depsMajorUpdate[$key] as $name => $version) {
        echo "  $name => $version\n";
        $needsMajor[$key] = true;
    }
    // echo "\nMajor update required for $key (dev)\n";
    foreach ($devDepsMajorUpdate[$key] as $name => $version) {
        echo "  $name => $version\n";
        $needsMajor[$key] = true;
    }
}
echo "\n\n# Needs minor\n";
foreach (array_keys($needsMinor) as $key) {
    echo "  $key\n";
}
echo "\n\n# Needs major\n";
foreach (array_keys($needsMajor) as $key) {
    echo "  $key\n";
}

$output = ob_get_clean();
file_put_contents('output.txt', $output);
echo "Wrote to output.txt\n";

// Update package.json files minor only
if (count($argv) >= 2 && $argv[1] == 'update') {
    foreach ($keys as $key) {
        $file = __DIR__ . '/../../' . $key . '/package.json';
        $c = file_get_contents($file);
        $deps = array_merge($depsMinorUpdate[$key], $devDepsMinorUpdate[$key]);
        foreach ($deps as $name => $version) {
            $existing = $existingDep[$key][$name];
            $c = str_replace("\"$name\": \"^$existing\"", "\"$name\": \"^$version\"", $c);
        }
        file_put_contents($file, $c);
        echo "Updated $file\n";
    }
}
