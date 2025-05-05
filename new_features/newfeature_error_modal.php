<div id="errorModalVersion2" class="modal">
    <div class="modal-content" style="padding: 15px">
        <h1 style="color: <?php echo $dark_theme_color_40_percent; ?>; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-exclamation-circle"></i> Error</h1>
        <p id="errorTextVersion2" style="text-align: left; max-height: 200px; overflow: auto"></P>
        <br>
        <div style="align-items: right; text-align: right; right: 0">
            <button type="button" id="okayButtonVersion2" class="red-button" style="margin: 0" onclick="closeErrorModalVersion2()">Okay</button>
        </div>
    </div>
</div>

<script>
    document.getElementById('okayButtonVersion2').addEventListener('click', function() {
        closeErrorModalVersion2();
    });

    function closeErrorModalVersion2() {
        var modal = document.getElementById('errorModalVersion2');
        modal.style.display = 'none';
    }

    function errorModalMessage(errorMessage) {
        var modal = document.getElementById('errorModalVersion2');
        modal.style.display = 'flex';

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Display error message
        document.getElementById('errorTextVersion2').textContent = errorMessage;

        // Okay Button click event
        document.getElementById('okayButtonVersion2').addEventListener('click', function () {
            modal.style.display = 'none';
        });
    }
</script>