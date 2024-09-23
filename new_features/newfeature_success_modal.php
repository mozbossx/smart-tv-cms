<div id="successMessageModalVersion2" class="modal">
    <div class="modal-content">
        <div class="green-bar-vertical">
            <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
            <p id="successMessageVersion2"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button type="button" id="okayButton" class="green-button" style="margin-right: 0" onclick="closeSuccessMessageModalVersion2()">Okay</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('okayButton').addEventListener('click', function() {
        closeSuccessMessageModalVersion2();
    });

    function closeSuccessMessageModalVersion2() {
        var successMessageModalVersion2 = document.getElementById('successMessageModalVersion2');
        successMessageModalVersion2.style.display = 'none';
    }
</script>