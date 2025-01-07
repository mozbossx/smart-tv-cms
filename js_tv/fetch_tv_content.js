// fetch_tv_content.js
const Ws = new WebSocket('ws://10.0.0.39:8081');

// Function to get the containers based on type
const getContainerElements = (type) => {
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    const pageNumberContainer = document.getElementById(`${type}PageNumberContainer`);

    // Check if the containers are found
    if (!carouselContainer) {
        console.error(`Carousel container for ${type} not found from getContainerElements in fetch_tv_content.js.`);
    }
    if (!pageNumberContainer) {
        console.error(`Page number container for ${type} not found from getContainerElements in fetch_tv_content.js.`);
    }

    return {
        carouselContainer,
        pageNumberContainer
    };
};

let currentIndex = { 
    announcement: 0, 
    event: 0, 
    news: 0, 
    promaterial: 0,
    peo: 0, 
    so: 0,
    orgchart: 0
};

let contents = { 
    announcements: [], 
    events: [], 
    news: [], 
    promaterial: [],
    peo: [],
    so: [],
    orgcharts: []
};

let displayTimeIntervals = { 
    announcement: null, 
    event: null, 
    news: null,
    promaterial: null,
    peo: null,
    so: null,
    orgchart: null
};

// Function to format date to "MM DD YYYY"
function formatDateTime(dateTimeString) {
    const dateTime = new Date(dateTimeString);
    const dateOptions = {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    };
    const timeOptions = {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    };
    const formattedDate = new Intl.DateTimeFormat('en-US', dateOptions).format(dateTime);
    const formattedTime = new Intl.DateTimeFormat('en-US', timeOptions).format(dateTime);
    return `${formattedDate} (${formattedTime})`;
}

// Function to format time to "01:00pm"
const formatTime = (timeString) => {
    const time = new Date(`1970-01-01T${timeString}Z`);
    const options = { hour: '2-digit', minute: '2-digit' };
    return time.toLocaleTimeString('en-US', options).replace(/(:\d{2})$/, '').toLowerCase();
};

