<?php
require_once '../includes/db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$errors = [];
$success = false;

// Handle job application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    
    if ($job_id) {
        try {
            $conn = getDBConnection();
            
            // Check if already applied
            $stmt = $conn->prepare("SELECT id FROM job_applications WHERE user_id = ? AND job_id = ?");
            $stmt->execute([$_SESSION['user_id'], $job_id]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "You have already applied for this job.";
            } else {
                // Insert new application
                $stmt = $conn->prepare("INSERT INTO job_applications (user_id, job_id, application_date) VALUES (?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $job_id]);
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = "Failed to submit application: " . $e->getMessage();
        }
    }
}

// Fetch all active job advertisements
try {
    $conn = getDBConnection();
    $query = "SELECT ja.*, u.email as manager_email 
              FROM job_advertisements ja 
              JOIN users u ON ja.manager_id = u.id 
              WHERE ja.status = 'active' AND ja.expiry_date >= CURDATE() 
              ORDER BY ja.posted_date DESC";
    $stmt = $conn->query($query);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch user's applications
    $stmt = $conn->prepare("SELECT job_id FROM job_applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $applied_jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $errors[] = "Failed to fetch jobs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Jobs - CV Shortlisting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Candidate Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="jobs.php">Available Jobs</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                Job application submitted successfully!
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Available Job Positions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobs)): ?>
                            <div class="alert alert-info">
                                No job positions are currently available.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($jobs as $job): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <?php echo htmlspecialchars($job['company']); ?>
                                                </h6>
                                                
                                                <div class="mb-3">
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                                    <?php if ($job['salary_range']): ?>
                                                        <span class="badge bg-success"><?php echo htmlspecialchars($job['salary_range']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <p class="card-text">
                                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?><br>
                                                    <i class="bi bi-calendar"></i> Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?><br>
                                                    <i class="bi bi-clock"></i> Expires: <?php echo date('M d, Y', strtotime($job['expiry_date'])); ?>
                                                </p>
                                                
                                                <div class="mb-3">
                                                    <strong>Description:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Requirements:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                                                </div>
                                                
                                                <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                    <button class="btn btn-secondary" disabled>
                                                        <i class="bi bi-check-circle"></i> Applied
                                                    </button>
                                                <?php else: ?>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <button type="submit" name="apply_job" class="btn btn-primary">
                                                            <i class="bi bi-send"></i> Apply Now
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" 
                                                        data-bs-target="#jobDetailsModal<?php echo $job['id']; ?>">
                                                    <i class="bi bi-info-circle"></i> More Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Job Details Modal -->
                                    <div class="modal fade" id="jobDetailsModal<?php echo $job['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <h6>Company Information</h6>
                                                    <p>
                                                        <strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?><br>
                                                        <strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?><br>
                                                        <strong>Job Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?><br>
                                                        <?php if ($job['salary_range']): ?>
                                                            <strong>Salary Range:</strong> <?php echo htmlspecialchars($job['salary_range']); ?><br>
                                                        <?php endif; ?>
                                                    </p>
                                                    
                                                    <h6>Job Description</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                                    
                                                    <h6>Requirements</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                                                    
                                                    <h6>Additional Information</h6>
                                                    <p>
                                                        <strong>Posted Date:</strong> <?php echo date('M d, Y', strtotime($job['posted_date'])); ?><br>
                                                        <strong>Expiry Date:</strong> <?php echo date('M d, Y', strtotime($job['expiry_date'])); ?><br>
                                                        <strong>Contact Email:</strong> <?php echo htmlspecialchars($job['manager_email']); ?>
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <?php if (!in_array($job['id'], $applied_jobs)): ?>
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <button type="submit" name="apply_job" class="btn btn-primary">
                                                                <i class="bi bi-send"></i> Apply Now
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>