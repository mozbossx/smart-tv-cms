<?php
echo '<div class="form-row">';
echo '<div class="rounded-container-column" style="flex: 1">';
    echo '<p class="input-container-label">Expiration Date & Time</p>';
    echo '<div class="left-flex">';
        echo '<input type="date" id="expiration_date" name="expiration_date" class="input-date">';
    echo '</div>';
    echo '<div class="right-flex">';
        echo '<input type="time" id="expiration_time" name="expiration_time" class="input-time">';
    echo '</div>';
echo '</div>';
?>