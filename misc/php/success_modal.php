<div id="successMessageModal" class="modal">
    <div class="modal-content">
        <div class="green-bar-vertical">
            <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
            <p id="successMessage"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button type="button" id="createMoreButton" class="green-button" style="background: none; border: 1px solid #264B2B; color: #264B2B;" onclick="closeSuccessMessageModal()">Make more</button>
                <button type="button" id="homeButton" class="green-button" style="margin-right: 0" onclick="resetFormAndGoHome()">Home</button>
            </div>
        </div>
    </div>
</div>
<div id="successMessageModalVersion2" class="modal">
    <div class="modal-content">
        <div class="green-bar-vertical">
            <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
            <p id="successMessageVersion2" style="max-height: 200px; overflow: auto"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button type="button" id="okayButton" class="green-button" style="margin-right: 0" onclick="closeSuccessMessageModalVersion2()">Okay</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('createMoreButton').addEventListener('click', function() {
        closeSuccessMessageModal();
    });

    document.getElementById('okayButton').addEventListener('click', function() {
        closeSuccessMessageModalVersion2();
    });

    function closeSuccessMessageModal() {
        var successMessageModal = document.getElementById('successMessageModal');
        var previewModal = document.getElementById('previewModal');
        isFormDirty = false;
        successMessageModal.style.display = 'none';
        previewModal.style.display = 'none';
    }

    function closeSuccessMessageModalVersion2() {
        var successMessageModalVersion2 = document.getElementById('successMessageModalVersion2');
        successMessageModalVersion2.style.display = 'none';
    }
</script>