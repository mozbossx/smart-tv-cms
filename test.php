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

        .changeFont {
            font-family: 'Courier New';
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="changeFont">
        This is a different font.
        <div>
            <p>Hello World!</p>
        </div>
    </div>
</body>
</html>
