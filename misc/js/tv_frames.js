const tvFrames = document.querySelectorAll('.tv-frame');

tvFrames.forEach((tvFrame, index) => {
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
    }

    // Fit frame on load
    fitFrameToParent();
});