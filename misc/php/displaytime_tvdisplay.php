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

<div class="form-column" style="flex: 1">
    <div class="floating-label-container" style="flex: 1">
        <select id="display_time" name="display_time" class="floating-label-input" required style="background: #FFFF; padding-left: 8px">
            <option value="">Select Display Time</option>
            <?php for($i = $minTime; $i <= $maxTime; $i+=$displayInterval): ?>
                <option value="<?php echo $i; ?>" <?php echo ($displayTime == $i) ? 'selected' : ''; ?>>
                    <?php echo $i; ?> seconds
                </option>
            <?php endfor; ?>
        </select>
        <label for="display_time" class="floating-label">Display Time (seconds)</label>
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
        </div>
    </div>
</div>
<!-- TV Modal -->
<div id="tvModal" class="modal">
    <div class="modal-content" style="padding: 10px">
        <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-tv" aria-hidden="true"></i></h1>
        <p>Select TV Displays</p>
        <br>
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

        // Open the TV Modal
        tvModalButton.addEventListener("click", function () {
            tvModal.style.display = "flex";
        });

        // Close the TV Modal
        closeTvModal.addEventListener("click", function () {
            tvModal.style.display = "none";
        });

        // Save the selected TV displays
        saveTvSelection.addEventListener("click", function () {
            const selectedTvs = [];
            document.querySelectorAll('#tvCheckboxList input[type="checkbox"]:checked').forEach(function (checkedBox) {
                selectedTvs.push(checkedBox.getAttribute('data-tv-name'));
            });
            
            if (selectedTvs.length > 0) {
                if (selectedTvs.length === 1) {
                    tvModalButton.textContent = `${selectedTvs.length} TV Selected`;
                } else {
                    tvModalButton.textContent = `${selectedTvs.length} TVs Selected`;
                }
            } else {
                tvModalButton.textContent = "Select TV Displays";
            }
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
            // selectedTvNames.push(checkbox.getAttribute('data-tv-name'));
        }
    });

    // Update the TV button text
    const tvModalButton = document.getElementById('tvModalButton');
    if (selectedTvNames.length > 0) {
        tvModalButton.textContent = selectedTvNames.join(", ");
    }
});
</script>
<?php endif; ?>