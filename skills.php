<?php
require_once '../includes/db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $technical_skills = filter_input(INPUT_POST, 'technical_skills', FILTER_SANITIZE_STRING);
    $soft_skills = filter_input(INPUT_POST, 'soft_skills', FILTER_SANITIZE_STRING);
    $certificates = filter_input(INPUT_POST, 'certificates', FILTER_SANITIZE_STRING);

    // Validation
    if (empty($technical_skills)) $errors[] = "Technical skills are required";
    if (empty($soft_skills)) $errors[] = "Soft skills are required";

    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            // Check if skills record already exists
            $stmt = $conn->prepare("SELECT id FROM skills WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE skills SET 
                    technical_skills = ?, soft_skills = ?, certificates = ? 
                    WHERE user_id = ?");
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO skills 
                    (technical_skills, soft_skills, certificates, user_id) 
                    VALUES (?, ?, ?, ?)");
            }
            
            $stmt->execute([
                $technical_skills, $soft_skills, $certificates, $_SESSION['user_id']
            ]);
            
            $success = true;
            // Redirect to documents page
            redirect('documents.php');
        } catch (PDOException $e) {
            $errors[] = "Failed to save information: " . $e->getMessage();
        }
    }
}

// Fetch existing data if available
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM skills WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $skills = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Failed to fetch existing data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skills - CV Shortlisting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Skills</h3>
                    </div>
                    <div class="card-body">
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
                                Information saved successfully! Redirecting...
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="technical_skills" class="form-label">Technical Skills</label>
                                <textarea class="form-control" id="technical_skills" name="technical_skills" rows="4" 
                                          placeholder="Enter your technical skills (e.g., Programming languages, tools, technologies)" required><?php echo htmlspecialchars($skills['technical_skills'] ?? ''); ?></textarea>
                                <div class="form-text">Separate skills with commas</div>
                            </div>

                            <div class="mb-3">
                                <label for="soft_skills" class="form-label">Soft Skills</label>
                                <textarea class="form-control" id="soft_skills" name="soft_skills" rows="4" 
                                          placeholder="Enter your soft skills (e.g., Communication, Leadership, Teamwork)" required><?php echo htmlspecialchars($skills['soft_skills'] ?? ''); ?></textarea>
                                <div class="form-text">Separate skills with commas</div>
                            </div>

                            <div class="mb-3">
                                <label for="certificates" class="form-label">Certificates</label>
                                <textarea class="form-control" id="certificates" name="certificates" rows="4" 
                                          placeholder="Enter your certificates and achievements"><?php echo htmlspecialchars($skills['certificates'] ?? ''); ?></textarea>
                                <div class="form-text">Separate certificates with commas</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Save and Continue</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>