<script>
    (function () {
        document.querySelectorAll('.mstoo-perm-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('.mstoo-perm-module').classList.toggle('is-open');
            });
        });
        document.querySelectorAll('.mstoo-perm-select-all').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('.mstoo-perm-module').querySelectorAll('.mstoo-permission-box:not(:disabled)').forEach(function (box) {
                    box.checked = true;
                });
            });
        });
        document.querySelectorAll('.mstoo-perm-clear').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('.mstoo-perm-module').querySelectorAll('.mstoo-permission-box:not(:disabled)').forEach(function (box) {
                    box.checked = false;
                });
            });
        });
    })();
</script>
