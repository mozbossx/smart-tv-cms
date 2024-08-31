<?php
// Schedule Post Modal
echo '<div id="schedulePostModal" class="modal">';
    echo '<div class="modal-content">';
        echo '<div class="rounded-container-column">';
            echo '<p class="input-container-label">Schedule Date & Time</p>';
            echo '<div class="left-flex">';
                echo '<input type="date" id="schedule_date" name="schedule_date" class="input-date" placeholder="Schedule Date">';
            echo '</div>';
            echo '<div class="right-flex">';
                echo '<input type="time" id="schedule_time" name="schedule_time" class="input-time" placeholder="Schedule Time">';
            echo '</div>';
        echo '</div>';
        echo '<div style="display: flex; float: right; margin-top: 5px">';
            echo '<button type="button" id="clearSchedulePost" class="green-button" style="background: none; border: none; color: #264B2B">Clear</button>';
            echo '<button type="button" id="closeSchedulePostModal" class="green-button" style="background: none; border: 1px solid #264B2B; color: #264B2B">Cancel</button>';
            echo '<button type="button" id="saveSchedulePostSelection" class="green-button" style="border: 1px solid #264B2B; margin-right: 0">Save</button>';
        echo '</div>';
    echo '</div>';
echo '</div>';
?>

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
            const scheduleDate = document.getElementById('schedule_date').value;
            const scheduleTime = document.getElementById('schedule_time').value;

            // Print the values inside the form or wherever needed
            document.getElementById('schedulePostButton').style = `
                border: 1px solid #264B2B;
                background: #264B2B;
                color: white;
            `;

            schedulePostModal.style.display = "none";
        });

        clearSchedulePost.addEventListener('click', function () {
            document.getElementById('schedule_date').value = null;
            document.getElementById('schedule_time').value = null;
        });

        // Close the modal if the user clicks outside of it
        window.onclick = function (event) {
            if (event.target == schedulePostModal) {
                schedulePostModal.style.display = "none";
            }
        };
    });
</script>