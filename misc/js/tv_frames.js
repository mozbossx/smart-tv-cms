const tvFrames = document.querySelectorAll('.tv-frame');
// const sliders = document.querySelectorAll('.scale-slider');

tvFrames.forEach((tvFrame, index) => {
    // let isDragging = false;
    // let startX, startY;

    // Function to fit the tv-frame inside its parent
    function fitFrameToParent() {
        const parent = tvFrame.parentElement;
        const parentRect = parent.getBoundingClientRect();

        // Reset any existing transformations and positioning
        tvFrame.style.transform = 'none';
        
        const frameRect = tvFrame.getBoundingClientRect();

        const scaleX = parentRect.width / frameRect.width;
        const scaleY = parentRect.height / frameRect.height;
        const scale = Math.min(scaleX, scaleY, 1); // Don't scale up if already smaller

        // Apply transformations
        tvFrame.style.transform = `scale(${scale})`;
        // sliders[index].value = scale;
    }

    // Fit frame on load
    fitFrameToParent();

    // Refit on window resize
    window.addEventListener('resize', fitFrameToParent);

    // Scale Slider
    // sliders[index].addEventListener('input', (e) => {
    //     const scale = e.target.value;
    //     const parent = tvFrame.parentElement;
    //     const parentRect = parent.getBoundingClientRect();
    //     const frameRect = tvFrame.getBoundingClientRect();

    //     // Center the frame
    //     const leftOffset = (parentRect.width - frameRect.width * scale) / 2;
    //     const topOffset = (parentRect.height - frameRect.height * scale) / 2;

    //     // Apply transformations
    //     tvFrame.style.transform = `translate(${leftOffset}px, ${topOffset}px) scale(${scale})`;
    // });

    // Drag to Pan (Mouse Events)
    // tvFrame.parentElement.addEventListener('mousedown', startDragging);
    // document.addEventListener('mousemove', drag);
    // document.addEventListener('mouseup', stopDragging);

    // // Drag to Pan (Touch Events)
    // tvFrame.parentElement.addEventListener('touchstart', startDragging);
    // document.addEventListener('touchmove', drag);
    // document.addEventListener('touchend', stopDragging);

    // function startDragging(e) {
    //     isDragging = true;
    //     if (e.type === 'mousedown') {
    //         startX = e.clientX - tvFrame.offsetLeft;
    //         startY = e.clientY - tvFrame.offsetTop;
    //     } else if (e.type === 'touchstart') {
    //         startX = e.touches[0].clientX - tvFrame.offsetLeft;
    //         startY = e.touches[0].clientY - tvFrame.offsetTop;
    //     }
    //     tvFrame.parentElement.style.cursor = 'grabbing';
    // }

    // function drag(e) {
    //     if (!isDragging) return;
    //     e.preventDefault();
    //     let clientX, clientY;
    //     if (e.type === 'mousemove') {
    //         clientX = e.clientX;
    //         clientY = e.clientY;
    //     } else if (e.type === 'touchmove') {
    //         clientX = e.touches[0].clientX;
    //         clientY = e.touches[0].clientY;
    //     }
    //     const x = clientX - startX;
    //     const y = clientY - startY;
    //     requestAnimationFrame(() => {
    //         tvFrame.style.left = `${x}px`;
    //         tvFrame.style.top = `${y}px`;
    //     });
    // }

    // function stopDragging() {
    //     isDragging = false;
    //     tvFrame.parentElement.style.cursor = 'grab';
    // }
});