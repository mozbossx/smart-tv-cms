document.addEventListener('DOMContentLoaded', function() {
    // font awesome icons version 6
    const iconGrid = document.getElementById('featureIconsGridList');
    const iconClasses = [
        // Solid Style Icons
        'fa-solid fa-address-book', 'fa-solid fa-address-card', 'fa-solid fa-align-center', 
        'fa-solid fa-align-justify', 'fa-solid fa-align-left', 'fa-solid fa-align-right', 
        'fa-solid fa-anchor', 'fa-solid fa-angles-down', 'fa-solid fa-angles-left', 
        'fa-solid fa-angles-right', 'fa-solid fa-angles-up', 'fa-solid fa-angle-down',
        'fa-solid fa-angle-left', 'fa-solid fa-angle-right', 'fa-solid fa-angle-up', 
        'fa-solid fa-archive', 'fa-solid fa-arrow-down', 'fa-solid fa-arrow-left', 
        'fa-solid fa-arrow-right', 'fa-solid fa-arrow-up', 'fa-solid fa-arrows-rotate', 
        'fa-solid fa-asterisk', 'fa-solid fa-at', 'fa-solid fa-award', 'fa-solid fa-ban',
        'fa-solid fa-bars', 'fa-solid fa-bell', 'fa-solid fa-book', 'fa-solid fa-bookmark', 
        'fa-solid fa-briefcase', 'fa-solid fa-bug', 'fa-solid fa-building', 
        'fa-solid fa-bullhorn', 'fa-solid fa-calendar', 'fa-solid fa-camera',
        'fa-solid fa-cart-shopping', 'fa-solid fa-chart-line', 'fa-solid fa-chart-pie', 
        'fa-solid fa-check', 'fa-solid fa-circle-check', 'fa-solid fa-circle', 
        'fa-solid fa-circle-info', 'fa-solid fa-circle-user', 'fa-solid fa-clipboard', 
        'fa-solid fa-clock', 'fa-solid fa-cloud', 'fa-solid fa-code', 'fa-solid fa-coffee',
        'fa-solid fa-comment', 'fa-solid fa-comments', 'fa-solid fa-compass', 
        'fa-solid fa-copy', 'fa-solid fa-credit-card', 'fa-solid fa-database', 
        'fa-solid fa-desktop', 'fa-solid fa-download', 'fa-solid fa-pen-to-square', 
        'fa-solid fa-envelope', 'fa-solid fa-eye', 'fa-solid fa-eye-slash', 
        'fa-solid fa-file', 'fa-solid fa-file-lines', 'fa-solid fa-filter',
        'fa-solid fa-flag', 'fa-solid fa-folder', 'fa-solid fa-folder-open', 
        'fa-solid fa-font', 'fa-solid fa-gear', 'fa-solid fa-globe', 
        'fa-solid fa-graduation-cap', 'fa-solid fa-grip', 'fa-solid fa-hashtag',
        'fa-solid fa-heart', 'fa-solid fa-house', 'fa-solid fa-image', 'fa-solid fa-inbox',
        'fa-solid fa-info', 'fa-solid fa-key', 'fa-solid fa-keyboard', 'fa-solid fa-laptop',
        'fa-solid fa-lightbulb', 'fa-solid fa-link', 'fa-solid fa-list', 'fa-solid fa-list-check',
        'fa-solid fa-location-dot', 'fa-solid fa-lock', 'fa-solid fa-lock-open',
        'fa-solid fa-magnifying-glass', 'fa-solid fa-magnifying-glass-plus',
        'fa-solid fa-map', 'fa-solid fa-message', 'fa-solid fa-microphone',
        'fa-solid fa-minus', 'fa-solid fa-mobile', 'fa-solid fa-money-bill',
        'fa-solid fa-music', 'fa-solid fa-newspaper', 'fa-solid fa-paperclip',
        'fa-solid fa-paper-plane', 'fa-solid fa-pause', 'fa-solid fa-pen',
        'fa-solid fa-pencil', 'fa-solid fa-phone', 'fa-solid fa-play', 'fa-solid fa-plus',
        'fa-solid fa-print', 'fa-solid fa-question', 'fa-solid fa-rectangle-list',
        'fa-solid fa-rotate', 'fa-solid fa-floppy-disk', 'fa-solid fa-share',
        'fa-solid fa-shield', 'fa-solid fa-shuffle', 'fa-solid fa-signal',
        'fa-solid fa-right-to-bracket', 'fa-solid fa-right-from-bracket',
        'fa-solid fa-sitemap', 'fa-solid fa-sliders', 'fa-solid fa-face-smile',
        'fa-solid fa-sort', 'fa-solid fa-square', 'fa-solid fa-star', 'fa-solid fa-stop',
        'fa-solid fa-tag', 'fa-solid fa-tags', 'fa-solid fa-list-check',
        'fa-solid fa-thumbs-down', 'fa-solid fa-thumbs-up', 'fa-solid fa-trash',
        'fa-solid fa-trophy', 'fa-solid fa-upload', 'fa-solid fa-user', 'fa-solid fa-users',
        'fa-solid fa-video', 'fa-solid fa-volume-high', 'fa-solid fa-wifi', 'fa-solid fa-xmark',
        
        // Regular Style Icons
        'fa-regular fa-address-book', 'fa-regular fa-address-card', 'fa-regular fa-bell', 
        'fa-regular fa-bookmark', 'fa-regular fa-calendar', 'fa-regular fa-chart-bar', 
        'fa-regular fa-circle', 'fa-regular fa-circle-check', 'fa-regular fa-circle-user', 
        'fa-regular fa-clipboard', 'fa-regular fa-clock', 'fa-regular fa-comment',
        'fa-regular fa-comments', 'fa-regular fa-copy', 'fa-regular fa-credit-card', 
        'fa-regular fa-envelope', 'fa-regular fa-eye', 'fa-regular fa-eye-slash', 
        'fa-regular fa-face-smile', 'fa-regular fa-file', 'fa-regular fa-file-lines',
        'fa-regular fa-folder', 'fa-regular fa-folder-open', 'fa-regular fa-heart',
        'fa-regular fa-image', 'fa-regular fa-keyboard', 'fa-regular fa-lightbulb',
        'fa-regular fa-message', 'fa-regular fa-newspaper', 'fa-regular fa-paper-plane',
        'fa-regular fa-rectangle-list', 'fa-regular fa-share-from-square',
        'fa-regular fa-square', 'fa-regular fa-square-check', 'fa-regular fa-star',
        'fa-regular fa-thumbs-down', 'fa-regular fa-thumbs-up', 'fa-regular fa-user',
        
        // Brands
        'fa-brands fa-android', 'fa-brands fa-apple', 'fa-brands fa-bluetooth', 
        'fa-brands fa-bootstrap', 'fa-brands fa-chrome', 'fa-brands fa-css3', 
        'fa-brands fa-discord', 'fa-brands fa-docker', 'fa-brands fa-dribbble',
        'fa-brands fa-edge', 'fa-brands fa-facebook', 'fa-brands fa-firefox', 
        'fa-brands fa-github', 'fa-brands fa-gitlab', 'fa-brands fa-google', 
        'fa-brands fa-html5', 'fa-brands fa-instagram', 'fa-brands fa-java', 
        'fa-brands fa-jira', 'fa-brands fa-js', 'fa-brands fa-linkedin', 
        'fa-brands fa-node', 'fa-brands fa-npm', 'fa-brands fa-php',
        'fa-brands fa-pinterest', 'fa-brands fa-python', 'fa-brands fa-react',
        'fa-brands fa-slack', 'fa-brands fa-spotify', 'fa-brands fa-stack-overflow',
        'fa-brands fa-telegram', 'fa-brands fa-tiktok', 'fa-brands fa-twitter',
        'fa-brands fa-ubuntu', 'fa-brands fa-vuejs', 'fa-brands fa-whatsapp',
        'fa-brands fa-windows', 'fa-brands fa-wordpress', 'fa-brands fa-youtube'
    ];

    const featureIconsModalButton = document.getElementById('featureIconsModalButton');
    const featureIconsModal = document.getElementById('featureIconsModal');
    const cancelFeatureIconsModal = document.getElementById('cancelFeatureIconsModal');

    iconClasses.forEach(iconClass => {
        const iconElement = document.createElement('i');
        iconElement.className = iconClass;
        iconElement.addEventListener('click', function() {
            document.getElementById('selected_icon').value = iconClass;
            featureIconsModal.style.display = 'none';
            featureIconsModalButton.innerHTML = `<i class="${iconClass}"></i>`;
        });
        iconGrid.appendChild(iconElement);
    });

    featureIconsModalButton.addEventListener('click', function() {
        featureIconsModal.style.display = 'flex';
    });

    cancelFeatureIconsModal.addEventListener('click', function() {
        featureIconsModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == featureIconsModal) {
            featureIconsModal.style.display = 'none';
        }
    });
});