var AJAX = function (url, params, ele, gif, func, p) {
    this.url = url;
    this.params = params;
    this.ele = ele;
    this.func = func;
    this.p = p;
    this.gif = gif;
    this.currentPercentage = 0;
    this.fileBlocksComplete = 0;
    this.files = null;
    this.currentFile = 0;
    this.numFiles = 0;
    this.maxRange = null;
}

AJAX.prototype.createRequest = function () {
    return (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
}

AJAX.prototype.setUpResponseHandler = function (request, append, filesUpload, func, numChunks) {
    var h = this.hide,
        p = this.p,
        ele = this.ele,
        i = 0,
        rt = '',
        gif = this.gif,
        a = this,
        numChunks = (numChunks == undefined) ? 0 : numChunks,
        f = (func == null) ? this.func : func,
        response = null;
    request.onreadystatechange = function () {
        if(this.getResponseHeader('Content-Range'))
            this.maxRange = this.getResponseHeader('Content-Range').split('/')[1];
        if (this.readyState == 4) {
            if (this.status == 200 || this.status == 206) {
                if (request.responseType != 'arraybuffer') {
                    if (ele != null && a.currentFile >= (a.numFiles - 1) && a.fileBlocksComplete >= (numChunks - 1))
                        (!append) ? ele.innerHTML = this.responseText : ele.innerHTML += this.responseText;

                } else {
                    if(ele != null)
                        ele.innerHTML = '';
                }
                if (f != null && p != null)
                    f(p, this.response);
                else if (f != null && p == null)
                    f(this.response);
                return true;
            } else {
                if(filesUpload)
                    a.retryFromChunk(a.files, a.currentFile, a.fileBlocksComplete);
            }
        } else {
            if (filesUpload == false) {
                if (ele != null && gif != null)
                    if (ele.innerHTML != gif)
                        ele.innerHTML = gif;
            }
            return false;
        }
    }
    if (filesUpload == true)
        this.setUpProgressHandler(request, filesUpload, numChunks);
}

AJAX.prototype.setUpProgressHandler = function (request, filesUpload, numChunks) {
    var ele = this.ele,
        gif = this.gif,
        a = this;
    if (filesUpload === false) {
        request.addEventListener('progress', function () {
            if (ele != null && gif != null)
                ele.innerHTML = gif;
        });
    } else {
        request.upload.addEventListener('progress', function (event) {
            if (event.lengthComputable) {
                var blockPercentage = 100 / numChunks,
                    percentComplete = (event.loaded / event.total) * 100,
                    piecePercentOfTotal = percentComplete / numChunks,
                    totalPercent = (a.fileBlocksComplete * blockPercentage) + piecePercentOfTotal,
                    progressSt = document.getElementsByClassName('progress-num')[0],
                    progressBar = document.getElementsByClassName('progress-bar')[0];
                a.currentPercentage = totalPercent;
                if (progressBar == null || progressSt == null) {
                    var progressStr = '<div class="progress-num">' + a.currentPercentage.toFixed(2) + '%</div><progress class="progress-bar" value="' + a.currentPercentage + '" max="100" ></progress>';
                    newe = document.createElement('div')
                    newe.setAttribute('class', 'media-file');
                    ele.appendChild(newe);
                    newe.innerHTML = '<div class="progress" style="width: 160px; text-align: center;">' + progressStr + '</div>';
                } else {
                    progressSt.innerHTML = a.currentPercentage.toFixed(2) + '%';
                    progressBar.setAttribute('value', a.currentPercentage.toFixed(2));
                }
                if (a.currentPercentage >= 100) {
                    ele.removeChild(newe);
                }
            }
        });
    }

}

AJAX.prototype.sendRequest = function (request, data, postGet, start, end) {
    str = '';
    if (postGet == undefined && postGet == null) postGet = 'POST';
    if (this.params != null) {
        str = this.url + '?' + this.params;
    } else {
        str = this.url;
    }
    request.open(postGet, str, true);
    if(start != null && end != null)
        request.setRequestHeader('Range', 'bytes='+start+'-'+end);
    (data != undefined && data != null) ? request.send(data): request.send();
}

AJAX.prototype.request = function (append, postGet) {
    var request;
    request = this.createRequest();
    postGet = (!postGet) ? 'POST' : postGet;
    this.setUpResponseHandler(request, append, false);
    this.sendRequest(request, null, postGet);
}

AJAX.prototype.uploadFileRequest = function (files, append) {
    var request;
    this.files = files.files;
    this.numFiles = files.files.length;
    this.chunkFiles(0, files.files.length, files.files, 0);
}

AJAX.prototype.retryFromChunk = function (files, file, chunk) {
    this.chunkFiles(file, files.length, files, chunk);
}

AJAX.prototype.chunkFiles = function (startFile, endFile, files, startChunk) {
    for (i = startFile; i < endFile; i++) {
        var file = files[i];
        var chunkSize = 1048576 * 5;
        var split = (file.size > chunkSize) ? parseInt(file.size / chunkSize, 10) : 1;
        var inc = file.size / split;
        var t = startChunk * inc;
        var k = t + inc;
        var a = this;
        var _sendChunk = function (file, start, end) {
            a.sendFileChunk(file, start, end, function () {
                if (k < file.size) {
                    t = k;
                    k += inc;
                    a.fileBlocksComplete += 1;
                    _sendChunk(file, t, k);
                }
            }, split);
        }
        _sendChunk(file, t, k);
        if (i > 0) {
            this.currentFile += 1;
            this.fileBlocksComplete = 0;
        }
    }
}

AJAX.prototype.sendFileChunk = function (file, start, end, func, numChunks) {
    var fileData = new FormData();
    var f = (end >= file.size) ? file.slice(start) : file.slice(start, end);
    fileData.append('file[]', f, file.name);
    request = this.createRequest();
    this.setUpResponseHandler(request, true, true, function () {
        func();
    }, numChunks);
    this.sendRequest(request, fileData);
}

AJAX.prototype.uploadFormRequest = function (form, append) {
    var request;
    var formData = new FormData();
    console.log(form.length);
    for (i = 0; i < form.length; i++) {
        formData.append(form[i].name, form[i].value);
    }
    request = this.createRequest();
    this.setUpResponseHandler(request, append, false);
    this.sendRequest(request, formData);
}

AJAX.prototype.uploadJSONRequest = function (json, append, postGet) {
    var request;
    request = this.createRequest();
    postGet = (!postGet) ? 'POST' : postGet;

    this.setUpResponseHandler(request, append, false);
    this.sendRequest(request, JSON.stringify(json), postGet);
}

AJAX.prototype.requestFileAsArray = function (append, start, end) {
    var request = this.createRequest();
    request.responseType = 'arraybuffer';
    this.setUpResponseHandler(request, append, false);
    if(this.maxRange != null && end > this.maxRange)
        end = this.maxRange;
    this.sendRequest(request, null, "GET", start, end);
}