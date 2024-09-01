<div id="successMessageModal" class="modal">
    <div class="modal-content">
        <div class="green-bar-vertical">
            <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
            <p id="successMessage"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button id="createMoreButton" class="green-button" style="background: none; border: 1px solid #264B2B; color: #264B2B;" onclick="closeSuccessMessageModal()">Make more</button>
                <button id="homeButton" class="green-button" style="margin-right: 0" onclick="resetFormAndGoHome()">Home</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('createMoreButton').addEventListener('click', function() {
        closeSuccessMessageModal();
    });

    function closeSuccessMessageModal() {
        var successMessageModal = document.getElementById('successMessageModal');
        var previewModal = document.getElementById('previewModal');
        successMessageModal.style.display = 'none';
        previewModal.style.display = 'none';
    }
</script>