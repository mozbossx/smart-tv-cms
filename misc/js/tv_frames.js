const tvFrames = document.querySelectorAll('.tv-frame');
const scaleUpButtons = document.querySelectorAll('.scale-up');
const scaleDownButtons = document.querySelectorAll('.scale-down');

tvFrames.forEach((tvFrame, index) => {
    let scale = 1;
    let isDragging = false;
    let startX, startY;

    // Scale Up Button
    scaleUpButtons[index].addEventListener('click', () => {
        scale += 0.1;
        tvFrame.style.transform = `scale(${scale})`;
    });

    // Scale Down Button
    scaleDownButtons[index].addEventListener('click', () => {
        if (scale > 0.2) {  // Prevent scaling too small
            scale -= 0.1;
            tvFrame.style.transform = `scale(${scale})`;
        }
    });

    // Drag to Pan
    tvFrame.parentElement.addEventListener('mousedown', (e) => {
        isDragging = true;
        startX = e.clientX - tvFrame.offsetLeft;
        startY = e.clientY - tvFrame.offsetTop;
        tvFrame.parentElement.style.cursor = 'grabbing';
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        tvFrame.parentElement.style.cursor = 'grab';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.clientX - startX;
        const y = e.clientY - startY;
        requestAnimationFrame(() => {
            tvFrame.style.left = `${x}px`;
            tvFrame.style.top = `${y}px`;
        });
    });
});