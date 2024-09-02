<?php
// Start session
session_start();

include 'config_connection.php';

// Set headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

include 'tv_initialize.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <link rel="icon" type="image/png" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style_tv.css">
    <!-- <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"> -->
    <title><?php echo $_SESSION['tv_name'] ?></title>
</head>
<p id="screen" style="color: white; position: fixed"></p>
<body>
    <?php 
        if ($topbarPosition === 'top'){
            include('tv_topbar.php'); 
        }
    ?>
    <div style="background: <?php echo $backgroundColor ?>; cursor: pointer; width: 100%; height: calc(100% - 7vh); overflow: hidden; display: flex; flex-direction: column; /* Arrange containers vertically */ height: 100vh; overflow: hidden; /* Prevent any overflow */" id="tvBackgroundColor">
        <div class="main-container" id="main-container">
        <?php foreach ($containers as $container): ?>
                <?php $containerNameLower = strtolower($container['container_name']); ?>
                <div id="<?php echo $container['container_name']; ?>" class="content-container" data-container-id="<?php echo $container['container_id'];?>" 
                    style="background: <?php echo $container['parent_background_color']; ?>;
                            display: <?php echo $container['visible'] ? 'block' : 'none'; ?>;
                            height: <?php echo $container['height_px']; ?>px;
                            width: <?php echo $container['width_px']; ?>px;"
                    onclick="openContentContainerRightSidePanel('<?php echo $container['container_id']; ?>')">
                    <h1 class="content-title" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>"><?php echo $container['container_name']; ?></h1>
                    <div id="<?php echo $containerNameLower; ?>CarouselContainer" class="carousel-container"
                        style="background: <?php echo $container['child_background_color']; ?>;
                               color: <?php echo $container['child_font_color']; ?>;
                               font-style: <?php echo $container['child_font_style'];?>;
                               font-family: <?php echo $container['child_font_family']; ?>">
                        <!-- Content for carousel-container will be displayed here -->
                    </div>
                    <div id="<?php echo $containerNameLower; ?>PageNumberContainer" class="<?php echo $containerNameLower; ?>PageNumberContainer" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; text-align: center"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php 
        if ($topbarPosition === 'bottom'){
            include('tv_topbar.php'); 
        }
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/web-animations/2.3.1/web-animations.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/muuri/0.5.3/muuri.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.11/dist/interact.min.js"></script>
    <script src="js_tv/fetch_tv_content.js"></script>
    <script>
        window.onload = function() {
            var w = window.innerWidth;
            var h = window.innerHeight;
            console.log("Width: ", w);
            console.log("Height: ", h);

            // Update the <p> tag with the screen dimensions
            var screenHeightElement = document.getElementById("screen");
            screenHeightElement.innerText = w + " x " + h;
        };
        window.addEventListener('resize', function() {
            var w = window.innerWidth;
            var h = window.innerHeight;
            console.log("Width: ", w);
            console.log("Height: ", h);

            // Update the <p> tag with the screen dimensions
            var screenHeightElement = document.getElementById("screen");
            screenHeightElement.innerText = w + " x " + h;
        });

        function openContentContainerRightSidePanel(containerId) {
            window.parent.postMessage({
                action: 'openContentContainerRightSidePanel',
                containerId: containerId
            }, '*');
        }

        initGrid();

        function initGrid() {
            const grid = new Muuri('.main-container', {
                dragEnabled: true,
                dragSortPredicate: {
                    threshold: 50,
                    action: 'move'
                },
                dragReleaseDuration: 400,
                dragContainer: document.body,
                layoutOnInit: true, // Layout should initialize
                layout: {
                    fillGaps: true // Prevent overflow by filling gaps
                }
            });

            // Directly establish WebSocket connection and load layout from the server
            const ws = new WebSocket('ws://192.168.1.19:8081');

            ws.onopen = () => {
                console.log("WebSocket connection established.");
                // Request the layout from the server once the connection is open
                ws.send(JSON.stringify({
                    action: 'load_layout',
                    tv_id: <?php echo $_GET['tvId']; ?>
                }));
            };

            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);

                if (message.action === 'save_layout') {
                    ws.send(JSON.stringify({
                        action: 'load_layout',
                        tv_id: <?php echo $_GET['tvId']; ?>
                    }));
                } else if (message.action === 'load_layout') {
                    if (message.success && message.layout) {
                        loadLayout(grid, message.layout);
                    } else {
                        grid.layout(true); // Default layout if no saved layout
                    }
                } 
            };

            ws.onerror = function(error) {
                console.error('WebSocket Error:', error);
                grid.layout(true); // Default layout in case of error
            };

            // Save layout whenever items are moved
            grid.on('move', function () {
                saveLayoutToServer(grid, ws);
            });

            grid.on('dragReleaseEnd', function () {
                grid.layout(true);
            });

            // Make each container resizable with constraints
            interact('.content-container').resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                listeners: {
                    move(event) {
                        const target = event.target;
                        const parentContainer = target.parentElement;

                        let x = (parseFloat(target.getAttribute('data-x')) || 0);
                        let y = (parseFloat(target.getAttribute('data-y')) || 0);

                        // Calculate the new dimensions
                        let newWidth = event.rect.width;
                        let newHeight = event.rect.height;

                        // Get the parent container's dimensions
                        const maxWidth = parentContainer.clientWidth - target.offsetLeft;
                        const maxHeight = parentContainer.clientHeight - target.offsetTop;

                        // Apply constraints to ensure no overflow
                        if (newWidth > maxWidth) newWidth = maxWidth;
                        if (newHeight > maxHeight) newHeight = maxHeight;

                        // Update the element's style
                        target.style.width = newWidth + 'px';
                        target.style.height = newHeight + 'px';

                        // Translate when resizing from top or left edges
                        x += event.deltaRect.left;
                        y += event.deltaRect.top;

                        target.style.transform = 'translate(' + x + 'px,' + y + 'px)';

                        // Update the data attributes
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);

                        // Refresh Muuri layout
                        grid.refreshItems().layout();
                    },
                    end(event) {
                        const target = event.target;
                        const containerId = target.getAttribute('data-container-id');
                        const newWidth = parseInt(target.style.width, 10);
                        const newHeight = parseInt(target.style.height, 10);

                        // Send the new dimensions to the server
                        const message = JSON.stringify({
                            action: 'update_container_dimensions',
                            tv_id: <?php echo $_GET['tvId']; ?>,
                            container_id: containerId,
                            width: newWidth,
                            height: newHeight
                        });

                        ws.send(message);

                        // Ensure the layout is recalculated
                        grid.refreshItems().layout(); 
                    }
                }
            });

            // Update drag constraints based on parent container
            grid.on('dragStart', function (item) {
                const parentContainer = item.getElement().parentElement;
                item.getElement().style.pointerEvents = 'none'; // Disable pointer events during drag
                item.getElement().style.position = 'absolute'; // Set position to absolute for dragging
                item.getElement().style.zIndex = 1000; // Bring to front
                item.getElement().style.maxWidth = parentContainer.clientWidth + 'px'; // Set max width
                item.getElement().style.maxHeight = parentContainer.clientHeight + 'px'; // Set max height
            });

            grid.on('dragEnd', function (item) {
                item.getElement().style.pointerEvents = ''; // Re-enable pointer events
                item.getElement().style.position = ''; // Reset position
                item.getElement().style.zIndex = ''; // Reset z-index
            });
        }

        function serializeLayout(grid) {
            return grid.getItems().map(item => {
                const el = item.getElement();
                return {
                    id: el.getAttribute('data-container-id'),
                    x: item.getPosition().left,
                    y: item.getPosition().top,
                    width: el.offsetWidth,
                    height: el.offsetHeight
                };
            });
        }

        function saveLayoutToServer(grid, ws) {
            const layout = serializeLayout(grid);
            const message = JSON.stringify({
                action: 'save_layout',
                tv_id: <?php echo $_GET['tvId']; ?>,
                layout: layout
            });

            ws.send(message); // Send the updated layout to the server
        }

        function loadLayout(grid, serializedLayout) {
            const layout = serializedLayout;
            const currentItems = grid.getItems();
            const itemMap = {};

            // Map the current items by their container ID
            currentItems.forEach(item => {
                const id = item.getElement().getAttribute('data-container-id');
                itemMap[id] = item;
            });

            // Apply the layout from the server
            layout.forEach((item, index) => {
                if (itemMap[item.id]) {
                    const element = itemMap[item.id].getElement();
                    element.style.transform = `translate(${item.x}px, ${item.y}px)`;
                    grid.move(itemMap[item.id], index);
                }
            });

            // Refresh the grid layout
            grid.layout();
        }

        // Function to update the clock
        function updateClock() {
            const now = new Date();
            const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12; // Convert to 12-hour format
            const dayOfWeek = daysOfWeek[now.getDay()]; // Get day of the week
            const month = months[now.getMonth()]; // Get full month name
            const day = now.getDate().toString().padStart(2, '0');
            const year = now.getFullYear();

            document.getElementById('live-clock').textContent = hours + ':' + minutes + ' ' + ampm;
            document.getElementById('live-date').textContent = dayOfWeek + ', ' + month + ' ' + day + ', ' + year;
        }

        // Update the clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial update
    </script>
</body>
</html>