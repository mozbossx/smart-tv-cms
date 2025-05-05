<style>
    .radio-option {
        display: flex;
        margin-bottom: 7px;
        background: #f3f3f3;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .radio-option:hover {
        background-color: #f5f5f5;
    }

    .radio-option input[type="radio"] {
        margin-right: 10px;
        margin-left: 10px;
    }

    .radio-option label {
        cursor: pointer;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-right: 10px;
    }
</style>
<?php
$displayTime = ($isEditMode && isset($contentData['display_time'])) 
    ? $contentData['display_time'] 
    : '';

// Fetch selected TVs if in edit mode
$selectedTvs = [];
if ($isEditMode && isset($contentData["{$type}_id"])) {
    if ($type == 'news' || $type == 'peo' || $type == 'so'){
        $stmt = $conn->prepare("SELECT tv_id FROM {$type}_tb WHERE {$type}_id = ? AND tv_id = ?");
    } else if ($type == 'announcement' || $type == 'event' || $type == 'promaterial'){
        $stmt = $conn->prepare("SELECT tv_id FROM {$type}s_tb WHERE {$type}_id = ? AND tv_id = ?");
    } else {
        $stmt = $conn->prepare("SELECT tv_id FROM {$type}_tb WHERE {$type}_id = ? AND tv_id = ?");
    }
    $stmt->bind_param("ii", $contentData["{$type}_id"], $contentData['tv_id']);
    $stmt->execute();
    $tvResult = $stmt->get_result();
    while ($row = $tvResult->fetch_assoc()) {
        $selectedTvs[] = $row['tv_id'];
    }
}
$displayTimeQuery = $conn->query("SELECT display_seconds_range_first, display_seconds_range_last, display_interval FROM display_time_tb LIMIT 1");
$displayTimeRange = $displayTimeQuery->fetch_assoc();
$minTime = $displayTimeRange['display_seconds_range_first'];
$maxTime = $displayTimeRange['display_seconds_range_last'];
$displayInterval = $displayTimeRange['display_interval'];
?>

<div class="form-column" style="flex: 1; margin-top: 3px">
    <div class="form-column" style="flex: 1">
        <div class="floating-label-container" style="flex: 1">
            <button type="button" id="displayTimeModalButton" class="floating-label-input" style="background: #FFFF;">
                Select Display Time
            </button>
            <label for="display_time" class="floating-label">Display Time (seconds)</label>
            <div id="display-time-message" style="display: none; margin-top: 5px; margin-bottom: 5px; margin-left: 10px; font-size: 14px;"></div>
            <input type="hidden" name="display_time" id="display_time" value="<?php echo htmlspecialchars($displayTime); ?>" required>
        </div>
    </div>
    <div class="form-column" style="flex: 1">
        <div class="floating-label-container" style="flex: 1">
            <?php if ($isEditMode): ?>
                <!-- In edit mode, show a disabled input instead of a button -->
                <input type="text" 
                       class="floating-label-input" 
                       style="background: none; border: none; box-shadow: none" 
                       value="<?php echo htmlspecialchars($tv_names[$editTvId] ?? ''); ?>" 
                       disabled>
            <?php else: ?>
                <button type="button" id="tvModalButton" class="floating-label-input" style="background: #FFFF;">
                    Select TV Displays
                </button>
            <?php endif; ?>
            <label for="tv_id" class="floating-label">TV Display</label>
            <div id="tv-display-message" style="display: none; margin-top: 5px; margin-bottom: 5px; margin-left: 10px; font-size: 14px;"></div>
        </div>
    </div>
</div>
<div id="displayTimeModal" class="modal">
    <div class="modal-content" style="padding: 15px">
        <h1 style="color: <?php echo $theme_color; ?>; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-clock-o"></i> Display Time (seconds)</h1>
        <div id="displayTimeList" style="height: 150px; max-height: 200px; overflow: auto; text-align: left;">
            <?php for($i = $minTime; $i <= $maxTime; $i+=$displayInterval): ?>
                <div class="radio-option">
                    <input type="radio" 
                           id="time_<?php echo $i; ?>" 
                           name="display_time" 
                           value="<?php echo $i; ?>"
                           <?php echo ($displayTime == $i) ? 'checked' : ''; ?>>
                    <label for="time_<?php echo $i; ?>" style="width: 100%;"><?php echo $i; ?> seconds</label>
                </div>
            <?php endfor; ?>
        </div>
        <div style="display: flex; float: right">
            <button type="button" id="closeDisplayTimeModal" class="grey-button" style="margin-top: 10px; margin-right: 5px">Cancel</button>
            <button type="button" id="saveDisplayTimeInput" class="green-button" style="border: 1px solid #264B2B; margin-right: 0">Save</button>
        </div>
    </div>
