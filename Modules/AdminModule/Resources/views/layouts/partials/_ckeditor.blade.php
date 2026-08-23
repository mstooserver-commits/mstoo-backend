<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
    if (window.CKEDITOR) {
        CKEDITOR.config.versionCheck = false;
    }

    $(function () {
        $('textarea.ckeditor').each(function () {
            if (!this.id) {
                this.id = 'ckeditor-' + Math.random().toString(36).slice(2, 10);
            }
            if (window.CKEDITOR && CKEDITOR.instances[this.id]) {
                return;
            }
            CKEDITOR.replace(this.id);
        });
    });
</script>
