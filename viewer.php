<?php
$file = isset($_GET['file']) ? basename($_GET['file']) : '';
$filePath = "documents/" . $file;
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Document Viewer</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f5f5f5; }
        .toolbar { background: #333; color: white; padding: 10px; position: sticky; top: 0; z-index: 100; }
        .container { padding: 20px; display: flex; flex-direction: column; align-items: center; }
        #pdf-viewer, #docx-viewer { width: 100%; max-width: 900px; margin: 0 auto; }
        #pdf-viewer canvas {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            width: 100%;
            height: auto;
            background: white;
        }
        #docx-viewer {
            background: white;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        button {
            background: #007bff;
            border: none;
            color: white;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background: #0056b3;
        }
        .loading {
            text-align: center;
            padding: 20px;
            font-size: 18px;
            color: #666;
        }
    </style>
    
    <!-- PDF.js for PDF files -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    
    <!-- docx-preview for DOCX files -->
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx-preview-lib@0.1.14-fix-3/dist/docx-preview.min.js"></script>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.location.href='index.php'">← Back</button>
        Viewing: <?php echo htmlspecialchars($file); ?>
    </div>
    <div class="container">
        <div id="pdf-viewer" style="display: none;"></div>
        <div id="docx-viewer" style="display: none;"></div>
    </div>

    <script>
        const fileType = '<?php echo $ext; ?>';
        const filePath = '<?php echo $filePath; ?>';
        
        if (fileType === 'pdf') {
            // Show PDF viewer
            const pdfContainer = document.getElementById('pdf-viewer');
            pdfContainer.style.display = 'block';
            
            // Show loading indicator
            pdfContainer.innerHTML = '<div class="loading">Loading PDF... Please wait.</div>';
            
            // Load and render all pages of PDF
            pdfjsLib.getDocument(filePath).promise.then(function(pdf) {
                const numPages = pdf.numPages;
                pdfContainer.innerHTML = ''; // Clear loading message
                
                // Loop through all pages and render each one
                for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                    pdf.getPage(pageNum).then(function(page) {
                        // Create canvas for each page
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        
                        // Set scale for better quality (1.5 = 150%)
                        const scale = 1.5;
                        const viewport = page.getViewport({ scale: scale });
                        
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        
                        // Add page number indicator
                        const pageWrapper = document.createElement('div');
                        pageWrapper.style.marginBottom = '30px';
                        pageWrapper.style.position = 'relative';
                        
                        const pageLabel = document.createElement('div');
                        pageLabel.textContent = `Page ${pageNum} of ${numPages}`;
                        pageLabel.style.textAlign = 'center';
                        pageLabel.style.marginBottom = '10px';
                        pageLabel.style.fontWeight = 'bold';
                        pageLabel.style.color = '#555';
                        
                        pageWrapper.appendChild(pageLabel);
                        pageWrapper.appendChild(canvas);
                        pdfContainer.appendChild(pageWrapper);
                        
                        // Render the page
                        page.render({
                            canvasContext: context,
                            viewport: viewport
                        }).promise.then(function() {
                            console.log(`Page ${pageNum} rendered successfully`);
                        }).catch(function(error) {
                            console.error(`Error rendering page ${pageNum}:`, error);
                            const errorMsg = document.createElement('div');
                            errorMsg.style.color = 'red';
                            errorMsg.textContent = `Failed to render page ${pageNum}`;
                            pageWrapper.appendChild(errorMsg);
                        });
                    }).catch(function(error) {
                        console.error(`Error loading page ${pageNum}:`, error);
                        const errorMsg = document.createElement('div');
                        errorMsg.style.color = 'red';
                        errorMsg.textContent = `Error loading page ${pageNum}: ${error.message}`;
                        pdfContainer.appendChild(errorMsg);
                    });
                }
            }).catch(function(error) {
                console.error('Error loading PDF:', error);
                pdfContainer.innerHTML = `<div style="color: red; padding: 20px; text-align: center;">
                    Error loading PDF file: ${error.message}<br>
                    Please make sure the file exists and is a valid PDF.
                </div>`;
            });
            
        } else if (fileType === 'docx') {
            // Show DOCX viewer
            const container = document.getElementById('docx-viewer');
            container.style.display = 'block';
            container.innerHTML = '<div class="loading">Loading DOCX document...</div>';
            
            // Fetch and render DOCX
            fetch(filePath)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.arrayBuffer();
                })
                .then(buffer => {
                    docx.renderAsync(buffer, container, null, {
                        className: "docx",
                        inWrapper: true,
                        ignoreWidth: false,
                        ignoreHeight: false,
                        breakPages: true,
                        debug: false
                    }).then(function() {
                        console.log("Document rendered successfully");
                    }).catch(function(error) {
                        console.error('DOCX render error:', error);
                        container.innerHTML = `<p style="color: red;">Error rendering DOCX: ${error.message}</p>`;
                    });
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    container.innerHTML = `<p style="color: red;">Error loading DOCX file: ${error.message}</p>`;
                });
        } else {
            // Unsupported file type
            document.querySelector('.container').innerHTML = `
                <div style="color: red; padding: 20px; text-align: center;">
                    Unsupported file type: ${fileType}<br>
                    Only PDF and DOCX files are supported.
                </div>
            `;
        }
    </script>
</body>
</html>