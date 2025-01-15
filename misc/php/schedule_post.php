<!-- Schedule Post Modal -->
<div id="schedulePostModal" class="modal">
    <div class="modal-content" style="padding: 15px">
        <div class="rounded-container-column">
            <p class="input-container-label">Schedule Date & Time</p>
            <input type="datetime-local" id="schedule_datetime" name="schedule_datetime" class="input-datetime">
        </div>
        <br>
        <div style="text-align: right">
            <button type="button" id="clearSchedulePost" class="green-button" style="background: none; border: none; color: <?php echo $theme_color; ?>">Clear</button>
            <button type="button" id="closeSchedulePostModal" class="grey-button">Cancel</button>
            <button type="button" id="saveSchedulePostSelection" class="green-button" style="border: 1px solid <?php echo $theme_color; ?>; margin-right: 0">Save</button>
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
            const schedulePostButton = document.getElementById('schedulePostButton');
            
            // Fixed the condition to properly check for empty value
            if (scheduleDateTime && scheduleDateTime.trim() !== "") {
                const date = new Date(scheduleDateTime);
                const formattedDate = date.toLocaleDateString('en-GB', { 
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                const formattedTime = date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }).toLowerCase();

                schedulePostButton.style = `
                    font-weight: bold;
                `;
                schedulePostButton.textContent = `${formattedDate} ${formattedTime}`;
            } else {
                schedulePostButton.style = `
                    font-weight: normal;
                    background: none;
                    border: 1px solid <?php echo $theme_color; ?>;
                    color: <?php echo $theme_color; ?>;
                `;
                schedulePostButton.innerHTML = '<i class="fa fa-calendar" style="padding-right: 5px"></i> Schedule Post';  // Reset to default text
            }
            schedulePostModal.style.display = "none";
        });

        clearSchedulePost.addEventListener('click', function () {
            const schedulePostButton = document.getElementById('schedulePostButton');
            document.getElementById('schedule_datetime').value = "";
            
            schedulePostButton.style = `
                border: 1px solid <?php echo $theme_color; ?>;
                background: none;
                color: <?php echo $theme_color; ?>;
                font-weight: normal;
            `;
            schedulePostButton.innerHTML = '<i class="fa fa-calendar" style="padding-right: 5px"></i> Schedule Post';  // Reset to default text
        });
    });
</script>