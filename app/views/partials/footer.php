</div>
</div>
<script>
// Mencari semua tombol dengan kelas .comment-button
const commentButtons = document.querySelectorAll('.comment-button');

// Menambahkan fungsi 'click' ke setiap tombol
commentButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Ambil ID post dari atribut 'data-post-id' tombol yang diklik
        const postId = this.dataset.postId;

        // Cari area komentar yang sesuai dengan ID tersebut
        const commentSection = document.getElementById('comment-section-' + postId);

        // Jika area komentar ditemukan, tampilkan/sembunyikan
        if (commentSection) {
            commentSection.classList.toggle('hidden');

            // Jika area komentar sekarang terlihat, fokuskan kursor ke textarea
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