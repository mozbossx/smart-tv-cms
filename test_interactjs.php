<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draggable & Resizable Containers</title>
    <style>
        body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #parent-container {
            width: 80%;
            height: 80%;
            border: 2px solid #333;
            position: relative;
            overflow: hidden;
            padding: 10px;
        }
        .draggable-resizable {
            width: 150px;
            height: 150px;
            background-color: lightblue;
            border: 2px solid blue;
            position: absolute;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 10px;
        }
        #container1 {
            top: 20px;
            left: 20px;
        }
        #container2 {
            top: 200px;
            left: 200px;
        }
        .content {
            padding: 10px;
            background-color: white;
            margin: 0px auto;
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>

<div id="parent-container">
    <div id="container1" class="draggable-resizable">
        <div class="content">
            <p>Container 1</p>
        </div>
    </div>
    <div id="container2" class="draggable-resizable"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script>
    interact('.draggable-resizable')
        .draggable({
            // Enable dragging
            listeners: {
                move(event) {
                    const target = event.target;
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                    // Move element by updating its transform property
                    target.style.transform = `translate(${x}px, ${y}px)`;

                    // Store position for future use
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
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
                move(event) {
                    const target = event.target;
                    let x = (parseFloat(target.getAttribute('data-x')) || 0);
                    let y = (parseFloat(target.getAttribute('data-y')) || 0);

                    // Update the element's width and height
                    target.style.width = `${event.rect.width}px`;
                    target.style.height = `${event.rect.height}px`;

                    // Translate when resizing from top or left edges
                    x += event.deltaRect.left;
                    y += event.deltaRect.top;

                    target.style.transform = `translate(${x}px, ${y}px)`;

                    // Store new position
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                }
            },
            modifiers: [
                interact.modifiers.restrictSize({
                    min: { width: 50, height: 50 } // Minimum size
                    // max: { width: 400, height: 400 } // Maximum size
                }),
                interact.modifiers.restrictEdges({
                    outer: 'parent' // Restrict resizing within parent
                })
            ]
        });
</script>

</body>
</html>
