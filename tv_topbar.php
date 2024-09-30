<div class="topbar" id="topbar" style="background: <?php echo $topbarColor; ?>">
    <img src="images/soe_icon.png" alt="" style="width: 3vh; height: 3vh; margin-left: 5px">
    <div class="tv-id" style="color: <?php echo $topbarTvIdColor; ?>; font-style: <?php echo $topbarTvIdFontStyle; ?>; font-family: <?php echo $topbarTvIdFontFamily; ?>;">
        <p>TV ID:</p>
        <?php echo htmlspecialchars($_SESSION['tv_id']); ?>
    </div>
    <h1 class="tv-name" style="color: <?php echo $topbarTvNameColor; ?>; font-style: <?php echo $topbarTvNameFontStyle; ?>; font-family: <?php echo $topbarTvNameFontFamily; ?>;">
        <?php echo htmlspecialchars($_SESSION['tv_name']); ?>
    </h1>
    <div class="date-time">
        <span id="live-clock" class="time" style="color: <?php echo $topbarTimeColor; ?>; font-style: <?php echo $topbarTimeFontStyle; ?>; font-family: <?php echo $topbarTimeFontFamily; ?>;"></span>
        <span id="live-date" class="date" style="color: <?php echo $topbarDateColor; ?>; font-style: <?php echo $topbarDateFontStyle; ?>; font-family: <?php echo $topbarDateFontFamily; ?>;"></span>
    </div>
</div>