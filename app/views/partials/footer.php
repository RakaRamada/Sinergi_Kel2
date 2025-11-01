<?php
// File: app/views/partials/footer.php
?>

</div>
</div>
<script>
// --- Skrip untuk Tombol Komentar (Script Global Anda) ---
const commentButtons = document.querySelectorAll('.comment-button');
commentButtons.forEach(button => {
    button.addEventListener('click', function() {
        const postId = this.dataset.postId;
        const commentSection = document.getElementById('comment-section-' + postId);

        if (commentSection) {
            commentSection.classList.toggle('hidden');
            if (!commentSection.classList.contains('hidden')) {
                const textarea = commentSection.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                }
            }
        }
    });
});
</script>

</body>

</html>