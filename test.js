import React, { useState, useRef, useEffect } from 'react';

const ResizableTextContainer = () => {
  const [text, setText] = useState("It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English.");
  const containerRef = useRef(null);
  const textRef = useRef(null);

  useEffect(() => {
    const resizeObserver = new ResizeObserver(adjustFontSize);
    if (containerRef.current) {
      resizeObserver.observe(containerRef.current);
    }
    return () => resizeObserver.disconnect();
  }, []);

  const adjustFontSize = () => {
    if (!containerRef.current || !textRef.current) return;

    const containerWidth = containerRef.current.offsetWidth;
    const containerHeight = containerRef.current.offsetHeight;

    let fontSize = 1;
    textRef.current.style.fontSize = `${fontSize}px`;

    while (
      textRef.current.offsetWidth < containerWidth &&
      textRef.current.offsetHeight < containerHeight &&
      fontSize < 100
    ) {
      fontSize++;
      textRef.current.style.fontSize = `${fontSize}px`;
    }

    fontSize--;
    textRef.current.style.fontSize = `${fontSize}px`;
  };

  return (
    <div
      ref={containerRef}
      className="resize-container bg-green-200 border-2 border-green-500 overflow-hidden p-4"
      style={{
        resize: 'both',
        minWidth: '200px',
        minHeight: '100px',
        width: '400px',
        height: '200px',
      }}
    >
      <div ref={textRef} className="text-container">
        <h2 className="font-bold mb-2">Announcements</h2>
        <p>{text}</p>
        <p className="mt-2 text-sm">Posted By Carlo</p>
      </div>
    </div>
  );
};

export default ResizableTextContainer;