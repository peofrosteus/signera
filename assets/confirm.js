// Bekräftelsedialog för formulär med data-confirm.
document.addEventListener('submit', function (e) {
    var message = e.target.dataset && e.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        e.preventDefault();
    }
});
