<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
$admin_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$admin_initial = !empty($admin_name) ? strtoupper($admin_name[0]) : 'A';
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .method-toggle {
            display: inline-flex;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 16px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .method-btn {
            padding: 10px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            background: transparent;
            color: #64748b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .method-btn:hover {
            color: #1e293b;
        }
        .method-btn.active {
            background: #2563eb;
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
        }
        .custom-file-upload {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .custom-file-upload:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }
        #resultSummary {
            display: none;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
        }
        .result-stat-box {
            padding: 20px;
            text-align: center;
            flex: 1;
        }
        .result-stat-box:first-child {
            border-right: 1px solid #f1f5f9;
        }
    </style>
</head>
<body class="admin-portal">
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 55px;">
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Admin Menu</div>
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="bulk_create_users.php" class="menu-item <?php echo ($current_page == 'bulk_create_users.php') ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Create Users</a>
                <a href="users.php" class="menu-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Users</a>
                <a href="cases.php" class="menu-item <?php echo ($current_page == 'cases.php') ? 'active' : ''; ?>"><i class="fas fa-folder-open"></i> All Cases</a>
                <a href="manage_case_types.php" class="menu-item <?php echo ($current_page == 'manage_case_types.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Case Types</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="settings.php" class="menu-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
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
                
                <div class="user-nav ms-auto d-flex align-items-center gap-4">
                    <a href="users.php" class="btn d-flex align-items-center gap-2 px-3 py-2 shadow-sm" style="background: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; border-radius: 12px; transition: all 0.3s ease; font-weight: 600; font-size: 0.85rem;">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                    <div class="user-profile d-flex align-items-center gap-3">
                        <div class="text-end" style="line-height: 1.2;">
                            <div style="font-size: 0.9rem; font-weight: 750; color: #1e293b; font-family: 'Outfit';">
                                <?php echo ($_SESSION['role'] === 'admin' ? 'Admin | ' : '') . htmlspecialchars($admin_name); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; font-weight: 500;">
                                <?php echo htmlspecialchars($admin_email); ?>
                            </div>
                        </div>
                        <div class="avatar shadow-sm bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; border-radius: 50%;">
                            <?php echo $admin_initial; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result Summary (Hidden by default) -->
            <div id="resultSummary" class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-body p-0">
                    <div class="d-flex align-items-stretch">
                        <div class="result-stat-box">
                            <span class="d-block h4 fw-bold text-success mb-1" id="successCount">0</span>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Successful</span>
                        </div>
                        <div class="result-stat-box" style="background: #fffcfc;">
                            <span class="d-block h4 fw-bold text-danger mb-1" id="failedCount">0</span>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Failed</span>
                        </div>
                        <div class="p-3 d-flex align-items-center border-start">
                            <button onclick="location.reload()" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">New Upload</button>
                        </div>
                    </div>
                    <div id="errorList" class="p-4 border-top small" style="max-height: 200px; overflow-y: auto; display: none;"></div>
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
                                <h5 class="fw-bold m-0" style="font-size: 1rem; color: #1e293b;">Upload CSV File</h5>
                                <a href="../assets/samples/sample_users.csv" download class="btn btn-sm text-primary fw-bold" style="background: #f0f7ff; border-radius: 8px;">
                                    <i class="fas fa-download me-2"></i>Sample Template
                                </a>
                            </div>

                            <form id="csvForm">
                                <!-- Upload Area -->
                                <div class="custom-file-upload mb-4" id="dropZone">
                                    <input type="file" id="csvFileInput" name="csv_file" class="d-none" accept=".csv, .xlsx, .xls, .pdf, .docx">
                                    <div class="mb-3">
                                        <div class="icon-circle bg-white shadow-sm d-inline-flex p-3 rounded-circle" style="color: #2563eb;">
                                            <i class="fas fa-cloud-upload-alt" style="font-size: 1.8rem;"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-2">Click to upload or drag and drop</h6>
                                    <p class="text-muted small mb-0">CSV, Excel, Word, and PDF files are supported.</p>
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
                                                <th>Year</th>
                                                <th>Batch</th>
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
                    <div id="manualMethod" class="card border-0 shadow-sm d-none" style="border-radius: 16px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold m-0" style="font-size: 1rem; color: #1e293b;">Manual Entry List</h5>
                                <button type="button" id="openAddUserModalBtn" class="btn btn-sm text-primary fw-bold" style="background: #f0f7ff; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-plus-circle me-2"></i>Add User
                                </button>
                            </div>
                            <form id="manualForm">
                                <div class="table-responsive d-none" id="manualPreviewWrapper">
                                    <table class="table table-hover border rounded" style="font-size: 0.85rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>ID</th>
                                                <th>Dept</th>
                                                <th>Role</th>
                                                <th>Password</th>
                                                <th class="text-center">Photo</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="manualTableBody"></tbody>
                                    </table>
                                </div>
                                <div id="manualEmptyState" class="text-center py-5 text-muted border rounded mb-3" style="border-color: #cbd5e1 !important; border-style: dashed !important; border-width: 2px !important;">
                                    <i class="fas fa-users-viewfinder fs-1 mb-2 text-primary opacity-50"></i>
                                    <p class="m-0 fw-medium">No users added yet.</p>
                                    <p class="small opacity-75">Click 'Add User' to add details row by row.</p>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <span class="text-muted small">Total: <span id="manualUserCount" class="fw-bold text-dark">0</span>/100 users</span>
                                </div>
                                <button type="submit" id="submitManualBtn" class="btn btn-primary mt-4 w-100 rounded-pill py-2 fw-bold" disabled>
                                    Create All Users
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                        <div class="card-body">
                            <h6 class="fw-bold text-uppercase small text-muted mb-4" style="letter-spacing: 0.05em;">Quick Guidelines</h6>
                            <div class="guideline-item d-flex gap-3 mb-3">
                                <div class="icon-sm text-primary p-2 rounded-3 bg-soft-blue" style="width: 35px; height: 35px; min-width: 35px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                                <div class="small text-muted"><strong>Batch Limit:</strong> Up to 100 accounts per upload for stability.</div>
                            </div>
                            <div class="guideline-item d-flex gap-3 mb-3">
                                <div class="icon-sm text-primary p-2 rounded-3 bg-soft-blue" style="width: 35px; height: 35px; min-width: 35px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div class="small text-muted"><strong>Formats:</strong> CSV, Excel, Word, or text-based PDFs.</div>
                            </div>
                            <div class="guideline-item d-flex gap-3 mb-3">
                                <div class="icon-sm text-primary p-2 rounded-3 bg-soft-blue" style="width: 35px; height: 35px; min-width: 35px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-fingerprint"></i>
                                </div>
                                <div class="small text-muted"><strong>Uniqueness:</strong> Emails and IDs must not already exist.</div>
                            </div>
                            <div class="guideline-item d-flex gap-3">
                                <div class="icon-sm text-primary p-2 rounded-3 bg-soft-blue" style="width: 35px; height: 35px; min-width: 35px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user-tag"></i>
                                </div>
                                <div class="small text-muted"><strong>Roles:</strong> Use the exact keywords 'student' or 'staff'.</div>
                            </div>
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

    <!-- Add User Modal for Manual Entry -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold m-0">Add New User Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" id="addName" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" id="addEmail" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Roll / Staff ID</label>
                            <input type="text" id="addID" class="form-control rounded-pill" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Department</label>
                                <input type="text" id="addDept" class="form-control rounded-pill">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Role</label>
                                <select id="addRole" class="form-select rounded-pill">
                                    <option value="student">Student</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3 student-only-fields">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Year</label>
                                <select id="addYear" class="form-select rounded-pill">
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Batch</label>
                                <input type="text" id="addBatch" class="form-control rounded-pill" placeholder="e.g. 2021-2025">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="text" id="addPassword" class="form-control rounded-pill" value="welcome@123" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Profile Picture (Optional)</label>
                            <input type="file" id="addPhoto" name="photo_input" class="form-control rounded-pill" accept="image/*">
                            <div id="addPhotoPreviewContainer" class="mt-2 text-center d-none">
                                <img id="addPhotoPreview" src="" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
                                <input type="hidden" id="addPhotoBase64">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Save User to List</button>
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

            // Role change toggle: Show/Hide Student Only Fields
            const addRoleSelect = document.getElementById('addRole');
            const studentFields = document.querySelector('.student-only-fields');
            
            function toggleStudentFields() {
                if (addRoleSelect.value === 'student') {
                    studentFields.style.display = 'flex';
                } else {
                    studentFields.style.display = 'none';
                }
            }
            
            addRoleSelect.addEventListener('change', toggleStudentFields);
            toggleStudentFields(); // Run on init

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
                                    password: cols[5] || 'welcome@123',
                                    year: cols[6] || '',
                                    batch: cols[7] || ''
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
                                register_no: (cols[2] || '').toString(),
                                department: cols[3] || '',
                                year: (cols[4] || '').toString(),
                                batch: (cols[5] || '').toString(),
                                role: (cols[6] || '').toString().toLowerCase().trim(),
                                password: (cols[7] || 'welcome@123').toString()
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
                                        const tokens = line.trim().split(/\s+/);
                                        const eIdx = tokens.findIndex(t => t === email);
                                        
                                        if (eIdx !== -1) {
                                            // The PDF format seems to be: S.No User Role Email Department Year [yr] Batch JoinedDate UserID
                                            
                                            // 1. Role: Token immediately before email
                                            const role = (tokens[eIdx - 1] || "student").toLowerCase();
                                            
                                            // 2. Name: Tokens between S.No and Role
                                            // Usually tokens[1...eIdx-2]
                                            let name = "";
                                            if (eIdx > 2) {
                                                name = tokens.slice(1, eIdx - 1).join(" ");
                                            } else if (eIdx === 2) {
                                                name = tokens[1];
                                            }
                                            
                                            // 3. User ID: Usually the LAST token in our specific PDF format
                                            const register_no = tokens[tokens.length - 1] || "";
                                            
                                            // 4. Dept: Token immediately after email
                                            const department = (tokens[eIdx + 1] || "").toUpperCase();
                                            
                                            // 5. Year: Look for a single digit (1-4) or '-'
                                            let year = "";
                                            const potentialYear = tokens[eIdx + 2];
                                            if (potentialYear && /^[1-4]$/.test(potentialYear)) {
                                                year = potentialYear;
                                            } else if (potentialYear === '-') {
                                                year = "";
                                            }
                                            
                                            // 6. Batch: Look for pattern \d{4}-\d{4}
                                            let batch = "";
                                            // It's usually a few tokens after the year
                                            for (let j = eIdx + 2; j < tokens.length; j++) {
                                                if (/\d{4}-\d{4}/.test(tokens[j])) {
                                                    batch = tokens[j];
                                                    break;
                                                }
                                            }

                                            // 7. Password: Default or heuristic
                                            let password = 'welcome@123';
                                            // Check if there's any token that looks like a password (often at the end if not using ID)
                                            // But for this PDF, let's keep the default as it's not visible
                                            
                                            rows.push({
                                                name: name || 'Unknown',
                                                email: email,
                                                register_no: register_no,
                                                department: department,
                                                year: year,
                                                batch: batch,
                                                role: role,
                                                password: password
                                            });
                                        }
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
                                                year: rowData[emailIdx + 2] || '',
                                                batch: rowData[emailIdx + 3] || '',
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
                        <td>${user.year || ''}</td>
                        <td>${user.batch || ''}</td>
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
                    ...parsedExcelData[index],
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

            // Manual Entry Array Management
            let manualUsersData = [];

            function displayManualPreview() {
                const tbody = document.getElementById('manualTableBody');
                const wrapper = document.getElementById('manualPreviewWrapper');
                const emptyState = document.getElementById('manualEmptyState');
                const submitBtn = document.getElementById('submitManualBtn');
                const countBadge = document.getElementById('manualUserCount');

                countBadge.textContent = manualUsersData.length;
                
                if (manualUsersData.length === 0) {
                    wrapper.classList.add('d-none');
                    emptyState.classList.remove('d-none');
                    submitBtn.disabled = true;
                    tbody.innerHTML = '';
                    return;
                }

                wrapper.classList.remove('d-none');
                emptyState.classList.add('d-none');
                submitBtn.disabled = false;

                let html = '';
                manualUsersData.forEach((user, index) => {
                    const imgHtml = user.profile_photo ? `<img src="${user.profile_photo}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">` : '<i class="fas fa-user-circle text-muted fs-4"></i>';
                    html += `<tr>
                        <td class="text-center fw-bold align-middle">${index + 1}</td>
                        <td class="align-middle">${user.name}</td>
                        <td class="small align-middle">${user.email}</td>
                        <td class="align-middle">${user.register_no}</td>
                        <td class="align-middle">
                            ${user.department}<br>
                            <span class="text-muted small">${user.year ? user.year + ' yr' : ''} ${user.batch ? '| ' + user.batch : ''}</span>
                        </td>
                        <td class="text-capitalize align-middle">${user.role}</td>
                        <td class="align-middle"><code>${user.password}</code></td>
                        <td class="text-center align-middle">${imgHtml}</td>
                        <td class="text-center align-middle">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="javascript:void(0)" class="text-primary small" onclick="editManualUser(${index})"><i class="fas fa-edit"></i></a>
                                <a href="javascript:void(0)" class="text-danger small" onclick="deleteManualUser(${index})"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>`;
                });
                tbody.innerHTML = html;
            }

            window.deleteManualUser = function(index) {
                if(confirm('Remove this user from the list?')) {
                    manualUsersData.splice(index, 1);
                    displayManualPreview();
                }
            };

            let editingManualIndex = -1;
            const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));

            window.editManualUser = function(index) {
                editingManualIndex = index;
                const user = manualUsersData[index];
                document.getElementById('addName').value = user.name;
                document.getElementById('addEmail').value = user.email;
                document.getElementById('addID').value = user.register_no;
                document.getElementById('addDept').value = user.department;
                document.getElementById('addYear').value = user.year || '';
                document.getElementById('addBatch').value = user.batch || '';
                document.getElementById('addRole').value = user.role;
                if(typeof toggleStudentFields === 'function') toggleStudentFields();
                document.getElementById('addPassword').value = user.password;
                
                if (user.profile_photo) {
                    document.getElementById('addPhotoBase64').value = user.profile_photo;
                    document.getElementById('addPhotoPreview').src = user.profile_photo;
                    document.getElementById('addPhotoPreviewContainer').classList.remove('d-none');
                } else {
                    document.getElementById('addPhotoBase64').value = '';
                    document.getElementById('addPhotoPreviewContainer').classList.add('d-none');
                }
                
                document.querySelector('#addUserModal .modal-title').textContent = "Edit User Detail";
                document.querySelector('#addUserForm button[type="submit"]').innerHTML = "Update User Details";
                addUserModal.show();
            };

            document.getElementById('openAddUserModalBtn').addEventListener('click', function() {
                editingManualIndex = -1;
                document.getElementById('addUserForm').reset();
                document.getElementById('addPhotoBase64').value = '';
                document.getElementById('addPhotoPreviewContainer').classList.add('d-none');
                document.querySelector('#addUserModal .modal-title').textContent = "Add New User Detail";
                document.querySelector('#addUserForm button[type="submit"]').innerHTML = "Save User to List";
            });

            document.getElementById('addUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const dataObj = {
                    name: document.getElementById('addName').value,
                    email: document.getElementById('addEmail').value,
                    register_no: document.getElementById('addID').value,
                    department: document.getElementById('addDept').value,
                    year: document.getElementById('addYear').value,
                    batch: document.getElementById('addBatch').value,
                    role: document.getElementById('addRole').value,
                    password: document.getElementById('addPassword').value,
                    profile_photo: document.getElementById('addPhotoBase64').value || null
                };

                if (editingManualIndex !== -1) {
                    manualUsersData[editingManualIndex] = dataObj;
                    editingManualIndex = -1;
                } else {
                    if (manualUsersData.length >= 100) {
                        alert('Maximum 100 users allowed manual entry.');
                        return;
                    }
                    manualUsersData.push(dataObj);
                }
                
                this.reset();
                document.getElementById('addPhotoPreviewContainer').classList.add('d-none');
                document.getElementById('addPhotoBase64').value = '';
                addUserModal.hide();
                displayManualPreview();
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

            // Upload & Cropping Logic
            let cropper = null;
            let currentFileInput = null;
            const cropperModalEl = document.getElementById('cropperModal');
            const cropperModal = new bootstrap.Modal(cropperModalEl);
            const imageToCrop = document.getElementById('imageToCrop');

            document.addEventListener('change', function(e) {
                if (e.target.matches('input[type="file"][name^="photo_"], #addPhoto')) {
                    const file = e.target.files[0];
                    if (file) {
                        currentFileInput = e.target;
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            imageToCrop.src = event.target.result;
                            cropperModal.show();
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });

            cropperModalEl.addEventListener('shown.bs.modal', function () {
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                });
            });

            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                if(currentFileInput) currentFileInput.value = ''; // Reset input to allow re-selection
            });

            document.getElementById('btnCrop').addEventListener('click', function() {
                if (!cropper || !currentFileInput) return;
                
                const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
                const base64Photo = canvas.toDataURL('image/jpeg');
                
                if (currentFileInput.id === 'addPhoto') {
                    document.getElementById('addPhotoBase64').value = base64Photo;
                    document.getElementById('addPhotoPreview').src = base64Photo;
                    document.getElementById('addPhotoPreviewContainer').classList.remove('d-none');
                } else {
                    currentFileInput.dataset.base64 = base64Photo;
                    let previewImg = currentFileInput.parentElement.querySelector('.crop-preview');
                    if(!previewImg) {
                        previewImg = document.createElement('img');
                        previewImg.className = 'crop-preview mt-1 d-block bg-white';
                        previewImg.style.width = '40px';
                        previewImg.style.height = '40px';
                        previewImg.style.borderRadius = '50%';
                        previewImg.style.objectFit = 'cover';
                        currentFileInput.parentElement.appendChild(previewImg);
                    }
                    previewImg.src = base64Photo;
                    currentFileInput.style.color = "transparent";
                }

                cropperModal.hide();
            });

            // Manual Form Submit
            manualForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('users_json', JSON.stringify(manualUsersData));

                fetch('api/bulk_create_users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(async res => {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Raw response:', text);
                        throw new Error('Server returned non-JSON response: ' + text.substring(0, 500));
                    }
                })
                .then(data => {
                    if (data.error) alert(data.error);
                    else handleResults(data);
                })
                .catch(err => {
                    console.error(err);
                    alert(err.message || 'An error occurred during bulk creation.');
                });
            });
        });
    </script>
    
    <!-- Cropper Modal -->
    <div class="modal fade" id="cropperModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="max-height: 400px; display:inline-block; width:100%;">
                        <img id="imageToCrop" src="" style="max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnCrop">Crop</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</body>
</html>
