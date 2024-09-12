<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Improved Resizable Text Container</title>
    <style>
        .resize-container {
            background-color: #a7f3d0;
            border: 2px solid #34d399;
            padding: 16px;
            overflow: hidden;
            width: 400px;
            height: 200px;
            min-width: 200px;
            min-height: 100px;
            resize: both;
            position: relative;
            text-align: justify;
        }
        .text-container {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        h2 {
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 8px;
        }
        .posted-by {
            margin-top: 8px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="resize-container">
        <div class="text-container">
            <h2>Announcements</h2>
            <p id="content1">
                HELLO WORLD
            </p>
            <p class="posted-by">Posted By Carlo</p>
        </div>
    </div>
    <div class="resize-container">
        <div class="text-container">
            <h2>Events</h2>
            <p id="content2">
            Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?
            </p>
            <p class="posted-by">Posted By Carlo</p>
        </div>
    </div>

    <script>
        const containers = document.querySelectorAll('.resize-container');

        function adjustFontSize(container) {
            const textContainer = container.querySelector('.text-container');
            const containerWidth = container.offsetWidth;
            const containerHeight = container.offsetHeight;

            let fontSize = 10;
            textContainer.style.fontSize = `${fontSize}px`;

            while (
                textContainer.scrollWidth <= containerWidth &&
                textContainer.scrollHeight <= containerHeight &&
                fontSize < 100
            ) {
                fontSize++;
                textContainer.style.fontSize = `${fontSize}px`;
            }

            fontSize -= 2; // Reduce by 2 to ensure text fits within container
            textContainer.style.fontSize = `${fontSize}px`;
        }

        // Initial adjustment for all containers
        containers.forEach(container => adjustFontSize(container));

        // Use ResizeObserver for dynamic resizing
        const resizeObserver = new ResizeObserver((entries) => {
            for (let entry of entries) {
                adjustFontSize(entry.target);
            }
        });

        containers.forEach(container => resizeObserver.observe(container));
    </script>
</body>
</html>
