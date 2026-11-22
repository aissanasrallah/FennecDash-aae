<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>

<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $heading; ?></title>
    <style type="text/css">
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .container { text-align: center; padding: 2rem; max-width: 28rem; }
    h2 { font-size: 6rem; font-weight: 800; color: #2563eb; position: relative; margin-bottom: 1.5rem; }
    h2 svg { width: 2.5rem; height: 2.5rem; position: absolute; top: 0.25rem; left: 50%; margin-left: 3rem; color: #60a5fa; }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border-width: 0; }
    p.title { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; }
    p.message { color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <h2>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
            </svg>
            <span class="sr-only">Error</span>404
        </h2>
        <p class="title"><?= $heading; ?></p>
        <p class="message"><?= $message; ?></p>
    </div>
</body>

</html>