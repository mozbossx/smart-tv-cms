<div id="errorModal" class="modal">
    <div class="modal-content">
        <div class="red-bar-vertical">
            <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></h1>
            <p id="errorText"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button type="button" id="okayButton" class="red-button" style="margin: 0" onclick="closeErrorModal()">Okay</button>
            </div>
        </div>
    </div>
</div>
<div id="errorModalVersion2" class="modal">
    <div class="modal-content">
        <div class="red-bar-vertical">
            <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></h1>
            <p id="errorTextVersion2"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button type="button" id="okayButtonVersion2" class="red-button" style="margin: 0" onclick="closeErrorModalVersion2()">Okay</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('okayButton').addEventListener('click', function() {
        closeErrorModal();
    });

    document.getElementById('okayButtonVersion2').addEventListener('click', function() {
        closeErrorModalVersion2();
    });

    // Function to close the preview modal
    function closeErrorModal() {
        var modal = document.getElementById('errorModal');
        modal.style.display = 'none';
    }

    function closeErrorModalVersion2() {
        var modal = document.getElementById('errorModalVersion2');
        modal.style.display = 'none';
    }

    function errorModalMessage(errorMessage) {
        var modal = document.getElementById('errorModal');
        modal.style.display = 'flex';

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Display error message
        document.getElementById('errorText').textContent = errorMessage;

        // Okay Button click event
        document.getElementById('okayButton').addEventListener('click', function () {
            modal.style.display = 'none';
        });
    }
</script>