<div class="modal fade" id="modalPreviewImage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalPreviewImageSrc" src="" alt="Preview" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-zoom-image').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const src = btn.dataset.src;
                document.getElementById('modalPreviewImageSrc').src = src;
                const modal = new bootstrap.Modal(document.getElementById('modalPreviewImage'));
                modal.show();
            });
        });
    });
</script>
