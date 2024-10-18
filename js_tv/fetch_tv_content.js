// fetch_tv_content.js
const Ws = new WebSocket('ws://192.168.1.30:8081');

// Function to get the containers based on type
const getContainerElements = (type) => {
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    const pageNumberContainer = document.getElementById(`${type}PageNumberContainer`);

    // Check if the containers are found
    if (!carouselContainer) {
        console.error(`Carousel container for ${type} not found.`);
    }
    if (!pageNumberContainer) {
        console.error(`Page number container for ${type} not found.`);
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
    orgchart: []
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
const formatDate = (dateString) => {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: '2-digit' };
    return date.toLocaleDateString('en-US', options);
};

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

    if (data.status === 'Pending' || data.status === 'Draft' || data.isCancelled === 1 || !data[`${type}_id`]) {
        return;
    }

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    const id = data[`${type}_id`]; // Assuming the ID of the item to delete/archive is sent in the message

    // const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const existingDiv = document.querySelector(`[data-${type}-id="${id}"]`);
    let mediaContent = '';

    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}">` :
            isVideo ? `<video controls><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    let contentHTML = '';

    if (type === 'announcement') {
        contentHTML = `
            <div class="content-container-con">
                <div class="content-main">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <p class="main-message" style="word-break: break-word;">${data.announcement_body}</p>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
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
            <div class="content-container-con">
                <div class="content-main">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <p class="main-message" style="word-break: break-word;">${data.event_body}</p>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
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
            <div class="content-container-con">
                <div class="content-main">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <p class="main-message" style="word-break: break-word;">${data.news_body}</p>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
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
            <div class="content-container-con">
                <div class="content-main">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
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
            <div class="content-container-con">
                <div class="content-main">
                    <p class="main-message" style="word-break: break-word;"><b>${data.peo_title}</b></p>
                    <p class="main-message" style="word-break: break-word;">${data.peo_description}</p>
                    <p class="main-message" style="word-break: break-word;">${data.peo_subdescription}</p>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
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
            <div class="content-container-con">
                <div class="content-main">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <p class="main-message" style="word-break: break-word;"><b>${data.so_title}</b></p>
                    <p class="main-message" style="word-break: break-word;">${data.so_description}</p>
                    <p class="main-message" style="word-break: break-word;">${data.so_subdescription}</p>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'orgchart') {
        contentHTML = `
            <div class="content-container-con">
                <div id="orgChartContainer-${data.orgchart_id}" class="orgchart-container" style="width: 100%; height: 100%;"></div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
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
        if (type === 'orgchart') {
            createOrgChart(data.orgchart_id, data.chartData);
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
            padding: 10px;
            text-align: justify;
            height: calc(100% - 20px);
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
        if (type === 'orgchart') {
            createOrgChart(data.orgchart_id, data.chartData);
        }
    }
    // Ensure the orgchart is scaled after it's added to the DOM
    if (type === 'orgchart') {
        setTimeout(() => {
            const container = document.getElementById(`orgChartContainer-${data.orgchart_id}`);
            if (container) {
                scaleOrgChart(container);
            }
        }, 0);
    }
    adjustFontSize(type);
};

function adjustFontSize(type) {
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    if (carouselContainer) {       
        const textContainers = carouselContainer.querySelectorAll('.content-main');
        textContainers.forEach((textContainer, index) => {           
            const containerWidth = carouselContainer.offsetWidth;
            const containerHeight = carouselContainer.offsetHeight;

            let fontSize = 1;
            textContainer.style.fontSize = `${fontSize}px`;

            while (
                textContainer.scrollWidth <= containerWidth &&
                textContainer.scrollHeight <= containerHeight &&
                fontSize < 100
            ) {
                fontSize++;
                textContainer.style.fontSize = `${fontSize}px`;
            }

            fontSize -= 2; // Reduce by 2 to ensure text fits within container
            textContainer.style.fontSize = `${fontSize}px`;
        });
    } else {
        console.log(`${type}CarouselContainer not found`);
    }
}

// Set up a MutationObserver to watch for changes in a specific carousel container
function setupFontSizeObserver(type) {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                adjustFontSize(type);
            }
        });
    });

    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    if (carouselContainer) {
        observer.observe(carouselContainer, { childList: true, subtree: true, characterData: true });
    }
}

// Function to fetch org chart data
const fetchOrgChartData = () => {
    return fetch('database/fetch_orgchart.php')
        .then(response => response.json())
        .catch(error => {
            console.error('Error fetching org chart data:', error);
            return {};
        });
};

// Function to create org chart
const createOrgChart = (orgchartId, chartData) => {
    const containerId = `orgChartContainer-${orgchartId}`;
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Container not found for orgchart ${orgchartId}`);
        return;
    }

    // Clear any existing chart
    container.innerHTML = '<div class="orgchart-content"></div>';
    const contentDiv = container.querySelector('.orgchart-content');

    const chartConfig = {
        chart: {
            container: `#${containerId}`,
            nodeAlign: "BOTTOM",
            connectors: {
                type: 'step'
            },
            node: {
                HTMLclass: 'nodeExample1'
            }
        },
        nodeStructure: buildOrgChartNodeStructure(chartData.members)
    };

    // new Treant(chartConfig);
    container.chartConfig = chartConfig;
    // After the chart is created, scale it to fit
    scaleOrgChart(container);
};

