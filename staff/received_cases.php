<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

$staff_uid = $_SESSION['user_id'];
// Fetch fresh staff data from DB
$u_stmt = $conn->prepare("SELECT name, staff_id, profile_photo FROM users WHERE id = ?");
$u_stmt->bind_param("i", $staff_uid);
$u_stmt->execute();
$u_res = $u_stmt->get_result()->fetch_assoc();

$staff_name = $u_res['name'];
$staff_id = $u_res['staff_id'];
$profile_photo = $u_res['profile_photo'];

// Handle Action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_id = $_POST['case_id'];
    $status = $_POST['status'];
    $remark = $_POST['remark'];
    $processed_by = $staff_name;

    $sql = "UPDATE cases SET status = ?, staff_remark = ?, processed_by = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sssi", $status, $remark, $processed_by, $case_id);
    
    if ($stmt->execute()) {
        $success = "Case updated successfully!";
        
        // Notify student
        $get_student = $conn->prepare("SELECT student_id, case_type FROM cases WHERE id = ?");
        $get_student->bind_param("i", $case_id);
        $get_student->execute();
        $student_data = $get_student->get_result()->fetch_assoc();
        
        if ($student_data) {
            $notif_msg = "Your case (" . $student_data['case_type'] . ") has been " . strtolower($status);
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'case_status')");
            $notif_stmt->bind_param("is", $student_data['student_id'], $notif_msg);
            $notif_stmt->execute();
        }
    } else {
        $error = "Error updating case.";
    }
}

