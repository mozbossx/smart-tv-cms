<?php
// Start session
session_start();

include 'config_connection.php';

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
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="misc/js/treant-js-master/vendor/jquery.min.js"></script>
    <script src="misc/js/treant-js-master/vendor/raphael.js"></script>
    <script src="misc/js/treant-js-master/Treant.js"></script>
    <link rel="stylesheet" href="misc/js/treant-js-master/Treant.css" />
    <script src="jquery-textfill-master/dist/jquery.textfill.min.js"></script>
    <script src="jquery-textfill-master/dist/jquery.textfill.js"></script>
    <script src="https://bumbu.me/svg-pan-zoom/dist/svg-pan-zoom.min.js"></script>
    <title><?php echo $_SESSION['tv_name'] ?></title>
</head>
<p id="screen" style="display: none; color: white; position: fixed"></p>
<body>
    <div style="<?php 
        if ($isStaticBackground) {
            echo "background: $backgroundColor;";
        } else if ($isImageBackground) {
            echo "background-image: url('servers/tv_backgrounds/$tvId/$backgroundImagePath');";
            echo "background-size: cover;";
            echo "background-position: center;";
            echo "background-repeat: no-repeat;";
        }
        ?> cursor: pointer; width: 100%; height: calc(100% - 7vh); overflow: hidden; display: flex; flex-direction: column; /* Arrange containers vertically */ height: 100vh; overflow: hidden; /* Prevent any overflow */" id="tvBackgroundColor">
        <?php if ($topbarPosition === 'top') { ?>
            <?php include('tv_topbar.php'); ?>
        <?php } ?>
        <div class="main-container" id="main-container">
        <?php foreach ($containers as $container): ?>
            <?php if ($container['type'] !== 'orgchart'):  ?>
                <?php $containerNameLower = strtolower($container['type']); ?>
                <div id="<?php echo $container['container_name']; ?>" class="content-container" data-container-id="<?php echo $container['container_id'];?>" 
                    data-x="<?php echo $container['xaxis']; ?>"
                    data-y="<?php echo $container['yaxis']; ?>"
                    style="background: <?php echo $container['parent_background_color']; ?>;
                            display: <?php echo $container['visible'] ? 'block' : 'none'; ?>;
                            height: <?php echo $container['height_px']; ?>px;
                            width: <?php echo $container['width_px']; ?>px;
                            transform: translate(<?php echo $container['xaxis']; ?>px, <?php echo $container['yaxis']; ?>px);"
                    onclick="openContentContainerRightSidePanel('<?php echo $container['container_id']; ?>')">
                    <div>
                        <h1 id="<?php echo $containerNameLower; ?>ContainerName" class="content-title" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; width: 100%; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: <?php echo $container['container_name_text_align']; ?>;"><?php echo $container['container_name']; ?></h1>
                    </div>
                    <div id="<?php echo $containerNameLower; ?>CarouselContainer" class="carousel-container"
                        style="background: <?php echo $container['child_background_color']; ?>;
                                color: <?php echo $container['child_font_color']; ?>;
                                font-style: <?php echo $container['child_font_style'];?>;
                                font-family: <?php echo $container['child_font_family']; ?>">
                        <!-- Content for carousel-container will be displayed here -->
                    </div>
                    <div id="<?php echo $containerNameLower; ?>PageNumberContainer" class="page-number-container" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; text-align: center"></div>
                </div>        
            <?php endif; ?>
            <?php if ($container['type'] === 'orgchart'):  ?>
                <?php $containerNameLower = strtolower($container['type']); ?>
                <div id="<?php echo $container['container_name']; ?>" class="content-container" data-container-id="<?php echo $container['container_id'];?>" 
                    data-x="<?php echo $container['xaxis']; ?>"
                    data-y="<?php echo $container['yaxis']; ?>"
                    style="background: <?php echo $container['parent_background_color']; ?>;
                            display: <?php echo $container['visible'] ? 'block' : 'none'; ?>;
                            height: <?php echo $container['height_px']; ?>px;
                            width: <?php echo $container['width_px']; ?>px;
                            min-height: 450px;
                            min-width: 500px;
                            transform: translate(<?php echo $container['xaxis']; ?>px, <?php echo $container['yaxis']; ?>px);"
                    onclick="openContentContainerRightSidePanel('<?php echo $container['container_id']; ?>')">
                    <div>
                        <h1 id="<?php echo $containerNameLower; ?>ContainerName" class="content-title" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; width: 100%; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: <?php echo $container['container_name_text_align']; ?>;"><i class="<?php echo $iconClass; ?>" style="margin-right: 6px" aria-hidden="true"></i><?php echo $container['container_name']; ?></h1>
                    </div>
                    <div id="<?php echo $containerNameLower; ?>CarouselContainer" class="carousel-container"
                        style="background: <?php echo $container['child_background_color']; ?>;
                                color: <?php echo $container['child_font_color']; ?>;
                                font-style: <?php echo $container['child_font_style'];?>;
                                font-family: <?php echo $container['child_font_family']; ?>">
                        <!-- Content for carousel-container will be displayed here -->
                    </div>
                    <div id="<?php echo $containerNameLower; ?>PageNumberContainer" class="page-number-container" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; text-align: center"></div>
                </div> 
            <?php endif; ?>

            
        <?php endforeach; ?>

            
        </div>
        <?php if ($topbarPosition === 'bottom') { ?>
            <?php include('tv_topbar.php'); ?>
        <?php } ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/web-animations/2.3.1/web-animations.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/muuri/0.5.3/muuri.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script src="js_tv/fetch_tv_content.js"></script>
    <script>
        document.querySelectorAll('.content-container').forEach(container => {
            container.addEventListener('click', function(e) {
                e.stopPropagation();
                const rect = this.getBoundingClientRect();
                window.parent.postMessage({
                    action: 'containerClicked',
                    containerId: this.dataset.containerId,
                    containerRect: {
                        top: rect.top,
                        left: rect.left,
                        width: rect.width,
                        height: rect.height
                    }
                }, '*');
            });
        });

        document.getElementById('tvBackgroundColor').addEventListener('click', function(e) {
            e.stopPropagation();
            const rect = this.getBoundingClientRect();
            window.parent.postMessage({
                action: 'backgroundClicked',
                containerId: this.dataset.containerId,
                containerRect: {
                    top: rect.top,
                    left: rect.left,
                    width: rect.width,  
                    height: rect.height
                }
            }, '*');
        });

        document.getElementById('topbar').addEventListener('click', function(e) {
            e.stopPropagation();
            const rect = this.getBoundingClientRect();
            window.parent.postMessage({
                action: 'topbarClicked',
                containerId: this.dataset.containerId,
                containerRect: {
                    top: rect.top,
                    left: rect.left,
                    width: rect.width,  
                    height: rect.height
                }
            }, '*');
        });

        function sendScreenDimensions() {
            var w = window.innerWidth;
            var h = window.innerHeight;

            // Send dimensions to the server
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "save_screen_dimensions.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("width=" + w + "&height=" + h + "&tvId=" + <?php echo $_GET['tvId']; ?>);
        }

        window.onload = function() {
            sendScreenDimensions();
            var w = window.innerWidth;
            var h = window.innerHeight;

            // Update the <p> tag with the screen dimensions
            var screenHeightElement = document.getElementById("screen");
            screenHeightElement.innerText = w + " x " + h;
        };

        window.addEventListener('resize', function() {
            sendScreenDimensions();
            var w = window.innerWidth;
            var h = window.innerHeight;
            // Update the <p> tag with the screen dimensions
            var screenHeightElement = document.getElementById("screen");
            screenHeightElement.innerText = w + " x " + h;
        });
        
        initGrid();

        function initGrid() {
            interact('.content-container')
                .draggable({
                    // Enable dragging
                    listeners: {
                        start(event) {
                            const target = event.target;
                            const x = parseFloat(target.getAttribute('data-x')) || 0;
                            const y = parseFloat(target.getAttribute('data-y')) || 0;
                            
                            // Set initial position if not already set
                            if (!target.hasAttribute('data-x')) {
                                target.setAttribute('data-x', x);
                            }
                            if (!target.hasAttribute('data-y')) {
                                target.setAttribute('data-y', y);
                            }
                        },
                        move(event) {
                            const target = event.target;
                            const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                            const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                            target.style.transform = `translate(${x}px, ${y}px)`;
                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            // Send updated position to parent window
                            const rect = target.getBoundingClientRect();
                            window.parent.postMessage({
                                action: 'containerMoved',
                                containerId: target.dataset.containerId,
                                containerRect: {
                                    top: rect.top,
                                    left: rect.left,
                                    width: rect.width,
                                    height: rect.height
                                }
                            }, '*');
                        }
                    },
                    modifiers: [
                        interact.modifiers.restrictRect({
                            restriction: 'parent', // Constrain within parent
                            endOnly: true
                        })
                    ]
                })
                .resizable({
                    // Enable resizing
                    edges: { left: true, right: true, bottom: true, top: true },
                    listeners: {
                        start(event) {
                            const target = event.target;
                            const x = parseFloat(target.getAttribute('data-x')) || 0;
                            const y = parseFloat(target.getAttribute('data-y')) || 0;
                            
                            // Set initial position if not already set
                            if (!target.hasAttribute('data-x')) {
                                target.setAttribute('data-x', x);
                            }
                            if (!target.hasAttribute('data-y')) {
                                target.setAttribute('data-y', y);
                            }
                        },
                        move(event) {
                            const target = event.target;
                            let x = parseFloat(target.getAttribute('data-x')) || 0;
                            let y = parseFloat(target.getAttribute('data-y')) || 0;

                            target.style.width = `${event.rect.width}px`;
                            target.style.height = `${event.rect.height}px`;

                            // Adjust position if resizing from top or left edges
                            x += event.deltaRect.left;
                            y += event.deltaRect.top;

                            target.style.transform = `translate(${x}px, ${y}px)`;
                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            // Find all message elements within this container and update their text fitting
                            const messageElements = target.querySelectorAll('.main-message');
                            messageElements.forEach(element => {
                                if (element.id) {
                                    requestAnimationFrame(() => {
                                        fitText(element.id);
                                    });
                                }
                            });

                            // Notify parent window that a container was resized
                            const rect = target.getBoundingClientRect();
                            window.parent.postMessage({
                                action: 'containerMoved',
                                containerId: target.dataset.containerId,
                                containerRect: {
                                    top: rect.top,
                                    left: rect.left,
                                    width: rect.width,
                                    height: rect.height
                                }
                            }, '*');
                        },
                        end(event) {
                            const target = event.target;

                            // Final text fit after resize
                            const messageElements = target.querySelectorAll('.main-message');
                            messageElements.forEach(element => {
                                if (element.id) {
                                    fitText(element.id);
                                }
                            });
                        }
                    },
                    modifiers: [
                        interact.modifiers.restrictSize({
                            min: { width: 0, height: 0 } // Minimum size
                            // max: { width: 400, height: 400 } // Maximum size
                        }),
                        interact.modifiers.restrictEdges({
                            outer: 'parent' // Restrict resizing within parent
                        })
                    ]
                });
        }

        function updateContainerPositions() {
            const containers = document.querySelectorAll('.content-container');
            const positions = Array.from(containers).map(container => {
                const style = window.getComputedStyle(container);
                const transform = new DOMMatrix(style.transform);
                return {
                    id: container.dataset.containerId,
                    x: transform.m41,
                    y: transform.m42,
                    width: container.offsetWidth,
                    height: container.offsetHeight
                };
            });
            return positions;
        }

        // Add an event listener for messages from the parent window
        window.addEventListener('message', function(event) {
            if (event.data.action === 'updateTemplate') {
                const positions = updateContainerPositions();
                window.parent.postMessage({
                    action: 'containerPositionsUpdated',
                    positions: positions
                }, '*');
            }
            
            // Handle WebSocket messages for cookie management
            if (event.data.action === 'delete_smart_tv' && event.data.unset_cookies && event.data.success) {
                // Delete the tv_id cookie
                document.cookie = "tv_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                
                // Reload the page to generate a new TV ID
                window.location.reload();
            }
        });

        function openContentContainerRightSidePanel(containerId) {
            window.parent.postMessage({
                action: 'openContentContainerRightSidePanel',
                containerId: containerId
            }, '*');
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