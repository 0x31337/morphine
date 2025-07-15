var reconnectInterval = 5000; // Reconnect every 5 seconds
var maxRetries = 9999999750; // Maximum number of retries
var retryCount = 0; // Current retry count


function createWebSocket() {
    let Zsocket = new WebSocket('ws://localhost:8080');

    // Connection opened
    Zsocket.addEventListener('open', function (event) {
        console.log('Connected to the WebSocket server');

        retryCount = 0; // Reset retry count on successful connection

        // Subscribe to Socket
        const message = 'subscribe_phrase::20210801';
        try {
            Zsocket.send(message);
        } catch (Exception)
        {
            Zsocket.close();
        }

    });

    // Listen for messages
    Zsocket.addEventListener('message', function (event) {
        //console.log('Message from server ', event.data);
        var data = JSON.parse(event.data);

        if(data.data !== null)
        {
            zsocket_do_action(data);
        }
    });

    // Handle connection close
    Zsocket.addEventListener('close', function (event) {
        console.log('WebSocket connection closed', event);
        attemptReconnect();
    });

    // Handle errors
    Zsocket.addEventListener('error', function (event) {
        console.error('WebSocket error observed:', event);
        Zsocket.close();
    });
}

function attemptReconnect() {
    if (retryCount < maxRetries) {
        setTimeout(function () {
            console.log('Attempting to reconnect...');
            retryCount++;
            createWebSocket();
        }, reconnectInterval);
    } else {
        console.log('Max retries reached. Giving up on reconnecting.');
    }
}

// Initial WebSocket connection
createWebSocket();