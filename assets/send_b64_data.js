function send_b64_data( b64data , filename , post_data , success_callback ) {

    var boundary, dashdash, crlf, multipart_string,
        file_data_name, name, mime_type , xhr;

    // setup multipart
    boundary            = '----multipart_boundary'+(new Date().getTime().toString(32))+Math.random().toString(32);
    dashdash            = '--';
    crlf                = '\r\n';


    // build request payload
    multipart_string    = '';
    for ( name in post_data ) {
        multipart_string += dashdash + boundary + crlf +
            'Content-Disposition: form-data; name="' + name + '"' + crlf + crlf;
        multipart_string += unescape(encodeURIComponent(post_data[name])) + crlf;
    }

    // add image data
    mime_type       = 'image/png';
    file_data_name  = 'async-upload';
    multipart_string += dashdash + boundary + crlf +
        'Content-Disposition: form-data; name="' + wp.Uploader.defaults.file_data_name + '"; filename="' + filename + '"' + crlf +
        'Content-Type: ' + mime_type + crlf +
            crlf + atob( b64data ) + crlf +
            dashdash + boundary + dashdash + crlf;

    // build and send request
    xhr = new XMLHttpRequest()
    xhr.open("post", wp.Uploader.defaults.url, true);
    xhr.setRequestHeader('Content-Type', 'multipart/form-data; boundary=' + boundary);
    xhr.onreadystatechange = function() {
        var httpStatus, chunkArgs;
        if (xhr.readyState == 4 ) {
            try {
                httpStatus = xhr.status;
            } catch (ex) {
                httpStatus = 0;
            }
            if (httpStatus == 200) {
                // will load contents to file fake
                success_callback(xhr,httpStatus);
            } else if ( httpStatus >= 400 ) {
                // handle error
            }
        }
    }

    if (xhr.sendAsBinary) { // Gecko
        xhr.sendAsBinary(multipart_string);
    } else { // WebKit with typed arrays support
        var ui8a = new Uint8Array(multipart_string.length);
        for (var i = 0; i < multipart_string.length; i++) {
            ui8a[i] = (multipart_string.charCodeAt(i) & 0xff);
        }
        xhr.send(ui8a.buffer);
    }
}
