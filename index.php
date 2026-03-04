<?php
require __DIR__ . '/includes/content.php';
$content = load_site_content();

function build_video_embed_url(string $rawUrl): string
{
    $url = trim($rawUrl);
    if ($url === '') {
        return '';
    }

    if (preg_match('~^https://drive\.google\.com/file/d/([a-zA-Z0-9_-]+)~', $url, $matches)) {
        return 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
    }

    if (preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $url, $matches)) {
        return 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
    }

    if (str_starts_with($url, 'https://drive.google.com') && str_contains($url, '/preview')) {
        return $url;
    }

    return $url;
}

$videoEmbedUrl = build_video_embed_url((string)($content['videoUrl'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($content['title']); ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>❤️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="bg-orb orb-one"></div>
    <div class="bg-orb orb-two"></div>
    <div class="bg-grid"></div>
    <div id="loveFloatLayer" class="love-float-layer" aria-hidden="true"></div>

    <main class="story-shell">
        <section class="story-page is-active" data-page-index="0">
            <article class="card center-card">
                <p class="kicker"><?php echo h($content['kicker']); ?></p>
                <h1><?php echo h($content['title']); ?></h1>
                <p class="subtitle"><?php echo h($content['subtitle']); ?></p>
                <p class="label"><?php echo h($content['countdownLabel']); ?></p>
                <p id="countdown" class="countdown">Loading...</p>
            </article>
        </section>

        <section class="story-page" data-page-index="1">
            <article class="card">
                <h2><?php echo h($content['timelineTitle']); ?></h2>
                <div class="timeline-list">
                    <?php foreach ($content['timeline'] as $timelineItem): ?>
                        <article class="timeline-item">
                            <span class="dot"></span>
                            <div>
                                <h3><?php echo h($timelineItem['title'] ?? ''); ?></h3>
                                <p><?php echo h($timelineItem['text'] ?? ''); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="story-page" data-page-index="2">
            <article class="card">
                <h2><?php echo h($content['galleryTitle']); ?></h2>
                <p class="small"><?php echo h($content['galleryHint']); ?></p>
                <?php if (!empty($content['photos']) && is_array($content['photos'])): ?>
                    <div class="fancy-gallery" id="fancyGallery">
                        <div class="gallery-stage">
                            <?php foreach ($content['photos'] as $index => $photoPath): ?>
                                <img
                                    src="<?php echo h($photoPath); ?>"
                                    alt="Memory photo <?php echo (int)($index + 1); ?>"
                                    class="gallery-slide <?php echo $index === 0 ? 'is-active' : ''; ?>"
                                    data-slide-index="<?php echo (int)$index; ?>"
                                    loading="lazy"
                                >
                            <?php endforeach; ?>
                        </div>
                        <div class="gallery-controls">
                            <button id="prevSlideBtn" class="btn btn-ghost" type="button">Previous</button>
                            <button id="nextSlideBtn" class="btn btn-primary" type="button">Next</button>
                        </div>
                        <div class="gallery-dots" id="galleryDots">
                            <?php foreach ($content['photos'] as $index => $photoPath): ?>
                                <button
                                    class="gallery-dot <?php echo $index === 0 ? 'is-active' : ''; ?>"
                                    data-dot-index="<?php echo (int)$index; ?>"
                                    type="button"
                                    aria-label="Go to slide <?php echo (int)($index + 1); ?>"
                                ></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="small">No photos yet. Add them from admin mode.</p>
                <?php endif; ?>
            </article>
        </section>

        <section class="story-page" data-page-index="3">
            <article class="card center-card">
                <h2><?php echo h($content['finalTitle']); ?></h2>
                <p class="subtitle"><?php echo h($content['finalMessage']); ?></p>
            </article>
        </section>

        <section class="story-page" data-page-index="4">
            <article class="card">
                <h2><?php echo h($content['videoTitle'] ?? 'A Video For You'); ?></h2>
                <p class="small"><?php echo h($content['videoHint'] ?? ''); ?></p>
                <?php if ($videoEmbedUrl !== ''): ?>
                    <div class="video-actions">
                        <button id="videoMuteBtn" class="btn btn-ghost" type="button">Mute Background Music</button>
                    </div>
                    <div class="video-wrap">
                        <iframe
                            src="<?php echo h($videoEmbedUrl); ?>"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            title="Anniversary Video"
                        ></iframe>
                    </div>
                <?php else: ?>
                    <p class="small">No video yet. Add one in admin mode.</p>
                <?php endif; ?>
            </article>
        </section>

        <section class="story-page" data-page-index="5">
            <article class="card center-card finale-card">
                <div class="confetti-box">
                    <canvas id="confettiCanvas"></canvas>
                    <div class="confetti-copy">
                        <h2>Happy 11th Anniversary Bii</h2>
                        <p>Forever grateful for your love. Here is to all the years ahead, together.</p>
                    </div>
                </div>
            </article>
        </section>
    </main>

    <div class="story-nav">
        <button id="pagePrevBtn" class="nav-btn" type="button" aria-label="Previous page">&#8592;</button>
        <p class="page-indicator"><span id="pageIndicatorCurrent">1</span> / <span id="pageIndicatorTotal">6</span></p>
        <button id="pageNextBtn" class="nav-btn" type="button" aria-label="Next page">&#8594;</button>
    </div>

    <script>
        window.SITE_CONTENT = <?php echo json_encode($content, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>
