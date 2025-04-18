<?php
//about_us.php
include 'database_connection.php';
include 'function.php';
include 'header.php';

// Fetch library settings
$query = "SELECT * FROM lms_setting LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$settings = $statement->fetch(PDO::FETCH_ASSOC);

// Check if about_us table exists
$about_us_content = null;
try {
    $check_table = "SELECT 1 FROM lms_about_us LIMIT 1";
    $check_statement = $connect->prepare($check_table);
    $check_statement->execute();
    
    // If no exception, fetch the about us content
    $about_query = "SELECT * FROM lms_about_us ORDER BY updated_at DESC LIMIT 1";
    $about_statement = $connect->prepare($about_query);
    $about_statement->execute();
    $about_us_content = $about_statement->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist, we'll use default content
}
?>

<!-- Hero Banner Section -->
<div class="container-fluid p-0">
    <div class="about-hero-banner d-flex align-items-center justify-content-center text-center">
        <div class="about-hero-overlay"></div>
        <div class="container position-relative z-index-1">
            <h1 class="display-4 text-white fw-bold mb-4">About <?php echo htmlspecialchars($settings['library_name']); ?></h1>
            <p class="lead text-white mb-4">Your Gateway to Knowledge and Discovery</p>
        </div>
    </div>
</div>

<!-- Main Content Section -->
<div class="container py-5">
    <div class="row">
        <!-- Left Column - About Content -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="border-bottom pb-3 mb-4">Our Story</h2>
                
                <?php if ($about_us_content && !empty($about_us_content['history'])): ?>
                    <div class="mb-5">
                        <?php echo $about_us_content['history']; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-5">
                        <p>SmartLib is the heart of the Western Mindanao State University - External Studies Unit in Curuan, serving as the intellectual center for our academic community. Since our establishment, we have been committed to providing resources and services that support the university's teaching, learning, and research missions.</p>
                        <p>Our library has evolved from a small collection of books to a comprehensive resource center equipped with modern technology and a diverse range of materials to meet the needs of our growing student population and faculty.</p>
                    </div>
                <?php endif; ?>

                <h2 class="border-bottom pb-3 mb-4">Our Mission</h2>
                
                <?php if ($about_us_content && !empty($about_us_content['mission'])): ?>
                    <div class="mb-5">
                        <?php echo $about_us_content['mission']; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-5">
                        <p>At SmartLib, our mission is to empower students, faculty, and the community through access to information resources, technology, and services that enhance learning, teaching, research, and personal growth. We strive to be innovative, responsive, and user-focused while fostering academic excellence and intellectual discovery.</p>
                    </div>
                <?php endif; ?>

                <h2 class="border-bottom pb-3 mb-4">Our Vision</h2>
                
                <?php if ($about_us_content && !empty($about_us_content['vision'])): ?>
                    <div class="mb-5">
                        <?php echo $about_us_content['vision']; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-5">
                        <p>SmartLib aims to be a leading academic library that inspires intellectual curiosity, promotes digital literacy, and serves as a model for innovative library services. We envision a library that adapts to evolving educational needs while preserving our cultural heritage and contributing to the academic success of our community.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Library Staff Section -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="border-bottom pb-3 mb-4">Meet Our Team</h2>
                
                <?php 
                // Check if staff info exists in the database
                $staff_exists = false;
                try {
                    $staff_query = "SELECT * FROM lms_library_staff ORDER BY position_order";
                    $staff_statement = $connect->prepare($staff_query);
                    $staff_statement->execute();
                    $staff_members = $staff_statement->fetchAll(PDO::FETCH_ASSOC);
                    $staff_exists = (count($staff_members) > 0);
                } catch (PDOException $e) {
                    // Table doesn't exist or other error
                }
                
                if ($staff_exists): 
                ?>
                    <div class="row">
                        <?php foreach ($staff_members as $staff): ?>
                            <div class="col-md-4 mb-4">
                                <div class="text-center">
                                    <img src="<?php echo !empty($staff['profile_img']) ? 'asset/img/' . $staff['profile_img'] : 'asset/img/placeholder_profile.png'; ?>" 
                                         class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <h5><?php echo htmlspecialchars($staff['name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($staff['position']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <img src="asset/img/placeholder_profile.png" class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <h5>Maria Santos</h5>
                                <p class="text-muted">Head Librarian</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <img src="asset/img/placeholder_profile.png" class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <h5>Juan Dela Cruz</h5>
                                <p class="text-muted">Assistant Librarian</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <img src="asset/img/placeholder_profile.png" class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <h5>Ana Reyes</h5>
                                <p class="text-muted">Library Assistant</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column - Information Cards -->
        <div class="col-lg-4">
            <!-- Hours and Contact -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h4 class="border-bottom pb-3 mb-4">Library Hours</h4>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-clock text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Operating Hours</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($settings['library_open_hours']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h4 class="border-bottom pb-3 mb-4">Contact Information</h4>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-map-marker-alt text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Address</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($settings['library_address']); ?></p>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-phone text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Phone</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($settings['library_contact_number']); ?></p>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-envelope text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Email</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($settings['library_email_address']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Library Stats -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h4 class="border-bottom pb-3 mb-4">Library Stats</h4>
                <?php
                // Get library statistics from database
                $stats_query = "SELECT 
                    (SELECT COUNT(*) FROM lms_book WHERE book_status = 'Enable') as total_books,
                    (SELECT COUNT(*) FROM lms_user WHERE user_status = 'Enable') as total_members,
                    (SELECT COUNT(*) FROM lms_issue_book) as total_borrows";
                
                try {
                    $stats_statement = $connect->prepare($stats_query);
                    $stats_statement->execute();
                    $stats = $stats_statement->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // Handle error or use default values
                    $stats = [
                        'total_books' => 0,
                        'total_members' => 0,
                        'total_borrows' => 0
                    ];
                }
                ?>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-book text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Total Books</h5>
                        <p class="mb-0"><?php echo number_format($stats['total_books']); ?></p>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-users text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Registered Members</h5>
                        <p class="mb-0"><?php echo number_format($stats['total_members']); ?></p>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-exchange-alt text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Books Borrowed</h5>
                        <p class="mb-0"><?php echo number_format($stats['total_borrows']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Policies Card -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h4 class="border-bottom pb-3 mb-4">Library Policies</h4>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-calendar-alt text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Loan Period</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($settings['library_total_book_issue_day']); ?> days</p>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-money-bill text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Late Fee</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($settings['library_currency']); ?> <?php echo htmlspecialchars($settings['library_one_day_fine']); ?> per day</p>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-books text-primary me-3 fa-2x"></i>
                    <div>
                        <h5 class="mb-0">Books Per User</h5>
                        <p class="mb-0">Maximum <?php echo htmlspecialchars($settings['library_issue_total_book_per_user']); ?> books</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS for the About Us page -->
<style>
.about-hero-banner {
    position: relative;
    background-image: url('asset/img/library-banner.jpg');
    background-size: cover;
    background-position: center;
    height: 400px;
    color: white;
}

.about-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 0;
}

.z-index-1 {
    z-index: 1;
}

/* Add a fallback if image is not found */
.about-hero-banner:before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #343a40;
    z-index: -1;
}
</style>

<?php
include 'footer.php';
?>