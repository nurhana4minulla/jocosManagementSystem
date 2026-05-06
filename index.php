<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | JO/COS Profiling System</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            
            background-image: 
                linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.85)),
                url('assets/img/food.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

    
        .glass-card {
            background: rgba(255, 255, 255, 0.6) !important; 
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.8) !important;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06); 
            border-radius: 1.25rem;
            color: #0F172A; 
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2.5rem;
        }


        .glass-input {
            background: rgba(255, 255, 255, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            color: #0F172A !important;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .glass-input:focus {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
        }

        .glass-input::placeholder {
            color: #64748b;
        }

   
        .btn-hybrid {
            background-color: #0F172A;
            color: white;
            font-weight: 700;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-hybrid:hover {
            background-color: #1e293b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(15, 23, 42, 0.2);
        }
    </style>
</head>
<body>

    <div class="glass-card">
        <div class="text-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
            <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.5px;">DTI Region IX</h3>
            <p class="mb-0 small text-muted fw-medium">Finance & Administrative Office</p>
        </div>
        
        <div class="login-body">
            <!-- <h5 class="text-center fw-bold mb-4" style="color: #0F172A; letter-spacing: 0.5px;">SYSTEM LOG IN</h5> -->
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center p-2 small shadow-sm border-0" style="background: rgba(254, 226, 226, 0.8); color: #991b1b;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Invalid username or password.
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label small fw-bold text-secondary">Username</label>
                    <input type="text" class="form-control glass-input shadow-sm" id="username" name="username" placeholder="Enter your username" required autocomplete="off">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold text-secondary">Password</label>
                    <input type="password" class="form-control glass-input shadow-sm" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-hybrid w-100 py-2 mt-2">Log In </button>
            </form>
        </div>
    </div>

</body>
</html>