// Unified function to update UI
const updateUI = (data, type) => {
    const { carouselContainer, pageNumberContainer } = getContainerElements(type);
    const currentIndexKey = type;
    const contentsArray = contents[`${type}s`];

    // if (data.status === 'Pending' || data.status === 'Draft' || data.isArchived === 1 || !data[`${type}_id`]) {
    //     return;
    // }

    // if (data.status !== 'Approved' || data.isArchived === 1 || data.isTrashed === 1 || data.schedule_datetime !== null || !data[`${type}_id`]) {
    //     return;
    // }

    // Check status based on content type
    // Check status based on content type
    if (type === 'orgchart') {
        // No status check needed for orgchart
        if (!data[`${type}_id`]) {
            return;
        }
    } else if (type === 'peo' || type === 'so') {
        if (data.isArchived === 1 || data.isTrashed === 1 || !data[`${type}_id`]) {
            return;
        }
    } else {
        if (data.status !== 'Approved' || data.isArchived === 1 || data.isTrashed === 1 || 
            data.schedule_datetime !== null || !data[`${type}_id`]) {
            return;
        }
    }
    
    const formattedCreatedDateTime = type !== 'orgchart' ? formatDateTime(data.created_datetime) : '';
    const id = data[`${type}_id`]; 

    // const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const existingDiv = document.querySelector(`[data-${type}-id="${id}"]`);
    
    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}">` :
            isVideo ? `<video controls><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    // Special handling for orgchart type
    if (type === 'orgchart') {
        const containerDiv = document.createElement('div');
        containerDiv.dataset.orgchartId = id;
        containerDiv.classList.add('carousel-item');
        containerDiv.dataset.displayTime = data.display_time;
        
        // Store the chart data for later use
        containerDiv.chartData = data.chartData;

        const contentHTML = `
            <div class="content-container-con" id="orgChartParentContainer-${id}">
                <div id="orgChartContainerParent-${id}" class="orgchart-container">
                    <div id="orgChartContainer-${id}" class="orgchart-container-parent">
                        <div class="orgchart-content"></div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right">
                                <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                <span class="time-left">${data.display_time}s</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        containerDiv.innerHTML = contentHTML;

        if (existingDiv) {
            existingDiv.innerHTML = contentHTML;
            existingDiv.chartData = data.chartData;
            resetCountdown(existingDiv, data.display_time, currentIndexKey);
            // setupOrgChartResizeObserver(existingDiv.querySelector('.orgchart-container'));
        } else {
            contentsArray.push(containerDiv);
            carouselContainer.appendChild(containerDiv);

            if (contentsArray.length === 1) {
                containerDiv.classList.add('active');
                // Render the first chart immediately
                setTimeout(() => {
                    createOrgChart(id, data.chartData);
                    // setupOrgChartResizeObserver(containerDiv.querySelector('.orgchart-container'));
                }, 100);
            }
        }

        updatePageNumber(currentIndexKey);
        return;
    }

    let contentHTML = '';

    if (type === 'announcement') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <span class="main-message" style="word-break: break-word;">
                        ${data.announcement_body}
                    </span>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'event') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <span class="main-message" style="word-break: break-word;">
                        ${data.event_body}
                    </span>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'news') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <span class="main-message" style="word-break: break-word;">
                        ${data.news_body}
                    </span>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'promaterial') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px; max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">${mediaContent}</div>` : ''}
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'peo') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    <span class="main-message" style="word-break: break-word;">
                        <b>${data.peo_title}</b>
                    </span>
                    <span class="main-message" style="word-break: break-word;">
                        ${data.peo_description}
                    </span>
                    <span class="main-message" style="word-break: break-word;">
                        ${data.peo_subdescription}
                    </span>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'so') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <span class="main-message" style="word-break: break-word;">
                        <b>${data.so_title}</b>
                    </span>
                    <span class="main-message" style="word-break: break-word;">
                        ${data.so_description}
                    </span>
                    <span class="main-message" style="word-break: break-word;">
                        ${data.so_subdescription}
                    </span>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else {
        // Generic handler for new features
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <div class="main-message" style="word-break: break-word;">
                        ${Object.entries(data)
                            .filter(([key]) => !['id', 'created_datetime', 'display_time', 'status', 'expiration_datetime', 
                                'isArchived', 'isTrashed', 'tv_id', 'author_id', 'type', 
                                'department', 'user_type', 'category', 'evaluated_by', `${type}_author_id`, `${type}_id`,
                                'evaluated_message', 'author_name', 'schedule_datetime', 
                                'author_user_type', 'author_department', 'datetime_archived', 
                                'datetime_trashed', 'media_path'].includes(key))
                            .map(([key, value]) => `<p>${value}</p>`)
                            .join('')}
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    if (existingDiv) {
        const contentDiv = existingDiv.querySelector('.content-container-con');
        if (contentDiv) {
            contentDiv.innerHTML = contentHTML;
        }
        resetCountdown(existingDiv, data.display_time, currentIndexKey);
        updatePageNumber(currentIndexKey);

        if (type !== 'orgchart') {
            scaleContentContainer(type, id);
            setupContentResizeObserver(type, id);
        }
    } else {
        const containerDiv = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}_id`];
        containerDiv.classList.add('carousel-item');
        containerDiv.dataset.displayTime = data.display_time;

        const contentDiv = document.createElement('div');

        // containerDiv.setAttribute('data-announcement-id', data.announcement_id);

        containerDiv.style = `
            width: auto;
            margin-bottom: 5px;
            height: 100%;
            border-radius: 5px;
        `;

        contentDiv.style = `
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            text-align: justify;
            height: 100%;
            bottom: 0;
        `;

        contentDiv.innerHTML = contentHTML;
        containerDiv.appendChild(contentDiv);
        contentsArray.push(containerDiv);
        carouselContainer.appendChild(containerDiv);
        // carouselContainer.appendChild(pageNumberContainer);

        if (contentsArray.length === 1) {
            containerDiv.classList.add('active');
            updatePageNumber(currentIndexKey);
        } else {
            updatePageNumber(currentIndexKey);
        }

        setTimeout(() => {
            scaleContentContainer(type, id);
            setupContentResizeObserver(type, id);
        }, 100);
        // if (type === 'orgchart') {
        //     createOrgChart(data.orgchart_id, data.chartData);
        // }
    }
    // adjustFontSize(type);
};


// function adjustFontSize(type) {
//     const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    
//     if (!carouselContainer) {
//         console.warn(`${type}CarouselContainer not found`);
//         return;
//     }

//     const textContainers = carouselContainer.querySelectorAll('.main-message');
//     if (!textContainers.length) {
//         return; // No text containers found, exit quietly
//     }

//     textContainers.forEach((textContainer) => {
//         // Get container dimensions
//         const containerWidth = carouselContainer.offsetWidth;
//         const containerHeight = carouselContainer.offsetHeight;

//         // Reset styles to get proper measurements
//         textContainer.style.fontSize = '10px';
//         textContainer.style.lineHeight = '1.2';

//         // Incrementally adjust font size
//         let fontSize = 10;
//         const maxFontSize = 24; // Maximum font size
        
//         while (
//             fontSize < maxFontSize &&
//             textContainer.scrollHeight <= containerHeight * 0.9 && // Use 90% of container height
//             textContainer.scrollWidth <= containerWidth * 0.9 // Use 90% of container width
//         ) {
//             fontSize++;
//             textContainer.style.fontSize = `${fontSize}px`;
//         }

//         // Step back one size to ensure fit
//         fontSize = Math.max(10, fontSize - 1);
//         textContainer.style.fontSize = `${fontSize}px`;

//         // Adjust line height for readability
//         textContainer.style.lineHeight = '1.4';
//     });
// }

// Set up a MutationObserver to watch for changes in a specific carousel container
// function setupFontSizeObserver(type) {
//     const observer = new MutationObserver((mutations) => {
//         mutations.forEach((mutation) => {
//             if (mutation.type === 'childList' || mutation.type === 'characterData') {
//                 adjustFontSize(type);
//             }
//         });
//     });

//     const carouselContainer = document.getElementById(`${type}CarouselContainer`);
//     if (carouselContainer) {
//         observer.observe(carouselContainer, { childList: true, subtree: true, characterData: true });
//     }
// }

// Function to fetch org chart data
// const fetchOrgChartData = () => {
//     return fetch('database/fetch_orgchart.php')
//         .then(response => response.json())
//         .catch(error => {
//             console.error('Error fetching org chart data:', error);
//             return {};
//         });
// };

// Function to create org chart
const createOrgChart = (orgchartId, chartData) => {
    const containerId = `orgChartContainer-${orgchartId}`;
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Container not found for orgchart ${orgchartId}`);
        return;
    }

    // Clear any existing chart
    const contentDiv = container.querySelector('.orgchart-content');
    if (!contentDiv) {
        return;
    }

    contentDiv.innerHTML = '';

    // Calculate base node size based on container size
    const containerWidth = container.offsetWidth;
    const containerHeight = container.offsetHeight;
    const baseNodeWidth = Math.min(containerWidth * 0.15, 130); // 15% of container width, max 100px
    const baseNodeHeight = Math.min(containerHeight * 0.15, 130); // 15% of container height, max 100px

    // Update chart configuration with dynamic spacing
    const chartConfig = {
        chart: {
            container: `#${containerId}`,
            nodeAlign: "BOTTOM",
            // levelSeparation: containerHeight * 0.08, // Dynamic level separation
            // siblingSeparation: containerWidth * 0.03, // Dynamic sibling separation
            // subTeeSeparation: containerWidth * 0.03, // Dynamic subtree separation
            levelSeparation: 15, // Dynamic level separation
            siblingSeparation: 20, // Dynamic sibling separation
            subTeeSeparation: 20, // Dynamic subtree separation
            connectors: {
                type: 'step',
                style: {
                    'stroke-width': '1px'
                }
            },
            node: {
                HTMLclass: 'nodeExample1'
            },
            animation: {
                nodeAnimation: "easeOutBounce",
                nodeSpeed: 700,
                connectorsAnimation: "bounce",
                connectorsSpeed: 700
            }
        },
        nodeStructure: createNodeStructure(chartData.members, baseNodeWidth, baseNodeHeight)
    };

    try {
        // Create and store the Treant instance
        container.treant = new Treant(chartConfig);

        // Get the SVG element and add a unique ID
        // const svg = container.querySelector('svg');
        // if (svg) {
        //     // Add unique ID to the SVG
        //     svg.id = `orgchart-svg-${orgchartId}`;
            
        //     // Get the SVG dimensions
        //     const svgWidth = svg.getBoundingClientRect().width;
        //     const svgHeight = svg.getBoundingClientRect().height;
            
        //     console.log(`Org Chart SVG dimensions for ${orgchartId}:`, {
        //         svgId: svg.id,
        //         width: svgWidth,
        //         height: svgHeight
        //     });

        //     // Store dimensions as data attributes
        //     container.dataset.svgWidth = svgWidth;
        //     container.dataset.svgHeight = svgHeight;
        // }
        
        // Initial scaling
        requestAnimationFrame(() => {
            scaleOrgChart(container, orgchartId);
        });
    } catch (error) {
        console.error('Error creating org chart:', error);
        contentDiv.innerHTML = '<p style="text-align: center; color: #264B2B; font-size: 1.2em; padding: 20px;">Error loading organizational chart.</p>';
    }
};

