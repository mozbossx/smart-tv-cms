<?php
$expirationDateTime = ($isEditMode && isset($contentData['expiration_datetime'])) 
    ? $contentData['expiration_datetime'] 
    : '';
?>

<div class="rounded-container-column" style="flex: 1">
    <p class="input-container-label">Expiration Date & Time</p>
    <input type="datetime-local" 
           id="expiration_datetime" 
           name="expiration_datetime" 
           class="input-datetime"
           value="<?php echo htmlspecialchars($expirationDateTime); ?>">
</div>
