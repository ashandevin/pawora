<?php
declare(strict_types=1);

/**
 * Copies remote catalog images into the project and rewrites products.image to the local copy.
 *
 * Usage:
 *   php database/catalog/localize-images.php                    # check only, writes nothing
 *   php database/catalog/localize-images.php --apply            # download and rewrite
 *   php database/catalog/localize-images.php --apply --id 12    # a single product
 *   php database/catalog/localize-images.php --apply --max 300  # how many to process per run
 *
 * products.image_url keeps the original external URL for attribution, so nothing is lost and
 * the change can be undone by pointing image back at image_url. Failed downloads are reported
 * and skipped; the product keeps its remote image.
 */

require_once __DIR__ . '/../../includes/data.php';

const IMAGE_FOLDER = __DIR__ . '/../../assets/images/products';
const MAX_IMAGE_BYTES = 4194304;
const ALLOWED_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const USER_AGENT = 'PAWORA catalog localizer (coursework; contact via the PAWORA site)';

$apply = in_array('--apply', $argv, true);
$onlyId = 0;
$max = 50;
foreach ($argv as $index => $argument) {
    if ($argument === '--id' && isset($argv[$index + 1])) {
        $onlyId = max(0, (int) $argv[$index + 1]);
    }
    if ($argument === '--max' && isset($argv[$index + 1])) {
        $max = max(1, (int) $argv[$index + 1]);
    }
}

$sql = "SELECT id, sku, name, image, image_url FROM products WHERE image LIKE 'http%'";
$params = [];
if ($onlyId > 0) {
    $sql .= ' AND id = ?';
    $params[] = $onlyId;
}
$sql .= ' ORDER BY id LIMIT ' . $max;
$rows = db_all($sql, $params);

if ($rows === []) {
    echo "No remote product images to localise.\n";
    exit(0);
}

$folderError = null;
if (!is_dir(IMAGE_FOLDER)) {
    if ($apply && !@mkdir(IMAGE_FOLDER, 0775, true) && !is_dir(IMAGE_FOLDER)) {
        $folderError = 'Could not create ' . IMAGE_FOLDER;
    }
} elseif (!is_writable(IMAGE_FOLDER)) {
    $folderError = IMAGE_FOLDER . ' is not writable';
}
if ($folderError !== null) {
    fwrite(STDERR, "Image folder problem: $folderError\n");
    fwrite(STDERR, "Fix it with: chmod 775 " . realpath(__DIR__ . '/../..') . "/assets/images/products\n");
    exit(1);
}

/**
 * Downloads an image and reports the extension that matches its real content, so a PNG is never
 * saved as a .jpg file.
 *
 * @return array{0: string, 1: string}|null [body, extension] on success, or a problem message
 */
function catalog_resize_candidates(string $url): array
{
    $separator = str_contains($url, '?') ? '&' : '?';
    $candidates = [];
    foreach ([1200, 900, 600] as $width) {
        $candidates[] = $url . $separator . 'width=' . $width;
    }
    return $candidates;
}

function catalog_download(string $url): array|string|null
{
    $bytes = null;
    if (function_exists('curl_init')) {
        $problem = catalog_download_with_curl($url, $bytes);
    } else {
        $problem = catalog_download_with_streams($url, $bytes);
    }
    if ($problem !== null) {
        return $problem;
    }
    $body = (string) $bytes;
    if (strlen($body) > MAX_IMAGE_BYTES) {
        return 'the image is larger than ' . round(MAX_IMAGE_BYTES / 1048576, 1) . ' MB';
    }
    $info = @getimagesizefromstring($body);
    $mime = is_array($info) ? ($info['mime'] ?? '') : '';
    if (!isset(ALLOWED_TYPES[$mime])) {
        return 'unsupported content (' . ($mime !== '' ? $mime : 'unknown type') . '), expected JPG, PNG or WEBP';
    }
    if ((int) ($info[0] ?? 0) < 200 || (int) ($info[1] ?? 0) < 200) {
        return 'the image is only ' . (int) ($info[0] ?? 0) . 'x' . (int) ($info[1] ?? 0) . ' pixels';
    }
    return [$body, ALLOWED_TYPES[$mime]];
}

function catalog_download_with_curl(string $url, ?string &$bytes): ?string
{
    $ch = curl_init($url);
    if ($ch === false) {
        return 'curl could not be initialised';
    }
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => USER_AGENT,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => ['Accept: image/jpeg,image/png'],
        CURLOPT_BUFFERSIZE => 65536,
    ]);
    $source = fopen('php://temp', 'w+b');
    curl_setopt($ch, CURLOPT_FILE, $source);
    $ok = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($ok === false) {
        fclose($source);
        return 'download failed: ' . $error;
    }
    if ($status >= 400) {
        fclose($source);
        return 'server returned HTTP ' . $status;
    }
    rewind($source);
    $bytes = stream_get_contents($source);
    fclose($source);
    if ($bytes === false || $bytes === '') {
        return 'the response was empty';
    }
    return null;
}

