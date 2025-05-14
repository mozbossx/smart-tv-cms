<!-- Urgent Post is only available for Super Admin, Admin, Faculty, and Staff Users -->
<?php if ($user_type !== 'Student' && !$isEditMode): ?>
<label class="option-div" style="box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px; background: #ffffff; margin-top: 10px; cursor: pointer; margin-bottom: 5px" title="Urgent posts will be displayed first">
    <input type="checkbox" id="isUrgentPost" name="isUrgentPost" style="margin-right: 10px;">
    <label for="isUrgentPost">Urgent Post <i class="fa fa-exclamation-circle" style="color: <?php echo $theme_color; ?>; margin-left: 5px"></i></label>
</label>
<div id="urgent-post-success-message" style="display: none; margin-top: 3px; margin-left: 10px; font-size: 14px; "></div>
<?php endif; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Only show the urgent post message if the user is not a student
        const urgentCheckbox = document.getElementById("isUrgentPost");
        const messageDiv = document.getElementById("urgent-post-success-message");
        
        if (urgentCheckbox && messageDiv) {
            urgentCheckbox.addEventListener("change", function() {
                if(this.checked) {
                    messageDiv.innerHTML = '<i class="fa fa-check-circle" style="color: green;"></i> You set this post as URGENT';
                    messageDiv.style.color = 'green';
                    messageDiv.style.display = 'block';
                } else {
                        messageDiv.style.display = 'none';
                    }
            });
        }
    });
</script>