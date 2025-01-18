<div class="topbar" id="topbar" style="background: <?php echo $topbarColor; ?>">
    <div class="tv-id" style="color: <?php echo $topbarTvIdColor; ?>; font-style: <?php echo $topbarTvIdFontStyle; ?>; font-family: <?php echo $topbarTvIdFontFamily; ?>;">
        
        <?php 
            if ($departmentLogoPath == NULL) {
                echo '<img src="images/soe_icon.png" alt="" style="width: 6vh; height: 6vh;" id="department_logo_topbar">';
            } else {
                echo '<img src="servers/tv_department_logos/' . $_SESSION['tv_id'] . '/' . $departmentLogoPath . '" alt="" style="width: 6vh; height: 6vh;" id="department_logo_topbar">';
            }
        ?>
        <div class="tv-id-text">
            <p id="tvDepartment_<?php echo $_SESSION['tv_id']; ?>"><?php echo htmlspecialchars($_SESSION['tv_department']); ?></p>
            <p id="tv-id"><i class="fa fa-television" aria-hidden="true"></i>: <?php echo htmlspecialchars($_SESSION['tv_id']); ?></p>
        </div>
    </div>
    <h1 class="tv-name" style="color: <?php echo $topbarTvNameColor; ?>; font-style: <?php echo $topbarTvNameFontStyle; ?>; font-family: <?php echo $topbarTvNameFontFamily; ?>;">
        <?php echo htmlspecialchars($_SESSION['tv_name']); ?>
    </h1>
    <div class="date-time">
        <span id="live-clock" class="time" style="color: <?php echo $topbarTimeColor; ?>; font-style: <?php echo $topbarTimeFontStyle; ?>; font-family: <?php echo $topbarTimeFontFamily; ?>;"></span>
        <span id="live-date" class="date" style="color: <?php echo $topbarDateColor; ?>; font-style: <?php echo $topbarDateFontStyle; ?>; font-family: <?php echo $topbarDateFontFamily; ?>;"></span>
    </div>
</div>

<!-- Loading Websocket Connection Modal -->
<div id="loadingWebsocketConnectionModal" class="modal" style="z-index: 10000000000000;">
    <div class="modal-content" style="padding: 15px; text-align: center;">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin" style="font-size: 50px; color: <?php echo $topbarColor; ?>"></i>
        </div>
        <p id="loadingWebsocketConnectionMessage"></p>
    </div>
</div>

<!-- Success Websocket Connection Modal -->
<div id="successWebsocketConnectionModal" class="modal" style="z-index: 10000000000000;">
    <div class="modal-content" style="padding: 15px; text-align: center;">
        <h1 style="color: <?php echo $topbarColor; ?>; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
        <p id="successWebsocketConnectionMessage"></p>
    </div>
</div>