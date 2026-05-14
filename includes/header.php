<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTI Region IX - Admin Panel</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/bootstrap/icons/bootstrap-icons.css">
    <link type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/2.3.0/styles/overlayscrollbars.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <style>
        :root {
            --dti-blue: #0F172A; 
            --sidebar-width: 195px;
            --sidebar-collapsed: 75px;
            --navbar-height: 70px;
        }
        
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f1f5f9 50%, #ffe4e6 100%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif !important;
            letter-spacing: -0.01em;
            overflow-x: hidden;
        }

        .top-navbar {
            height: var(--navbar-height);
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1050;
            background: rgba(255, 255, 255, 0.7) !important;
            backdrop-filter: blur(12px) saturate(150%);
            -webkit-backdrop-filter: blur(12px) saturate(150%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
        }

       .sidebar {
            position: fixed;
            top: var(--navbar-height);
            bottom: 0;
            left: 0;
            z-index: 1040;
            width: var(--sidebar-width);
            
            background: linear-gradient(180deg, rgba(5, 10, 20, 0.98) 0%, rgba(15, 23, 42, 0.92) 100%);
            backdrop-filter: blur(15px) saturate(150%);
            -webkit-backdrop-filter: blur(15px) saturate(150%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);

            
            padding-top: 1rem;
            overflow-x: hidden;
            white-space: nowrap;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.08);
        }
        
        .sidebar-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 1.05rem;
            border-left: 4px solid transparent; 
        }
        
        .sidebar-link i {
            font-size: 1.3rem;
            min-width: 30px;
            margin-right: 15px;
            text-align: center;
        }
        
        .sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-link.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #ffffff;
            box-shadow: inset 4px 0 0px rgba(255, 255, 255, 0.2);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--navbar-height);
            min-height: calc(100vh - var(--navbar-height));
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 20px;
        }

        body.sidebar-collapsed .sidebar { width: var(--sidebar-collapsed); }
        body.sidebar-collapsed .main-content { margin-left: var(--sidebar-collapsed); }
        body.sidebar-collapsed .sidebar-text { display: none; }

        /* burger */
        #sidebarToggle {
            cursor: pointer;
            border-radius: 50%;
            margin-left: -6px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        /* Make hover state translucent to match glass */
        #sidebarToggle:hover { background-color: rgba(0, 0, 0, 0.05); }

        /* Make profile button match glass */
        .profile-dropdown-btn:hover .rounded-circle {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }
        .rounded-circle.bg-light {
            background-color: rgba(255, 255, 255, 0.5) !important;
            backdrop-filter: blur(5px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px); 
            }
            to {
                opacity: 1;
                transform: translateY(0); 
            }
        }

        .page-transition {
            animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .card-gradient-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
        }
        .card-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%) !important;
        }
        .card-gradient-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%) !important;
        }

        .stat-icon-wrapper {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .filter-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b; 
            font-weight: 700;
            margin-bottom: 0.4rem;
        }

        .filter-input {
            background-color: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(15, 23, 42, 0.1) !important; 
            border-radius: 8px; 
            color: #0F172A !important;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
        }

        .filter-input:focus {
            background-color: #ffffff !important;
            border-color: #3b82f6 !important; 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
            outline: none;
        }
        
        select.filter-input {
            cursor: pointer;
        }

        .btn-glass-danger {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #ef4444 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
            transition: all 0.3s ease;
        }
        .btn-glass-danger:hover {
            background: rgba(239, 68, 68, 0.25) !important;
            color: #dc2626 !important;
            transform: translateY(-2px);
        }

        /* --- PROFILE DROPDOWN  --- */
        .dropdown-item {
            display: flex !important;
            align-items: center !important;
            padding: 0.6rem 1.2rem !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
            transition: all 0.2s ease;
        }

        .dropdown-item i {
            font-size: 1.1rem !important;
            width: 24px;
            text-align: center;
        }

        .dropdown-item.logout-link {
            color: #ef4444 !important;
        }

        .dropdown-item.logout-link:hover {
            background-color: rgba(239, 68, 68, 0.08) !important;
            color: #dc2626 !important;
        }

            @keyframes fadeInGlide {
                from {
                    opacity: 0;
                    transform: translateY(15px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .dashboard-animate {
                animation: fadeInGlide 0.5s ease-out forwards;
            }

            .page-transition {
                opacity: 0;
                animation: fadeInGlide 0.3s ease-out forwards;
            }

  /* --- MOBILE RESPONSIVENESS  */
        @media (max-width: 768px) {
            html, body { font-size: 14px !important; }
            h3 { font-size: 1.5rem !important; }
            h4 { font-size: 1.2rem !important; }
            h5 { font-size: 1.1rem !important; }
            h6 { font-size: 0.95rem !important; }
            p, span, .text-muted, .form-label { font-size: 0.85rem !important; }
            .btn { font-size: 0.85rem !important; padding: 0.4rem 0.8rem !important; }
            .table td, .table th { font-size: 0.8rem !important; padding: 0.5rem !important; }
            
            #sidebar {
                position: fixed !important;
                top: 0;
                left: -100% !important; 
                width: 190px !important; 
                height: 100vh;
                z-index: 1060 !important; 
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: none !important; 
            }
            
            #sidebar.active {
                left: 0 !important; /* Pulls it perfectly onto the screen */
                box-shadow: 4px 0 24px rgba(15, 23, 42, 0.15) !important;
            }
            
            #content, .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem !important;
            }
            
            div[style*="width: 80%"], div[style*="width: 75%"] {
                width: 100% !important;
            }
            
            .step-indicator {
                font-size: 0.8rem !important;
                flex: 1 1 45%; 
                text-align: center;
                margin-bottom: 0.5rem;
                padding: 0.25rem;
            }

        .mobile-overlay {
            visibility: hidden;
            opacity: 0;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(3px);
            z-index: 1055; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mobile-overlay.active {
            visibility: visible;
            opacity: 1;
        }
            
        }
    </style>
</head>
<body>

    <header class="top-navbar">
        <div class="d-flex align-items-center">
            <div id="sidebarToggle" class="me-2" onclick="toggleSidebar()">
                <i class="bi bi-list fs-3 text-dark"></i>
            </div>
            
            <div class="d-flex align-items-center ms-2">
                <img src="../assets/img/dtino.png" alt="DTI Logo" style="height: 35px; margin-right: 12px;">
                <!-- <h1 class="fw-bold fs-5 text-dark letter" style="font-weight: 900 !important; margin-top: 0.5rem;"> DTI IX - Regional Office IX</h1> -->
            </div>
        </div>
        
        <div class="dropdown">
            <div class="d-flex align-items-center profile-dropdown-btn" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.2;">
                        <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                    </div>
                    <div class="text-muted small" style="font-size: 0.65rem;">System Administrator</div>
                </div>
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center transition-all" style="width: 40px; height: 40px; border: 1px solid #e2e8f0;">
                    <i class="bi bi-person-fill text-secondary fs-5"></i>
                </div>
            </div>
            
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="profileDropdown" style="border-radius: 12px; padding: 0.5rem;">
                <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Account Management</h6></li>
                
                <li>
                    <a class="dropdown-item py-2" href="account_settings.php">
                        <i class="bi bi-gear me-3 text-muted"></i> 
                        <span>Account Settings</span>
                    </a>
                </li>
                
                <li><hr class="dropdown-divider mx-2 opacity-50"></li>
                
                <li>
                    <a class="dropdown-item logout-link fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="bi bi-box-arrow-right me-3"></i> 
                        <span>Secure Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </header>

    <nav class="sidebar" id="sidebar">
        <div class="d-flex align-items-center d-md-none px-3 mb-2" style="height: 70px; border-bottom: 1px solid rgba(255,255,255,0.08);">
            <div onclick="toggleSidebar()" class="me-2" style="cursor: pointer; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-list fs-3 text-white"></i>
            </div>
            <div class="d-flex align-items-center ms-2">
                <!-- <img src="../assets/img/dtino.png" alt="DTI Logo" style="height: 35px;"> -->
            </div>
        </div>
        <div class="d-flex flex-column h-100">
            <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
            
            <a href="dashboard.php" class="sidebar-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i> <span class="sidebar-text">Dashboard</span>
            </a>
            
            <a href="add_employee.php" class="sidebar-link <?php echo ($currentPage == 'add_employee.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-plus-fill"></i> <span class="sidebar-text">Add Profile</span>
            </a>
            
            <a href="manage_employees.php" class="sidebar-link <?php echo ($currentPage == 'manage_employees.php' || $currentPage == 'view_employee.php' || $currentPage == 'edit_employee.php' || $currentPage == 'recycle_bin.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i> <span class="sidebar-text">Manage</span>
            </a>
            
            </div>

            <!-- <div class="mobile-overlay" id="mobileOverlay" onclick="toggleSidebar()"></div> -->
    </nav>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body p-4 text-center">
                <div class="mb-3 d-flex justify-content-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(15, 23, 42, 0.1); border: 1px solid rgba(15, 23, 42, 0.2);">
                        <i class="bi bi-box-arrow-right fs-4" style="color: #0F172A;"></i>
                    </div>
                </div>
                <h6 class="fw-bold mb-2" style="color: #0F172A; font-size: 1.1rem;">Ready to leave?</h6>
                <p class="text-muted small mb-4">Are you sure you want to end your current session and log out?</p>
                
                <div class="d-flex gap-2 w-100">
                    <button type="button" class="btn btn-light fw-bold flex-fill shadow-sm" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <a href="logout.php" class="btn fw-bold flex-fill text-white shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">Yes, Log Out</a>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const body = document.body;

    if (window.innerWidth > 768 && localStorage.getItem('sidebar-state') === 'collapsed') {
        body.classList.add('sidebar-collapsed');
    }

    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            // --- MOBILE BEHAVIOR 
            document.getElementById('sidebar').classList.toggle('active');
            
            let overlay = document.getElementById('mobileOverlay');
            if(overlay) {
                overlay.classList.toggle('active');
            }
        } else {
            body.classList.toggle('sidebar-collapsed');
            
            if (body.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('sidebar-state', 'collapsed');
            } else {
                localStorage.setItem('sidebar-state', 'expanded');
            }
        }
    }
</script>

    <main class="main-content page-transition">