function catalog_download_with_streams(string $url, ?string &$bytes): ?string
{
    if (!ini_get('allow_url_fopen')) {
        return 'this PHP build has neither curl nor allow_url_fopen, so remote images cannot be fetched';
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'follow_location' => 1,
            'max_redirects' => 5,
            'timeout' => 25,
            'user_agent' => USER_AGENT,
            'header' => "Accept: image/jpeg,image/png\r\n",
            'ignore_errors' => true,
        ],
    ]);
    $source = @fopen($url, 'rb', false, $context);
    if ($source === false) {
        return 'could not open the URL (check it loads in a browser)';
    }
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
            $status = (int) $matches[1];
        }
    }
    $bytes = stream_get_contents($source);
    fclose($source);
    if ($status >= 400) {
        return 'server returned HTTP ' . $status;
    }
    if ($bytes === false || $bytes === '') {
        return 'the response was empty';
    }
    return null;
}

echo 'PAWORA image localizer - ' . ($apply ? 'APPLY' : 'DRY RUN') . "\n";
echo str_repeat('=', 60) . "\n";
printf("  products with a remote image: %d\n\n", count($rows));

$done = 0;
$skipped = 0;
foreach ($rows as $row) {
    $url = (string) $row['image_url'];
    if ($url === '') {
        $url = (string) $row['image'];
    }
    if (!preg_match('#^https?://#i', $url)) {
        echo '  - ' . $row['sku'] . ": no usable image URL\n";
        $skipped++;
        continue;
    }
    $base = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $row['sku']));
    if (!$apply) {
        echo '  - ' . $row['sku'] . ": would download\n    from " . substr($url, 0, 110)
            . "\n    to   assets/images/products/" . $base . '.{jpg|png|webp}' . "\n";
        continue;
    }
    $download = catalog_download($url);
    if (!is_array($download) && str_contains((string) $download, 'larger than')) {
        // Shopify and most image CDNs resize on request, so an oversized pack shot can be
        // refetched at a sane size instead of being skipped. The smaller URL is stored as the
        // attribution link too, which keeps later runs idempotent instead of failing again.
        foreach (catalog_resize_candidates($url) as $candidate) {
            $retry = catalog_download($candidate);
            if (is_array($retry)) {
                $download = $retry;
                $url = $candidate;
                echo '  ~ ' . $row['sku'] . ': refetched smaller as ' . substr($candidate, -46) . "\n";
                break;
            }
        }
    }
    if (!is_array($download)) {
        echo '  ! ' . $row['sku'] . ': ' . (string) $download . "\n    " . substr($url, 0, 110) . "\n";
        $skipped++;
        continue;
    }
    [$body, $extension] = $download;
    $target = realpath(IMAGE_FOLDER) . '/' . $base . '.' . $extension;
    if (@file_put_contents($target, $body) === false) {
        echo '  ! ' . $row['sku'] . ': could not write ' . $target . "\n";
        $skipped++;
        continue;
    }
    @chmod($target, 0644);
    // A previous run may have stored the same image under a different extension.
    foreach (ALLOWED_TYPES as $staleExtension) {
        if ($staleExtension !== $extension) {
            @unlink(realpath(IMAGE_FOLDER) . '/' . $base . '.' . $staleExtension);
        }
    }
    try {
        // Keep the original remote URL for attribution, filling it in when the catalog only had it in image.
        $update = $url === (string) $row['image_url']
            ? db()->prepare('UPDATE products SET image = ? WHERE id = ?')
            : db()->prepare('UPDATE products SET image = ?, image_url = ? WHERE id = ?');
        $update->execute($url === (string) $row['image_url']
            ? ['products/' . basename($target), (int) $row['id']]
            : ['products/' . basename($target), $url, (int) $row['id']]);
        $done++;
        echo '  + ' . $row['sku'] . ' -> assets/images/products/' . basename($target) . "\n";
    } catch (Throwable $exception) {
        @unlink($target);
        echo '  ! ' . $row['sku'] . ': could not update the row: ' . $exception->getMessage() . "\n";
        $skipped++;
    }
}

echo "\n";
if (!$apply) {
    echo "Dry run only. Re-run with --apply to download and rewrite.\n";
    exit(0);
}
printf("localised: %d, skipped: %d\n", $done, $skipped);
echo "images/image_url still holds the original remote URL for attribution.\n";
