const contentType = document.querySelector('[name="type"]').value;
const form = document.getElementById(`${contentType}Form`);
let quillEditors = {};

// Common toolbar configuration
const toolbarOptions = [
    ['bold', 'italic', 'underline'],
    ['link']
];

if (contentType === 'announcement') {
    quillEditors.announcementBodyQuill = new Quill('#announcement_body', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // If edit mode, populate the editor
    const existingContent = document.querySelector('#existing_announcement_content');
    if (existingContent) {
        quillEditors.announcementBodyQuill.root.innerHTML = existingContent.value;
    }
} else if (contentType === 'event') {
    quillEditors.eventBodyQuill = new Quill('#event_body', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // If edit mode, populate the editor
    const existingContent = document.querySelector('#existing_event_content');
    if (existingContent) {
        quillEditors.eventBodyQuill.root.innerHTML = existingContent.value;
    }
} else if (contentType === 'news') {
    quillEditors.newsBodyQuill = new Quill('#news_body', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // If edit mode, populate the editor
    const existingContent = document.querySelector('#existing_news_content');
    if (existingContent) {
        quillEditors.newsBodyQuill.root.innerHTML = existingContent.value;
    }
} else if (contentType === 'peo') {
    quillEditors.peoTitleQuill = new Quill('#peo_title', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    quillEditors.peoDescriptionQuill = new Quill('#peo_description', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    quillEditors.peoSubdescriptionQuill = new Quill('#peo_subdescription', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // If edit mode, populate the editor
    const existingContentTitle = document.querySelector('#existing_peo_content_title');
    const existingContentDescription = document.querySelector('#existing_peo_content_description');
    const existingContentSubdescription = document.querySelector('#existing_peo_content_subdescription');
    if (existingContentTitle && existingContentDescription && existingContentSubdescription) {
        quillEditors.peoTitleQuill.root.innerHTML = existingContentTitle.value;
        quillEditors.peoDescriptionQuill.root.innerHTML = existingContentDescription.value;
        quillEditors.peoSubdescriptionQuill.root.innerHTML = existingContentSubdescription.value;
    }
} else if (contentType === 'so') {
    quillEditors.soTitleQuill = new Quill('#so_title', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    quillEditors.soDescriptionQuill = new Quill('#so_description', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    quillEditors.soSubdescriptionQuill = new Quill('#so_subdescription', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // If edit mode, populate the editor
    const existingContentTitle = document.querySelector('#existing_so_content_title');
    const existingContentDescription = document.querySelector('#existing_so_content_description');
    const existingContentSubdescription = document.querySelector('#existing_so_content_subdescription');
    if (existingContentTitle && existingContentDescription && existingContentSubdescription) {
        quillEditors.soTitleQuill.root.innerHTML = existingContentTitle.value;
        quillEditors.soDescriptionQuill.root.innerHTML = existingContentDescription.value;
        quillEditors.soSubdescriptionQuill.root.innerHTML = existingContentSubdescription.value;
    }
} else if (contentType === 'facility') {
    quillEditors.facilityBodyQuill = new Quill('#facility_body', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // If edit mode, populate the editor
    const existingContent = document.querySelector('#existing_facility_content');
    if (existingContent) {
        quillEditors.facilityBodyQuill.root.innerHTML = existingContent.value;
    }
}

// Add change listeners
Object.values(quillEditors).forEach(editor => {
    editor.on('text-change', () => {
        isFormDirty = true;
    });
});

function handleFormSubmission(event) {
    event.preventDefault();

    // Handle each editor's content
    if (quillEditors.announcementBodyQuill) {
        document.querySelector('input[name=announcement_body]').value = 
            quillEditors.announcementBodyQuill.root.innerHTML;
    }
    if (quillEditors.eventBodyQuill) {
        document.querySelector('input[name=event_body]').value = 
            quillEditors.eventBodyQuill.root.innerHTML;
    }
    if (quillEditors.newsBodyQuill) {
        document.querySelector('input[name=news_body]').value = 
            quillEditors.newsBodyQuill.root.innerHTML;
    }
    if (quillEditors.peoTitleQuill) {
        document.querySelector('input[name=peo_title]').value = 
            quillEditors.peoTitleQuill.root.innerHTML;
    }
    if (quillEditors.peoDescriptionQuill) {
        document.querySelector('input[name=peo_description]').value = 
            quillEditors.peoDescriptionQuill.root.innerHTML;
    }
    if (quillEditors.peoSubdescriptionQuill) {
        document.querySelector('input[name=peo_subdescription]').value = 
            quillEditors.peoSubdescriptionQuill.root.innerHTML;
    }
    if (quillEditors.soTitleQuill) {
        document.querySelector('input[name=so_title]').value = 
            quillEditors.soTitleQuill.root.innerHTML;
    }
    if (quillEditors.soDescriptionQuill) {
        document.querySelector('input[name=so_description]').value = 
            quillEditors.soDescriptionQuill.root.innerHTML;
    }
    if (quillEditors.soSubdescriptionQuill) {
        document.querySelector('input[name=so_subdescription]').value = 
            quillEditors.soSubdescriptionQuill.root.innerHTML;
    } 
    if (quillEditors.facilityBodyQuill) {
        document.querySelector('input[name=facility_body]').value = 
            quillEditors.facilityBodyQuill.root.innerHTML;
    }

    // Submit the form
    event.target.submit();
}

form.onsubmit = handleFormSubmission;