// Fetch Pending Cases
$sql = "SELECT * FROM cases WHERE status = 'Pending' AND deleted_by_staff = 0 AND deleted_by_student = 0 ORDER BY created_at ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Cases - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="received_cases.php" class="active"><i class="fas fa-inbox"></i> Received Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="history.php"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php"><i class="fas fa-cog"></i> Settings</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search received cases...">
                </div>
                
                <div class="user-nav">

                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden; background: var(--secondary-color);">
                                <?php if($profile_photo): ?>
                                    <?php 
                                        $photo = trim($profile_photo);
                                        $pic_src = (strpos($photo, 'http') === 0) 
                                            ? $photo 
                                            : "../uploads/profile/" . $photo;
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($staff_name, 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                    <?php echo htmlspecialchars($staff_name); ?>
                                </span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                    <?php echo htmlspecialchars($staff_id ?? ''); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

    <div class="container-fluid py-0">
        <div class="kanban-board full-width-grid">
            
            <!-- Column: Newly Added (Pending) -->
            <div class="kanban-column">
                <div class="kanban-column-header">
                    <span class="kanban-column-title">Newly Added Cases</span>
                    <span class="kanban-column-count"><?php echo $result->num_rows; ?></span>
                </div>
                
                <div class="kanban-cards-container">
                    <?php 
                    $cases_data = [];
                    if ($result->num_rows > 0): 
                    ?>
                        <?php while($row = $result->fetch_assoc()): 
                            // Store row data for JS
                            $cases_data[$row['id']] = $row;

                            // Determine accent color based on Case Type
                            $type = $row['case_type'];
                            $accent = '#3b82f6'; // Default Blue
                            $badge_bg = '#dbeafe';
                            $badge_text = '#1e40af';
                            
                            switch($type) {
                                case 'Academic': 
                                    $accent = '#8b5cf6'; // Purple
                                    $badge_bg = '#ede9fe';
                                    $badge_text = '#5b21b6';
                                    break;
                                case 'Disciplinary': 
                                    $accent = '#ef4444'; // Red
                                    $badge_bg = '#fee2e2';
                                    $badge_text = '#991b1b';
                                    break;
                                case 'Hostel': 
                                    $accent = '#f59e0b'; // Amber
                                    $badge_bg = '#fef3c7';
                                    $badge_text = '#92400e';
                                    break;
                                case 'Library': 
                                    $accent = '#10b981'; // Emerald
                                    $badge_bg = '#d1fae5';
                                    $badge_text = '#065f46';
                                    break;
                            }
                        ?>
                            <div class="kanban-card" style="border-left-color: <?php echo $accent; ?>;" 
                                 onclick="openQuickView('<?php echo $row['id']; ?>')">
                                
                                <span class="card-tag" style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_text; ?>;">
                                    <?php echo htmlspecialchars($type); ?>
                                </span>
                                
                                <div class="card-title"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                <div class="card-subtitle"><?php echo htmlspecialchars($row['roll_no']); ?></div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <div class="small fw-bold text-muted" style="font-size: 0.75rem;">
                                        <i class="far fa-calendar-alt me-1"></i> <?php echo date('d M, Y h:i A', strtotime($row['incident_date'])); ?>
                                    </div>
                                    <span class="quick-view-link">
                                        <?php if (!empty($row['attachment'])): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($row['attachment']); ?>" target="_blank" class="text-decoration-none" style="color: #0284c7;" onclick="event.stopPropagation();">
                                                View file <i class="fas fa-external-link-alt ms-1"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small" style="cursor: default;" onclick="event.stopPropagation();">
                                                No file <i class="fas fa-ban ms-1"></i>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="card-desc">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </div>

                                <div class="mb-2">
                                    <textarea class="form-control form-control-sm" id="remark-<?php echo $row['id']; ?>" rows="1" placeholder="Add Remark *" style="font-size: 0.85rem; resize: none; border-color: #e5e7eb; background: #f9fafb;" onclick="event.stopPropagation();"></textarea>
                                    <div class="error-feedback" id="error-<?php echo $row['id']; ?>" style="display: none; color: #ef4444; font-size: 0.7rem; margin-top: 2px; font-weight: 500;">
                                        <i class="fas fa-exclamation-circle me-1"></i> Remark required
                                    </div>
                                </div>
                                
                                <div class="card-actions">
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-approve fw-bold" style="font-size: 0.75rem; border-radius: 8px;" title="Approve" 
                                            onclick="handleAction(event, '<?php echo $row['id']; ?>', 'Approved')">
                                            Approve <i class="fas fa-check ms-1"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-reject fw-bold ms-2" style="font-size: 0.75rem; border-radius: 8px;" title="Reject" 
                                            onclick="handleAction(event, '<?php echo $row['id']; ?>', 'Rejected')">
                                            Reject <i class="fas fa-times ms-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted mt-5 w-100" style="grid-column: 1 / -1;">
                            <i class="fas fa-folder-open fa-2x mb-2" style="opacity: 0.3;"></i>
                            <p>No Cases</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Case Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="actionForm" method="POST">
                        <input type="hidden" name="case_id" id="modalCaseId">
                        <input type="hidden" name="status" id="modalStatus">
                        
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">STUDENT DETAILS</label>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <h6 class="mb-0 fw-bold" id="modalStudentName">Student Name</h6>
                                <span class="badge bg-primary" id="modalRollNo">Roll No</span>
                            </div>
                            <div class="mt-2 small fw-bold text-muted">
                                <i class="far fa-clock me-1"></i> Updated Date & Time: <span id="modalIncidentDate" class="text-dark"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted fw-bold">DESCRIPTION</label>
                            <div class="p-3 bg-light rounded mt-1" id="modalDesc" style="font-size: 0.9rem;">
                                Description text...
                            </div>
                        </div>

                        <div id="modalAttachmentDiv" class="mb-3" style="display:none;">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="openDocumentViewerBtn">
                                <i class="fas fa-eye me-2"></i> Read / View Attachment
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" id="remarkLabel">Add Staff Remark</label>
                            <textarea name="remark" class="form-control" rows="3" placeholder="Enter reason..." required></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-flex justify-content-end">
                             <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                             <button type="submit" class="btn btn-primary px-4" id="modalSubmitBtn">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div class="modal fade" id="documentViewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-height: 90vh;">
            <div class="modal-content">
                <div class="modal-header py-2 bg-light">
                    <h6 class="modal-title fw-bold">Document Viewer</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh; background: #525659;">
                    <iframe id="docViewerIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                <div class="modal-footer py-1 d-flex justify-content-center">
                    <a href="#" id="directDownloadBtn" class="btn btn-sm btn-link text-decoration-none" target="_blank">
                        <i class="fas fa-download me-1"></i> Download File Instead
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index: 1050;" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index: 1050;" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <script>
        // Pass PHP data to JS
        const casesData = <?php echo json_encode($cases_data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        console.log('Cases Data Loaded:', casesData);
        
        const myModal = new bootstrap.Modal(document.getElementById('quickViewModal'));
        const docModal = new bootstrap.Modal(document.getElementById('documentViewerModal'));
        const docIframe = document.getElementById('docViewerIframe');
        const downloadBtn = document.getElementById('directDownloadBtn');
        let currentAttachmentPath = '';

        document.getElementById('openDocumentViewerBtn').addEventListener('click', function() {
            if (!currentAttachmentPath) return;
            
            const fileUrl = window.location.origin + '/OCMS/uploads/' + currentAttachmentPath;
            const ext = currentAttachmentPath.split('.').pop().toLowerCase();
            
            // For Documents (Word/Excel), use Office Online Viewer
            if (['doc', 'docx', 'xls', 'xlsx'].includes(ext)) {
                docIframe.src = `https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileUrl)}`;
            } 
            // For others (PDF/Images), use direct URL (browser native viewer)
            else {
                docIframe.src = fileUrl;
            }
            
            downloadBtn.href = fileUrl;
            docModal.show();
        });

        function handleAction(event, id, status) {
            event.stopPropagation();
            
            const remarkInput = document.getElementById('remark-' + id);
            let remark = remarkInput.value.trim();

            const errorMsg = document.getElementById('error-' + id);
            
            // Clear previous errors
            remarkInput.style.borderColor = '#e5e7eb';
            errorMsg.style.display = 'none';

            if (status === 'Approved' || status === 'Rejected') {
                if (remark === '') {
                    remarkInput.style.borderColor = '#ef4444';
                    errorMsg.style.display = 'block';
                    remarkInput.focus();
                    return;
                }
                submitForm(id, status, remark);
            }
        }

        function submitForm(id, status, remark) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'case_id';
            idInput.value = id;
            form.appendChild(idInput);

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            form.appendChild(statusInput);

            const remarkInput = document.createElement('input');
            remarkInput.type = 'hidden';
            remarkInput.name = 'remark';
            remarkInput.value = remark;
            form.appendChild(remarkInput);

            document.body.appendChild(form);
            form.submit();
        }

        function openQuickView(id, preStatus = null) {
            console.log('Opening Quick View for ID:', id, 'Status:', preStatus);
            if (!casesData) {
                console.error('casesData is undefined!');
                alert('Error: Data not loaded properly. Please refresh the page.');
                return;
            }

            const data = casesData[id];
            if (!data) {
                 console.error('Case data not found for ID:', id);
                 alert('Error: Case data not found.');
                 return;
            }

            document.getElementById('modalCaseId').value = data.id;
            document.getElementById('modalStudentName').innerText = data.student_name;
            document.getElementById('modalRollNo').innerText = data.roll_no;
            
            // Format date for modal
            const incidentDate = new Date(data.incident_date);
            const options = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
            document.getElementById('modalIncidentDate').innerText = incidentDate.toLocaleString('en-GB', options).replace(',', '');
            
            document.getElementById('modalDesc').innerText = data.description;
            
            // Handle Attachment
            const attachDiv = document.getElementById('modalAttachmentDiv');
            if(data.attachment) {
                attachDiv.style.display = 'block';
                currentAttachmentPath = data.attachment;
            } else {
                attachDiv.style.display = 'none';
                currentAttachmentPath = '';
            }

            const btn = document.getElementById('modalSubmitBtn');
            const statusInput = document.getElementById('modalStatus');
            const modalTitle = document.getElementById('modalTitle');
            const remarkLabel = document.getElementById('remarkLabel');
            
            if (preStatus === 'Approved') {
                statusInput.value = 'Approved';
                btn.innerText = 'Confirm Approval';
                btn.className = 'btn btn-success px-4';
                modalTitle.innerText = 'Approve Case';
                remarkLabel.innerText = 'Reason for Approval (Required)';
                remarkLabel.className = 'form-label fw-bold text-success';
            } else if (preStatus === 'Rejected') {
                statusInput.value = 'Rejected';
                btn.innerText = 'Confirm Rejection';
                btn.className = 'btn btn-danger px-4';
                modalTitle.innerText = 'Reject Case';
                remarkLabel.innerText = 'Reason for Rejection (Required)';
                remarkLabel.className = 'form-label fw-bold text-danger';
            } else {
                statusInput.value = ''; 
                btn.innerText = 'Update Status';
                btn.className = 'btn btn-primary px-4';
                modalTitle.innerText = data.case_type + ' Case Details';
                remarkLabel.innerText = 'Add Staff Remark';
                remarkLabel.className = 'form-label fw-bold';
            }

            myModal.show();
        }
    </script>

    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