// Helper function to create node structure with dynamic sizing
const createNodeStructure = (members, baseNodeWidth, baseNodeHeight) => {
    const nodeMap = new Map();
    
    // First pass: create all nodes with dynamic sizing
    members.forEach(member => {
        nodeMap.set(member.parent_node_id, {
            text: {
                name: member.name,
                title: member.title,
            },
            image: member.picture,
            innerHTML: `
                <div class="node-content" style="width: ${baseNodeWidth}px; ">
                    ${member.picture ? 
                        `<img src="${member.picture}" class="node-image" alt="${member.name}" style="width: ${baseNodeWidth * 0.4}px; height: ${baseNodeWidth * 0.4}px;">` : 
                        `<img src="images/profile_picture.png" class="node-image" alt="Default Profile" style="width: ${baseNodeWidth * 0.4}px; height: ${baseNodeWidth * 0.4}px;">`
                    }
                    <div class="node-details">
                        <div class="node-name" style="font-size: ${baseNodeWidth * 0.15}px">${member.name}</div>
                        <div class="node-title" style="font-size: ${baseNodeWidth * 0.13}px">${member.title}</div>
                    </div>
                </div>
            `,
            HTMLclass: "custom-node",
            children: []
        });
    });

    // Second pass: build hierarchy
    const rootNodes = [];
    members.forEach(member => {
        const node = nodeMap.get(member.parent_node_id);
        if (member.parent_id && member.parent_id !== 'NULL') {
            const parentNode = nodeMap.get(member.parent_id);
            if (parentNode) {
                parentNode.children.push(node);
            } else {
                rootNodes.push(node);
            }
        } else {
            rootNodes.push(node);
        }
    });

    return rootNodes.length === 1 ? rootNodes[0] : {
        text: { name: "Department" },
        HTMLclass: "hidden-node",
        children: rootNodes
    };
};

