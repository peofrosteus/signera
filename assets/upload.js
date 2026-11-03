// Nytt dokument: dynamiska undertecknarrader och uppladdning med normalisering av PDF:en.
(function () {
    'use strict';

    var form = document.getElementById('document-form');
    if (!form) return;

    var signers = document.getElementById('signers');
    var errorsBox = document.getElementById('form-errors');
    var submitBtn = document.getElementById('submit-btn');
    var nextIndex = signers.children.length;

    document.getElementById('add-signer').addEventListener('click', function () {
        var row = signers.firstElementChild.cloneNode(true);
        row.querySelectorAll('input').forEach(function (input) {
            input.value = '';
            input.name = input.name.replace(/signers\[\d+\]/, 'signers[' + nextIndex + ']');
        });
        nextIndex++;
        signers.appendChild(row);
        row.querySelector('input').focus();
    });

    signers.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-signer');
        if (!btn) return;
        if (signers.children.length > 1) {
            btn.closest('.signer-row').remove();
        } else {
            btn.closest('.signer-row').querySelectorAll('input').forEach(function (i) { i.value = ''; });
        }
    });

    function showErrors(errors) {
        errorsBox.innerHTML = '';
        errors.forEach(function (msg) {
            var div = document.createElement('div');
            div.className = 'alert alert-error';
            div.textContent = msg;
            errorsBox.appendChild(div);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Sparar om PDF:en utan objektströmmar så att servern (FPDI) kan läsa den.
    async function normalize(file) {
        if (!window.PDFLib) return file;
        try {
            var doc = await PDFLib.PDFDocument.load(await file.arrayBuffer(), { updateMetadata: false });
            var bytes = await doc.save({ useObjectStreams: false });
            return new File([bytes], file.name, { type: 'application/pdf' });
        } catch (err) {
            // T.ex. krypterad PDF: skicka originalet, servern ger ett tydligt fel om den inte går att läsa.
            console.warn('Kunde inte normalisera PDF:en', err);
            return file;
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var file = form.elements.pdf.files[0];
        var maxBytes = parseInt(form.dataset.maxMb, 10) * 1024 * 1024;
        if (!file) return showErrors(['Välj en PDF-fil.']);
        if (file.size > maxBytes) return showErrors(['Filen är för stor (max ' + form.dataset.maxMb + ' MB).']);

        var label = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Förbereder…';
        try {
            var data = new FormData(form);
            data.set('pdf', await normalize(file), file.name);
            submitBtn.textContent = 'Skickar…';

            var res = await fetch(form.action, {
                method: 'POST',
                body: data,
                headers: { 'X-Requested-With': 'fetch' },
                credentials: 'same-origin'
            });
            var json = null;
            try { json = await res.json(); } catch (_) { /* inte JSON */ }

            if (res.ok && json && json.redirect) {
                window.location.href = json.redirect;
                return;
            }
            showErrors((json && json.errors) || ['Något gick fel (' + res.status + '). Försök igen.']);
        } catch (err) {
            showErrors(['Kunde inte skicka formuläret. Kontrollera din anslutning och försök igen.']);
        }
        submitBtn.disabled = false;
        submitBtn.textContent = label;
    });
})();
