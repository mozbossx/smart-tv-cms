<?php
echo '<div style="display: flex; flex-direction: row-reverse">';
echo '<a href="#" id="schedulePostOption" class="green-button" style="text-decoration: none; width: 100px; margin-right: 0; margin-bottom: 5px" onclick="displaySchedulePostInputs()">Schedule Post<i class="fa fa-clock-o" style="padding-left: 5px"></i></a>';
echo '<a href="#" id="cancelSchedulePostOption" style="display: none; text-decoration: none; width: 135px; margin-right: 0; margin-bottom: 5px" class="green-button" onclick="cancelSchedulePostInputs()">Cancel Schedule Post<i class="fa fa-times" style="padding-left: 5px"></i></a>';
echo '</div>';
echo '<div id="schedulePostInputs" class="schedule-post-inputs">';
echo '<div class="rounded-container-column">';
    echo '<p class="input-container-label">Schedule Post Date & Time (Optional)</p>';
    echo '<div class="left-flex">';
        echo '<input type="date" id="schedule_date" name="schedule_date" class="input-date">';
    echo '</div>';
    echo '<div class="right-flex">';
        echo '<input type="time" id="schedule_time" name="schedule_time" class="input-time">';
    echo '</div>';
echo '</div>';
echo '</div>';
?>

<script>
    // Function to display the Schedule Post inputs
    function displaySchedulePostInputs() {
        var schedulePostOption = document.getElementById("schedulePostOption");
        schedulePostOption.style.display = "none";

        var cancelSchedulePostOption = document.getElementById("cancelSchedulePostOption");
        cancelSchedulePostOption.style.display = "block";

        var schedulePostInputs = document.getElementById("schedulePostInputs");
        schedulePostInputs.classList.remove('hide'); // Remove hide class if present
        schedulePostInputs.style.display = "block"; // Ensure it's displayed before adding class
        schedulePostInputs.classList.add('show'); // Trigger the slide-in animation
    }

    function cancelSchedulePostInputs() {
        var schedulePostInputs = document.getElementById("schedulePostInputs");
        schedulePostInputs.classList.remove('show'); // Remove show class to start slide-out animation
        schedulePostInputs.classList.add('hide'); // Add hide class for slide-out effect

        var schedulePostOption = document.getElementById("schedulePostOption");
        schedulePostOption.style.display = "block";

        var cancelSchedulePostOption = document.getElementById("cancelSchedulePostOption");
        cancelSchedulePostOption.style.display = "none";

        // Hide the element after the animation is done (500ms)
        setTimeout(function() {
            schedulePostInputs.style.display = "none"; 
        }, 500);

        document.getElementById("schedule_date").value = null; 
        document.getElementById("schedule_time").value = null; 
    }
</script>