(function (global) {
    'use strict';

    const MorphAjax = {
        get: function (url, successCallback, errorCallback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        successCallback(xhr.responseText, xhr);
                    } else {
                        if (errorCallback) errorCallback(xhr.status, xhr);
                    }
                }
            };
            xhr.send();
        },

        post: function (url, data, successCallback, errorCallback) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        successCallback(xhr.responseText, xhr);
                    } else {
                        if (errorCallback) errorCallback(xhr.status, xhr);
                    }
                }
            };
            xhr.send(MorphAjax.encodeFormData(data));
        },

        encodeFormData: function (data) {
            const pairs = [];
            for (const name in data) {
                if (data.hasOwnProperty(name)) {
                    pairs.push(encodeURIComponent(name) + '=' + encodeURIComponent(data[name]));
                }
            }
            return pairs.join('&');
        }
    };

    // Export to global scope
    global.MorphAjax = MorphAjax;

})(this);
