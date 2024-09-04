<div class="form-column" style="flex: 1">
    <div class="floating-label-container" style="flex: 1">
        <select id="display_time" name="display_time" class="floating-label-input" required style="background: #FFFF; padding-left: 8px">
            <option value="">Select Display Time</option>
            <option value="10">10 seconds</option>
            <option value="11">11 seconds</option>
            <option value="12">12 seconds</option>
            <option value="13">13 seconds</option>
            <option value="14">14 seconds</option>
            <option value="15">15 seconds</option>
            <option value="16">16 seconds</option>
            <option value="17">17 seconds</option>
            <option value="18">18 seconds</option>
            <option value="19">19 seconds</option>
            <option value="20">20 seconds</option>
            <option value="21">21 seconds</option>
            <option value="22">22 seconds</option>
            <option value="23">23 seconds</option>
            <option value="24">24 seconds</option>
            <option value="25">25 seconds</option>
            <option value="26">26 seconds</option>
            <option value="27">27 seconds</option>
            <option value="28">28 seconds</option>
            <option value="29">29 seconds</option>
            <option value="30">30 seconds</option>
        </select>
        <label for="display_time" class="floating-label">Display Time (seconds)</label>
    </div>
    <div class="form-column" style="flex: 1">
        <div class="floating-label-container" style="flex: 1">
            <button type="button" id="tvModalButton" class="floating-label-input" style="background: #FFFF">
                Select TV Displays
            </button>
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
        <div id="tvCheckboxList" style="height: 150px; max-height: 200px; overflow: auto; text-align: left">
            <?= $options_tv; ?>
        </div>
        <div style="display: flex; float: right">
            <button type="button" id="closeTvModal" class="green-button" style="background: none; border: 1px solid #264B2B; color: #264B2B">Cancel</button>
            <button type="button" id="saveTvSelection" class="green-button" style="border: 1px solid #264B2B; margin-right: 0">Save</button>
        </div>
    </div>
</div>

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
                selectedTvs.push(checkedBox.getAttribute('data-tv-name')); // Change to get tv_name
            });
            tvModalButton.textContent = selectedTvs.length > 0 ? selectedTvs.join(", ") : "Select TV Displays";
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