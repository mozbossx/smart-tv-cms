<!-- Schedule Post Modal -->
<div id="schedulePostModal" class="modal">
    <div class="modal-content">
        <div class="rounded-container-column">
            <p class="input-container-label">Schedule Date & Time</p>
            <input type="datetime-local" id="schedule_datetime" name="schedule_datetime" class="input-datetime">
        </div>
        <div style="display: flex; float: right; margin-top: 5px">
            <button type="button" id="clearSchedulePost" class="green-button" style="background: none; border: none; color: #264B2B">Clear</button>
            <button type="button" id="closeSchedulePostModal" class="green-button" style="background: none; border: 1px solid #264B2B; color: #264B2B">Cancel</button>
            <button type="button" id="saveSchedulePostSelection" class="green-button" style="border: 1px solid #264B2B; margin-right: 0">Save</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const schedulePostButton = document.getElementById("schedulePostButton");
        const schedulePostModal = document.getElementById("schedulePostModal");
        const closeSchedulePostModal = document.getElementById("closeSchedulePostModal");
        const saveSchedulePostSelection = document.getElementById("saveSchedulePostSelection");
        const clearSchedulePost = document.getElementById("clearSchedulePost");

        schedulePostButton.addEventListener("click", function () {
            schedulePostModal.style.display = "flex";
        });

        closeSchedulePostModal.addEventListener("click", function () {
            schedulePostModal.style.display = "none";
        });

        saveSchedulePostSelection.addEventListener("click", function () {
            const scheduleDateTime = document.getElementById('schedule_datetime').value;
            
            if (scheduleDateTime != null || scheduleDateTime != "") {
                document.getElementById('schedulePostButton').style = `
                    border: 1px solid #264B2B;
                    background: #264B2B;
                    color: white;
                `;
            }
            schedulePostModal.style.display = "none";
        });

        clearSchedulePost.addEventListener('click', function () {
            document.getElementById('schedule_datetime').value = null;

            document.getElementById('schedulePostButton').style = `
                border: 1px solid #264B2B;
                background: none;
                color: #264B2B;
            `;
        });

        // Close the modal if the user clicks outside of it
        window.onclick = function (event) {
            if (event.target == schedulePostModal) {
                schedulePostModal.style.display = "none";
            }
        };
    });
</script>