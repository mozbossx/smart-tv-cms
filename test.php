<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Flexbox</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .flex-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 10px;
            height: 100vh;
            padding: 10px;
        }

        .flex-item {
            flex: 1 1 calc(20% - 20px);
            margin: 10px;
            height: 100vh;
            overflow: hidden;
        }

        .flex-item img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="flex-container">
        <div class="flex-item">
            <img src="servers/announcements_media/387.png" alt="Image 1">
        </div>
        <div class="flex-item">
            <img src="servers/announcements_media/387.png" alt="Image 2">
        </div>
        <div class="flex-item">
            <img src="servers/announcements_media/387.png" alt="Image 3">
        </div>
        <div class="flex-item">
            <img src="servers/announcements_media/387.png" alt="Image 4">
        </div>
        <div class="flex-item">
            <img src="servers/announcements_media/387.png" alt="Image 5">
        </div>
    </div>
</body>
</html>
