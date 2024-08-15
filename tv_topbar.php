<div class="topbar" id="topbar" style="background: <?php echo $topbarColor; ?>">
    <img src="images/soe_icon.png" alt="" style="width: 3vh; height: 3vh; margin-left: 5px">
    <div class="device-id" style="color: <?php echo $topbarDeviceIdColor; ?>">
        <p>Device ID:</p>
        <?php echo htmlspecialchars($_SESSION['device_id']); ?>
    </div>
    <h1 class="tv-name" style="color: <?php echo $topbarTvNameColor; ?>">
        <?php echo htmlspecialchars($_SESSION['tv_name']); ?>
    </h1>
    <div class="date-time">
        <span id="live-clock" style="color: <?php echo $topbarTimeColor; ?>"></span>
        <span id="live-date" style="color: <?php echo $topbarDateColor; ?>"></span>
    </div>
</div>