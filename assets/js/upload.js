document.addEventListener("DOMContentLoaded", function () {
        // File selection for upload
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        let selectedFiles = [];
        
        fileInput.addEventListener('change', function(e) {
            selectedFiles = Array.from(e.target.files);
            updateFileList();
        });
        
        function updateFileList() {
            if (selectedFiles.length === 0) {
                fileList.classList.remove('active');
               
                return;
            }
            
            fileList.classList.add('active');
            
            let html = '<h4 style="margin-bottom: 10px;">Selected Files:</h4>';
            selectedFiles.forEach((file, index) => {
                const size = (file.size / 1024).toFixed(2);
                html += `
                    <div class="file-item">
                        <span class="file-name">📄 ${file.name}</span>
                        <span class="file-size">${size} KB</span>
                        <span class="remove-file" onclick="removeFile(${index})">✖</span>
                    </div>
                `;
            });
            fileList.innerHTML = html;
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
            updateFileList();
        }
        
        // Drag and drop
        const dropZone = document.getElementById('dropZone');
        const uploadSection = document.getElementById('uploadSection');
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadSection.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadSection.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadSection.classList.remove('drag-over');
            const files = Array.from(e.dataTransfer.files);
            const dt = new DataTransfer();
            [...selectedFiles, ...files].forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
            selectedFiles = Array.from(fileInput.files);
            updateFileList();
        });
        
        // Document selection for bulk delete
        let selectedDocuments = [];
        
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.document-checkbox:checked');
            selectedDocuments = Array.from(checkboxes).map(cb => cb.value);
            
            const selectionToolbar = document.getElementById('selectionToolbar');
            const selectedCountSpan = document.getElementById('selectedCount');
            const selectedFilesInput = document.getElementById('selectedFilesInput');
            
            if (selectedDocuments.length > 0) {
                selectionToolbar.classList.add('show');
                selectedCountSpan.textContent = selectedDocuments.length;
                
                // Update hidden inputs for form submission
                selectedFilesInput.innerHTML = '';
                selectedDocuments.forEach(file => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_files[]';
                    input.value = file;
                    selectedFilesInput.appendChild(input);
                });
                
                // Highlight selected cards
                document.querySelectorAll('.document-card').forEach(card => {
                    const filename = card.dataset.filename;
                    if (selectedDocuments.includes(filename)) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                });
            } else {
                selectionToolbar.classList.remove('show');
                document.querySelectorAll('.document-card').forEach(card => {
                    card.classList.remove('selected');
                });
            }
        }
        
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.document-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            updateSelection();
        }
        
        function clearSelection() {
            document.querySelectorAll('.document-checkbox').forEach(cb => cb.checked = false);
            updateSelection();
        }
        
        function deleteSingleFile(filename) {
            if (confirm('Delete this file?')) {
                window.location.href = `index.php?delete=${encodeURIComponent(filename)}`;
            }
        }
        
        function viewDocument(filename, type) {
            if (type === 'jpg' || type === 'jpeg') {
                window.open('documents/' + filename, '_blank');
            } else {
                window.location.href = `viewer.php?file=${encodeURIComponent(filename)}&type=${type}`;
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 4000);
            });
        }, 1000);
});