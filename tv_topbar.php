<div class="topbar" id="topbar" style="background: <?php echo $topbarColor; ?>">
    <img src="images/soe_icon.png" alt="" style="width: 3vh; height: 3vh; margin-left: 5px">
    <div class="tv-id" style="color: <?php echo $topbarTvIdColor; ?>; font-style: <?php echo $topbarTvIdFontStyle; ?>; font-family: <?php echo $topbarTvIdFontFamily; ?>;">
        <p id="tvDepartment_<?php echo $_SESSION['tv_id']; ?>"><?php echo htmlspecialchars($_SESSION['tv_department']); ?></p>
        <p id="tv-id"><i class="fa fa-television" aria-hidden="true"></i>: <?php echo htmlspecialchars($_SESSION['tv_id']); ?></p>
    </div>
    <h1 class="tv-name" style="color: <?php echo $topbarTvNameColor; ?>; font-style: <?php echo $topbarTvNameFontStyle; ?>; font-family: <?php echo $topbarTvNameFontFamily; ?>;">
        <?php echo htmlspecialchars($_SESSION['tv_name']); ?>
    </h1>
    <div class="date-time">
        <span id="live-clock" class="time" style="color: <?php echo $topbarTimeColor; ?>; font-style: <?php echo $topbarTimeFontStyle; ?>; font-family: <?php echo $topbarTimeFontFamily; ?>;"></span>
        <span id="live-date" class="date" style="color: <?php echo $topbarDateColor; ?>; font-style: <?php echo $topbarDateFontStyle; ?>; font-family: <?php echo $topbarDateFontFamily; ?>;"></span>
    </div>
</div>