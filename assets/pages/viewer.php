<?php
/**
 * Smart document viewer for uploaded class materials and submissions.
 *
 * Supports PDF, DOCX, Excel/CSV, TXT, and common image formats from the
 * documents directory while preventing path traversal outside that folder.
 */
$fileInput = isset($_GET['file']) ? (string) $_GET['file'] : '';
$fileInput = urldecode($fileInput);
$fileInput = str_replace('\\', '/', $fileInput);
$fileInput = ltrim($fileInput, '/');

$documentsDir = realpath(__DIR__ . '/documents');

if ($documentsDir === false) {
    die('Documents folder not found.');
}

if ($fileInput === '') {
    die('No file selected.');
}

if (strpos($fileInput, 'documents/') === 0) {
    $relativePath = $fileInput;
} else {
    $relativePath = 'documents/' . basename($fileInput);
}

$fullPath = realpath(__DIR__ . '/' . $relativePath);

if ($fullPath === false || strpos($fullPath, $documentsDir) !== 0 || !is_file($fullPath)) {
    die('File not found.');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$allowedExtensions = [
    'pdf', 'docx', 'xlsx', 'xls', 'csv', 'txt',
    'jpg', 'jpeg', 'png', 'gif', 'webp'
];

if (!in_array($ext, $allowedExtensions, true)) {
    die('Unsupported file type.');
}

$fileName = htmlspecialchars(basename($fullPath), ENT_QUOTES, 'UTF-8');
$fileUrl = htmlspecialchars($relativePath, ENT_QUOTES, 'UTF-8');
$safeExt = htmlspecialchars($ext, ENT_QUOTES, 'UTF-8');
$textContent = '';

if ($ext === 'txt') {
    $textContent = htmlspecialchars((string) file_get_contents($fullPath), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Viewer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .toolbar { background: #111827; color: white; padding: 12px 18px; position: sticky; top: 0; z-index: 100; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .toolbar button, .toolbar a { background: #2563eb; color: white; border: none; padding: 8px 14px; border-radius: 8px; text-decoration: none; cursor: pointer; font-size: 14px; }
        .toolbar button:hover, .toolbar a:hover { background: #1d4ed8; }
        .file-title { font-weight: bold; word-break: break-word; }
        .container { padding: 24px; max-width: 1100px; margin: auto; }
        #pdf-viewer, #docx-viewer, #excel-viewer, #text-viewer, #image-viewer { display: none; width: 100%; }
        .page-wrapper { margin-bottom: 30px; text-align: center; }
        .page-label { font-weight: bold; margin-bottom: 10px; color: #374151; }
        #pdf-viewer canvas { max-width: 100%; height: auto; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.12); border-radius: 8px; }
        #docx-viewer, #excel-viewer, #text-viewer { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); overflow-x: auto; }
        #excel-viewer table { border-collapse: collapse; width: 100%; min-width: 700px; }
        #excel-viewer th, #excel-viewer td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; white-space: nowrap; }
        #excel-viewer th { background: #f9fafb; font-weight: bold; }
        #text-viewer pre { white-space: pre-wrap; word-wrap: break-word; font-family: Consolas, monospace; line-height: 1.5; }
        #image-viewer { text-align: center; }
        #image-viewer img { max-width: 100%; height: auto; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .loading, .error-box { background: white; padding: 20px; text-align: center; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .error-box { color: #dc2626; font-weight: bold; }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx-preview@0.1.20/dist/docx-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>

<div class="toolbar">
    <button onclick="history.back()">← Back</button>
    <a href="<?= $fileUrl ?>" download>Download</a>
    <span class="file-title">Viewing: <?= $fileName ?></span>
</div>

<div class="container">
    <div id="pdf-viewer"></div>
    <div id="docx-viewer"></div>
    <div id="excel-viewer"></div>
    <div id="text-viewer"><pre><?= $textContent ?></pre></div>
    <div id="image-viewer"><img src="<?= $fileUrl ?>" alt="<?= $fileName ?>"></div>
</div>

<script>
    const fileType = "<?= $safeExt ?>";
    const filePath = "<?= $fileUrl ?>";
    const pdfViewer = document.getElementById('pdf-viewer');
    const docxViewer = document.getElementById('docx-viewer');
    const excelViewer = document.getElementById('excel-viewer');
    const textViewer = document.getElementById('text-viewer');
    const imageViewer = document.getElementById('image-viewer');

    function hideAllViewers() {
        [pdfViewer, docxViewer, excelViewer, textViewer, imageViewer].forEach(function (viewer) {
            viewer.style.display = 'none';
        });
    }

    hideAllViewers();

    if (fileType === 'pdf') {
        pdfViewer.style.display = 'block';
        pdfViewer.innerHTML = '<div class="loading">Loading PDF...</div>';
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        pdfjsLib.getDocument(filePath).promise.then(function (pdf) {
            pdfViewer.innerHTML = '';
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                pdf.getPage(pageNum).then(function (page) {
                    const scale = 1.5;
                    const viewport = page.getViewport({ scale: scale });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    const wrapper = document.createElement('div');
                    wrapper.className = 'page-wrapper';
                    const label = document.createElement('div');
                    label.className = 'page-label';
                    label.textContent = `Page ${pageNum} of ${pdf.numPages}`;
                    wrapper.appendChild(label);
                    wrapper.appendChild(canvas);
                    pdfViewer.appendChild(wrapper);

                    page.render({ canvasContext: context, viewport: viewport });
                });
            }
        }).catch(function (error) {
            pdfViewer.innerHTML = `<div class="error-box">Error loading PDF: ${error.message}</div>`;
        });
    } else if (fileType === 'docx') {
        docxViewer.style.display = 'block';
        docxViewer.innerHTML = '<div class="loading">Loading DOCX...</div>';

        fetch(filePath).then(function (response) {
            if (!response.ok) throw new Error('File could not be loaded.');
            return response.arrayBuffer();
        }).then(function (buffer) {
            docxViewer.innerHTML = '';
            return docx.renderAsync(buffer, docxViewer, null, {
                className: 'docx', inWrapper: true, ignoreWidth: false,
                ignoreHeight: false, breakPages: true, debug: false
            });
        }).catch(function (error) {
            docxViewer.innerHTML = `<div class="error-box">Error loading DOCX: ${error.message}</div>`;
        });
    } else if (['xlsx', 'xls', 'csv'].includes(fileType)) {
        excelViewer.style.display = 'block';
        excelViewer.innerHTML = '<div class="loading">Loading spreadsheet...</div>';

        fetch(filePath).then(function (response) {
            if (!response.ok) throw new Error('File could not be loaded.');
            return response.arrayBuffer();
        }).then(function (buffer) {
            const workbook = XLSX.read(buffer, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            excelViewer.innerHTML = `<h3>Sheet: ${firstSheetName}</h3>${XLSX.utils.sheet_to_html(worksheet)}`;
        }).catch(function (error) {
            excelViewer.innerHTML = `<div class="error-box">Error loading spreadsheet: ${error.message}</div>`;
        });
    } else if (fileType === 'txt') {
        textViewer.style.display = 'block';
    } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileType)) {
        imageViewer.style.display = 'block';
    } else {
        document.querySelector('.container').innerHTML = `<div class="error-box">Unsupported file type: ${fileType}</div>`;
    }
</script>

</body>
</html>
