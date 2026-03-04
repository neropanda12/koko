<?php
session_start();
require __DIR__ . '/includes/content.php';

const ADMIN_PASSWORD = '1234';

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['is_admin']);
}

function handle_image_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $name = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    $newName = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = PHOTO_UPLOAD_DIR . '/' . $newName;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return 'uploads/photos/' . $newName;
}

function handle_multiple_image_uploads(array $files): array
{
    $uploaded = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $uploaded;
    }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $singleFile = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
        $photoPath = handle_image_upload($singleFile);
        if ($photoPath !== null) {
            $uploaded[] = $photoPath;
        }
    }

    return $uploaded;
}

function handle_music_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['mp3', 'wav', 'ogg', 'm4a'];
    $name = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    $newName = 'music_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = MUSIC_UPLOAD_DIR . '/' . $newName;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return 'uploads/music/' . $newName;
}

$content = load_site_content();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $password = (string)($_POST['password'] ?? '');
        if (hash_equals(ADMIN_PASSWORD, $password)) {
            $_SESSION['is_admin'] = true;
            header('Location: admin.php');
            exit;
        }
        $error = 'Wrong password.';
    } elseif ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        header('Location: admin.php');
        exit;
    } elseif (!is_admin_logged_in()) {
        $error = 'You must log in first.';
    } elseif ($action === 'save_content') {
        $timeline = [];
        for ($i = 0; $i < 3; $i++) {
            $timeline[] = [
                'title' => trim((string)($_POST["timeline_title_$i"] ?? '')),
                'text' => trim((string)($_POST["timeline_text_$i"] ?? '')),
            ];
        }

        $content['anniversaryDate'] = trim((string)($_POST['anniversaryDate'] ?? $content['anniversaryDate']));
        $content['kicker'] = trim((string)($_POST['kicker'] ?? ''));
        $content['title'] = trim((string)($_POST['title'] ?? ''));
        $content['subtitle'] = trim((string)($_POST['subtitle'] ?? ''));
        $content['countdownLabel'] = trim((string)($_POST['countdownLabel'] ?? ''));
        $content['timelineTitle'] = trim((string)($_POST['timelineTitle'] ?? ''));
        $content['timeline'] = $timeline;
        $content['galleryTitle'] = trim((string)($_POST['galleryTitle'] ?? ''));
        $content['galleryHint'] = trim((string)($_POST['galleryHint'] ?? ''));
        $content['finalTitle'] = trim((string)($_POST['finalTitle'] ?? ''));
        $content['finalMessage'] = trim((string)($_POST['finalMessage'] ?? ''));
        $content['videoTitle'] = trim((string)($_POST['videoTitle'] ?? ''));
        $content['videoHint'] = trim((string)($_POST['videoHint'] ?? ''));
        $content['videoUrl'] = trim((string)($_POST['videoUrl'] ?? ''));
        $content['anniversaryTitle'] = trim((string)($_POST['anniversaryTitle'] ?? ''));
        $content['anniversaryMessage'] = trim((string)($_POST['anniversaryMessage'] ?? ''));

        if (save_site_content($content)) {
            $message = 'Content saved.';
        } else {
            $error = 'Could not save content.';
        }
    } elseif ($action === 'save_music_volume') {
        $volume = (float)($_POST['musicVolume'] ?? 0.45);
        $content['musicVolume'] = max(0, min(1, $volume));
        if (save_site_content($content)) {
            $message = 'Music volume saved.';
        } else {
            $error = 'Could not save volume.';
        }
    } elseif ($action === 'upload_photo') {
        $newPhotos = handle_multiple_image_uploads($_FILES['photos'] ?? []);
        if (count($newPhotos) === 0) {
            $error = 'No photos uploaded. Allowed: jpg, jpeg, png, webp, gif.';
        } else {
            foreach ($newPhotos as $photoPath) {
                $content['photos'][] = $photoPath;
            }
            save_site_content($content);
            $message = count($newPhotos) . ' photo(s) uploaded.';
        }
    } elseif ($action === 'delete_photo') {
        $index = (int)($_POST['photo_index'] ?? -1);
        if (isset($content['photos'][$index])) {
            $path = __DIR__ . '/' . ltrim((string)$content['photos'][$index], '/');
            if (is_file($path)) {
                unlink($path);
            }
            array_splice($content['photos'], $index, 1);
            save_site_content($content);
            $message = 'Photo removed.';
        } else {
            $error = 'Invalid photo index.';
        }
    } elseif ($action === 'upload_music') {
        $newMusic = handle_music_upload($_FILES['music'] ?? []);
        if ($newMusic === null) {
            $error = 'Music upload failed. Allowed: mp3, wav, ogg, m4a.';
        } else {
            if (!empty($content['musicFile'])) {
                $oldPath = __DIR__ . '/' . ltrim((string)$content['musicFile'], '/');
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }
            $content['musicFile'] = $newMusic;
            save_site_content($content);
            $message = 'Music updated.';
        }
    } elseif ($action === 'remove_music') {
        if (!empty($content['musicFile'])) {
            $oldPath = __DIR__ . '/' . ltrim((string)$content['musicFile'], '/');
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
            $content['musicFile'] = '';
            save_site_content($content);
            $message = 'Music removed.';
        }
    }

    $content = load_site_content();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anniversary Admin</title>
    <style>
        :root {
            --bg: #fff1f7;
            --card: #fff;
            --ink: #3b1530;
            --muted: #8a4f73;
            --accent: #ff4f9a;
            --line: #f0cfe2;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { width: min(980px, 94%); margin: 20px auto 40px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 18px; margin-bottom: 14px; }
        h1, h2, h3 { margin-top: 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        label { font-size: 0.86rem; color: var(--muted); display: block; margin-bottom: 3px; }
        input[type="text"], input[type="number"], input[type="datetime-local"], input[type="password"], textarea, select {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 10px;
        }
        textarea { min-height: 90px; resize: vertical; }
        .btn { border: none; border-radius: 8px; padding: 10px 14px; cursor: pointer; font-weight: 700; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-ghost { background: #fff; color: var(--ink); border: 1px solid #ccc; }
        .msg { color: #0f6b2f; font-weight: 700; }
        .err { color: #992020; font-weight: 700; }
        .photo-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
        .photo { border: 1px solid var(--line); border-radius: 10px; padding: 8px; background: #fff; }
        .photo img { width: 100%; height: 110px; object-fit: cover; border-radius: 8px; display: block; margin-bottom: 6px; }
        .top-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        @media (max-width: 850px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card top-actions">
        <div>
            <h1>Anniversary Admin</h1>
            <p>Edit messages, upload photos/music, and control your live site content.</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-ghost" style="text-decoration:none;display:inline-block;">Open Public Site</a>
        </div>
    </div>

    <?php if ($message): ?><p class="msg"><?php echo h($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="err"><?php echo h($error); ?></p><?php endif; ?>

    <?php if (!is_admin_logged_in()): ?>
        <div class="card" style="max-width:420px;">
            <h2>Login</h2>
            <p>Change <code>ADMIN_PASSWORD</code> in <code>admin.php</code> before sharing this site.</p>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <label>Password</label>
                <input type="password" name="password" required>
                <button class="btn btn-primary" type="submit">Enter Admin Mode</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <form method="post">
                <input type="hidden" name="action" value="logout">
                <button class="btn btn-ghost" type="submit">Log Out</button>
            </form>
        </div>

        <form method="post" class="card">
            <input type="hidden" name="action" value="save_content">
            <h2>Editable Text Content</h2>

            <div class="grid">
                <div>
                    <label>Anniversary Date/Time</label>
                    <input type="datetime-local" name="anniversaryDate" value="<?php echo h(date('Y-m-d\TH:i', strtotime((string)$content['anniversaryDate']))); ?>">
                </div>
                <div>
                    <label>Kicker</label>
                    <input type="text" name="kicker" value="<?php echo h($content['kicker']); ?>">
                </div>
            </div>

            <label>Hero Title</label>
            <input type="text" name="title" value="<?php echo h($content['title']); ?>">
            <label>Hero Subtitle</label>
            <textarea name="subtitle"><?php echo h($content['subtitle']); ?></textarea>

            <label>Countdown Label</label>
            <input type="text" name="countdownLabel" value="<?php echo h($content['countdownLabel']); ?>">

            <h3>Timeline</h3>
            <label>Timeline Title</label>
            <input type="text" name="timelineTitle" value="<?php echo h($content['timelineTitle']); ?>">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="grid">
                    <div>
                        <label>Timeline Item <?php echo $i + 1; ?> Title</label>
                        <input type="text" name="timeline_title_<?php echo $i; ?>" value="<?php echo h($content['timeline'][$i]['title'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Timeline Item <?php echo $i + 1; ?> Text</label>
                        <input type="text" name="timeline_text_<?php echo $i; ?>" value="<?php echo h($content['timeline'][$i]['text'] ?? ''); ?>">
                    </div>
                </div>
            <?php endfor; ?>

            <h3>Gallery</h3>
            <div class="grid">
                <div>
                    <label>Gallery Title</label>
                    <input type="text" name="galleryTitle" value="<?php echo h($content['galleryTitle']); ?>">
                </div>
                <div>
                    <label>Gallery Hint</label>
                    <input type="text" name="galleryHint" value="<?php echo h($content['galleryHint']); ?>">
                </div>
            </div>

            <h3>Final Message Section</h3>
            <div class="grid">
                <div>
                    <label>Final Title</label>
                    <input type="text" name="finalTitle" value="<?php echo h($content['finalTitle']); ?>">
                </div>
                <div>
                    <label>Happy Anniversary Title</label>
                    <input type="text" name="anniversaryTitle" value="<?php echo h($content['anniversaryTitle'] ?? 'Happy 11th Anniversary'); ?>">
                </div>
            </div>
            <label>Final Message</label>
            <textarea name="finalMessage"><?php echo h($content['finalMessage']); ?></textarea>

            <h3>Video Page Section</h3>
            <div class="grid">
                <div>
                    <label>Video Title</label>
                    <input type="text" name="videoTitle" value="<?php echo h($content['videoTitle'] ?? 'A Video For You'); ?>">
                </div>
                <div>
                    <label>Video Hint</label>
                    <input type="text" name="videoHint" value="<?php echo h($content['videoHint'] ?? ''); ?>">
                </div>
            </div>
            <label>Google Drive Video URL (share or preview link)</label>
            <input type="text" name="videoUrl" value="<?php echo h($content['videoUrl'] ?? ''); ?>">

            <label>Happy Anniversary Message</label>
            <textarea name="anniversaryMessage"><?php echo h($content['anniversaryMessage'] ?? ''); ?></textarea>

            <button class="btn btn-primary" type="submit">Save All Text Content</button>
        </form>

        <div class="card">
            <h2>Photo Upload</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_photo">
                <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple required>
                <button class="btn btn-primary" type="submit">Upload Selected Photos</button>
            </form>

            <div class="photo-list" style="margin-top:12px;">
                <?php foreach (($content['photos'] ?? []) as $index => $photoPath): ?>
                    <div class="photo">
                        <img src="<?php echo h($photoPath); ?>" alt="photo">
                        <form method="post">
                            <input type="hidden" name="action" value="delete_photo">
                            <input type="hidden" name="photo_index" value="<?php echo (int)$index; ?>">
                            <button class="btn btn-ghost" type="submit">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Music Upload</h2>
            <form method="post" class="grid">
                <input type="hidden" name="action" value="save_music_volume">
                <div>
                    <label>Autoplay Volume (0.0 to 1.0)</label>
                    <input type="number" min="0" max="1" step="0.01" name="musicVolume" value="<?php echo h((string)($content['musicVolume'] ?? 0.45)); ?>">
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button class="btn btn-ghost" type="submit">Save Volume</button>
                </div>
            </form>

            <?php if (!empty($content['musicFile'])): ?>
                <p>Current music: <code><?php echo h($content['musicFile']); ?></code></p>
                <audio controls src="<?php echo h($content['musicFile']); ?>"></audio>
                <form method="post" style="margin-top:10px;">
                    <input type="hidden" name="action" value="remove_music">
                    <button class="btn btn-ghost" type="submit">Remove Music</button>
                </form>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
                <input type="hidden" name="action" value="upload_music">
                <input type="file" name="music" accept=".mp3,.wav,.ogg,.m4a" required>
                <button class="btn btn-primary" type="submit">Upload / Replace Music</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
