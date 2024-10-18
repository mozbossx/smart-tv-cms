let Ws;

function initializeWebSocket() {
    Ws = new WebSocket('ws://192.168.1.30:8081');

    Ws.onopen = function(event) {
        console.log("WebSocket connection established");
    };

    Ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        if (window.handleContentMessage) {
            handleContentMessage(data);
        } else if (window.handleTvContentMessage) {
            handleTvContentMessage(data);
        } else if (window.handleNotificationMessage) {
            handleNotificationMessage(data);
        } else if (window.handleMyProfileMessage) {
            handleMyProfileMessage(data);
        } else if (window.handleArchivedContentMessage) {
            handleArchivedContentMessage(data);
        } else if (window.handleUserMessage) {
            handleUserMessage(data);
        } else if (window.handleSmartTVMessage) {
            handleSmartTVMessage(data);
        } else if (window.handleUserSessionMessage) {
            handleUserSessionMessage(data);
        }
    };

    Ws.onerror = function(error) {
        console.error('WebSocket Error:', error);
    };

    Ws.onclose = function(event) {
        console.log('WebSocket connection closed');
        setTimeout(initializeWebSocket, 5000); // Attempt to reconnect after 5 seconds
    };
}

document.addEventListener('DOMContentLoaded', initializeWebSocket);

function sendWebSocketMessage(message) {
    if (Ws && Ws.readyState === WebSocket.OPEN) {
        Ws.send(JSON.stringify(message));
    } else {
        console.error('WebSocket is not connected');
    }
}