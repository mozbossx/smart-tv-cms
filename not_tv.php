<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted</title>
    <link rel="icon" type="image/png" href="images/usc_icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: black;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
        }

        .container {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(133, 1, 1, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            max-width: 600px;
            width: 90%;
            margin: 20px;
        }

        .icon {
            font-size: 5rem;
            color: #e74c3c;
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #ecf0f1;
        }

        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: #bdc3c7;
            line-height: 1.6;
        }

        .back-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: #007638;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .back-button:hover {
            background:rgb(2, 75, 36);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }

            p {
                font-size: 1rem;
            }

            .icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-tv icon"></i>
        <h1>Access Restricted</h1>
        <p>This page is only accessible through Smart TV devices. If you're trying to access this content, please use a Smart TV instead of a mobile device, laptop, or desktop computer.</p>
        <button class="back-button" onclick="goBack()">Go Back</button>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html> 