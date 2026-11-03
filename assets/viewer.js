// Visar PDF:en sida för sida med pdf.js (fungerar även på mobilen).
(function () {
    'use strict';

    var container = document.getElementById('pdf-viewer');
    var status = document.getElementById('viewer-status');
    if (!container || !window.pdfjsLib) return;

    pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/vendor/pdf.worker.min.js';

    pdfjsLib.getDocument({ url: container.dataset.src, withCredentials: true }).promise.then(async function (pdf) {
        status.textContent = pdf.numPages + (pdf.numPages === 1 ? ' sida' : ' sidor');
        var ratio = window.devicePixelRatio || 1;
        var width = Math.max(container.clientWidth - 24, 300);

        for (var n = 1; n <= pdf.numPages; n++) {
            var page = await pdf.getPage(n);
            var base = page.getViewport({ scale: 1 });
            var viewport = page.getViewport({ scale: (width / base.width) * ratio });
            var canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.setAttribute('aria-label', 'Sida ' + n);
            container.appendChild(canvas);
            await page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise;
        }
    }).catch(function (err) {
        console.error(err);
        status.textContent = 'Dokumentet kunde inte visas här. Använd "Öppna PDF".';
    });
})();