// Add this new function to handle scaling
function scaleOrgChart(container, orgchartId) {
    const contentConContainer = document.getElementById(`orgChartParentContainer-${orgchartId}`);
    const contentMain = document.getElementById(`orgChartContainer-${orgchartId}`);

    // console.log("contentConContainer: ", contentConContainer);
    // console.log("contentMain: ", contentMain);
    
    if (!contentConContainer || !contentMain) {
        console.warn(`Container elements not found for ${type} with id ${id}`);
        return;
    }

    // Reset zoom and transform to get true dimensions
    contentMain.style.zoom = '1';
    contentConContainer.style.transform = 'none';
    
    // Force a reflow
    void contentMain.offsetHeight;

    // Get dimensions
    const containerRect = contentConContainer.getBoundingClientRect();
    const contentRect = contentMain.getBoundingClientRect();

    // console.log("containerRect: ", containerRect);
    // console.log("contentRect: ", contentRect);

    // Calculate zoom level needed to fit the content
    let zoomLevel;
    if (containerRect.width > contentRect.width || containerRect.height > contentRect.height) {
        // Content is too big - scale down
        const scaleX = (containerRect.width / contentRect.width) / 3.5;
        const scaleY = (containerRect.height / contentRect.height) / 3.5;
        // console.log("scaleX: (scale-down)", scaleX);
        // console.log("scaleY: (scale-down)", scaleY);
        zoomLevel = Math.min(scaleX, scaleY) * 0.80; // Scale down by taking the smaller ratio, with 5% margin
        // console.log("zoomLevel (scale-down) of type ", type, " : ", zoomLevel);
    } 

    // Apply zoom with smooth transition
    contentMain.style.transition = 'zoom 0.3s ease-in-out';
    contentMain.style.zoom = zoomLevel;
    contentMain.style.display = 'block';

    // Ensure container doesn't show scrollbars
    contentConContainer.style.overflow = 'hidden';
}

function scaleContentContainer(type, id) {
    const contentConContainer = document.getElementById(`${type}ContentConContainer-${id}`);
    const contentMain = document.getElementById(`${type}ContentMain-${id}`);
    
    if (!contentConContainer || !contentMain) {
        console.warn(`Container elements not found for ${type} with id ${id}`);
        return;
    }

    // Reset zoom and transform to get true dimensions
    contentMain.style.zoom = '1';
    contentConContainer.style.transform = 'none';
    
    // Force a reflow
    void contentMain.offsetHeight;

    // Get dimensions
    const containerRect = contentConContainer.getBoundingClientRect();
    const contentRect = contentMain.getBoundingClientRect();

    // Calculate zoom level needed to fit the content
    let zoomLevel;
    if (containerRect.width > contentRect.width || containerRect.height > contentRect.height) {
        // Content is too big - scale down
        const scaleX = containerRect.width / contentRect.width;
        const scaleY = containerRect.height / contentRect.height;
        zoomLevel = Math.min(scaleX, scaleY) * 0.90; // Scale down by taking the smaller ratio, with 5% margin
        // console.log("zoomLevel (scale-down) of type ", type, " : ", zoomLevel);
    } else {
        // Content fits or is too small - keep at 1 or scale up slightly if too small
        const scaleX = containerRect.width / contentRect.width;
        const scaleY = containerRect.height / contentRect.height;
        const minScale = Math.min(scaleX, scaleY);
        zoomLevel = minScale > 1 ? Math.min(minScale, 1.2) : 1; // Cap the scale up at 1.2

        // console.log("zoomLevel (scale-up) of type ", type, " : ", zoomLevel);
    }

    // Apply zoom with smooth transition
    contentMain.style.transition = 'zoom 0.3s ease-in-out';
    contentMain.style.zoom = zoomLevel;

    // Ensure container doesn't show scrollbars
    // contentConContainer.style.overflow = 'hidden';
}

// Resize observer for content containers
function setupContentResizeObserver(type, id) {
    const contentConContainer = document.getElementById(`${type}ContentConContainer-${id}`);
    if (!contentConContainer) return;

    let resizeTimeout;
    const resizeObserver = new ResizeObserver((entries) => {
        for (const entry of entries) {
            // Clear any existing timeout
            if (resizeTimeout) {
                clearTimeout(resizeTimeout);
            }

            // Set a new timeout to debounce the resize event
            resizeTimeout = setTimeout(() => {
                const activeItem = entry.target.closest('.carousel-item');
                if (activeItem && activeItem.classList.contains('active')) {
                    scaleContentContainer(type, id);
                }
            }, 100);
        }
    });

    resizeObserver.observe(contentConContainer);
    return resizeObserver;
}

// Add resize observer to handle container resizing
function setupOrgChartResizeObserver(container) {
    let resizeTimeout;
    const resizeObserver = new ResizeObserver((entries) => {
        for (const entry of entries) {
            if (entry.target === container) {
                // Clear any existing timeout
                if (resizeTimeout) {
                    clearTimeout(resizeTimeout);
                }

                // Set a new timeout to debounce the resize event
                resizeTimeout = setTimeout(() => {
                    const activeItem = entry.target.closest('.carousel-item');
                    if (activeItem && activeItem.classList.contains('active')) {
                        const chartData = activeItem.chartData;
                        if (chartData) {
                            // First try to scale the existing chart
                            const existingChart = container.querySelector('.Treant');
                            if (existingChart) {
                                scaleOrgChart(container);
                            }

                            // If scaling doesn't provide good results, recreate the chart
                            const containerRect = container.getBoundingClientRect();
                            const chartRect = existingChart?.getBoundingClientRect();
                            if (!chartRect || 
                                chartRect.width > containerRect.width * 1.5 || 
                                chartRect.height > containerRect.height * 1.5) {
                                createOrgChart(activeItem.dataset.orgchartId, chartData);
                            }
                        }
                    }
                }, 100); // Small delay for performance
            }
        }
    });

    resizeObserver.observe(container);
    return resizeObserver;
}

