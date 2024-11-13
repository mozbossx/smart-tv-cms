<!DOCTYPE html>
<html>
<head>
    <style>
        .container-wrapper {
            display: flex;
            gap: 20px;
            padding: 20px;
            height: 100vh;
            box-sizing: border-box;
        }

        .content-container {
            background: #e0ffe7;
            border: 1px solid #ccc;
            padding: 15px;
            overflow: hidden;
            min-width: 200px;
            min-height: 200px;
        }

        .content {
            transform-origin: center center;
            width: 100%;
        }

        .content h2 {
            margin: 0 0 10px 0;
            font-size: 1.5em;
        }

        .content p {
            margin: 0;
            line-height: 1.4;
            font-size: 1em;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
</head>
<body>
    <div class="container-wrapper">
        <div class="content-container">
            <div class="content">
                <h2>Announcements</h2>
                <p>It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters.</p>
            </div>
        </div>
        <div class="content-container">
            <div class="content">
                <h2>Events</h2>
                <p>Another container with different content that will scale proportionally when resized. This container can be independently resized while maintaining proper text scaling.</p>
            </div>
        </div>
        <div class="content-container">
            <div class="content">
                <h2>News</h2>
                <p>A third container demonstrating the flexible layout system. Each container maintains its aspect ratio and content scaling independently.</p>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Make containers resizable
            $('.content-container').resizable({
                handles: 'all',
                resize: function(event, ui) {
                    const container = $(this);
                    const content = container.find('.content');
                    
                    // Get the original dimensions (set these as data attributes when first loading)
                    if (!container.data('original-width')) {
                        container.data('original-width', container.width());
                        container.data('original-height', container.height());
                    }
                    
                    // Calculate scale factors
                    const scaleX = container.width() / container.data('original-width');
                    const scaleY = container.height() / container.data('original-height');
                    
                    // Use the smaller scale factor to maintain aspect ratio
                    const scale = Math.min(scaleX, scaleY);
                    
                    // Apply the scaling transform
                    content.css('transform', `scale(${scale})`);
                }
            });
            
            // Store original dimensions on load
            $('.content-container').each(function() {
                $(this).data('original-width', $(this).width());
                $(this).data('original-height', $(this).height());
            });
        });
    </script>
</body>
</html>