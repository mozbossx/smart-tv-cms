// function for sorting content
const sortContent = (type, sortBy) => {
    const container = document.getElementById(`${type}CarouselContainer`);
    const contents = Array.from(container.children).filter(child => child.hasAttribute(`data-${type}-id`));
    
    contents.sort((a, b) => {
        switch(sortBy) {
            case 'post_datetime_newest':
                const dateA1 = new Date(a.querySelector('.ann-author small').textContent.split('on ')[1]);
                const dateB1 = new Date(b.querySelector('.ann-author small').textContent.split('on ')[1]);
                return dateB1 - dateA1;
            
            case 'post_datetime_oldest':
                const dateA2 = new Date(a.querySelector('.ann-author small').textContent.split('on ')[1]);
                const dateB2 = new Date(b.querySelector('.ann-author small').textContent.split('on ')[1]);
                return dateA2 - dateB2;
            
            case 'expiration_datetime_newest':
                // Only sort by expiration if the content type has expiration dates
                if (type !== 'peo' && type !== 'so') {
                    const expA1 = new Date(a.querySelector('.expiration-date small').textContent.split('on ')[1]);
                    const expB1 = new Date(b.querySelector('.expiration-date small').textContent.split('on ')[1]);
                    return expB1 - expA1;
                }
                return 0;
            
            case 'expiration_datetime_oldest':
                if (type !== 'peo' && type !== 'so') {
                    const expA2 = new Date(a.querySelector('.expiration-date small').textContent.split('on ')[1]);
                    const expB2 = new Date(b.querySelector('.expiration-date small').textContent.split('on ')[1]);
                    return expA2 - expB2;
                }
                return 0;
            
            case 'display_time_longest':
                const timeA1 = parseInt(a.querySelector('.display-time').textContent.match(/\d+/)[0]);
                const timeB1 = parseInt(b.querySelector('.display-time').textContent.match(/\d+/)[0]);
                return timeB1 - timeA1;
            
            case 'display_time_shortest':
                const timeA2 = parseInt(a.querySelector('.display-time').textContent.match(/\d+/)[0]);
                const timeB2 = parseInt(b.querySelector('.display-time').textContent.match(/\d+/)[0]);
                return timeA2 - timeB2;
            
            default:
                return 0;
        }
    });

    // Clear container and append sorted elements
    const noItemsMessage = container.querySelector(`#no-${type}-message`);
    container.innerHTML = '';
    if (noItemsMessage) {
        container.appendChild(noItemsMessage);
    }
    contents.forEach(content => container.appendChild(content));
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

// Function to update content count
const updateContentCount = (type, count) => {
    const countElement = document.getElementById(`${type}Count`);
    if (countElement) {
        countElement.textContent = count > 0 ? `(${count})` : '(0)';
    }
};

let currentArchiveDays = 120; // Default value, will be updated from database
let currentTrashDays = 30; // Default value, will be updated from database

// Function to fetch archive days
const fetchArchiveDays = async () => {
    try {
        const response = await fetch('database/fetch_archive_days.php');
        const data = await response.json();
        currentArchiveDays = data.archive_days;
    } catch (error) {
        console.error('Error fetching archive days:', error);
        return 120; // Default fallback
    }
};

// Function to fetch trash days
const fetchTrashDays = async () => {
    try {
        const response = await fetch('database/fetch_trash_days.php');
        const data = await response.json();
        currentTrashDays = data.trash_days;
    } catch (error) {
        console.error('Error fetching trash days:', error);
        return 30; // Default fallback
    }
};

// Utility function to create buttons
const createDeleteButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'light-green-button';
    button.style = `
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

const createTrashButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'light-green-button';
    button.style = `
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

const createArchiveButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'light-green-button';
    button.style = `
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

const createEditButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'green-button';
    button.onclick = onClick;
    return button;
};

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type) => {
    let contentHtml = '';

    if (type !== 'orgchart') {
        if (type !== 'facility') {
            contentHtml += `
                <div class="content-container-con">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
            `;
        } else {
            contentHtml += `<div class="content-container-con">`;       
            // Special handling for facility type with multiple images
            if (type === 'facility' && data.facility_images_array) {
                const images = data.facility_images_array.split(',');
                contentHtml += `
                    <div class="image-slideshow-container" style="width: 100%; height: 300px; position: relative; margin-bottom: 5px;">
                        <div class="image-slides" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                            ${images.map((image, index) => `
                                <img src="servers/${type}_media/${image}" 
                                    class="facility-image-preview ${index === 0 ? 'active' : ''}"
                                    data-index="${index}"
                                    style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: ${index === 0 ? 'block' : 'none'}; margin: 0 auto; border-radius: 5px">
                            `).join('')}
                        </div>
                        ${images.length > 1 ? `
                            <button type="button" class="nav-btn prev" onclick="changeFacilitySlide(${data.facility_id}, -1)">❮</button>
                            <button type="button" class="nav-btn next" onclick="changeFacilitySlide(${data.facility_id}, 1)">❯</button>
                        ` : ''}
                    </div>
                    ${images.length > 1 ? `
                        <div class="dot-indicators" id="facility-dots-${data.facility_id}">
                            ${images.map((_, index) => `
                                <div class="dot ${index === 0 ? 'active' : ''}" 
                                     onclick="goToFacilitySlide(${data.facility_id}, ${index})">${index + 1}</div>
                            `).join('')}
                        </div>
                    ` : ''}
                `;
            } else if (mediaContent) {
                contentHtml += mediaContent;
            }
        }
        
    }

    // Add type-specific content
    switch(type) {
        case 'announcement':
        case 'event':
        case 'news':
            contentHtml += `<pre class="ann-body" style="word-break: break-word">${data[`${type}_body`]}</pre>`;
            break;
        case 'promaterial':
            break;
        case 'peo':
        case 'so':
            contentHtml += `<pre class="ann-body" style="word-break: break-word"><b>${data[`${type}_title`]}</b></pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_description`]}</pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_subdescription`]}</pre>`;
            break;
        case 'orgchart':
            contentHtml = `
                <div class="content-container-con" id="orgChartParentContainer-${data.orgchart_id}">
                    <div id="orgChartContainerParent-${data.orgchart_id}" class="orgchart-container" style="background-color: none; place-items: center">
                        <div id="orgChartContainer-${data.orgchart_id}" class="orgchart-container-parent">
                            <div class="orgchart-content"></div>
                        </div>
                    </div>
                </div>
            `;
            break;
        case 'facility':
            contentHtml += `<pre class="ann-body" style="word-break: break-word">${data.facility_body}</pre>`;
            break;
        default:
            // For new features, only display content fields (skip metadata fields)
            for (let key in data) {
                if (!key.match(/^(created_datetime|expiration_datetime|display_time|status|isArchived|isTrashed|tv_id|author_id|type|department|user_type|category|evaluated_by|evaluated_message|author_name|media_path|media_upload_only_zz|author_user_type|author_department|datetime_archived|datetime_trashed)$/) 
                    && !key.endsWith('_id')) {
                    contentHtml += `<pre class="ann-body" style="word-break: break-word">${data[key]}</pre>`;
                }
            }
    }

    if (type !== 'orgchart') {
        contentHtml += `
            <div class="line-separator"></div>
            <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.author_name || 'Unknown'} on ${formattedCreatedDateTime}</small></p>
        `;
    }

    // Only add expiration date for content types that have it
    if (type !== 'peo' && type !== 'so' && type !== 'orgchart' && type !== 'facility') {
        contentHtml += `
            <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDateTime}</small></p>
            <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs</p>
        `;
    } else {
        contentHtml += `
            <p class="display-time" style="margin-top: 10px"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs</p>
        `;
    }

    contentHtml += `</div>`;

    return contentHtml;
};

const changeFacilitySlide = (facilityId, direction) => {
    const container = document.querySelector(`[data-facility-id="${facilityId}"]`);
    if (!container) return;

    const images = container.querySelectorAll('.facility-image-preview');
    const currentImage = container.querySelector('.facility-image-preview.active');
    const currentIndex = parseInt(currentImage.dataset.index);
    const newIndex = (currentIndex + direction + images.length) % images.length;

    // Hide current image
    currentImage.style.display = 'none';
    currentImage.classList.remove('active');

    // Show new image
    const newImage = container.querySelector(`.facility-image-preview[data-index="${newIndex}"]`);
    newImage.style.display = 'block';
    newImage.classList.add('active');

    // Update dots
    updateFacilityDots(facilityId, newIndex);
};

const goToFacilitySlide = (facilityId, index) => {
    const container = document.querySelector(`[data-facility-id="${facilityId}"]`);
    if (!container) return;

    const images = container.querySelectorAll('.facility-image-preview');
    
    // Hide all images
    images.forEach(img => {
        img.style.display = 'none';
        img.classList.remove('active');
    });

    // Show selected image
    const selectedImage = container.querySelector(`.facility-image-preview[data-index="${index}"]`);
    selectedImage.style.display = 'block';
    selectedImage.classList.add('active');

    // Update dots
    updateFacilityDots(facilityId, index);
};

const updateFacilityDots = (facilityId, activeIndex) => {
    const dotsContainer = document.getElementById(`facility-dots-${facilityId}`);
    if (!dotsContainer) return;

    const dots = dotsContainer.querySelectorAll('.dot');
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === activeIndex);
    });
};

// Function to check if content is expired and trash it if necessary
const checkAndTrashExpiredContent = (data, type) => {
    if (!data.expiration_datetime) return false;
    
    const now = new Date();
    const expirationDate = new Date(data.expiration_datetime);
    
    if (now >= expirationDate) {
        // Prepare the trash data
        const trashData = {
            action: 'trash_content',
            type: type,
            [`${type}_id`]: data[`${type}_id`],
            user_id: data[`${type}_author_id`], // Using author as the user
            author_id: data[`${type}_author_id`],
            author_name: data.author_name || 'System',
            author_user_type: data.author_user_type || 'System',
            author_department: data.author_department || 'System',
            evaluator_name: 'System',
            evaluator_message: `Content automatically moved to Trash upon reaching expiration date (${formatDateTime(data.expiration_datetime)})`,
            tv_id: data.tv_id
        };
        
        // Send the trash request through WebSocket
        Ws.send(JSON.stringify(trashData));
        return true;
    }
    return false;
};

// Function to update UI for announcement, event, news, etc.
const updateUI = (data, type) => {
    if (type === 'orgchart' || type === 'facility') {
        // No status check needed for orgchart
        if (!data[`${type}_id`]) {
            return;
        }
    } else if (type === 'peo' || type === 'so') {
        if (data.isArchived === 1 || data.isTrashed === 1 || !data[`${type}_id`]) return;
    } else {
        if (data.status === 'Pending' || data.status === 'Draft' || data.isArchived === 1 || 
            data.isTrashed === 1 || !data[`${type}_id`]) return;
    }

    // Special handling for orgchart type
    if (type === 'orgchart') {
        if (!data.orgchart_id) return;

        const existingDiv = document.querySelector(`[data-${type}-id="${data.orgchart_id}"]`);
        const formattedCreatedDateTime = null; // Not needed for orgchart
        const formattedExpirationDateTime = null; // Not needed for orgchart

        const contentHtml = createContentDiv(data, null, formattedCreatedDateTime, formattedExpirationDateTime, type);

        if (existingDiv) {
            existingDiv.querySelector('.content-container-con').innerHTML = contentHtml;
        } else {
            const containerDiv = document.createElement('div');
            containerDiv.dataset[`${type}Id`] = data.orgchart_id;
            containerDiv.setAttribute(`data-${type}-id`, data.orgchart_id);
            containerDiv.classList.add('content-container-main');
            containerDiv.innerHTML = contentHtml;

            containerDiv.style = `
                height: auto;
                width: auto;
                margin-bottom: 10px;
                margin-right: 8px;
                border-radius: 15px;
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                padding: 15px;
            `;

            const buttonContainer = document.createElement('div');
            buttonContainer.style = `
                display: flex;
                justify-content: flex-end;
                margin-top: 10px;
            `;

            // Add delete button for orgchart
            const deleteButton = createDeleteButton('Delete', 'fa fa-trash', () => showDeleteModal(data.orgchart_id, type, null, null, null, null, null, data.tv_id));
            if (userType === 'Super Admin' || (userType === 'Admin' && data.user_type !== 'Super Admin') || data.author_id === parseInt(user_id)) {
                buttonContainer.appendChild(deleteButton);
            }

            // Add edit button for orgchart
            // const editButton = createEditButton('Edit', 'fa fa-pencil-square', () => {
            //     const queryParams = new URLSearchParams({
            //         orgchart_id: data.orgchart_id,
            //         type: type,
            //         tv_id: data.tv_id,
            //         action: 'edit'
            //     }).toString();
            //     window.location.href = `form_department_organizational_chart.php?${queryParams}`;
            // });

            // if (userType === 'Super Admin' || (userType === 'Admin' && data.user_type !== 'Super Admin') || data.author_id === parseInt(user_id)) {
            //     buttonContainer.appendChild(editButton);
            // }

            containerDiv.appendChild(buttonContainer);
            document.getElementById(`${type}CarouselContainer`).insertBefore(containerDiv, document.getElementById(`${type}CarouselContainer`).firstChild);

            updateContentCount(type, document.getElementById(`${type}CarouselContainer`).querySelectorAll(`[data-${type}-id]`).length);

            // Create the org chart after the container is added to the DOM
            setTimeout(() => {
                createOrgChart(data.orgchart_id, data);
            }, 100);
        }
        return;
    }

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDateTime = formatDateTime(data.created_datetime);
    // Only format expiration datetime for content types that have it
    const formattedExpirationDateTime = (type !== 'peo' && type !== 'so' && type !== 'facility') ? formatDateTime(data.expiration_datetime) : null;

    let mediaContent = '';

    if (type === 'facility') {
        // fetch facility images array by separating the comma in the facility_images_array
        const facilityImagesArray = data.facility_images_array.split(',');
        // console.log("Facility Images Array:", facilityImagesArray);
    }

    if (data.media_path && type !== 'facility') {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls controlsList="nodownload nofullscreen nopictureinpicture nopictureinpicture" style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    } 

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type);
    
    if (existingDiv) {
        existingDiv.querySelector('.content-container-con').innerHTML = contentDiv;
    } else {
        const containerDiv = document.createElement('div');
        const buttonContainer = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}_id`];
        containerDiv.setAttribute(`data-${type}-id`, data[`${type}_id`]);
        containerDiv.classList.add('content-container-main');
        containerDiv.innerHTML = contentDiv;

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 10px;
            margin-right: 8px;
            border-radius: 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
        `;

        contentDiv.style = `
            border: none;
            border-radius: 15px;
            padding: 20px;;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        buttonContainer.style = `
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        `;

        // Only create archive and trash buttons for specific content types
        const isStandardContent = ['announcement', 'event', 'news', 'promaterial'].includes(type);
        const isDynamicFeature = type !== 'orgchart' && type !== 'facility';

        // for standard content types and dynamic features, create archive and trash buttons
        if (isStandardContent || isDynamicFeature) {
            if (checkAndTrashExpiredContent(data, type)) {
                return; // Skip UI update if content was trashed
            }
            const trashButton = createTrashButton('Trash', 'fa fa-trash', () => showTrashModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
            const archiveButton = createArchiveButton('Archive', 'fa fa-archive', () => showArchiveModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
            
            if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
                buttonContainer.appendChild(trashButton);
                buttonContainer.appendChild(archiveButton);
            }
        } else {
            // Do not include trash and archive buttons for non-standard contents
            const deleteButton = createDeleteButton('Delete', 'fa fa-trash', () => showDeleteModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
            if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
                buttonContainer.appendChild(deleteButton);
            }
        }

        // const trashButton = createTrashButton('Trash', 'fa fa-trash', () => showTrashModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
        // const archiveButton = createArchiveButton('Archive', 'fa fa-archive', () => showArchiveModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
        if (type !== 'facility') {
            const editButton = createEditButton('Edit', 'fa fa-pencil-square', () => {
                const queryParams = new URLSearchParams({
                    [`${type}_id`]: data[`${type}_id`],
                    type: type,
                    tv_id: data.tv_id,
                    action: 'edit'
                }).toString();
                if (type === 'promaterial') {
                    window.location.href = `form_promotional_material.php?${queryParams}`;
                } else if (type === 'so') {
                    window.location.href = `form_student_outcomes.php?${queryParams}`;
                } else {
                    window.location.href = `form_${type}.php?${queryParams}`;
                }
            });
            if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
                buttonContainer.appendChild(editButton);
            }
        }
        
        // Append the button container to the main container
        containerDiv.appendChild(buttonContainer);

        document.getElementById(`${type}CarouselContainer`).insertBefore(containerDiv, document.getElementById(`${type}CarouselContainer`).firstChild);
    }
};

