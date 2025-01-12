<div id="successMessageModalVersion2" class="modal">
    <div class="modal-content" style="padding: 15px">
        <div class="green-bar-vertical">
            <h1 style="font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
            <p id="successMessageVersion2"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <?php
                    if (!isset($_GET['action']) || $_GET['action'] !== 'edit'){
                        echo '<button type="button" id="createMoreButton" class="green-button" style="background: none; border: 1px solid ' . $theme_color . '; color: ' . $theme_color . ';" onclick="closeSuccessMessageModalVersion2()">Make More</button>';
                    } else if (isset($_GET['action']) && $_GET['action'] === 'edit'){
                        echo '<button type="button" id="backToTvContentsButton" class="green-button" style="background: none; border: 1px solid ' . $theme_color . '; color: ' . $theme_color . ';" onclick="window.location.href=\'tv_contents.php?tvId=' . $_GET['tv_id'] . '&isIframe=true\'">Back to View Contents</button>';
                    }
                ?>
                <button type="button" id="okayButton" class="green-button" style="margin-right: 0" onclick="resetFormAndGoHome()">Home</button>
            </div>
        </div>
    </div>
</div>

<script>
    function closeSuccessMessageModalVersion2() {
        var successMessageModalVersion2 = document.getElementById('successMessageModalVersion2');
        var newFeaturePreviewModal = document.getElementById('newFeaturePreviewModal');

        newFeaturePreviewModal.style.display = 'none';
        successMessageModalVersion2.style.display = 'none';
    }

    function resetFormAndGoHome() {
        const contentType = document.querySelector('[name="type"]').value;
        const form = document.getElementById(`${contentType}Form`);
        const isEditMode = form.querySelector('input[name="action"]')?.value === 'edit';

        if (isEditMode) {
            // If in edit mode, go back to the TV contents page
            const tvId = form.querySelector('input[name="tv_id"]').value;
            location.href = `tv_contents.php?tvId=${tvId}&isIframe=true`;
        } else {
            // If in create mode, go to home page
            form.reset();
            location.href = `user_home.php?pageid=UserHome&userId=${user_id}&fullName=${full_name}`;
        }
    }
</script>