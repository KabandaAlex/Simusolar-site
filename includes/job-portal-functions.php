<?php
/**
 * Tulima Solar - Job Portal Helper Functions
 * 
 * This file contains all the helper functions for the job portal,
 * including job management, applications, categories, and locations.
 * 
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

/**
 * Get all job categories with optional filtering
 * 
 * @param PDO $pdo Database connection
 * @param bool $activeOnly Only return active categories
 * @return array Array of categories
 */
function getJobCategories(PDO $pdo, bool $activeOnly = true): array {
    try {
        $sql = 'SELECT * FROM job_categories';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, name';
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching job categories: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single job category by ID or slug
 * 
 * @param PDO $pdo Database connection
 * @param int|string $identifier Category ID or slug
 * @return array|null Category data or null
 */
function getJobCategory(PDO $pdo, $identifier): ?array {
    try {
        if (is_numeric($identifier)) {
            $stmt = $pdo->prepare('SELECT * FROM job_categories WHERE id = ?');
            $stmt->execute([(int)$identifier]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM job_categories WHERE slug = ?');
            $stmt->execute([$identifier]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Error fetching job category: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get all job locations with optional filtering
 * 
 * @param PDO $pdo Database connection
 * @param bool $activeOnly Only return active locations
 * @return array Array of locations
 */
function getJobLocations(PDO $pdo, bool $activeOnly = true): array {
    try {
        $sql = 'SELECT * FROM job_locations';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, name';
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching job locations: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single job location by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $id Location ID
 * @return array|null Location data or null
 */
function getJobLocation(PDO $pdo, int $id): ?array {
    try {
        $stmt = $pdo->prepare('SELECT * FROM job_locations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Error fetching job location: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get all job tags
 * 
 * @param PDO $pdo Database connection
 * @return array Array of tags
 */
function getJobTags(PDO $pdo): array {
    try {
        $stmt = $pdo->query('SELECT * FROM job_tags ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching job tags: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get tags for a specific job
 * 
 * @param PDO $pdo Database connection
 * @param int $jobId Job ID
 * @return array Array of tags
 */
function getJobTagsForJob(PDO $pdo, int $jobId): array {
    try {
        $stmt = $pdo->prepare('
            SELECT t.* 
            FROM job_tags t 
            INNER JOIN job_tag_relations jtr ON t.id = jtr.tag_id 
            WHERE jtr.job_id = ?
            ORDER BY t.name
        ');
        $stmt->execute([$jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching job tags: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single job by ID or slug
 * 
 * @param PDO $pdo Database connection
 * @param int|string $identifier Job ID or slug
 * @param string $status Filter by status (optional)
 * @return array|null Job data or null
 */
function getJob(PDO $pdo, $identifier, string $status = null): ?array {
    try {
        $sql = 'SELECT j.*, 
                       jc.name as category_name, jc.slug as category_slug,
                       jl.name as location_name, jl.country as location_country, 
                       jl.city as location_city, jl.is_remote
                FROM jobs j
                LEFT JOIN job_categories jc ON j.category_id = jc.id
                LEFT JOIN job_locations jl ON j.location_id = jl.id
                WHERE ';
        
        if (is_numeric($identifier)) {
            $sql .= 'j.id = ?';
            $params = [(int)$identifier];
        } else {
            $sql .= 'j.slug = ?';
            $params = [$identifier];
        }
        
        if ($status) {
            $sql .= ' AND j.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' LIMIT 1';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Error fetching job: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get jobs with filtering and pagination
 * 
 * @param PDO $pdo Database connection
 * @param array $filters Filter options
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array Array with 'jobs' and 'total' keys
 */
function getJobs(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 10): array {
    try {
        $where = ['1=1'];
        $params = [];
        
        // Status filter
        if (!empty($filters['status'])) {
            $where[] = 'j.status = ?';
            $params[] = $filters['status'];
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = 'j.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        
        // Category slug filter
        if (!empty($filters['category_slug'])) {
            $where[] = 'jc.slug = ?';
            $params[] = $filters['category_slug'];
        }
        
        // Location filter
        if (!empty($filters['location_id'])) {
            $where[] = 'j.location_id = ?';
            $params[] = (int)$filters['location_id'];
        }
        
        // Job type filter
        if (!empty($filters['job_type'])) {
            $where[] = 'j.job_type = ?';
            $params[] = $filters['job_type'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Featured only
        if (!empty($filters['featured'])) {
            $where[] = 'j.is_featured = 1';
        }
        
        // Exclude expired (if status is active)
        if (!empty($filters['exclude_expired'])) {
            $where[] = '(j.application_deadline IS NULL OR j.application_deadline >= NOW())';
        }
        
        // Count total
        $countSql = 'SELECT COUNT(DISTINCT j.id) FROM jobs j 
                     LEFT JOIN job_categories jc ON j.category_id = jc.id
                     LEFT JOIN job_locations jl ON j.location_id = jl.id
                     WHERE ' . implode(' AND ', $where);
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        // Get jobs
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT j.*, 
                       jc.name as category_name, jc.slug as category_slug,
                       jl.name as location_name, jl.is_remote
                FROM jobs j
                LEFT JOIN job_categories jc ON j.category_id = jc.id
                LEFT JOIN job_locations jl ON j.location_id = jl.id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY j.is_featured DESC, j.is_urgent DESC, j.created_at DESC
                LIMIT ' . $offset . ', ' . $perPage;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'jobs' => $jobs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, ceil($total / $perPage))
        ];
    } catch (PDOException $e) {
        error_log('Error fetching jobs: ' . $e->getMessage());
        return [
            'jobs' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => 1
        ];
    }
}

/**
 * Get related jobs (same category)
 * 
 * @param PDO $pdo Database connection
 * @param int $jobId Current job ID
 * @param int $categoryId Category ID
 * @param int $limit Maximum number of results
 * @return array Array of related jobs
 */
function getRelatedJobs(PDO $pdo, int $jobId, int $categoryId, int $limit = 3): array {
    try {
        $stmt = $pdo->prepare('
            SELECT j.id, j.title, j.slug, j.job_type, j.created_at,
                   jl.name as location_name
            FROM jobs j
            LEFT JOIN job_locations jl ON j.location_id = jl.id
            WHERE j.category_id = ? AND j.id != ? AND j.status = "active"
            ORDER BY j.created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$categoryId, $jobId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching related jobs: ' . $e->getMessage());
        return [];
    }
}

/**
 * Submit a job application
 * 
 * @param PDO $pdo Database connection
 * @param int $jobId Job ID
 * @param array $data Application data
 * @return array Result with 'success' and 'message' or 'error' keys
 */
function submitJobApplication(PDO $pdo, int $jobId, array $data): array {
    try {
        // Validate required fields
        if (empty($data['applicant_name'])) {
            return ['success' => false, 'error' => 'Name is required'];
        }
        if (empty($data['applicant_email']) || !filter_var($data['applicant_email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Valid email is required'];
        }
        
        // Check if job exists and is active
        $job = getJob($pdo, $jobId, 'active');
        if (!$job) {
            return ['success' => false, 'error' => 'Job not found or no longer active'];
        }
        
        // Check for duplicate application
        $checkStmt = $pdo->prepare('SELECT id FROM job_applications WHERE job_id = ? AND applicant_email = ?');
        $checkStmt->execute([$jobId, $data['applicant_email']]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'error' => 'You have already applied for this position'];
        }
        
        // Insert application
        $stmt = $pdo->prepare('
            INSERT INTO job_applications (job_id, applicant_name, applicant_email, applicant_phone, cover_letter, resume_path, status)
            VALUES (?, ?, ?, ?, ?, ?, \'pending\')
        ');
        
        $stmt->execute([
            $jobId,
            $data['applicant_name'],
            $data['applicant_email'],
            $data['applicant_phone'] ?? null,
            $data['cover_letter'] ?? null,
            $data['resume_path'] ?? null
        ]);
        
        $applicationId = (int)$pdo->lastInsertId();
        
        // Log activity if function exists
        if (function_exists('logActivity')) {
            logActivity($pdo, 'job_application_submitted', 'job_application', $applicationId, [
                'job_id' => $jobId,
                'job_title' => $job['title'],
                'applicant_name' => $data['applicant_name'],
                'applicant_email' => $data['applicant_email']
            ]);
        }
        
        // Create notification if function exists
        if (function_exists('createNotification')) {
            createNotification($pdo, 'new_job_application', [
                'job_id' => $jobId,
                'job_title' => $job['title'],
                'applicant_name' => $data['applicant_name']
            ]);
        }
        
        // Send email notification via SMTP (if configured)
        try {
            require_once __DIR__ . '/mailer.php';
            $cfg = getSmtpSettings($pdo);

            $recipients = [];
            if (!empty($cfg['job_recipients'])) {
                foreach (explode(',', $cfg['job_recipients']) as $rcpt) {
                    $rcpt = trim($rcpt);
                    if ($rcpt !== '' && filter_var($rcpt, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $rcpt;
                    }
                }
            }

            // Fallback to primary contact email if no explicit HR recipients
            if (empty($recipients)) {
                $primaryContactEmail = getSetting($pdo, 'hr_email', '') ?: getSetting($pdo, 'default_contact_email', '');
                if ($primaryContactEmail && filter_var($primaryContactEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $primaryContactEmail;
                }
            }

            if (!empty($recipients) && $cfg['enabled']) {
                $subjectLine = 'New Job Application: ' . $job['title'];

                $htmlBody = '<h2>New Job Application Submitted</h2>'
                    . '<p><strong>Job Title:</strong> ' . e($job['title']) . '</p>'
                    . '<p><strong>Applicant Name:</strong> ' . e($data['applicant_name']) . '</p>'
                    . '<p><strong>Email:</strong> ' . e($data['applicant_email']) . '</p>'
                    . '<p><strong>Phone:</strong> ' . e($data['applicant_phone'] ?? '') . '</p>'
                    . '<p><strong>Cover Letter:</strong><br>' . nl2br(e($data['cover_letter'] ?? '')) . '</p>';

                if (!empty($data['resume_path'])) {
                    $htmlBody .= '<p><strong>Resume:</strong> ' . e($data['resume_path']) . '</p>';
                }

                $textBody = "New job application submitted\n\n"
                    . "Job: {$job['title']}\n"
                    . "Applicant: {$data['applicant_name']}\n"
                    . "Email: {$data['applicant_email']}\n"
                    . "Phone: " . ($data['applicant_phone'] ?? '') . "\n\n"
                    . "Cover Letter:\n" . ($data['cover_letter'] ?? '') . "\n"
                    . (!empty($data['resume_path']) ? "\nResume: {$data['resume_path']}\n" : '');

                foreach ($recipients as $rcpt) {
                    $res = sendSmtpEmail($pdo, $rcpt, $subjectLine, $htmlBody, $textBody);
                    if (!$res['success']) {
                        error_log('Job application email error for ' . $rcpt . ': ' . $res['error']);
                    }
                }
            }
        } catch (Throwable $mailEx) {
            error_log('Job application mailer error: ' . $mailEx->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Application submitted successfully',
            'application_id' => $applicationId
        ];
    } catch (PDOException $e) {
        error_log('Error submitting job application: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to submit application. Please try again.'];
    }
}

/**
 * Get applications for a job
 * 
 * @param PDO $pdo Database connection
 * @param int $jobId Job ID
 * @param string $status Filter by status (optional)
 * @return array Array of applications
 */
function getJobApplications(PDO $pdo, int $jobId, string $status = null): array {
    try {
        $sql = 'SELECT * FROM job_applications WHERE job_id = ?';
        $params = [$jobId];
        
        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY created_at DESC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching job applications: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single application by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $id Application ID
 * @return array|null Application data or null
 */
function getJobApplication(PDO $pdo, int $id): ?array {
    try {
        $stmt = $pdo->prepare('
            SELECT ja.*, j.title as job_title, j.slug as job_slug
            FROM job_applications ja
            INNER JOIN jobs j ON ja.job_id = j.id
            WHERE ja.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Error fetching job application: ' . $e->getMessage());
        return null;
    }
}

/**
 * Update application status
 * 
 * @param PDO $pdo Database connection
 * @param int $id Application ID
 * @param string $status New status
 * @param string $notes Optional notes
 * @return bool Success
 */
function updateApplicationStatus(PDO $pdo, int $id, string $status, string $notes = null): bool {
    try {
        $allowedStatuses = ['pending', 'reviewed', 'interview', 'accepted', 'rejected'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }
        
        $stmt = $pdo->prepare('
            UPDATE job_applications 
            SET status = ?, notes = COALESCE(?, notes), updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        return $stmt->execute([$status, $notes, $id]);
    } catch (PDOException $e) {
        error_log('Error updating application status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a job
 * 
 * @param PDO $pdo Database connection
 * @param int $id Job ID
 * @param int|null $adminId Admin who performed the deletion (for logging)
 * @return bool Success
 */
function deleteJob(PDO $pdo, int $id, int $adminId = null): bool {
    try {
        // Get job info before deletion for logging
        $job = getJob($pdo, $id);
        
        // Delete job (cascade will handle related records)
        $stmt = $pdo->prepare('DELETE FROM jobs WHERE id = ?');
        $result = $stmt->execute([$id]);
        
        // Log activity if function exists
        if ($result && $job && function_exists('logActivity')) {
            logActivity($pdo, 'job_deleted', 'job', $id, [
                'job_title' => $job['title'],
                'deleted_by' => $adminId
            ]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log('Error deleting job: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get job statistics
 * 
 * @param PDO $pdo Database connection
 * @return array Statistics array
 */
function getJobStats(PDO $pdo): array {
    try {
        $stats = [
            'total_jobs' => 0,
            'active_jobs' => 0,
            'draft_jobs' => 0,
            'closed_jobs' => 0,
            'expired_jobs' => 0,
            'total_applications' => 0,
            'pending_applications' => 0,
            'categories' => 0,
            'locations' => 0
        ];
        
        $stats['total_jobs'] = (int)$pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
        $stats['active_jobs'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active' AND (application_deadline IS NULL OR application_deadline >= NOW())")->fetchColumn();
        $stats['draft_jobs'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'draft'")->fetchColumn();
        $stats['closed_jobs'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'closed'")->fetchColumn();
        $stats['expired_jobs'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active' AND application_deadline IS NOT NULL AND application_deadline < NOW()")->fetchColumn();
        $stats['total_applications'] = (int)$pdo->query('SELECT COUNT(*) FROM job_applications')->fetchColumn();
        $stats['pending_applications'] = (int)$pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'pending'")->fetchColumn();
        $stats['categories'] = (int)$pdo->query('SELECT COUNT(*) FROM job_categories WHERE is_active = 1')->fetchColumn();
        $stats['locations'] = (int)$pdo->query('SELECT COUNT(*) FROM job_locations WHERE is_active = 1')->fetchColumn();
        
        return $stats;
    } catch (PDOException $e) {
        error_log('Error fetching job stats: ' . $e->getMessage());
        return $stats;
    }
}

/**
 * Format salary for display
 * 
 * @param float|null $min Minimum salary
 * @param float|null $max Maximum salary
 * @param string $currency Currency code
 * @return string Formatted salary string
 */
function formatSalary(?float $min, ?float $max, string $currency = 'USD'): string {
    $currencies = [
        'USD' => '$',
        'KES' => 'KSh ',
        'EUR' => '€',
        'GBP' => '£',
        'UGX' => 'USh ',
        'TZS' => 'TSh ',
        'RWF' => 'FRw '
    ];
    
    $symbol = $currencies[$currency] ?? $currency . ' ';
    
    if ($min && $max) {
        return $symbol . number_format($min) . ' - ' . $symbol . number_format($max);
    } elseif ($min) {
        return 'From ' . $symbol . number_format($min);
    } elseif ($max) {
        return 'Up to ' . $symbol . number_format($max);
    }
    
    return 'Competitive';
}

/**
 * Get job type label
 * 
 * @param string $type Job type
 * @return string Label
 */
function getJobTypeLabel(string $type): string {
    $types = [
        'full-time' => 'Full Time',
        'part-time' => 'Part Time',
        'contract' => 'Contract',
        'internship' => 'Internship',
        'volunteer' => 'Volunteer'
    ];
    
    return $types[$type] ?? ucfirst($type);
}

/**
 * Get employment type label
 * 
 * @param string $type Employment type
 * @return string Label
 */
function getEmploymentTypeLabel(string $type): string {
    $types = [
        'permanent' => 'Permanent',
        'temporary' => 'Temporary',
        'seasonal' => 'Seasonal'
    ];
    
    return $types[$type] ?? ucfirst($type);
}

/**
 * Get application status label
 * 
 * @param string $status Status
 * @return array Label and color
 */
function getApplicationStatusLabel(string $status): array {
    $statuses = [
        'pending' => ['label' => 'Pending Review', 'color' => 'warning'],
        'reviewed' => ['label' => 'Reviewed', 'color' => 'info'],
        'interview' => ['label' => 'Interview Scheduled', 'color' => 'primary'],
        'accepted' => ['label' => 'Accepted', 'color' => 'success'],
        'rejected' => ['label' => 'Not Selected', 'color' => 'danger']
    ];
    
    return $statuses[$status] ?? ['label' => ucfirst($status), 'color' => 'secondary'];
}

/**
 * Handle file upload for job application
 * 
 * @param array $file $_FILES array element
 * @param string $uploadDir Upload directory (relative to project root)
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array Result with 'success' and 'path' or 'error' keys
 */
function handleJobFileUpload(array $file, string $uploadDir = 'uploads/applications/', array $allowedTypes = [], int $maxSize = 5242880): array {
    // Default allowed types
    if (empty($allowedTypes)) {
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds maximum upload size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed (' . round($maxSize / 1048576, 1) . 'MB)'];
    }
    
    // Check file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX'];
    }
    
    // Create upload directory if it doesn't exist
    $fullPath = dirname(__DIR__) . '/' . $uploadDir;
    if (!is_dir($fullPath)) {
        if (!mkdir($fullPath, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory'];
        }
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'doc_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = $fullPath . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    return [
        'success' => true,
        'path' => $uploadDir . $filename,
        'filename' => $filename,
        'original_name' => $file['name'],
        'size' => $file['size']
    ];
}
