<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Users - Admin OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .custom-file-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .custom-file-upload:hover {
            border-color: var(--primary-color);
            background: #f1f5f9;
        }
        .method-toggle {
            display: flex;
            gap: 10px;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .method-btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            background: transparent;
            color: #64748b;
            transition: all 0.2s;
        }
        .method-btn.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .manual-table input, .manual-table select {
            border: none;
            background: transparent;
            padding: 5px;
            width: 100%;
        }
        .manual-table tr:hover {
            background: #f8fafc;
        }
        .remove-row-btn {
            color: #ef4444;
            cursor: pointer;
            opacity: 0.6;
            transition: 0.2s;
        }
        .remove-row-btn:hover {
            opacity: 1;
        }
        #resultSummary {
            display: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Admin Menu</div>
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="bulk_create_users.php" class="active"><i class="fas fa-user-plus"></i> Create Users</a>
                <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="cases.php"><i class="fas fa-folder-open"></i> All Cases</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="header-text">
                    <h2>Create Users</h2>
                    <p>Create Student and Staff Account</p>
                </div>
                
                <div class="user-nav ms-auto">
                    <div class="user-profile">
                        <div class="avatar shadow-sm bg-primary text-white d-flex align-items-center justify-content-center">
                            A
                        </div>
                        <div class="text-center" style="line-height: 1.2;">
                            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                <?php echo htmlspecialchars($admin_name); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result Summary (Hidden by default) -->
            <div id="resultSummary" class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Processing Results</h5>
                    <div class="alert alert-info border-0 d-flex justify-content-around text-center py-3" style="border-radius: 12px; background: #eef2ff;">
                        <div>
                            <span class="d-block h3 fw-bold text-primary mb-0" id="successCount">0</span>
                            <small class="text-muted text-uppercase fw-700" style="font-size: 0.7rem;">Created</small>
                        </div>
                        <div class="border-start border-2 opacity-50"></div>
                        <div>
                            <span class="d-block h3 fw-bold text-danger mb-0" id="failedCount">0</span>
                            <small class="text-muted text-uppercase fw-700" style="font-size: 0.7rem;">Failed</small>
                        </div>
                    </div>
                    <div id="errorList" class="mt-3 small" style="max-height: 200px; overflow-y: auto;"></div>
                    <button onclick="location.reload()" class="btn btn-outline-primary btn-sm mt-3 w-100 rounded-pill">Create More</button>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Method Toggle -->
                    <div class="method-toggle">
                        <button type="button" class="method-btn active" data-method="csv"><i class="fas fa-file-csv me-2"></i>CSV Upload</button>
                        <button type="button" class="method-btn" data-method="manual"><i class="fas fa-keyboard me-2"></i>Manual Form</button>
                    </div>

                    <!-- Method A: CSV Upload -->
                    <div id="csvMethod" class="card border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold m-0">Upload CSV File</h5>
                                <a href="../assets/samples/sample_users.csv" download class="btn btn-sm btn-light rounded-pill px-3">
                                    <i class="fas fa-download me-2"></i>Sample Template
                                </a>
                            </div>

                            <form id="csvForm">
                                <!-- Upload Area -->
                                <div class="upload-area p-5 border-2 border-dashed rounded-4 text-center mb-4" id="dropZone" style="border-color: #dee2e6; cursor: pointer;">
                                    <input type="file" id="csvFileInput" name="csv_file" class="d-none" accept=".csv, .xlsx, .xls, .pdf, .docx">
                                    <div class="mb-3">
                                        <i class="fas fa-cloud-upload-alt text-primary" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">Click to upload or drag and drop</h5>
                                    <p class="text-muted small mb-0">Max file size 20MB. CSV, Excel, Word, and PDF files are supported.</p>
                                </div>
                                 <span id="fileNameDisplay" class="badge bg-soft-blue text-primary mt-2 d-none align-items-center">
                                    <span id="fileNameText"></span>
                                    <i class="fas fa-times ms-2" id="cancelFileBtn" style="cursor: pointer;" title="Remove file"></i>
                                 </span>
                                
                                <div id="csvPreview" class="table-responsive mt-4 d-none">
                                    <h6 class="fw-bold mb-3">File Preview & Verification</h6>
                                    <table class="table table-sm border align-middle" style="font-size: 0.85rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 50px;">S.No</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>ID</th>
                                                <th>Dept</th>
                                                <th>Role</th>
                                                <th>Password</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="csvPreviewBody"></tbody>
                                    </table>
                                </div>

                                <button type="submit" id="submitCsvBtn" class="btn btn-primary mt-4 w-100 rounded-pill py-2 fw-bold" disabled>
                                    Process & Create Users
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Method B: Manual Form -->
                    <div id="manualMethod" class="card border-0 shadow-sm d-none" style="border-radius: 15px;">
                        <div class="card-body">
                            <h5 class="fw-bold mb-4">Manual Entry</h5>
                            <form id="manualForm">
                                <div class="table-responsive">
                                    <table class="table manual-table border rounded" style="font-size: 0.85rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 25%;">Name</th>
                                                <th style="width: 25%;">Email</th>
                                                <th style="width: 15%;">ID (Reg/Staff)</th>
                                                <th style="width: 15%;">Dept</th>
                                                <th style="width: 15%;">Role</th>
                                                <th style="width: 5%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="manualTableBody">
                                            <!-- Rows added via JS -->
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" id="addRowBtn" class="btn btn-light rounded-pill px-3 btn-sm">
                                        <i class="fas fa-plus me-2"></i>Add Row
                                    </button>
                                    <span class="text-muted small">Max 100 rows</span>
                                </div>
                                
                                <button type="submit" class="btn btn-primary mt-4 w-100 rounded-pill py-2 fw-bold">
                                    Create All Users
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                        <div class="card-body">
                            <h6 class="fw-bold text-uppercase small text-muted mb-3">Guidelines</h6>
                            <ul class="ps-3 small text-muted" style="line-height: 1.6;">
                                <li class="mb-2"><strong>Max Limit:</strong> 100 accounts per upload. (Max 20MB file size)</li>
                                <li><strong>File Formats:</strong> Use CSV, Excel (.xlsx), Word (.docx), or PDF for account data.</li>
                                <li class="mb-2"><strong>Uniqueness:</strong> Emails and Register Numbers must be unique.</li>
                                <li class="mb-2"><strong>Roles:</strong> Ensure role names match exactly: <code>student</code> or <code>staff</code>.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Row Modal -->
    <div class="modal fade" id="editRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold m-0">Edit User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editRowForm">
                        <input type="hidden" id="editRowIndex">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" id="editName" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" id="editEmail" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Roll / Staff ID</label>
                            <input type="text" id="editID" class="form-control rounded-pill" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Department</label>
                                <input type="text" id="editDept" class="form-control rounded-pill">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Role</label>
                                <select id="editRole" class="form-select rounded-pill">
                                    <option value="student">Student</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="text" id="editPassword" class="form-control rounded-pill" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <!-- PDF.js for PDF Parsing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <!-- JSZip for Word (.docx) Parsing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM loaded, initializing parsers...");
            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                console.log("PDF.js worker configured.");
            } else {
                console.error("PDF.js library not found!");
            }
            const methodBtns = document.querySelectorAll('.method-btn');
            const csvMethod = document.getElementById('csvMethod');
            const manualMethod = document.getElementById('manualMethod');
            const csvForm = document.getElementById('csvForm');
            const manualForm = document.getElementById('manualForm');
            const csvFileInput = document.getElementById('csvFileInput');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const csvPreview = document.getElementById('csvPreview');
            const submitCsvBtn = document.getElementById('submitCsvBtn');
            const manualTableBody = document.getElementById('manualTableBody');
            const addRowBtn = document.getElementById('addRowBtn');
            const dropZone = document.getElementById('dropZone');
            let parsedExcelData = null;

            // Toggle Methods
            methodBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    methodBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const method = btn.dataset.method;
                    if (method === 'csv') {
                        csvMethod.classList.remove('d-none');
                        manualMethod.classList.add('d-none');
                    } else {
                        csvMethod.classList.add('d-none');
                        manualMethod.classList.remove('d-none');
                    }
                });
            });

            // Drag and Drop Handlers
            dropZone.addEventListener('click', () => csvFileInput.click());

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#0d6efd';
                dropZone.style.background = '#f8fbfc';
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.style.borderColor = '#dee2e6';
                dropZone.style.background = 'transparent';
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#dee2e6';
                dropZone.style.background = 'transparent';
                
                if (e.dataTransfer.files.length) {
                    csvFileInput.files = e.dataTransfer.files;
                    csvFileInput.dispatchEvent(new Event('change'));
                }
            });

            // Cancel/Remove File
            document.getElementById('cancelFileBtn').addEventListener('click', function(e) {
                e.stopPropagation();
                csvFileInput.value = '';
                fileNameDisplay.classList.add('d-none');
                fileNameDisplay.style.display = 'none';
                csvPreview.classList.add('d-none');
                submitCsvBtn.disabled = true;
                parsedExcelData = null;
                document.getElementById('fileNameText').textContent = '';
            });

            // File Parsing (CSV/Excel)
            csvFileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    document.getElementById('fileNameText').textContent = file.name;
                    fileNameDisplay.classList.remove('d-none');
                    fileNameDisplay.style.display = 'inline-flex';
                    submitCsvBtn.disabled = false;

                    const fileExt = file.name.split('.').pop().toLowerCase();

                    if (fileExt === 'csv') {
                        parsedExcelData = null; // Use standard file upload for CSV
                        // Parse CSV
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const csvData = event.target.result;
                            const rows_raw = csvData.split('\n').filter(r => r.trim()).slice(1);
                            parsedExcelData = rows_raw.map((row, idx) => {
                                const cols = row.split(',');
                                return {
                                    name: cols[0] || '',
                                    email: cols[1] || '',
                                    register_no: cols[2] || '',
                                    department: cols[3] || '',
                                    role: (cols[4] || 'student').toLowerCase().trim(),
                                    password: cols[5] || 'welcome@123'
                                };
                            });
                            displayPreview(parsedExcelData);
                        };
                        reader.readAsText(file);
                    } else if (['xlsx', 'xls'].includes(fileExt)) {
                        // Parse Excel using SheetJS
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const data = new Uint8Array(event.target.result);
                            const workbook = XLSX.read(data, { type: 'array' });
                            const firstSheetName = workbook.SheetNames[0];
                            const worksheet = workbook.Sheets[firstSheetName];
                            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                            
                            // Remove empty rows and keep first 5 (excluding header)
                            const rows = jsonData.filter(row => row.some(cell => cell !== null && cell !== ''));
                            
                            // Map all rows for submission, not just preview
                            parsedExcelData = rows.slice(1).map((cols, idx) => ({
                                name: cols[0] || '',
                                email: cols[1] || '',
                                register_no: cols[2] || '',
                                department: cols[3] || '',
                                role: (cols[4] || '').toString().toLowerCase().trim(),
                                password: (cols[5] || 'welcome@123').toString()
                            }));

                            displayPreview(parsedExcelData);
                        };
                        reader.readAsArrayBuffer(file);
                    } else if (fileExt === 'pdf') {
                        // Parse PDF using pdf.js
                        const reader = new FileReader();
                        reader.onload = async function(event) {
                            console.log("PDF file loaded into memory, starting parse...");
                            try {
                                if (typeof pdfjsLib === 'undefined') {
                                    throw new Error("PDF.js library is not loaded. Please check your internet connection.");
                                }
                                const typedarray = new Uint8Array(event.target.result);
                                const loadingTask = pdfjsLib.getDocument(typedarray);
                                const pdf = await loadingTask.promise;
                                console.log("PDF document loaded, pages:", pdf.numPages);
                                let fullText = "";
                                
                                for (let i = 1; i <= pdf.numPages; i++) {
                                    const page = await pdf.getPage(i);
                                    const textContent = await page.getTextContent();
                                    
                                    // Sort items by Y descending (top to bottom), then X ascending
                                    const items = textContent.items.sort((a, b) => {
                                        if (Math.abs(b.transform[5] - a.transform[5]) > 5) {
                                            return b.transform[5] - a.transform[5];
                                        }
                                        return a.transform[4] - b.transform[4];
                                    });

                                    let lastY = -1;
                                    let pageText = "";
                                    for (const item of items) {
                                        if (lastY !== -1 && Math.abs(item.transform[5] - lastY) > 5) {
                                            pageText += "\n";
                                        }
                                        pageText += item.str + " ";
                                        lastY = item.transform[5];
                                    }
                                    fullText += pageText + "\n";
                                }
                                
                                console.log("Extracted text length:", fullText.length);
                                if (!fullText.trim()) {
                                    throw new Error("No text could be extracted from this PDF. It might be an image-only PDF.");
                                }

                                // Clean up the text for easier matching
                                const cleanText = fullText.replace(/\s+/g, ' ');
                                
                                // Split by lines to process records row-by-row
                                const lines = fullText.split('\n');
                                console.log("Total lines to process:", lines.length);
                                const rows = [];

                                lines.forEach(line => {
                                    const emailMatch = line.match(/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/);
                                    if (emailMatch) {
                                        const email = emailMatch[0];
                                        const parts = line.split(email);
                                        const beforeEmail = parts[0].trim();
                                        const afterEmail = (parts[1] || "").trim();

                                        // 1. Name: Clean the text before email
                                        let name = beforeEmail.replace(/^[\d\s\W]+/, '').trim();
                                        // Header cleaning: remove common table headers
                                        const headers = ['name', 'email', 'register', 'no', 'dept', 'role', 'password', 'accounts', 'users'];
                                        headers.forEach(h => {
                                            const reg = new RegExp('\\b' + h + '\\b', 'gi');
                                            name = name.replace(reg, '');
                                        });
                                        name = name.trim();

                                        // 2. ID: Look for 5-15 char alphanum in text after email
                                        const idMatch = afterEmail.match(/\b([A-Z0-9-]{5,15})\b/);
                                        const register_no = idMatch ? idMatch[0] : "";

                                        // 3. Dept: First token between Email and ID or after ID
                                        const deptMatch = afterEmail.match(/\b(IT|CSE|ECE|EEE|MECH|CIVIL)\b/i);
                                        const department = deptMatch ? deptMatch[0].toUpperCase() : "";

                                        // 4. Role: student or staff
                                        const roleMatch = line.toLowerCase().match(/\b(student|staff)\b/);
                                        const role = roleMatch ? roleMatch[0] : "student";

                                        // 5. Password: Token after the role or at the end
                                        let password = 'welcome@123';
                                        if (roleMatch) {
                                            const partsAfterRole = line.toLowerCase().split(role);
                                            const afterRole = partsAfterRole[partsAfterRole.length - 1].trim();
                                            if (afterRole) {
                                                const tokens = afterRole.split(/\s+/);
                                                if (tokens.length > 0 && tokens[0].length >= 4) {
                                                    password = tokens[0];
                                                }
                                            }
                                        }

                                        rows.push({
                                            name: name || 'Unknown',
                                            email: email,
                                            register_no: register_no,
                                            department: department,
                                            role: role,
                                            password: password
                                        });
                                    }
                                });

                                console.log("Final rows parsed:", rows.length);
                                if (rows.length > 0) {
                                    parsedExcelData = rows;
                                    displayPreview(parsedExcelData);
                                } else {
                                    alert('Could not find structured user data (Email/Name) in PDF. Please ensure the PDF is not a scan/image.');
                                }
                            } catch (err) {
                                console.error('PDF Parse Error:', err);
                                alert('Error: ' + err.message);
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    } else if (fileExt === 'docx') {
                        // Basic docx parsing (unzipping and reading document.xml)
                        const reader = new FileReader();
                        reader.onload = async function(event) {
                            try {
                                const zip = new JSZip();
                                const content = await zip.loadAsync(event.target.result);
                                const docXml = await content.file("word/document.xml").async("string");
                                
                                // Specific Table Extraction for Word
                                const parser = new DOMParser();
                                const xmlDoc = parser.parseFromString(docXml, "text/xml");
                                const rows_xml = xmlDoc.getElementsByTagName("w:tr");
                                const rows = [];

                                if (rows_xml.length > 0) {
                                    for (let i = 0; i < rows_xml.length; i++) {
                                        const cells_xml = rows_xml[i].getElementsByTagName("w:tc");
                                        const rowData = [];
                                        for (let j = 0; j < cells_xml.length; j++) {
                                            // Get all text content from w:t tags within the cell
                                            const t_tags = cells_xml[j].getElementsByTagName("w:t");
                                            let cellText = "";
                                            for (let k = 0; k < t_tags.length; k++) {
                                                cellText += t_tags[k].textContent;
                                            }
                                            rowData.push(cellText.trim());
                                        }

                                        // Skip header rows or empty rows
                                        if (rowData.length >= 3 && rowData.some(c => c.includes('@'))) {
                                            const emailIdx = rowData.findIndex(c => c.includes('@'));
                                            const name = rowData[emailIdx - 1] || rowData[1] || 'Unknown';
                                            const email = rowData[emailIdx];
                                            const regNo = rowData[emailIdx + 1] || '';
                                            const dept = rowData[emailIdx + 2] || '';
                                            const role = (rowData.find(c => /\b(student|staff)\b/i.test(c)) || 'student').toLowerCase();
                                            const password = rowData[emailIdx + 4] || rowData[rowData.length - 1] || 'welcome@123';

                                            rows.push({
                                                name: name,
                                                email: email,
                                                register_no: regNo,
                                                department: dept,
                                                role: role,
                                                password: password
                                            });
                                        }
                                    }
                                }

                                if (rows.length === 0) {
                                    // Fallback to text heuristic if no table found
                                    const cleanText = docXml.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
                                    const emailRegex = /([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/g;
                                    const emails = cleanText.match(emailRegex) || [];
                                    
                                    emails.forEach(email => {
                                        const emailIdx = cleanText.indexOf(email);
                                        const beforeEmail = cleanText.substring(Math.max(0, emailIdx - 50), emailIdx);
                                        const afterEmail = cleanText.substring(emailIdx + email.length, emailIdx + email.length + 50);

                                        const nameMatch = beforeEmail.match(/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/);
                                        const idMatch = afterEmail.match(/\b([A-Z0-9]{5,15})\b/);
                                        const roleMatch = (beforeEmail + afterEmail).toLowerCase().match(/\b(student|staff)\b/);

                                        rows.push({
                                            name: nameMatch ? nameMatch[0] : 'Unknown',
                                            email: email,
                                            register_no: idMatch ? idMatch[0] : '',
                                            department: '',
                                            role: roleMatch ? roleMatch[0] : 'student',
                                            password: 'welcome@123'
                                        });
                                    });
                                }

                                if (rows.length > 0) {
                                    parsedExcelData = rows;
                                    displayPreview(parsedExcelData);
                                } else {
                                    alert('Could not find structured user data in Word file.');
                                }
                            } catch (err) {
                                console.error('Word Parse Error:', err);
                                alert('Error reading Word file. Please ensure it is a .docx file.');
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    }
                }
            });

            function displayPreview(rows) {
                let previewHtml = '';
                rows.forEach((user, index) => {
                    previewHtml += `<tr>
                        <td class="text-center fw-bold">${index + 1}</td>
                        <td>${user.name || ''}</td>
                        <td class="small">${user.email || ''}</td>
                        <td>${user.register_no || ''}</td>
                        <td>${user.department || ''}</td>
                        <td class="text-capitalize">${user.role}</td>
                        <td><code>${user.password || '******'}</code></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-3">
                                <a href="javascript:void(0)" class="text-primary text-decoration-underline small fw-bold" onclick="editPreviewRow(${index})">Edit</a>
                                <a href="javascript:void(0)" class="text-danger text-decoration-underline small fw-bold" onclick="deletePreviewRow(${index})">Delete</a>
                            </div>
                        </td>
                    </tr>`;
                });
                document.getElementById('csvPreviewBody').innerHTML = previewHtml;
                csvPreview.classList.remove('d-none');
            }

            window.deletePreviewRow = function(index) {
                if(confirm('Are you sure you want to remove this row?')) {
                    parsedExcelData.splice(index, 1);
                    displayPreview(parsedExcelData);
                    if (parsedExcelData.length === 0) {
                        csvPreview.classList.add('d-none');
                        submitCsvBtn.disabled = true;
                    }
                }
            };

            const editModal = new bootstrap.Modal(document.getElementById('editRowModal'));
            window.editPreviewRow = function(index) {
                const user = parsedExcelData[index];
                document.getElementById('editRowIndex').value = index;
                document.getElementById('editName').value = user.name;
                document.getElementById('editEmail').value = user.email;
                document.getElementById('editID').value = user.register_no;
                document.getElementById('editDept').value = user.department;
                document.getElementById('editRole').value = user.role;
                document.getElementById('editPassword').value = user.password;
                editModal.show();
            };

            document.getElementById('editRowForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const index = document.getElementById('editRowIndex').value;
                parsedExcelData[index] = {
                    name: document.getElementById('editName').value,
                    email: document.getElementById('editEmail').value,
                    register_no: document.getElementById('editID').value,
                    department: document.getElementById('editDept').value,
                    role: document.getElementById('editRole').value,
                    password: document.getElementById('editPassword').value
                };
                editModal.hide();
                displayPreview(parsedExcelData);
            });

            // Manual Form Row Management
            function createManualRow(index) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="text" name="name_${index}" placeholder="Full Name" required></td>
                    <td><input type="email" name="email_${index}" placeholder="Email" required></td>
                    <td><input type="text" name="reg_${index}" placeholder="Roll/Staff ID" required></td>
                    <td><input type="text" name="dept_${index}" placeholder="Dept"></td>
                    <td>
                        <select name="role_${index}">
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                        </select>
                    </td>
                    <td><i class="fas fa-times remove-row-btn" onclick="this.closest('tr').remove()"></i></td>
                    <input type="hidden" name="pass_${index}" value="welcome@123">
                `;
                return tr;
            }

            // Add initial 1 row
            for(let i=0; i<1; i++) {
                manualTableBody.appendChild(createManualRow(Date.now() + i));
            }

            addRowBtn.addEventListener('click', () => {
                if (manualTableBody.children.length < 100) {
                    manualTableBody.appendChild(createManualRow(Date.now()));
                }
            });

            // Handle Submissions
            function handleResults(data) {
                document.getElementById('successCount').textContent = data.success_count;
                document.getElementById('failedCount').textContent = data.failed_count;
                
                let errorHtml = '';
                if (data.errors && data.errors.length > 0) {
                    errorHtml = '<div class="text-danger fw-bold mb-2">Detailed Errors:</div>';
                    data.errors.forEach(err => errorHtml += `<div class="mb-1">• ${err}</div>`);
                }
                document.getElementById('errorList').innerHTML = errorHtml;
                
                document.getElementById('resultSummary').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // CSV Form Submit
            csvForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitCsvBtn.disabled = true;
                submitCsvBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

                let fetchOptions = {};

                if (parsedExcelData) {
                    // Send parsed/edited data as JSON (CSV, Excel, Word, PDF)
                    const formData = new FormData();
                    formData.append('users_json', JSON.stringify(parsedExcelData));
                    fetchOptions = {
                        method: 'POST',
                        body: formData
                    };
                } else {
                    // Fallback (should not happen for files that trigger preview)
                    const formData = new FormData(this);
                    fetchOptions = {
                        method: 'POST',
                        body: formData
                    };
                }

                fetch('api/bulk_create_users.php', fetchOptions)
                .then(res => res.json())
                .then(data => {
                    submitCsvBtn.disabled = false;
                    submitCsvBtn.innerHTML = 'Process & Create Users';
                    if (data.error) alert(data.error);
                    else handleResults(data);
                })
                .catch(err => {
                    submitCsvBtn.disabled = false;
                    submitCsvBtn.innerHTML = 'Process & Create Users';
                    console.error(err);
                    alert('An error occurred while processing the file.');
                });
            });

            // Manual Form Submit
            manualForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const rows = [];
                const tableRows = manualTableBody.querySelectorAll('tr');
                tableRows.forEach(tr => {
                    rows.push({
                        name: tr.querySelector('input[name^="name"]').value,
                        email: tr.querySelector('input[name^="email"]').value,
                        register_no: tr.querySelector('input[name^="reg"]').value,
                        department: tr.querySelector('input[name^="dept"]').value,
                        role: tr.querySelector('select').value,
                        password: 'welcome@123' // Default password for manual entry
                    });
                });

                const formData = new FormData();
                formData.append('users_json', JSON.stringify(rows));

                fetch('api/bulk_create_users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) alert(data.error);
                    else handleResults(data);
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred during bulk creation.');
                });
            });
        });
    </script>
</body>
</html>
