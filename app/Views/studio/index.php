<?php $v = static fn (string $file): string => base_url('assets/studio/' . $file) . '?v=' . filemtime(FCPATH . 'assets/studio/' . $file); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Fynefield Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=Hanken+Grotesk:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap">
    <style>
        html,body,#studio{height:100%}
        body{margin:0;font-family:'Instrument Sans',system-ui,sans-serif;color:#10263b;background:#f4f6f8;-webkit-font-smoothing:antialiased}
        *{box-sizing:border-box}a{color:inherit;text-decoration:none}a:hover{color:#c97d0f}
        input,textarea,button,select{font-family:inherit}::selection{background:#e59b23;color:#10263b}
        .sf-focus:focus{border-color:#e59b23!important;box-shadow:0 0 0 3px rgba(229,155,35,.18)}
        .sf-focus-plain:focus{border-color:#e59b23!important}
        .sf-hover-lib:hover{border-color:#e59b23!important;background:#fffaf1!important}
        .sf-hover-row:hover{background:#fafbfc!important}
        .sf-hover-menu:hover{background:#f4f6f8!important}
        .sf-hover-quick:hover{border-color:#e59b23!important;box-shadow:0 6px 18px rgba(16,38,59,.06)}
        .sf-hover-drop:hover{border-color:#e59b23!important;background:#fffaf1!important}
        .sf-hover-pick:hover{border-color:#e59b23!important;box-shadow:0 0 0 3px rgba(229,155,35,.2)}
        .sf-boot{display:grid;place-items:center;height:100%;color:#5b6977;font-size:14px}
    </style>
    <script type="application/json" id="studio-boot"><?= json_encode($boot + ['csrf' => csrf_hash(), 'csrfHeader' => config('Security')->headerName, 'base' => rtrim(site_url('admin'), '/'), 'logout' => site_url('admin/logout'), 'profile' => site_url('admin/profile')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
<div id="studio"><div class="sf-boot">Loading Studio…</div></div>
<script src="<?= $v('vendor/react.production.min.js') ?>"></script>
<script src="<?= $v('vendor/react-dom.production.min.js') ?>"></script>
<script src="<?= $v('vendor/htm.umd.js') ?>"></script>
<script src="<?= $v('core.js') ?>"></script>
<script src="<?= $v('preview.js') ?>"></script>
<script src="<?= $v('screens.js') ?>"></script>
<script src="<?= $v('app.js') ?>"></script>
</body>
</html>