const renderVisibleOrgChart = () => {
    const activeItem = document.querySelector('.carousel-item.active');
    if (activeItem) {
        const orgChartContainer = activeItem.querySelector('.orgchart-container-parent');
        if (orgChartContainer && orgChartContainer.chartConfig) {
            new Treant(orgChartContainer.chartConfig);
            // Remove the config after rendering to avoid re-rendering
            delete orgChartContainer.chartConfig;
        }
    }
};

// Function to initialize org charts
// const initializeOrgCharts = () => {
//     fetchOrgChartData().then(groupedData => {
//         Object.keys(groupedData).forEach(orgchartId => {
//             const containerId = `orgChartContainer-${orgchartId}`;
//             const containerDiv = document.createElement('div');
//             containerDiv.id = containerId;
//             containerDiv.style.width = '100%';
//             containerDiv.style.height = '100%';
//             document.getElementById('orgchartCarouselContainer').appendChild(containerDiv);

//             createOrgChart(groupedData[orgchartId], containerId);
//         });
//     });
// };

// Function to reset countdown for each content
const resetCountdown = (div, newDisplayTime, type) => {
    const timeLeftElement = div.querySelector('.time-left');
    if (timeLeftElement) {
        timeLeftElement.textContent = `${newDisplayTime}s`;
        setCarouselInterval(newDisplayTime, type);
    }
};

// Function to update page number
const updatePageNumber = (type) => {
    const { pageNumberContainer } = getContainerElements(type); // Get the page number container dynamically
    const currentIndexKey = type;
    const contentsArray = contents[`${type}s`];

    if (contentsArray.length > 0) {
        pageNumberContainer.textContent = `${currentIndex[currentIndexKey] + 1} of ${contentsArray.length}`;
    } else {
        pageNumberContainer.textContent = '0 of 0';
    }
};

// Function to show next content
const showNextContent = (type) => {
    const currentIndexKey = type;
    const contentsArray = contents[type + 's'];

    if (contentsArray.length > 0) {
        // Remove active class from the current item
        if (contentsArray[currentIndex[currentIndexKey]]) {
            contentsArray[currentIndex[currentIndexKey]].classList.remove('active');
        }

        // Increment the index and wrap around if necessary
        currentIndex[currentIndexKey] = (currentIndex[currentIndexKey] + 1) % contentsArray.length;

        // Add active class to the new current item
        if (contentsArray[currentIndex[currentIndexKey]]) {
            contentsArray[currentIndex[currentIndexKey]].classList.add('active');
            updatePageNumber(currentIndexKey);
            resetDisplayTime(type);

            // For orgchart, ensure proper rendering after transition
            if (type === 'orgchart') {
                // Clear the previous chart
                const currentChart = contentsArray[currentIndex[currentIndexKey]];
                const chartContainer = currentChart.querySelector('.orgchart-content');
                if (chartContainer) {
                    chartContainer.innerHTML = '';
                }
                
                // Re-render the chart with a delay to ensure DOM is ready
                setTimeout(() => {
                    if (currentChart.chartData) {
                        createOrgChart(currentChart.dataset.orgchartId, currentChart.chartData);
                    }
                }, 100);
            }
        }
    } else {
        clearInterval(displayTimeIntervals[currentIndexKey]);
    }
};

// Function to reset display time
const resetDisplayTime = (type) => {
    // // Special handling for orgchart
    // if (type === 'orgchart') {
    //     const currentContent = contents[type + 's'][currentIndex[type]];
    //     if (!currentContent) {
    //         console.warn(`No orgchart content found at index ${currentIndex[type]}`);
    //         return;
    //     }

    //     // Find the display time element within the orgchart structure
    //     const displayTimeElement = currentContent.querySelector('.content-container-con .time-left');
    //     if (!displayTimeElement) {
    //         console.warn('Display time element not found in orgchart');
    //         return;
    //     }

    //     const originalDisplayTime = currentContent.dataset.displayTime;
    //     if (!originalDisplayTime) {
    //         console.warn('No display time set for orgchart');
    //         return;
    //     }

    //     let remainingTime = parseInt(originalDisplayTime, 10);
    //     displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;

    //     // Clear existing interval
    //     if (displayTimeIntervals[type]) {
    //         clearInterval(displayTimeIntervals[type]);
    //     }

    //     // Set new interval
    //     displayTimeIntervals[type] = setInterval(() => {
    //         remainingTime -= 1;
    //         if (displayTimeElement) {
    //             displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;
    //         }
    //         if (remainingTime < 0) {
    //             clearInterval(displayTimeIntervals[type]);
    //             showNextContent(type);
    //         }
    //     }, 1000);

    //     return; // Exit early for orgchart
    // }
    
    const currentContent = contents[`${type}s`][currentIndex[type]];
    // const currentContent = type === 'announcement' ? contents.announcements[currentIndex.announcement] : contents.events[currentIndex.event];
    const displayTimeElement = currentContent.querySelector('.display-time');
    const originalDisplayTime = currentContent.dataset.displayTime;

    if (displayTimeElement && originalDisplayTime) {
        let remainingTime = parseInt(originalDisplayTime, 10);
        displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;

        if (displayTimeIntervals[type]) {
            clearInterval(displayTimeIntervals[type]);
        }

        displayTimeIntervals[type] = setInterval(() => {
            remainingTime -= 1;
            displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;
            if (remainingTime < 0) {
                clearInterval(displayTimeIntervals[type]);
                showNextContent(type);
            }
        }, 1000);
    }
};