const createOrgChart = (orgchartId, chartData) => {
    const containerId = `orgChartContainer-${orgchartId}`;
    const containerParentId = `orgChartContainerParent-${orgchartId}`;

    const container = document.getElementById(containerId);
    const containerParent = document.getElementById(containerParentId);

    if (!container) {
        console.error(`Container not found for orgchart ${orgchartId}`);
        return;
    }

    // Clear any existing chart
    const contentDiv = document.querySelector('.orgchart-content');
    if (!contentDiv) {
        return;
    }

    contentDiv.innerHTML = '';

    // Create a unique chart ID
    const uniqueChartId = `treant-${orgchartId}`;
    contentDiv.id = uniqueChartId;

    const chartConfig = {
        chart: {
            container: `#${containerId}`,
            nodeAlign: "BOTTOM",
            levelSeparation: 40,
            siblingSeparation: 40,
            subTeeSeparation: 40,
            connectors: {
                type: 'step',
                style: {
                    'stroke-width': '5px'
                }
            },
            node: {
                HTMLclass: 'nodeExample1'
            }
        },
        nodeStructure: createNodeStructure(chartData.chartData.members)
    };

    try {
        // Create the Treant instance
        new Treant(chartConfig, () => {
            const treant = document.querySelector(`#${containerId}`).closest('.Treant');
            const svg = treant ? treant.querySelector('svg') : null;

            if (!treant) {
                console.error(`Treant not found for orgchart ${orgchartId}`);
                return;
            }

            if (!svg) {
                console.error(`SVG not found for orgchart ${orgchartId}`);
                return;
            }

            // First, make the Treant match the SVG dimensions
            const svgWidth = svg.width.baseVal.value;
            const svgHeight = svg.height.baseVal.value;
            // console.log("svgWidth: ", svgWidth);
            // console.log("svgHeight: ", svgHeight);
            
            // Set both width and height of Treant to match SVG
            treant.style.width = `${svgWidth}px`;
            treant.style.height = `${svgHeight}px`;

            // Reset transform to get true dimensions
            treant.style.transform = 'none';
            
            // Calculate scale ratios
            const containerWidth = containerParent.offsetWidth;
            const containerHeight = containerParent.offsetHeight;
            const scale = Math.min(
                containerWidth / svgWidth,
                containerHeight / svgHeight,
                1
            ) * 0.9;

            // Apply the transform
            treant.style.zoom = `${scale}`;
            treant.style.transformOrigin = 'center center';
        });
    } catch (error) {
        console.error('Error creating org chart:', error);
        contentDiv.innerHTML = '<p style="text-align: center; color: #264B2B; font-size: 1.2em; padding: 20px;">Error loading organizational chart.</p>';
    }
};

