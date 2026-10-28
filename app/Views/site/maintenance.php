<?php use App\Libraries\Cms; ?>
<!doctype html>
<html lang="<?= esc(Cms::htmlLang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= esc($general['siteName']) ?></title>
    <link rel="stylesheet" href="<?= esc(Cms::fontUrl($theme)) ?>">
    <style><?= Cms::themeCss($theme) ?>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--ff-ink);color:#fff;font-family:var(--ff-sans);padding:32px;text-align:center}
img{height:40px;background:#fff;padding:8px 12px;border-radius:4px}h1{font-size:clamp(28px,4vw,42px);font-weight:var(--ff-hw);margin:28px 0 12px}p{color:#d0dae2;font-size:18px;line-height:1.6;max-width:520px;margin:0 auto}</style>
</head>
<body>
<main>
    <img src="<?= esc($general['logo']) ?>" alt="<?= esc($general['siteName']) ?>">
    <h1>We'll be back shortly.</h1>
    <p><?= esc($message) ?></p>
</main>
</body>
</html>