const scaleOrgChart = (container) => {
    const content = container.querySelector('.orgchart-content');
    if (!content) return;

    const chart = content.firstChild;
    if (!chart) return;

    const containerWidth = container.offsetWidth;
    const containerHeight = container.offsetHeight;
    const chartWidth = chart.offsetWidth;
    const chartHeight = chart.offsetHeight;

    const scaleX = containerWidth / chartWidth;
    const scaleY = containerHeight / chartHeight;
    const scale = Math.min(scaleX, scaleY, 1); // Don't scale up, only down

    content.style.transform = `scale(${scale})`;
};

const renderVisibleOrgChart = () => {
    const activeItem = document.querySelector('.carousel-item.active');
    if (activeItem) {
        const orgChartContainer = activeItem.querySelector('.orgchart-container');
        if (orgChartContainer && orgChartContainer.chartConfig) {
            new Treant(orgChartContainer.chartConfig);
            // Remove the config after rendering to avoid re-rendering
            delete orgChartContainer.chartConfig;
        }
    }
};

// Function to build org chart node structure
const buildOrgChartNodeStructure = (members) => {
    const nodeMap = new Map();
    const rootNodes = [];

    // First pass: create all nodes
    members.forEach(member => {
        nodeMap.set(member.parent_node_id, {
            text: { 
                name: member.name,
                title: member.title,
            },
            image: member.picture,
            innerHTML: `
                ${member.picture ? `<img src="${member.picture}" style="width: 50px; height: 50px; border-radius: 50%;">` : '<img src="images/profile_picture.png">'}
                <div class="details">
                    <div class="node-name">${member.name}</div>
                    <div class="node-title">${member.title}</div>
                    <div class="node-id">${member.parent_node_id}</div>                    
                </div>
            `,
            HTMLclass: "custom-node",
            children: []
        });
    });

    // Second pass: build hierarchy
    members.forEach(member => {
        const node = nodeMap.get(member.parent_node_id);
        if (member.parent_id && member.parent_id !== 'NULL') {
            const parentNode = nodeMap.get(member.parent_id);
            if (parentNode) {
                parentNode.children.push(node);
            } else {
                console.warn(`Parent node ${member.parent_id} not found for member ${member.parent_node_id}`);
                rootNodes.push(node);
            }
        } else {
            rootNodes.push(node);
        }
    });

    // If there's only one root node, return it
    if (rootNodes.length === 1) {
        return rootNodes[0];
    }

    // If there are multiple root nodes, create a dummy root
    return {
        text: { name: "Department" },
        HTMLclass: "hidden-node",
        children: rootNodes
    };
};