function createNodeStructure(members) {
    if (!members || members.length === 0) return null;

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
                <div class="node-content" style="width: auto; max-width: 400px;">
                    ${member.picture ? 
                        `<img src="${member.picture}" class="node-image" alt="${member.name}">` : 
                        `<img src="images/profile_picture.png" class="node-image" alt="Default Profile">`
                    }
                    <div class="node-details">
                        <div class="node-name">${member.name}</div>
                        <div class="node-title">${member.title}</div>
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
}

// Function to archive an item
const archiveItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const evaluatorMessageArchive = document.getElementById('evaluator_message_archive');
    let evaluatorMessage = null;

    if (evaluatorMessageArchive) {
        evaluatorMessage = evaluatorMessageArchive.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const data = {
        action: 'archive_content',
        type: contentType,
        [`${contentType}_id`]: contentId, // Dynamically set the ID based on the type
        user_id: user_id,
        author_id: authorId,
        author_name: authorName,
        author_user_type: authorUserType,
        author_department: authorDepartment,
        evaluator_name: loggedInFullName,
        evaluator_message: evaluatorMessage,
        tv_id: tvId
    };
    modal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

// Function to trash an item
const trashItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const evaluatorMessageTrash = document.getElementById('evaluator_message_trash');
    let evaluatorMessage = null;

    if (evaluatorMessageTrash) {
        evaluatorMessage = evaluatorMessageTrash.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const confirmTrashModal = document.getElementById(`confirmTrash${capitalizedType}Modal`);
    const data = {
        action: 'trash_content',
        type: contentType,
        [`${contentType}_id`]: contentId, // Dynamically set the ID based on the type
        user_id: user_id,
        author_id: authorId,
        author_name: authorName,
        author_user_type: authorUserType,
        author_department: authorDepartment,
        evaluator_name: loggedInFullName,
        evaluator_message: evaluatorMessage,
        tv_id: tvId
    };
    confirmTrashModal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

// Function to delete an item
const deleteItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const evaluatorMessageDeleteForever = document.getElementById('evaluator_message_delete');
    let evaluatorMessage = null;

    if (evaluatorMessageDeleteForever) {
        evaluatorMessage = evaluatorMessageDeleteForever.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const confirmTrashModal = document.getElementById(`confirmTrash${capitalizedType}Modal`);
    const confirmDeleteModal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const data = {
        action: 'delete_content',
        type: contentType,
        [`${contentType}_id`]: contentId, // Dynamically set the ID based on the type
        user_id: user_id,
        author_id: authorId ? authorId : null,
        author_name: authorName ? authorName : null,
        author_user_type: authorUserType ? authorUserType : null,
        author_department: authorDepartment ? authorDepartment : null,
        evaluator_name: loggedInFullName ? loggedInFullName : null,
        evaluator_message: evaluatorMessage ? evaluatorMessage : null,
        tv_id: tvId ? tvId : null
    };
    confirmTrashModal.style.display = 'none';
    confirmDeleteModal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

// Function to display a "No items to be displayed" message
const displayNoMessage = (type) => {
    const messageDiv = document.createElement('div');
    messageDiv.id = `no-${type}-message`;
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    if (type === 'promaterial') {
        messageDiv.textContent = `No promotional materials to be displayed`;
    } else if (type === 'peo') {
        messageDiv.textContent = `No Program Education Objective (PEO) to be displayed`;
    } else if (type === 'so') {
        messageDiv.textContent = `No Student Outcome (SO) to be displayed`;
    } else {
        messageDiv.textContent = `No ${type} to be displayed`;
    }
    
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    if (carouselContainer) {
        carouselContainer.appendChild(messageDiv);
    } else {
        console.error(`Carousel container for ${type} not found.`);
    }
};

// Array of default content types
// const defaultContentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];

// Function to create modals dynamically
const createModals = (contentTypes) => {
    const modalContainer = document.createElement('div');
    modalContainer.id = 'dynamicModals';

    contentTypes.forEach(type => {
        const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
        
        // Archive Modal
        const archiveModal = document.createElement('div');
        archiveModal.id = `confirmArchive${capitalizedType}Modal`;
        archiveModal.className = 'modal';
        archiveModal.innerHTML = `<div id="archive${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(archiveModal);

        // Trash Modal
        const trashModal = document.createElement('div');
        trashModal.id = `confirmTrash${capitalizedType}Modal`;
        trashModal.className = 'modal';
        trashModal.innerHTML = `<div id="trash${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(trashModal);

        // Delete Modal
        const deleteModal = document.createElement('div');
        deleteModal.id = `confirmDelete${capitalizedType}Modal`;
        deleteModal.className = 'modal';
        deleteModal.innerHTML = `<div id="delete${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(deleteModal);
    });

    document.body.appendChild(modalContainer);
};

// Function to show archive modal
const showArchiveModal = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const modalContent = document.getElementById(`archive${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_archive" id="evaluator_message_archive" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_archive" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
        <p id="trashMessage" style="text-align: center">Proceed to archive?</p>
        <p id="trashMessage2" style="font-size: 13px; color: #6E6E6E;">Archived items are stored for ${currentArchiveDays} days before being moved to Trash.</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="yellow-button" onclick="archiveItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, Archive</button>
        </div>
    `;
    
    modal.style.display = 'flex';
};

// Function to show trash modal
const showTrashModal = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmTrash${capitalizedType}Modal`);
    const modalContent = document.getElementById(`trash${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_trash" id="evaluator_message_trash" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_trash" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmTrash${capitalizedType}Modal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
        <p id="trashMessage" style="text-align: center">Proceed to move to Trash?</p>
        <p id="trashMessage2" style="font-size: 13px; color: #6E6E6E;">Trashed items are stored for ${currentTrashDays} days before being permanently deleted.</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <!-- <button type="button" class="grey-button" onclick="document.getElementById('confirmTrash${capitalizedType}Modal').style.display='none'">Cancel</button> -->
            <button type="button" class="grey-button" onclick="showDeleteModal(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Delete Forever</button>
            <button type="button" class="red-button" onclick="trashItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, move to Trash</button>
        </div>
    `;
    
    modal.style.display = 'flex';
};

// Function to show delete modal
const showDeleteModal = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const modalContent = document.getElementById(`delete${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    let evaluatorMessageHtml = '';

    if (contentType !== 'orgchart') {
        evaluatorMessageHtml = user_id != authorId ? `
            <div class="floating-label-container">
                <textarea name="evaluator_message_delete" id="evaluator_message_delete" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
                <label for="evaluator_message_delete" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
            </div>
            <br>
        ` : '';
    }
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to delete forever?</p>
        <p id="deleteMessage2" style="font-size: 13px; color: #6E6E6E;">Permanently deleted items cannot be recovered.</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="red-button" onclick="deleteItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, delete forever</button>
        </div>
    `;
    
    modal.style.display = 'flex';
};

// Function to fetch feature information
const getFeatureInfo = async (type) => {
    try {
        const response = await fetch(`database/fetch_feature_info.php?type=${type}`);
        const featureInfo = await response.json();
        return featureInfo;
    } catch (error) {
        console.error(`Error fetching feature info for ${type}:`, error);
        return null;
    }
};

const fetchOrgChartData = async (type, tvId) => {
    try {
        const response = await fetch(`database/fetch_orgchart.php?tvId=${tvId}`);
        const data = await response.json();

        // console.log("Fetched orgchart data:", data); // Debug log
        
        // Clear existing content
        const carouselContainer = document.getElementById(`${type}CarouselContainer`);
        if (carouselContainer) {
            carouselContainer.innerHTML = '';
        }

        // Process each orgchart
        Object.entries(data).forEach(([orgchartId, chartData]) => {
            const orgchartData = {
                orgchart_id: orgchartId,
                display_time: chartData.display_time,
                chartData: chartData, // Contains members array and other chart data
                tv_id: tvId
            };
            updateUI(orgchartData, type);
        });

        if (Object.keys(data).length === 0) {
            displayNoMessage(type);
        }
    } catch (error) {
        console.error('Error fetching orgchart data:', error);
        displayNoMessage(type);
    }
};

// Function to fetch and update content for all types
function fetchAndUpdate(type) {
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    // If we're not on a TV contents page, don't try to update the UI
    if (!tvId) return;
    
    if (type === 'orgchart') {
        fetchOrgChartData(type, tvId);
        return;
    } else if (type !== 'orgchart') {
        fetch(`database/fetch_content.php?type=${type}&tvId=${tvId}`)
        .then(response => response.json())
        .then(data => {
            let filteredData;
            // console.log("Data of ", type,":", data);

            if (Array.isArray(data)) {
                filteredData = data.filter(item => {
                    // Special handling for PEO, SO, and Facility
                    if (type === 'peo' || type === 'so' || type === 'facility') {
                        return item.tv_id === parseInt(tvId, 10) && 
                               item.isArchived === 0 && 
                               item.isTrashed === 0
                    }
                    // Regular handling for other content types
                    return item.tv_id === parseInt(tvId, 10) && 
                           item.isArchived === 0 && 
                           item.isTrashed === 0 && 
                           item.status === 'Approved';
                });
            } else if (typeof data === 'object' && data !== null) {
                // If data is a single object, wrap it in an array
                filteredData = [data].filter(item => {
                    // Special handling for PEO, SO, and Facility
                    if (type === 'peo' || type === 'so' || type === 'facility') {
                        return item.tv_id === parseInt(tvId, 10) && 
                               item.isArchived === 0 && 
                               item.isTrashed === 0 
                    }
                    // Regular handling for other content types
                    return item.tv_id === parseInt(tvId, 10) && 
                           item.isArchived === 0 && 
                           item.isTrashed === 0 && 
                           item.status === 'Approved';
                });
            } else {
                console.error(`Unexpected data format for ${type}:`, data);
                return;
            }

            // Update the count for this content type
            updateContentCount(type, filteredData.length);

            const carouselContainer = document.getElementById(`${type}CarouselContainer`);
            if (carouselContainer) {
                carouselContainer.innerHTML = '';
                if (filteredData.length === 0) {
                    displayNoMessage(type);
                } else {
                    filteredData.forEach(item => updateUI(item, type));

                    // Apply current sort after updating content
                    const sortSelect = document.getElementById(`sortBy_${type}`);
                    if (sortSelect) {
                        sortContent(type, sortSelect.value);
                    }
                }
            }
        })
        .catch(error => {
            console.error(`Error fetching ${type} of TV ${tvId}:`, error);
            // Set count to 0 if there's an error
            updateContentCount(type, 0);
        });
    }
}

// Function to check all visible content for expiration
const checkAllContentForExpiration = () => {
    // Get all content types from the DOM
    const contentTypes = Array.from(document.querySelectorAll('.content-container'))
        .map(container => container.id.replace('List', ''));
    
    contentTypes.forEach(type => {
        // Skip types that don't use expiration
        if (type === 'orgchart' || type === 'facility') return;

        const containers = document.querySelectorAll(`[data-${type}-id]`);
        containers.forEach(container => {
            const expirationElement = container.querySelector('.expiration-datetime');
            if (expirationElement && expirationElement.dataset.expiration) {
                const expirationDate = new Date(expirationElement.dataset.expiration);
                const now = new Date();
                
                if (now >= expirationDate) {
                    // Get the necessary data from the container
                    const contentId = container.dataset[`${type}Id`];
                    const authorId = container.dataset.authorId;
                    const authorName = container.dataset.authorName;
                    const authorUserType = container.dataset.authorUserType;
                    const authorDepartment = container.dataset.authorDepartment;
                    const tvId = container.dataset.tvId;
                    
                    // Prepare and send the trash request
                    const trashData = {
                        action: 'trash_content',
                        type: type,
                        [`${type}_id`]: contentId,
                        user_id: authorId,
                        author_id: authorId,
                        author_name: authorName || 'System',
                        author_user_type: authorUserType || 'System',
                        author_department: authorDepartment || 'System',
                        evaluator_name: 'System',
                        evaluator_message: `Content automatically moved to Trash upon reaching expiration date (${formatDateTime(expirationDate)})`,
                        tv_id: tvId
                    };
                    
                    Ws.send(JSON.stringify(trashData));
                }
            }
        });
    });
};

// Add periodic check for expired content (every minute)
setInterval(checkAllContentForExpiration, 60000);

/* Web-Socket Server Connection */
Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    const type = data.type || data.content_type;

    switch (data.action) {
        case 'delete_content':
            // Handle deletion confirmation
            const deleteMessageDiv = document.getElementById(`no-${data.type}-message`);
            if (deleteMessageDiv) {
                deleteMessageDiv.remove(); // Remove the "No items" message if it exists
            }
            const deletedDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (deletedDiv) {
                deletedDiv.remove(); // Remove the deleted item from the UI
            }
            // After removing content, update the count
            const deletedContainer = document.getElementById(`${type}CarouselContainer`);
            if (deletedContainer) {
                const remainingItems = deletedContainer.querySelectorAll(`[data-${type}-id]`).length;
                updateContentCount(type, Math.max(0, remainingItems));
            }
            // console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been deleted.`);
            break;
        
        case 'trash_content':
            // Handle deletion confirmation
            const trashMessageDiv = document.getElementById(`no-${data.type}-message`);
            if (trashMessageDiv) {
                trashMessageDiv.remove(); // Remove the "No items" message if it exists
            }
            const trashDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (trashDiv) {
                trashDiv.remove(); // Remove the trashed item from the UI
            }
            // After removing content, update the count
            const trashedContainer = document.getElementById(`${type}CarouselContainer`);
            if (trashedContainer) {
                const remainingItems = trashedContainer.querySelectorAll(`[data-${type}-id]`).length;
                updateContentCount(type, Math.max(0, remainingItems));
            }
            // console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been moved to Trash.`);
            break;

        case 'archive_content':
            // Handle archiving confirmation
            const archivedDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (archivedDiv) {
                archivedDiv.remove(); // Remove the archived item from the UI
            }
            // After removing content, update the count
            const archivedContainer = document.getElementById(`${type}CarouselContainer`);
            if (archivedContainer) {
                const remainingItems = archivedContainer.querySelectorAll(`[data-${type}-id]`).length;
                updateContentCount(type, Math.max(0, remainingItems));
            }
            // console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been archived.`);
            break;
        case 'update_content':
        case 'restore_content':
        case 'restore_content_and_update_expiration':
        case 'post_content':
        case 'post_now_content':
        case 'approve_post':
            // For these cases, fetchAndUpdate will handle the count update
            fetchAndUpdate(type);
            break;
    }
});

// Call fetchAndUpdate for each type
document.addEventListener('DOMContentLoaded', async () => {
    await fetchArchiveDays();
    await fetchTrashDays();

    checkAllContentForExpiration();
    
    // Get all content types from the DOM
    const contentTypes = Array.from(document.querySelectorAll('.content-container'))
        .map(container => container.id.replace('List', ''));

    contentTypes.forEach(type => {
        const sortSelect = document.getElementById(`sortBy_${type}`);
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                sortContent(type, e.target.value);
            });
        }
    });
    
    // Create modals for all content types
    createModals(contentTypes);

    // Fetch and update content for each type
    contentTypes.forEach(type => fetchAndUpdate(type));
});