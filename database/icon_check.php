<?php
/**
 * Reports Ionicons referenced in markup that are not available locally.
 * Usage: php database/icon_check.php
 */

$root = dirname(__DIR__);
$have = array_map(
    static fn(string $path): string => pathinfo($path, PATHINFO_FILENAME),
    glob($root . '/assets/vendor/ionicons/svg/*.svg') ?: []
);

$used = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'html'], true)) {
        continue;
    }
    if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $content = (string) file_get_contents($file->getPathname());

    if (preg_match_all('/ion-icon[^>]*name="([a-z0-9\-]+)"/i', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $used[$name][] = basename($file->getPathname());
        }
    }
    if (preg_match_all("/'icon'\s*=>\s*'([a-z0-9\-]+)'/i", $content, $matches)) {
        foreach ($matches[1] as $name) {
            $used[$name][] = basename($file->getPathname());
        }
    }
    if (preg_match_all("/'icon'\s*=>\s*'([a-z0-9\-]+)'/i", file_get_contents(__DIR__ . '/../config/app.php') ?: '', $matches)) {
        foreach ($matches[1] as $name) {
            $used[$name][] = 'config/app.php';
        }
    }
}

$missing = [];
foreach ($used as $name => $files) {
    if ($name === '<dynamic>' || in_array($name, $have, true)) {
        continue;
    }
    $missing[$name] = array_unique($files);
}

echo 'Local SVGs: ' . count($have) . "\n";
echo 'Referenced icons: ' . count($used) . "\n";

if (!$missing) {
    echo "All referenced icons are available locally.\n";
    exit(0);
}

echo "Missing:\n";
foreach ($missing as $name => $files) {
    echo "  {$name}  (" . implode(', ', $files) . ")\n";
}
exit(1);