</div>

<!-- TV Modal -->
<div id="tvModal" class="modal">
    <div class="modal-content" style="padding: 15px">
        <h1 style="color: <?php echo $theme_color; ?>; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-tv"></i> TV Displays</h1>
        <div id="tvCheckboxList" style="height: 150px; max-height: 200px; overflow: auto; text-align: left;">
            <?= $options_tv; ?>
        </div>
        <div style="display: flex; float: right">
            <button type="button" id="closeTvModal" class="grey-button" style="margin-top: 10px; margin-right: 5px">Cancel</button>
            <button type="button" id="saveTvSelection" class="green-button" style="border: 1px solid #264B2B; margin-right: 0">Save</button>
        </div>
    </div>
</div>

<?php if (!$isEditMode): ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const tvModalButton = document.getElementById("tvModalButton");
        const tvModal = document.getElementById("tvModal");
        const closeTvModal = document.getElementById("closeTvModal");
        const saveTvSelection = document.getElementById("saveTvSelection");
        const displayTimeModalButton = document.getElementById("displayTimeModalButton");
        const displayTimeModal = document.getElementById("displayTimeModal");
        const closeDisplayTimeModal = document.getElementById("closeDisplayTimeModal");
        const saveDisplayTimeInput = document.getElementById("saveDisplayTimeInput");
        const displayTimeInput = document.getElementById("display_time");
        const displayTimeMessage = document.getElementById("display-time-message");
        const tvDisplayMessage = document.getElementById("tv-display-message");

        // Update initial button text for pre-selected TVs
        const updateButtonText = () => {
            const selectedTvs = [];
            document.querySelectorAll('#tvCheckboxList input[type="checkbox"]:checked').forEach(function (checkedBox) {
                selectedTvs.push(checkedBox.getAttribute('data-tv-name'));
            });
            
            if (selectedTvs.length > 0) {
                if (selectedTvs.length === 1) {
                    tvModalButton.textContent = `${selectedTvs.length} TV Selected`;
                    // Show success message
                    tvDisplayMessage.style.color = 'green';
                    tvDisplayMessage.innerHTML = `<i class="fa fa-check-circle"></i> TV Display set to ${selectedTvs[0]}`;
                } else {
                    tvModalButton.textContent = `${selectedTvs.length} TVs Selected`;
                    // Show success message
                    tvDisplayMessage.style.color = 'green';
                    tvDisplayMessage.innerHTML = `<i class="fa fa-check-circle"></i> ${selectedTvs.length} TV Displays selected`;
                }
                tvDisplayMessage.style.display = 'block';
            } else {
                tvModalButton.textContent = "Select TV Displays";
                tvDisplayMessage.innerHTML = `<i class="fa fa-exclamation-triangle"></i> Must select at least one TV Display`;
                tvDisplayMessage.style.color = 'red';
                tvDisplayMessage.style.display = 'block';
            }
        };

        // Call immediately to set initial state
        updateButtonText();

        // Open the TV Modal
        tvModalButton.addEventListener("click", function () {
            tvModal.style.display = "flex";
        });

        displayTimeModalButton.addEventListener("click", function () {
            // Check if there's a video preview
            const videoPreview = document.getElementById('video-preview');
            if (videoPreview && videoPreview.style.display !== 'none') {
                // If video is displayed, don't show the modal
                return;
            }
            displayTimeModal.style.display = "flex";
        });

        // Close the TV Modal
        closeTvModal.addEventListener("click", function () {
            tvModal.style.display = "none";
        });

        // Close the Display Time Modal
        closeDisplayTimeModal.addEventListener("click", function () {
            displayTimeModal.style.display = "none";
        });

        saveDisplayTimeInput.addEventListener("click", function() {
            const selectedTime = document.querySelector('input[name="display_time"]:checked');
            if (selectedTime) {
                displayTimeInput.value = selectedTime.value;
                displayTimeModalButton.textContent = `${selectedTime.value} seconds`;
                displayTimeMessage.style.color = 'green';
                displayTimeMessage.innerHTML = `<i class="fa fa-check-circle"></i> Display time set to ${selectedTime.value} seconds`;
                displayTimeMessage.style.display = 'block';
                displayTimeModal.style.display = "none";
            } else {
                displayTimeMessage.style.color = 'red';
                displayTimeMessage.innerHTML = `<i class="fa fa-exclamation-triangle"></i> Must select a display time`;
                displayTimeMessage.style.display = 'block';
            }
        });

        if (displayTimeInput.value) {
            displayTimeModalButton.textContent = `${displayTimeInput.value} seconds`;
            displayTimeMessage.style.color = 'green';
            displayTimeMessage.innerHTML = `<i class="fa fa-check-circle"></i> Display time set to ${displayTimeInput.value} seconds`;
            displayTimeMessage.style.display = 'block';
        }

        // Save the selected TV displays
        // saveTvSelection.addEventListener("click", function () {
        //     const selectedTvs = [];
        //     document.querySelectorAll('#tvCheckboxList input[type="checkbox"]:checked').forEach(function (checkedBox) {
        //         selectedTvs.push(checkedBox.getAttribute('data-tv-name'));
        //     });
            
        //     if (selectedTvs.length > 0) {
        //         if (selectedTvs.length === 1) {
        //             tvModalButton.textContent = `${selectedTvs.length} TV Selected`;
        //         } else {
        //             tvModalButton.textContent = `${selectedTvs.length} TVs Selected`;
        //         }
        //     } else {
        //         tvModalButton.textContent = "Select TV Displays";
        //     }
        //     tvModal.style.display = "none";
        // });

        saveTvSelection.addEventListener("click", function () {
            updateButtonText();
            tvModal.style.display = "none";
        });

        // Close the modal if the user clicks outside of it
        window.onclick = function (event) {
            if (event.target == tvModal) {
                tvModal.style.display = "none";
            }
        };
    });