// Function to initialize org charts
const initializeOrgCharts = () => {
    fetchOrgChartData().then(groupedData => {
        Object.keys(groupedData).forEach(orgchartId => {
            const containerId = `orgChartContainer_${orgchartId}`;
            const containerDiv = document.createElement('div');
            containerDiv.id = containerId;
            containerDiv.style.width = '100%';
            containerDiv.style.height = '100%';
            document.getElementById('orgchartCarouselContainer').appendChild(containerDiv);

            createOrgChart(groupedData[orgchartId], containerId);
        });
    });
};

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
    const contentsArray = contents[`${type}s`];

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

            // Render the org chart if it's an orgchart type
            if (type === 'orgchart') {
                setTimeout(renderVisibleOrgChart, 100); // Small delay to ensure DOM update
            }
        }
    } else {
        clearInterval(displayTimeIntervals[currentIndexKey]);
    }
};

// Function to reset display time
const resetDisplayTime = (type) => {
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
        fetch(`database/fetch_smart_tvs2.php?tvId=${tvId}`)
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
    if (contents[type + 's'].length > 0) {
        // Remove active class from all contents
        contents[type + 's'].forEach(content => content.classList.remove('active'));

        // Set the current content as active
        if (contents[type + 's'][currentIndex[type]]) { // Check if the index is valid
            contents[type + 's'][currentIndex[type]].classList.add('active');
            resetDisplayTime(type);

            // Render the org chart if it's an orgchart type
            if (type === 'orgchart') {
                renderVisibleOrgChart();
            }
        } else {
            console.error("Invalid current content index.");
        }
    } else {
        console.error("Cannot start carousel: No valid content available.");
        // Optionally handle this scenario
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

    if (tvId) {
        fetch(`database/fetch_smart_tvs2.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvDepartment = data.tv_department;
                fetch(`database/fetch_${type}.php`)
                    .then(response => response.json())
                    .then(data => {
                        if (type === 'orgchart') {
                            console.log("Fetched orgchart data:", data); // Debug log
                            Object.entries(data).forEach(([orgchartId, orgchartData]) => {
                                console.log(`Processing orgchart ${orgchartId}:`, orgchartData); // Debug log
                                updateUI({
                                    orgchart_id: orgchartId,
                                    chartData: orgchartData,
                                    display_time: orgchartData.display_time
                                }, 'orgchart');
                            });
                            if (Object.keys(data).length > 0) {
                                currentIndex['orgchart'] = 0;
                                startCarousel('orgchart');
                            } else {
                                displayNoMessage('orgchart');
                            }
                        } else if (type === 'peo' || type === 'so') {
                            const filteredData = data.filter(item =>
                                item.tv_id === parseInt(tvId, 10) &&
                                item.isCancelled === 0 &&
                                item.department === tvDepartment
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
                                item.isCancelled === 0 && 
                                item.department === tvDepartment
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
                        adjustFontSize(type);
                    })
                    .catch(error => console.error(`Error fetching ${type}s:`, error));
            })
            .catch(error => console.error('Error fetching TV data:', error));
    } else {
        console.error('TV ID not found in URL');
    }
};

Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);
    if (data.action === 'delete_content' || data.action === 'archive') {
        const type = data.type;
        const id = data[`${type}_id`]; 

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
        
    } else if (data.action === 'unarchive' || data.action === 'unarchive_and_update_expiration') {
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
    } else if (data.action === 'update') {
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
    } else if (data.action === 'post_content' || data.action === 'approve_post') {
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
            fetchAndUpdateContents('orgchart');
        }
    } else if (data.action === 'edit_smart_tv') {
        fetchSmartTVDetails();
        // location.reload();
    } else if (data.action === 'update_container_positions' || data.action === 'show_hide_content') {
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
                    console.error(`Container not found for ID: ${container.container_id}`);
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
    fetchAndUpdateContents('announcement');
    fetchAndUpdateContents('event');
    fetchAndUpdateContents('news');
    fetchAndUpdateContents('promaterial');
    fetchAndUpdateContents('peo');
    fetchAndUpdateContents('so');
    fetchAndUpdateContents('orgchart');
    // initializeOrgCharts();
    const types = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so', 'orgchart'];
    types.forEach(type => {
        adjustFontSize(type);
        setupFontSizeObserver(type);
    });
    fetchSmartTVDetails();
});