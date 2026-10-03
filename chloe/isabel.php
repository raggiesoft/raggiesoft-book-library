<?php
// Simple verification test for Nginx and PHP
header('Content-Type: text/html; charset=utf-8');

$requestUri = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stardust Engine Library</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            background-color: #ffffff;
            padding: 2rem 4rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        h1 {
            color: #2563eb;
            margin-bottom: 0.5rem;
        }
        .details {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: monospace;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Stardust Engine Library</h1>
        <h2>books.raggiesoft.com is online</h2>
        <div class="details">
            <p><strong>Router:</strong> /chloe/isabel.php</p>
            <p><strong>Requested URI:</strong> <?php echo htmlspecialchars($requestUri); ?></p>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        </div>
    </div>
</body>
</html>
