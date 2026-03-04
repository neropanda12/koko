<?php

const CONTENT_FILE = __DIR__ . '/../data/content.json';
const PHOTO_UPLOAD_DIR = __DIR__ . '/../uploads/photos';
const MUSIC_UPLOAD_DIR = __DIR__ . '/../uploads/music';

function default_site_content(): array
{
    return [
        'anniversaryDate' => '2026-03-03T00:00:00',
        'kicker' => 'For My Favorite Person',
        'title' => '11 Years of Us',
        'subtitle' => 'March 3, 2015 to March 3, 2026. A tiny interactive page for our story.',
        'countdownLabel' => 'Time since our 11th anniversary',
        'timelineTitle' => 'Our Timeline',
        'timeline' => [
            ['title' => 'How We Met', 'text' => 'Write your first memory here. Keep it specific so it feels real.'],
            ['title' => 'First Big Adventure', 'text' => 'Add a place and date that means a lot to both of you.'],
            ['title' => 'March 3, 2026', 'text' => '11 years together. Still choosing each other every day.'],
        ],
        'galleryTitle' => 'Photo Memories',
        'galleryHint' => 'Moments I never want to forget.',
        'photos' => [],
        'finalTitle' => 'Final Message',
        'finalMessage' => 'Thank you for being my peace, my best friend, and my greatest gift. I still get excited for every new year with you.',
        'videoTitle' => 'A Video For You',
        'videoHint' => 'A small clip from us.',
        'videoUrl' => '',
        'anniversaryTitle' => 'Happy 11th Anniversary',
        'anniversaryMessage' => 'Forever grateful for your love. Here is to all the years ahead, together.',
        'musicFile' => '',
        'musicVolume' => 0.45,
    ];
}

function ensure_storage_paths(): void
{
    $dirs = [
        dirname(CONTENT_FILE),
        PHOTO_UPLOAD_DIR,
        MUSIC_UPLOAD_DIR,
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}

function merge_recursive_distinct(array $default, array $current): array
{
    $merged = $default;
    foreach ($current as $key => $value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = merge_recursive_distinct($merged[$key], $value);
            continue;
        }
        $merged[$key] = $value;
    }
    return $merged;
}

function load_site_content(): array
{
    ensure_storage_paths();
    $default = default_site_content();

    if (!file_exists(CONTENT_FILE)) {
        save_site_content($default);
        return $default;
    }

    $raw = file_get_contents(CONTENT_FILE);
    $decoded = json_decode($raw ?: '', true);
    if (!is_array($decoded)) {
        save_site_content($default);
        return $default;
    }

    $merged = merge_recursive_distinct($default, $decoded);
    if ($merged !== $decoded) {
        save_site_content($merged);
    }

    return $merged;
}

function save_site_content(array $content): bool
{
    ensure_storage_paths();
    $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    return file_put_contents(CONTENT_FILE, $json) !== false;
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
