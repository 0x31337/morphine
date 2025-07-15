function loadScript(src, callback) {
    const script = document.createElement('script');
    script.src = src;
    script.onload = callback;
    document.head.append(script);
}

function zsocket_do_action(data)
{
    if (data.data === "sample") {
        // do something
    }
}