// Function to set carousel interval
const setCarouselInterval = (displayTime, type) => {
    if (displayTimeIntervals[type]) {
        clearInterval(displayTimeIntervals[type]);
    }
    let timeLeft = displayTime;

    displayTimeIntervals[type] = setInterval(() => {
        const displayTimeElement = contents[`${type}s`][currentIndex[type]].querySelector('.time-left');
        // const displayTimeElement = type === 'announcement' ? contents.announcements[currentIndex.announcement].querySelector('.time-left') : contents.events[currentIndex.event].querySelector('.time-left');
        if (timeLeft > 0) {
            timeLeft -= 1;
            displayTimeElement.textContent = `${timeLeft}s`;
        } else {
            clearInterval(displayTimeIntervals[type]);
            showNextContent(type);
        }
    }, 1000);
};

const fetchSmartTVDetails = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    if (tvId) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvBrand = data.tv_brand;
                const tvDepartment = data.tv_department;
                updateTvDetails(tvName, tvBrand, tvDepartment, tvId);
            })
            .catch(error => console.error('Error fetching TV name:', error));
    } else {
        console.error('TV ID not found in URL');
    }
};

const updateTvDetails = (tvName, tvBrand, tvDepartment, tvId) => {
    const tvNameElement = document.querySelector('.tv-name');
    if (tvNameElement) {
        tvNameElement.textContent = tvName;
    }
    const tvBrandElement = document.querySelector('.tv-brand');
    if (tvBrandElement) {
        tvBrandElement.textContent = tvBrand;
    }
    const tvDepartmentElement = document.getElementById(`tvDepartment_${tvId}`);
    if (tvDepartmentElement) {
        tvDepartmentElement.textContent = tvDepartment;
    }
};

const startCarousel = (type) => {
    // Check specifically for orgchart type
    if (type === 'orgchart') {
        if (contents[type + 's'].length > 0) {
            if (contents[type + 's'][currentIndex[type]]) {
                contents[type + 's'][currentIndex[type]].classList.add('active');
            }
            resetDisplayTime(type);
            renderVisibleOrgChart(); // Ensure the orgchart is rendered
        } else {
            console.error("No orgchart content available");
            displayNoMessage(type);
        }
        return;
    }

    if (contents[type + 's'].length > 0) {
        // Remove active class from all contents
        contents[type + 's'].forEach(content => content.classList.remove('active'));

        // Set the current content as active
        if (contents[type + 's'][currentIndex[type]]) { // Check if the index is valid
            contents[type + 's'][currentIndex[type]].classList.add('active');
            resetDisplayTime(type);
        } else {
            console.error("Invalid current content index.");
        }
    } else {
        console.error("Cannot start carousel: No valid content available.", type);
    }
}

// Function to display a message when no content is available
const displayNoMessage = (type) => {
    const { carouselContainer } = getContainerElements(type);
    carouselContainer.innerHTML = `<p style="text-align: center; font-size: 1.2em; margin-top: auto;">No content available</p>`; // Display no content message
};

