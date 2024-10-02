const contentType = document.querySelector('[name="type"]').value;
const form = document.getElementById(`${contentType}Form`);

if (contentType === 'announcement') {
    var announcementBodyQuill = new Quill('#announcement_body', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter Announcement',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });
} else if (contentType === 'event') {
    var eventBodyQuill = new Quill('#event_body', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter Event',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });
} else if (contentType === 'news') {
    var newsBodyQuill = new Quill('#news_body', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter News',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });
} else if (contentType === 'peo') {
    var peoTitleQuill = new Quill('#peo_title', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter PEO Title',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });

    var peoDescriptionQuill = new Quill('#peo_description', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter PEO Description',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });

    var peoSubdescriptionQuill = new Quill('#peo_subdescription', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter PEO Sub-Description',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });
} else if (contentType === 'so') {
    var soTitleQuill = new Quill('#so_title', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter SO Title',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });

    var soDescriptionQuill = new Quill('#so_description', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter SO Description',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });

    var soSubdescriptionQuill = new Quill('#so_subdescription', {
        theme: 'snow', // 'snow' is the default theme
        placeholder: 'Enter SO Sub-Description',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });
}

// Add this after initializing your Quill editors
if (announcementBodyQuill) {
    announcementBodyQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (eventBodyQuill) {
    eventBodyQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (newsBodyQuill) {
    newsBodyQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (peoTitleQuill) {
    peoTitleQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (peoDescriptionQuill) {
    peoDescriptionQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (peoSubdescriptionQuill) {
    peoSubdescriptionQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (soTitleQuill) {
    soTitleQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (soDescriptionQuill) {
    soDescriptionQuill.on('text-change', () => {
        isFormDirty = true;
    });
} else if (soSubdescriptionQuill) {
    soSubdescriptionQuill.on('text-change', () => {
        isFormDirty = true;
    });
} 

function handleFormSubmission(event) {
    event.preventDefault();

    var announcementBodyQuillEditorContent = announcementBodyQuill.root.innerHTML;
    document.querySelector('input[name=announcement_body]').value = announcementBodyQuillEditorContent;

    var eventBodyQuillEditorContent = eventBodyQuill.root.innerHTML;
    document.querySelector('input[name=event_body]').value = eventBodyQuillEditorContent;

    var newsBodyQuillEditorContent = newsBodyQuill.root.innerHTML;
    document.querySelector('input[name=news_body]').value = newsBodyQuillEditorContent;

    var peoTitleQuillEditorContent = peoTitleQuill.root.innerHTML;
    document.querySelector('input[name=peo_title]').value = peoTitleQuillEditorContent;

    var peoDescriptionQuillEditorContent = peoDescriptionQuill.root.innerHTML;
    document.querySelector('input[name=peo_description]').value = peoDescriptionQuillEditorContent;

    var peoSubdescriptionQuillEditorContent = peoSubdescriptionQuill.root.innerHTML;
    document.querySelector('input[name=peo_subdescription]').value = peoSubdescriptionQuillEditorContent;

    var soTitleQuillEditorContent = soTitleQuill.root.innerHTML;
    document.querySelector('input[name=so_title]').value = soTitleQuillEditorContent;

    var soDescriptionQuillEditorContent = soDescriptionQuill.root.innerHTML;
    document.querySelector('input[name=so_description]').value = soDescriptionQuillEditorContent;

    var soSubdescriptionQuillEditorContent = soSubdescriptionQuill.root.innerHTML;
    document.querySelector('input[name=so_subdescription]').value = soSubdescriptionQuillEditorContent;

    // Now submit the form
    event.target.submit();
}

form.onsubmit = handleFormSubmission;