</script>
<?php endif; ?>

<?php if ($isEditMode): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pre-select TVs
    const selectedTvs = <?php echo json_encode($selectedTvs); ?>;
    const selectedTvNames = [];
    
    selectedTvs.forEach(tvId => {
        const checkbox = document.querySelector(`input[name="tv_id[]"][value="${tvId}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
});
</script>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Common elements for both edit and non-edit modes
    const displayTimeModalButton = document.getElementById("displayTimeModalButton");
    const displayTimeModal = document.getElementById("displayTimeModal");
    const closeDisplayTimeModal = document.getElementById("closeDisplayTimeModal");
    const saveDisplayTimeInput = document.getElementById("saveDisplayTimeInput");
    const displayTimeInput = document.getElementById("display_time");
    const displayTimeMessage = document.getElementById("display-time-message");

    // Display Time Modal Functions
    displayTimeModalButton.addEventListener("click", function() {
        displayTimeModal.style.display = "flex";
    });

    closeDisplayTimeModal.addEventListener("click", function() {
        displayTimeModal.style.display = "none";
    });

    saveDisplayTimeInput.addEventListener("click", function() {
        const selectedTime = document.querySelector('input[name="display_time"]:checked');
        if (selectedTime) {
            displayTimeInput.value = selectedTime.value;
            displayTimeModalButton.textContent = `${selectedTime.value} seconds`;
            displayTimeMessage.style.color = 'green';
            displayTimeMessage.innerHTML = `<i class="fa fa-check-circle"></i> Display time set to ${selectedTime.value} seconds`;
            displayTimeMessage.style.display = 'block';
            displayTimeModal.style.display = "none";
        } else {
            displayTimeMessage.style.color = 'red';
            displayTimeMessage.innerHTML = `<i class="fa fa-exclamation-triangle"></i> Must select a display time`;
            displayTimeMessage.style.display = 'block';
        }
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target == displayTimeModal) {
            displayTimeModal.style.display = "none";
        }
    });

    // Initialize display time button text if there's a pre-selected value
    if (displayTimeInput.value) {
        displayTimeModalButton.textContent = `${displayTimeInput.value} seconds`;
        displayTimeMessage.style.color = 'green';
        displayTimeMessage.innerHTML = `<i class="fa fa-check-circle"></i> Display time set to ${displayTimeInput.value} seconds`;
        displayTimeMessage.style.display = 'block';
    }
});
</script>