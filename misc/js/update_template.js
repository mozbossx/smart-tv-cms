let updatedContainers = {}; // Define updatedContainers in a higher scope
 
const websocket = new WebSocket('ws://192.168.1.13:8081');

websocket.onopen = function() {
    console.log('WebSocket connection established');
};

websocket.onmessage = function(event) {
    console.log('Message from server:', event.data);
};

interact('.content-container')
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

                // Store updated position locally
                const containerId = target.getAttribute('data-container-id');
                updatedContainers[containerId] = updatedContainers[containerId] || {};
                updatedContainers[containerId].xaxis = x;
                updatedContainers[containerId].yaxis = y;
                console.log('Updated position:', updatedContainers); // Debugging
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

                // Store updated dimensions and position locally
                const containerId = target.getAttribute('data-container-id');
                updatedContainers[containerId] = {
                    xaxis: x,
                    yaxis: y,
                    width: event.rect.width,
                    height: event.rect.height
                };
                console.log('Updated dimensions:', updatedContainers); // Debugging
            }
        },
        modifiers: [
            interact.modifiers.restrictSize({
                min: { width: 50, height: 50 } // Minimum size
            }),
            interact.modifiers.restrictEdges({
                outer: 'parent' // Restrict resizing within parent
            })
        ]
    });

// Function to send updates to the server
function updateTemplate() {
    const tvId = "<?php echo $_GET['tvId']; ?>";
    const data = {
        action: 'update_template',
        tv_id: tvId,
        containers: updatedContainers
    };
    console.log('Sending data to server:', data); // Debugging
    websocket.send(JSON.stringify(data));
}
