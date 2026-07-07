        </section>
        </main>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var layananSelectors = document.querySelectorAll('.layanan-selector');

                function applyKegiatanFilter(selectElement) {
                    var targetSelector = selectElement.getAttribute('data-target');
                    var selectedValue = selectElement.value;
                    var wrapper = targetSelector ? document.querySelector(targetSelector) : null;

                    if (!wrapper) {
                        return;
                    }

                    wrapper.querySelectorAll('.kegiatan-group').forEach(function(group) {
                        var layananId = group.getAttribute('data-layanan-id');
                        var shouldShow = selectedValue === '' || layananId === selectedValue;

                        group.classList.toggle('hidden-kegiatan', !shouldShow);

                        group.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                            checkbox.disabled = !shouldShow;
                        });
                    });
                }

                layananSelectors.forEach(function(selectElement) {
                    applyKegiatanFilter(selectElement);
                    selectElement.addEventListener('change', function() {
                        applyKegiatanFilter(selectElement);
                    });
                });

                var sidebar = document.querySelector('.sidebar');
                var sidebarToggle = document.getElementById('sidebarToggle');
                var sidebarOverlay = document.getElementById('sidebarOverlay');

                if (sidebarToggle && sidebar && sidebarOverlay) {
                    // Tampilkan sidebar saat tombol diklik
                    sidebarToggle.addEventListener('click', function() {
                        sidebar.classList.add('show');
                        sidebarOverlay.classList.add('show');
                    });

                    // Sembunyikan sidebar saat area gelap (overlay) diklik
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                    });
                }
            });
        </script>
        </body>

        </html>