// Function to fetch and update contents
const fetchAndUpdateContents = (type) => {
    const previousIndex = currentIndex[type]; // Save the current index
    contents[type + 's'] = [];
    const { carouselContainer, pageNumberContainer } = getContainerElements(type); // Get the containers dynamically
    carouselContainer.innerHTML = ''; // Clear the container
    pageNumberContainer.innerHTML = ''; // Clear the container

    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    // List of predefined content types
    const predefinedTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so', 'orgchart'];

    if (tvId && predefinedTypes.includes(type)) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvDepartment = data.tv_department;
                if (type === 'orgchart') {
                    fetch(`database/fetch_orgchart.php?tvId=${tvId}`)
                        .then(response => response.json())
                        .then(data => {
                            // console.log("Fetched orgchart data:", data); // Debug log
                            Object.entries(data).forEach(([orgchartId, orgchartData]) => {
                                // console.log(`Processing orgchart ${orgchartId}:`, orgchartData); // Debug log
                                updateUI({
                                    orgchart_id: orgchartId,
                                    chartData: orgchartData,
                                    display_time: orgchartData.display_time
                                }, 'orgchart');
                            });
                            if (Object.keys(data).length > 0) {
                                // currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1); // Starts at the first item
                                currentIndex['orgchart'] = Math.min(currentIndex['orgchart'], contents['orgcharts'].length - 1); // Starts at the first item;
                                startCarousel('orgchart');
                            } else {
                                displayNoMessage('orgchart');
                            }
                        })
                        .catch(error => console.error('Error fetching orgcharts:', error));
                } else if (type !== 'orgchart') {
                    fetch(`database/fetch_${type}.php`)
                    .then(response => response.json())
                    .then(data => {
                        if (type === 'peo' || type === 'so') {
                            const filteredData = data.filter(item =>
                                item.tv_id === parseInt(tvId, 10) &&
                                item.isArchived === 0 &&
                                item.isTrashed === 0
                            );
                            filteredData.forEach(item => updateUI(item, type));
                            if (contents[type + 's'].length > 0) {
                                // Set the current index to 0 to start from the first item
                                currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1); // Starts at the first item
                                startCarousel(type);
                            } else {
                                displayNoMessage(type); // Call displayNoMessage if no content
                            }
                        } else {
                            const filteredData = data.filter(item =>
                                item.status === 'Approved' &&
                                item.tv_id === parseInt(tvId, 10) &&
                                item.isArchived === 0 &&
                                item.isTrashed === 0 &&
                                item.schedule_datetime === null
                            );
                            filteredData.forEach(item => updateUI(item, type));
                            if (contents[type + 's'].length > 0) {
                                // Set the current index to 0 to start from the first item
                                currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1); // Starts at the first item
                                startCarousel(type);
                            } else {
                                displayNoMessage(type); // Call displayNoMessage if no content
                            }
                        }
                        // adjustFontSize(type);
                    })
                    .catch(error => console.error(`Error fetching ${type}s:`, error));
                }
            })
            .catch(error => console.error('Error fetching TV data:', error));
    } else if (tvId && !predefinedTypes.includes(type)) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvDepartment = data.tv_department;
                // Use fetch_content.php for new features
                fetch(`database/fetch_content.php?type=${type}&tvId=${tvId}`)
                    .then(response => response.json())
                    .then(data => {
                        // console.log(`Fetched ${type} data:`, data);
                        const { carouselContainer } = getContainerElements(type);
                        if (carouselContainer) {
                            carouselContainer.innerHTML = '';
                        }

                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(item => {
                                if (item.tv_id === parseInt(tvId, 10)) {
                                    updateUI(item, type);
                                }
                            });
                        } else if (data && typeof data === 'object' && data.tv_id === parseInt(tvId, 10)) {
                            updateUI(data, type);
                        }

                        // Start carousel if there's content
                        if (contents[`${type}s`].length > 0) {
                            // currentIndex[type] = 0;
                            currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1); // Starts at the first item
                            // showNextContent(type); // Initialize first content
                            resetDisplayTime(type); // Start the countdown timer
                        } else {
                            displayNoMessage(type);
                        }
                    })
                    .catch(error => console.error(`Error fetching new feature ${type}:`, error));
            })
            .catch(error => console.error('Error fetching TV data:', error));
    } else {
        console.error('TV ID not found in URL');
    }
};

Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);
    if (data.action === 'delete_content' || data.action === 'archive_content' || data.action === 'trash_content') {
        const type = data.type;
        const id = data[`${type}_id`]; 

        if (type == 'orgchart') {
            // Refresh TV since there's an issue for the orgchart when posting it
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                location.reload();
            }
        } else {
            const removeContent = (type, id) => {
                const contentDiv = document.querySelector(`[data-${type}-id="${id}"]`);
                if (contentDiv) {
                    const indexToRemove = contents[`${type}s`].findIndex(item => item.dataset[`${type}Id`] === id);
                    if (indexToRemove !== 0) {
                        contents[`${type}s`].splice(indexToRemove, 1); // Remove from the array
                        currentIndex[type] = Math.max(0, currentIndex[type] - 1); // Adjust the current index
                    }
                    contentDiv.remove(); // Remove from DOM
                    fetchAndUpdateContents(type);
                }
            };
    
            if (data.success) {
                removeContent(type, id); // Call the helper function
            } 
        } 
    } else if (data.action === 'restore_content' || data.action === 'restore_content_and_update_expiration') {
        if (data.type === 'announcement') {
            fetchAndUpdateContents('announcement');
        } else if (data.type === 'event') {
            fetchAndUpdateContents('event');
        } else if (data.type === 'news') {
            fetchAndUpdateContents('news');
        } else if (data.type === 'promaterial') {
            fetchAndUpdateContents('promaterial');
        } else if (data.type === 'peo') {
            fetchAndUpdateContents('peo');
        } else if (data.type === 'so') {
            fetchAndUpdateContents('so');
        }
    } else if (data.action === 'update_content') {
        if (data.type === 'announcement') {
            fetchAndUpdateContents('announcement');
        } else if (data.type === 'event') {
            fetchAndUpdateContents('event');
        } else if (data.type === 'news') {
            fetchAndUpdateContents('news');
        } else if (data.type === 'promaterial') {
            fetchAndUpdateContents('promaterial');
        } else if (data.type === 'peo') {
            fetchAndUpdateContents('peo');
        } else if (data.type === 'so') {
            fetchAndUpdateContents('so');
        } else if (data.type === 'orgchart') {
            console.log("Received new orgchart data:", data);
            // Refresh TV since there's an issue for the orgchart when posting it
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            console.log("TV IDS of orgchart: ", data.tv_ids);
            console.log("TV ID of orgchart: ", data.tv_id);

            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10) || parseInt(tvId, 10) === parseInt(data.tv_ids, 10)) {
                location.reload();
            }
        } else {
            // For new features 
            const type = data.type;
            fetchAndUpdateContents(type);
        }
    } else if (data.action === 'post_content' || data.action === 'post_now_content') {
        if (data.type === 'announcement') {
            fetchAndUpdateContents('announcement');
        } else if (data.type === 'event') {
            fetchAndUpdateContents('event');
        } else if (data.type === 'news') {
            fetchAndUpdateContents('news');
        } else if (data.type === 'promaterial') {
            fetchAndUpdateContents('promaterial');
        } else if (data.type === 'peo') {
            fetchAndUpdateContents('peo');
        } else if (data.type === 'so') {
            fetchAndUpdateContents('so');
        } else if (data.type === 'orgchart') {
            console.log("Received new orgchart data:", data);
            // Refresh TV since there's an issue for the orgchart when posting it
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            console.log("TV IDS of orgchart: ", data.tv_ids);
            console.log("TV ID of orgchart: ", data.tv_id);

            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10) || parseInt(tvId, 10) === parseInt(data.tv_ids, 10)) {
                location.reload();
            }
        } else {
            // For new features 
            const type = data.type;
            fetchAndUpdateContents(type);
        }
    } else if (data.action === 'approve_post') {
        const type = data.content_type || data.type;
        
        // Fetch and update the content immediately
        setTimeout(() => {
            fetchAndUpdateContents(type);
        }, 500);
    } else if (data.action === 'edit_smart_tv') {
        fetchSmartTVDetails();
        // location.reload();
    } else if (data.action === 'update_container_positions'|| data.action === 'delete_smart_tv' || data.action === 'show_hide_content' || data.action === 'update_background_type' || data.action === 'update_background_image' || data.action === 'update_department_logo' || data.action === 'update_topbar_position' || data.action === 'delete_feature') {
        const urlParams = new URLSearchParams(window.location.search);
        const tvId = urlParams.get('tvId');
        if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
            location.reload();
        }
    } else if (data.action === 'update_background_color') {
        if (data.success) {
            const backgroundElement = document.getElementById('tvBackgroundColor');
            backgroundElement.style.backgroundColor = data.background_hex_color;
        }
    } else if (data.action === 'update_container_colors') {
        if (data.success) {
            const containers = data.containers;
            containers.forEach(container => {
                const containerElement = document.querySelector(`[data-container-id="${container.container_id}"]`);
                if (containerElement) {
                    containerElement.style.backgroundColor = container.parent_background_color;
                    const titleElement = containerElement.querySelector('.content-title');
                    if (titleElement) {
                        titleElement.style.color = container.parent_font_color;
                        titleElement.style.fontFamily = container.parent_font_family;
                        titleElement.style.fontStyle = container.parent_font_style;
                    }

                    // Update the carousel container styles
                    const { carouselContainer } = getContainerElements(container.type); // Get the carousel container
                    if (carouselContainer) {
                        carouselContainer.style.backgroundColor = container.child_background_color; // Change the background color
                        carouselContainer.style.color = container.child_font_color; // Change the text color
                        carouselContainer.style.fontFamily = container.child_font_family; // Change the font family
                        carouselContainer.style.fontStyle = container.child_font_style; // Change the font style
                    } else {
                        console.error(`Carousel container not found for type: ${container.type}`);
                    }
                    
                    // Update the page number container styles
                    const { pageNumberContainer } = getContainerElements(container.type);
                    if (pageNumberContainer) {
                        pageNumberContainer.style.color = container.parent_font_color; // Change the color
                        pageNumberContainer.style.fontFamily = container.parent_font_family; // Change the font family
                        pageNumberContainer.style.fontStyle = container.parent_font_style; // Change the font style
                    } else {
                        console.error(`Page number container not found for type: ${container.type}`);
                    }
                } else {
                    // console.error(`Container not found for ID: ${container.container_id}`);
                }
            });
        }
    } else if (data.action === 'update_topbar_color') {
        if (data.success) {
            const topbarElement = document.getElementById('topbar');
            if (topbarElement) {
                topbarElement.style.backgroundColor = data.topbar_hex_color;
                topbarElement.querySelector('.tv-name').style.color = data.topbar_tvname_font_color;
                topbarElement.querySelector('.tv-name').style.fontStyle = data.topbar_tvname_font_style;
                topbarElement.querySelector('.tv-name').style.fontFamily = data.topbar_tvname_font_family;

                topbarElement.querySelector('.tv-id').style.color = data.topbar_tvid_font_color;
                topbarElement.querySelector('.tv-id').style.fontStyle = data.topbar_tvid_font_style;
                topbarElement.querySelector('.tv-id').style.fontFamily = data.topbar_tvid_font_family;

                topbarElement.querySelector('.time').style.color = data.topbar_time_font_color;
                topbarElement.querySelector('.time').style.fontStyle = data.topbar_time_font_style;
                topbarElement.querySelector('.time').style.fontFamily = data.topbar_time_font_family;

                topbarElement.querySelector('.date').style.color = data.topbar_date_font_color;
                topbarElement.querySelector('.date').style.fontStyle = data.topbar_date_font_style;
                topbarElement.querySelector('.date').style.fontFamily = data.topbar_date_font_family;
            }
        }
    } else if (data.action === 'save_layout') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            const message = JSON.stringify({ action: 'load_layout', tv_id: tvId });
            Ws.send(message);
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Get all content types from the containers
    const containers = document.querySelectorAll('[id$="CarouselContainer"]');
    containers.forEach(container => {
        const type = container.id.replace('CarouselContainer', '');
        // console.log(type);
        // Initialize arrays for new types if they don't exist
        if (!currentIndex[type]) {
            currentIndex[type] = 0;
        }
        if (!contents[`${type}s`]) {
            contents[`${type}s`] = [];
        }
        if (!displayTimeIntervals[type]) {
            displayTimeIntervals[type] = null;
        }
        // Fetch content for each type
        fetchAndUpdateContents(type);
    });
    // initializeOrgCharts();
    // const types = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so', 'orgchart'];
    // types.forEach(type => {
    //     adjustFontSize(type);
    //     setupFontSizeObserver(type);
    // });
    fetchSmartTVDetails();
});