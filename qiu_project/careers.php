<?php
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

// Get all open job positions with department names
$jobs = $conn->query("
    SELECT jp.*, d.name as department_name 
    FROM job_positions jp 
    LEFT JOIN departments d ON jp.department_id = d.id 
    WHERE jp.status = 'open' 
    ORDER BY jp.posted_date DESC
");

// Get departments for filter
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// Handle application submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $position_id = (int)$_POST['position_id'];
    $applicant_name = sanitize($_POST['applicant_name']);
    $applicant_email = sanitize($_POST['applicant_email']);
    $applicant_phone = sanitize($_POST['applicant_phone']);
    $cover_letter = sanitize($_POST['cover_letter']);
    
    // Handle CV upload
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/cvs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx'];
        
        if (in_array($ext, $allowed)) {
            $cv_filename = uniqid('cv_') . '_' . time() . '.' . $ext;
            $cv_path = $upload_dir . $cv_filename;
            
            if (move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) {
                $stmt = $conn->prepare("INSERT INTO job_applications (position_id, applicant_name, applicant_email, applicant_phone, cv_path, cover_letter) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $position_id, $applicant_name, $applicant_email, $applicant_phone, $cv_path, $cover_letter);
                
                if ($stmt->execute()) {
                    $success = "Your application has been submitted successfully! We will contact you soon.";
                } else {
                    $error = "Error submitting application. Please try again.";
                }
            } else {
                $error = "Failed to upload CV. Please try again.";
            }
        } else {
            $error = "Invalid file type. Only PDF, DOC, DOCX allowed.";
        }
    } else {
        $error = "Please upload your CV.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers | Qaiwan International University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0e244c;
            --primary-light: #1a3a6e;
            --accent: #c9a227;
            --accent-light: #e8c547;
            --white: #ffffff;
            --off-white: #f8f9fc;
            --gray-100: #f1f3f8;
            --gray-200: #e2e6ee;
            --gray-400: #9ca3b4;
            --gray-600: #5a6275;
            --gray-800: #2d3142;
            --success: #059669;
            --danger: #dc2626;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--off-white);
            color: var(--gray-800);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ===== Header/Navigation ===== */
        .header {
            background: var(--primary);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(14, 36, 76, 0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo img {
            height: 55px;
            width: auto;
        }

        .logo-text {
            color: var(--white);
        }

        .logo-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .logo-text span {
            font-size: 11px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        .btn-login {
            background: var(--accent);
            color: var(--primary) !important;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
        }

        .btn-login::after {
            display: none;
        }

        .btn-login:hover {
            background: var(--accent-light);
            color: var(--primary) !important;
        }

        /* ===== Hero Section ===== */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 180px 40px 120px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .hero-pattern {
            position: absolute;
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(201, 162, 39, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-pattern-2 {
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(201, 162, 39, 0.15);
            border: 1px solid rgba(201, 162, 39, 0.3);
            padding: 10px 20px;
            border-radius: 50px;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease;
        }

        .hero-badge i {
            color: var(--accent);
        }

        .hero-badge span {
            color: var(--accent);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 20px;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }

        .hero h1 span {
            color: var(--accent);
        }

        .hero p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto 40px;
            animation: fadeInUp 0.8s ease 0.4s backwards;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            animation: fadeInUp 0.8s ease 0.6s backwards;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ===== Main Content ===== */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .section-header p {
            color: var(--gray-600);
            font-size: 18px;
        }

        /* ===== Filters ===== */
        .filters {
            background: var(--white);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--gray-600);
            margin-bottom: 8px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(14, 36, 76, 0.1);
        }

        .btn-search {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 16px 32px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 24px;
        }

        .btn-search:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 36, 76, 0.25);
        }

        /* ===== Jobs Grid ===== */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 30px;
        }

        .job-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(14, 36, 76, 0.15);
        }

        .job-card:hover::before {
            transform: scaleX(1);
        }

        .job-card-header {
            padding: 30px 30px 0;
        }

        .job-department {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--gray-100);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .job-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .job-card-body {
            padding: 20px 30px;
        }

        .job-description {
            color: var(--gray-600);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--gray-600);
        }

        .job-meta-item i {
            color: var(--accent);
            font-size: 16px;
        }

        .job-card-footer {
            padding: 20px 30px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--gray-100);
        }

        .job-deadline {
            font-size: 13px;
            color: var(--gray-400);
        }

        .job-deadline strong {
            color: var(--gray-800);
        }

        .btn-apply {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-apply:hover {
            background: var(--accent);
            color: var(--primary);
            transform: scale(1.05);
        }

        /* ===== No Jobs ===== */
        .no-jobs {
            text-align: center;
            padding: 80px 40px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .no-jobs i {
            font-size: 60px;
            color: var(--gray-200);
            margin-bottom: 20px;
        }

        .no-jobs h3 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .no-jobs p {
            color: var(--gray-600);
        }

        /* ===== Modal ===== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(14, 36, 76, 0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal {
            background: var(--white);
            border-radius: 24px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-header {
            padding: 30px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--primary);
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: var(--gray-100);
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--danger);
            color: var(--white);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 8px;
        }

        .form-group label span {
            color: var(--danger);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(14, 36, 76, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .file-upload {
            border: 2px dashed var(--gray-200);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: var(--gray-100);
        }

        .file-upload i {
            font-size: 40px;
            color: var(--gray-400);
            margin-bottom: 10px;
        }

        .file-upload p {
            color: var(--gray-600);
            font-size: 14px;
        }

        .file-upload span {
            color: var(--primary);
            font-weight: 600;
        }

        .file-upload input {
            display: none;
        }

        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* ===== Alerts ===== */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #ecfdf5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        /* ===== Footer ===== */
        .footer {
            background: var(--primary);
            padding: 60px 40px 30px;
            margin-top: 80px;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 60px;
        }

        .footer-brand h3 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--white);
            margin-bottom: 15px;
        }

        .footer-brand p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            line-height: 1.8;
        }

        .footer-links h4 {
            color: var(--accent);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .footer-links a {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 12px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            max-width: 1400px;
            margin: 40px auto 0;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* ===== Animations ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== Job Card Animation on Scroll ===== */
        .job-card {
            animation: fadeInUp 0.6s ease backwards;
        }

        .job-card:nth-child(1) { animation-delay: 0.1s; }
        .job-card:nth-child(2) { animation-delay: 0.2s; }
        .job-card:nth-child(3) { animation-delay: 0.3s; }
        .job-card:nth-child(4) { animation-delay: 0.4s; }
        .job-card:nth-child(5) { animation-delay: 0.5s; }
        .job-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="assets/images/qiu.svg" alt="QIU Logo">
                <div class="logo-text">
                    <h1>Qaiwan International University</h1>
                    <span>Excellence in Education</span>
                </div>
            </a>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="careers.php">Careers</a>
                <a href="contact.php">Contact</a>
                <a href="login.php" class="btn-login">Login</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-pattern"></div>
        <div class="hero-pattern-2"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-briefcase"></i>
                <span>Join Our Team</span>
            </div>
            <h1>Build Your <span>Career</span> With Us</h1>
            <p>Join a community of passionate educators and professionals dedicated to shaping the future of education in Kurdistan.</p>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $jobs->num_rows; ?>+</div>
                    <div class="stat-label">Open Positions</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">7</div>
                    <div class="stat-label">Departments</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Team Members</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <h2>Open Positions</h2>
            <p>Discover exciting opportunities to grow with us</p>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label>Department</label>
                <select name="department">
                    <option value="">All Departments</option>
                    <?php 
                    $departments->data_seek(0);
                    while ($dept = $departments->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Search positions..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i>
                Search Jobs
            </button>
        </form>

        <!-- Jobs Grid -->
        <?php 
        // Reset and filter jobs
        $jobs->data_seek(0);
        $filtered_jobs = [];
        while ($job = $jobs->fetch_assoc()) {
            $show = true;
            if (isset($_GET['department']) && $_GET['department'] && $job['department_id'] != $_GET['department']) {
                $show = false;
            }
            if (isset($_GET['search']) && $_GET['search']) {
                $search = strtolower($_GET['search']);
                if (strpos(strtolower($job['title']), $search) === false && 
                    strpos(strtolower($job['description']), $search) === false) {
                    $show = false;
                }
            }
            if ($show) {
                $filtered_jobs[] = $job;
            }
        }
        ?>

        <?php if (count($filtered_jobs) > 0): ?>
            <div class="jobs-grid">
                <?php foreach ($filtered_jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-card-header">
                            <span class="job-department">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($job['department_name'] ?? 'General'); ?>
                            </span>
                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                        </div>
                        <div class="job-card-body">
                            <p class="job-description"><?php echo htmlspecialchars($job['description']); ?></p>
                            <div class="job-meta">
                                <div class="job-meta-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <?php echo htmlspecialchars($job['salary_range'] ?? 'Competitive'); ?>
                                </div>
                                <div class="job-meta-item">
                                    <i class="fas fa-clock"></i>
                                    Full-time
                                </div>
                                <div class="job-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Sulaymaniyah
                                </div>
                            </div>
                        </div>
                        <div class="job-card-footer">
                            <span class="job-deadline">
                                Deadline: <strong><?php echo date('M d, Y', strtotime($job['deadline'])); ?></strong>
                            </span>
                            <button class="btn-apply" onclick="openModal(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars(addslashes($job['title'])); ?>')">
                                Apply Now
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-jobs">
                <i class="fas fa-search"></i>
                <h3>No Positions Found</h3>
                <p>There are no open positions matching your criteria at the moment. Please check back later.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Application Modal -->
    <div class="modal-overlay" id="applicationModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Apply for <span id="modalJobTitle"></span></h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="position_id" id="positionId">
                    
                    <div class="form-group">
                        <label>Full Name <span>*</span></label>
                        <input type="text" name="applicant_name" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address <span>*</span></label>
                        <input type="email" name="applicant_email" required placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number <span>*</span></label>
                        <input type="tel" name="applicant_phone" required placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label>Upload CV <span>*</span></label>
                        <label class="file-upload" id="fileUpload">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p><span>Click to upload</span> or drag and drop</p>
                            <p>PDF, DOC, DOCX (Max 5MB)</p>
                            <input type="file" name="cv" accept=".pdf,.doc,.docx" required onchange="updateFileName(this)">
                        </label>
                        <p id="fileName" style="margin-top: 10px; color: var(--success); font-size: 14px;"></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Cover Letter</label>
                        <textarea name="cover_letter" placeholder="Tell us why you're the perfect fit for this position..."></textarea>
                    </div>
                    
                    <button type="submit" name="apply" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <h3>Qaiwan International University</h3>
                <p>Dedicated to providing world-class education and fostering innovation in Kurdistan. Join us in shaping the future of education.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="index.php">Home</a>
                <a href="about.php">About Us</a>
                <a href="careers.php">Careers</a>
                <a href="contact.php">Contact</a>
            </div>
            <div class="footer-links">
                <h4>Resources</h4>
                <a href="#">Student Portal</a>
                <a href="#">Staff Portal</a>
                <a href="#">Library</a>
                <a href="#">Research</a>
            </div>
            <div class="footer-links">
                <h4>Contact Info</h4>
                <a href="tel:+9647721411414"><i class="fas fa-phone"></i> +964 772 141 1414</a>
                <a href="mailto:info@uniq.edu.iq"><i class="fas fa-envelope"></i> info@uniq.edu.iq</a>
                <a href="#"><i class="fas fa-map-marker-alt"></i> Sulaymaniyah, Kurdistan</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Qaiwan International University. All rights reserved.</p>
            <div class="social-links">
                <a href="https://www.facebook.com/qaiwanuniversity" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/qaiwanuniversity" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://www.linkedin.com/school/qaiwanuniversity" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://twitter.com/qaiwanuniversity" target="_blank"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </footer>

    <script>
        function openModal(positionId, jobTitle) {
            document.getElementById('positionId').value = positionId;
            document.getElementById('modalJobTitle').textContent = jobTitle;
            document.getElementById('applicationModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('applicationModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileName').textContent = '📎 ' + input.files[0].name;
            }
        }

        // Close modal on outside click
        document.getElementById('applicationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
