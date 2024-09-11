<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Announcements</title>
    <style>
        /* Container for announcements */
        .announcement-container {
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid black;
            padding: 20px;
            width: 50%;
            margin: 20px auto;
            border-radius: 10px;
            background-color: #a5f2e7; /* Light cyan background color */
            box-sizing: border-box;
            transition: all 0.3s ease-in-out;
            resize: both;
        }

        /* Flex column layout for the content */
        .announcement-content {
            display: flex;
            flex-direction: column;
            text-align: justify;
            width: 100%;
        }

        /* Heading */
        .announcement-header {
            font-size: 2vw; /* Responsive heading size */
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* Paragraph content */
        .announcement-text {
            font-size: 1vw; /* Responsive text size */
            flex-grow: 1; /* Fills up space between header and footer */
        }

        /* Footer */
        .announcement-footer {
            font-size: 0.9vw; /* Slightly smaller footer text */
            margin-top: 15px;
            text-align: left;
            color: #333;
            font-style: italic;
        }

        /* Adjustments for medium-sized screens */
        @media (max-width: 768px) {
            .announcement-container {
                width: 80%;
            }

            .announcement-header {
                font-size: 3vw;
            }

            .announcement-text {
                font-size: 2vw;
            }

            .announcement-footer {
                font-size: 1.5vw;
            }
        }

        /* Adjustments for small screens */
        @media (max-width: 480px) {
            .announcement-container {
                width: 100%;
            }

            .announcement-header {
                font-size: 4vw;
            }

            .announcement-text {
                font-size: 3vw;
            }

            .announcement-footer {
                font-size: 2vw;
            }
        }
    </style>
</head>
<body>

<div class="announcement-container">
    <div class="announcement-content">
        <div class="announcement-header">Announcements</div>
        <div class="announcement-text">
            It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy.
        </div>
        <div class="announcement-footer">Posted By Carlo</div>
    </div>
</div>

</body>
</html>
