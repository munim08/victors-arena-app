document.addEventListener('contextmenu', event => event.preventDefault());

document.addEventListener('DOMContentLoaded', () => {
    document.body.style.webkitUserSelect = 'none';
    document.body.style.mozUserSelect = 'none';
    document.body.style.msUserSelect = 'none';
    document.body.style.userSelect = 'none';
});

document.addEventListener('gesturestart', function (e) {
    e.preventDefault();
});