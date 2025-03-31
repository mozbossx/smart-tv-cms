<div id="successMessageModal" class="modal">
    <div class="modal-content" style="padding: 15px">
        <h1 style="color: <?php echo $dark_theme_color_40_percent; ?>; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-check-circle"></i> Success</h1>
        <p id="successMessage" style="text-align: left"></P>
        <br>
        <div style="align-items: right; text-align: right; right: 0">
            <?php
                if (isset($_GET['action']) && $_GET['action'] === 'edit'){
                    echo '<button type="button" id="backToTvContentsButton" class="green-button" style="background: none; border: 1px solid ' . $theme_color . '; color: ' . $theme_color . ';" onclick="window.location.href=\'tv_contents.php?tvId=' . $_GET['tv_id'] . '&isIframe=true\'">Back to View Contents</button>';
                }
            ?>
            <button type="button" id="homeButton" class="green-button" style="margin-right: 0" onclick="resetFormAndGoHome()">Home</button>
        </div>
    </div>
</div>
<div id="successMessageModalVersion2" class="modal">
    <div class="modal-content" style="padding: 15px">
        <h1 style="color: <?php echo $dark_theme_color_40_percent; ?>; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-check-circle"></i> Success</h1>
        <p id="successMessageVersion2" style="text-align: left; max-height: 200px; overflow: auto"></P>
        <br>
        <div style="align-items: right; text-align: right; right: 0">
            <button type="button" id="okayButton" class="green-button" style="margin-right: 0" onclick="closeSuccessMessageModalVersion2()">Okay</button>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="modal">
    <div class="modal-content" style="padding: 15px; text-align: center;">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin" style="font-size: 50px; color: <?php echo $theme_color; ?>"></i>
        </div>
        <p id="loadingMessage">Uploading content, please wait...</p>
    </div>
</div>

<script>
    if (document.getElementById('createMoreButton')) {  
        document.getElementById('createMoreButton').addEventListener('click', function() {
            closeSuccessMessageModal();
        });
    }

    document.getElementById('okayButton').addEventListener('click', function() {
        closeSuccessMessageModalVersion2();
    });

    if (document.getElementById('backToTvContentsButton')) {    
        document.getElementById('backToTvContentsButton').addEventListener('click', function() {
            closeSuccessMessageModal();
        });
    }

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