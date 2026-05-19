document.addEventListener("DOMContentLoaded", function () {
    /**
     * Handles every upload box on the page.
     * This works for both the Announcement modal and the Activity modal.
     */
    document.querySelectorAll('.upload-section').forEach(function (section) {
        const fileInput = section.querySelector('input[type="file"]');
        const fileList = section.querySelector('.file-list');
        const dropZone = section.querySelector('.file-input-area') || section;
        let selectedFiles = [];

        if (!fileInput || !fileList || !dropZone) {
            return;
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) {
                return bytes + ' B';
            }

            if (bytes < 1024 * 1024) {
                return (bytes / 1024).toFixed(1) + ' KB';
            }

            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        function syncInputFiles() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(function (file) {
                dataTransfer.items.add(file);
            });

            fileInput.files = dataTransfer.files;
        }

        function updateFileList() {
            fileList.innerHTML = '';

            if (selectedFiles.length === 0) {
                fileList.classList.remove('active');
                return;
            }

            fileList.classList.add('active');

            const title = document.createElement('h6');
            title.className = 'mb-2 fw-bold';
            title.textContent = 'Selected Files:';
            fileList.appendChild(title);

            selectedFiles.forEach(function (file, index) {
                const item = document.createElement('div');
                item.className = 'file-item';

                const name = document.createElement('span');
                name.className = 'file-name';
                name.textContent = '📄 ' + file.name;

                const size = document.createElement('span');
                size.className = 'file-size';
                size.textContent = formatFileSize(file.size);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'remove-file';
                remove.textContent = '✖';
                remove.setAttribute('aria-label', 'Remove ' + file.name);
                remove.addEventListener('click', function () {
                    selectedFiles.splice(index, 1);
                    syncInputFiles();
                    updateFileList();
                });

                item.appendChild(name);
                item.appendChild(size);
                item.appendChild(remove);
                fileList.appendChild(item);
            });
        }

        fileInput.addEventListener('change', function (event) {
            selectedFiles = Array.from(event.target.files || []);
            updateFileList();
        });

        dropZone.addEventListener('dragover', function (event) {
            event.preventDefault();
            section.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', function (event) {
            event.preventDefault();
            section.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', function (event) {
            event.preventDefault();
            section.classList.remove('drag-over');

            const droppedFiles = Array.from(event.dataTransfer.files || []);
            selectedFiles = selectedFiles.concat(droppedFiles);
            syncInputFiles();
            updateFileList();
        });
    });

    /**
     * Auto-hide alerts after a short delay.
     */
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (alert) {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';

            setTimeout(function () {
                alert.remove();
            }, 300);
        });
    }, 5000);
});
