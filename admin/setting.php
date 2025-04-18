<?php
// setting.php - Modern UI design with tab layout for system settings

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
require_once('library_features.php');


$message = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// General Settings Form Submit
if (isset($_POST['edit_setting'])) {
    $data = array(
        ':library_name'                    => $_POST['library_name'],
        ':library_address'                => $_POST['library_address'],
        ':library_contact_number'        => $_POST['library_contact_number'],
        ':library_email_address'        => $_POST['library_email_address'],
		':library_open_hours'        => $_POST['library_open_hours'],
        ':library_total_book_issue_day'    => $_POST['library_total_book_issue_day'],
        ':library_one_day_fine'            => $_POST['library_one_day_fine'],
        ':library_currency'                => $_POST['library_currency'],
        ':library_timezone'                => $_POST['library_timezone'],
        ':library_issue_total_book_per_user'    => $_POST['library_issue_total_book_per_user']
    );

    $query = "
    UPDATE lms_setting 
    SET library_name = :library_name,
        library_address = :library_address, 
        library_contact_number = :library_contact_number, 
        library_email_address = :library_email_address, 
		library_open_hours = :library_open_hours, 
        library_total_book_issue_day = :library_total_book_issue_day, 
        library_one_day_fine = :library_one_day_fine, 
        library_currency = :library_currency, 
        library_timezone = :library_timezone, 
        library_issue_total_book_per_user = :library_issue_total_book_per_user
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    $message = 'general_success';
    $active_tab = 'general';
}

// Library Features - Add Feature
if (isset($_POST['add_feature'])) {
    $name = trim($_POST['feature_name']);
    $icon = $_POST['feature_icon'];
    $position_x = (int)$_POST['position_x'];
    $position_y = (int)$_POST['position_y'];
    $width = (int)$_POST['width'];
    $height = (int)$_POST['height'];
    $bg_color = $_POST['bg_color'];
    $text_color = $_POST['text_color'];
    $status = $_POST['feature_status'];

    $date_now = get_date_time($connect);

    $query = "
        INSERT INTO lms_library_features 
        (feature_name, feature_icon, position_x, position_y, width, height, bg_color, text_color, feature_status, created_on, updated_on) 
        VALUES (:name, :icon, :position_x, :position_y, :width, :height, :bg_color, :text_color, :status, :created_on, :updated_on)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':icon' => $icon,
        ':position_x' => $position_x,
        ':position_y' => $position_y,
        ':width' => $width,
        ':height' => $height,
        ':bg_color' => $bg_color,
        ':text_color' => $text_color,
        ':status' => $status,
        ':created_on' => $date_now,
        ':updated_on' => $date_now
    ]);
    
    $message = 'feature_add';
    $active_tab = 'features';
}

// Library Features - Edit Feature
if (isset($_POST['edit_feature'])) {
    $id = $_POST['feature_id'];
    $name = trim($_POST['feature_name']);
    $icon = $_POST['feature_icon'];
    $position_x = (int)$_POST['position_x'];
    $position_y = (int)$_POST['position_y'];
    $width = (int)$_POST['width'];
    $height = (int)$_POST['height'];
    $bg_color = $_POST['bg_color'];
    $text_color = $_POST['text_color'];
    $status = $_POST['feature_status'];

    $update_query = "
        UPDATE lms_library_features 
        SET feature_name = :name,
            feature_icon = :icon,
            position_x = :position_x,
            position_y = :position_y,
            width = :width,
            height = :height,
            bg_color = :bg_color,
            text_color = :text_color,
            feature_status = :status,
            updated_on = :updated_on
        WHERE feature_id = :id
    ";

    $params = [
        ':name' => $name,
        ':icon' => $icon,
        ':position_x' => $position_x,
        ':position_y' => $position_y,
        ':width' => $width,
        ':height' => $height,
        ':bg_color' => $bg_color,
        ':text_color' => $text_color,
        ':status' => $status,
        ':updated_on' => get_date_time($connect),
        ':id' => $id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    $message = 'feature_edit';
    $active_tab = 'features';
}

// Library Features - Enable/Disable
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'toggle_feature') {
    $feature_id = $_GET["code"];
    $status = $_GET["status"];

    $data = array(
        ':feature_status' => $status,
        ':feature_id'     => $feature_id
    );

    $query = "
    UPDATE lms_library_features 
    SET feature_status = :feature_status 
    WHERE feature_id = :feature_id";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    $message = 'feature_' . strtolower($status);
    $active_tab = 'features';
}

// Get General Settings
$query = "SELECT * FROM lms_setting LIMIT 1";
$result = $connect->query($query);
$settings = $result->fetch(PDO::FETCH_ASSOC);

// Get Library Features
$query = "SELECT * FROM lms_library_features ORDER BY feature_id ASC";
$statement = $connect->prepare($query);
$statement->execute();
$all_features = $statement->fetchAll(PDO::FETCH_ASSOC);

// Get rack data for map preview
$query = "SELECT * FROM lms_location_rack ORDER BY location_rack_id ASC";
$statement = $connect->prepare($query);
$statement->execute();
$all_racks = $statement->fetchAll(PDO::FETCH_ASSOC);

// Load individual feature if editing
$edit_feature = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit_feature' && isset($_GET['code'])) {
    $id = $_GET['code'];
    $query = "SELECT * FROM lms_library_features WHERE feature_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $edit_feature = $statement->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_feature) {
        $active_tab = 'features';
    }
}

// Handle view feature
$view_feature = null;
if (isset($_GET['action']) && $_GET['action'] === 'view_feature' && isset($_GET['code'])) {
    $id = $_GET['code'];
    $query = "SELECT * FROM lms_library_features WHERE feature_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $view_feature = $statement->fetch(PDO::FETCH_ASSOC);
    
    if ($view_feature) {
        $active_tab = 'features';
    }
}
?>

<!-- Alert Messages -->
<?php if ($message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($message === 'general_success'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Settings Updated',
            text: 'Library settings have been successfully updated.',
            confirmButtonColor: '#4361ee',
            confirmButtonText: 'Great!',
            timer: 2000,
            timerProgressBar: true
        });
    <?php elseif ($message === 'feature_add'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Feature Added',
            text: 'The library feature was added successfully!',
            confirmButtonColor: '#4361ee',
            confirmButtonText: 'Done',
            timer: 2000,
            timerProgressBar: true
        });
    <?php elseif ($message === 'feature_edit'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Feature Updated',
            text: 'The library feature was updated successfully!',
            confirmButtonColor: '#4361ee',
            confirmButtonText: 'Done',
            timer: 2000,
            timerProgressBar: true
        });
    <?php elseif ($message === 'feature_enable'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Feature Enabled',
            text: 'The library feature has been successfully enabled.',
            confirmButtonColor: '#4361ee',
            confirmButtonText: 'Done',
            timer: 2000,
            timerProgressBar: true
        });
    <?php elseif ($message === 'feature_disable'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Feature Disabled',
            text: 'The library feature has been successfully disabled.',
            confirmButtonColor: '#4361ee',
            confirmButtonText: 'Done',
            timer: 2000,
            timerProgressBar: true
        });
    <?php endif; ?>

    // Clean URL after alert
    if (window.history.replaceState) {
        window.history.replaceState(null, null, 'setting.php?tab=<?= $active_tab ?>');
    }
});
</script>
<?php endif; ?>

<main class="container-fluid py-4 px-lg-5 px-3">
    <!-- Header with Breadcrumbs -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h2 fw-bold">
                <i class="fas fa-cogs me-2 text-primary"></i>
                System Settings
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex">
            <span class="badge bg-primary p-2">
                <i class="fas fa-info-circle me-1"></i>
                Configure your library system settings here
            </span>
        </div>
    </div>

    <!-- Settings Tabs Card -->
    <div class="row mb-4">
        <div class="col-xl-3 col-sm-12 p-0">
			<div class="settings-header">
				<ul class="nav nav-tabs card-header-tabs" role="tablist">
					<li class="nav-item col-xl-12" role="presentation">
						<a class="nav-link <?= $active_tab === 'general' ? 'active bg-white text-primary' : 'text-white' ?>" 
						href="setting.php?tab=general" role="tab">
							<i class="fas fa-building"></i>
							General Settings
						</a>
					</li>
					<li class="nav-item col-xl-12" role="presentation">
						<a class="nav-link <?= $active_tab === 'features' ? 'active bg-white text-primary' : 'text-white' ?>" 
						href="setting.php?tab=features" role="tab">
							<i class="fas fa-map-marked-alt"></i>
							Library Features
						</a>
					</li>
					<li class="nav-item col-xl-12" role="presentation">
						<a class="nav-link <?= $active_tab === 'appearance' ? 'active bg-white text-primary' : 'text-white' ?>" 
						href="setting.php?tab=appearance" role="tab">
							<i class="fas fa-palette"></i>
							Appearance
						</a>
					</li>
					<li class="nav-item col-xl-12" role="presentation">
						<a class="nav-link <?= $active_tab === 'notifications' ? 'active bg-white text-primary' : 'text-white' ?>" 
						href="setting.php?tab=notifications" role="tab">
							<i class="fas fa-bell"></i>
							Notifications
						</a>
					</li>
				</ul>
			</div>
        </div>
        
        <div class="card col-xl-9 col-sm-12 p-0">
            <div class="tab-content">
                <!-- General Settings Tab -->
                <div class="tab-pane fade <?= $active_tab === 'general' ? 'show active' : '' ?>" id="general" role="tabpanel">
                    <div class="p-4">
                        <form method="post" class="needs-validation" novalidate>
                            <div class="row g-4">
                                <!-- General Information -->
                                <div class="col-lg-6">
                                    <div class="card h-100 settings-card">
                                        <div class="card-header bg-light py-3">
                                            <h5 class="card-title mb-0 d-flex align-items-center">
                                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                                General Information
                                            </h5>
                                        </div>
										
                                        <div class="card-body">
                                            <div class="mb-4">
                                                <label for="library_name" class="form-label fw-medium">Library Name</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-book-reader text-primary"></i></span>
                                                    <input type="text" name="library_name" id="library_name" class="form-control" value="<?= htmlspecialchars($settings['library_name'] ?? '') ?>" required />
                                                    <div class="invalid-feedback">Please provide a library name.</div>
                                                </div>
                                            </div>
											<div class="mb-3">
												<label for="library_open_hours" class="form-label fw-medium">Opening Hours</label>
												<div class="input-group">
													<span class="input-group-text"><i class="fas fa-clock text-primary"></i></span>
													<input type="text" name="library_open_hours" class="form-control" id="library_open_hours" name="library_open_hours" value="<?= htmlspecialchars($settings['library_open_hours'] ?? '') ?>">
												</div>
											</div>
                                            
                                            <div class="mb-4">
                                                <label for="library_address" class="form-label fw-medium">Address</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-map-marker-alt text-primary"></i></span>
                                                    <textarea name="library_address" id="library_address" class="form-control" rows="3" required><?= htmlspecialchars($settings["library_address"] ?? '') ?></textarea>
                                                    <div class="invalid-feedback">Please provide a library address.</div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="library_contact_number" class="form-label fw-medium">Contact Number</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-phone text-primary"></i></span>
                                                        <input type="text" name="library_contact_number" id="library_contact_number" class="form-control" value="<?= htmlspecialchars($settings['library_contact_number'] ?? '') ?>" required />
                                                        <div class="invalid-feedback">Please provide a contact number.</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label for="library_email_address" class="form-label fw-medium">Email Address</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-envelope text-primary"></i></span>
                                                        <input type="email" name="library_email_address" id="library_email_address" class="form-control" value="<?= htmlspecialchars($settings['library_email_address'] ?? '') ?>" required />
                                                        <div class="invalid-feedback">Please provide a valid email address.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Library Rules -->
                                <div class="col-lg-6">
                                    <div class="card h-100 settings-card">
                                        <div class="card-header bg-light py-3">
                                            <h5 class="card-title mb-0 d-flex align-items-center">
                                                <i class="fas fa-gavel me-2 text-primary"></i>
                                                Rules & Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-4">
                                                    <label for="library_total_book_issue_day" class="form-label fw-medium">Book Return Day Limit</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-calendar-day text-primary"></i></span>
                                                        <input type="number" name="library_total_book_issue_day" id="library_total_book_issue_day" class="form-control" value="<?= htmlspecialchars($settings['library_total_book_issue_day'] ?? '7') ?>" min="1" required />
                                                        <span class="input-group-text">days</span>
                                                        <div class="invalid-feedback">Please specify a valid number of days.</div>
                                                    </div>
                                                    <div class="form-text">Maximum days allowed for borrowing books</div>
                                                </div>
                                                
                                                <div class="col-md-6 mb-4">
                                                    <label for="library_one_day_fine" class="form-label fw-medium">Late Return Fine (Per Day)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-money-bill-wave text-primary"></i></span>
                                                        <input type="number" name="library_one_day_fine" id="library_one_day_fine" class="form-control" value="<?= htmlspecialchars($settings['library_one_day_fine'] ?? '0') ?>" step="0.01" min="0" required />
                                                        <div class="invalid-feedback">Please specify a valid fine amount.</div>
                                                    </div>
                                                    <div class="form-text">Amount to charge per day for overdue books</div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-4">
                                                    <label for="library_currency" class="form-label fw-medium">Currency</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-coins text-primary"></i></span>
                                                        <select name="library_currency" id="library_currency" class="form-select" required>
                                                            <?php echo Currency_list(); ?>
                                                        </select>
                                                        <div class="invalid-feedback">Please select a currency.</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6 mb-4">
                                                    <label for="library_timezone" class="form-label fw-medium">Timezone</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-globe text-primary"></i></span>
                                                        <select name="library_timezone" id="library_timezone" class="form-select" required>
                                                            <?php echo Timezone_list(); ?>
                                                        </select>
                                                        <div class="invalid-feedback">Please select a timezone.</div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="library_issue_total_book_per_user" class="form-label fw-medium">Book Issue Limit (Per User)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user-tag text-primary"></i></span>
                                                    <input type="number" name="library_issue_total_book_per_user" id="library_issue_total_book_per_user" class="form-control" value="<?= htmlspecialchars($settings['library_issue_total_book_per_user'] ?? '3') ?>" min="1" required />
                                                    <span class="input-group-text">books</span>
                                                    <div class="invalid-feedback">Please specify a valid number of books.</div>
                                                </div>
                                                <div class="form-text">Maximum number of books a user can borrow at once</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Save Settings Button -->
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" name="edit_setting" class="btn btn-primary btn-modern">
                                        <i class="fas fa-save me-2"></i>Save Settings
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Library Features Tab -->
                <div class="tab-pane fade <?= $active_tab === 'features' ? 'show active' : '' ?>" id="features" role="tabpanel">
                    <?php if (isset($_GET['action']) && $_GET['action'] === 'add_feature'): ?>
                        <!-- Add Feature Form -->
                        <div class="p-4">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Library Feature</h5>
                                    <a href="setting.php?tab=features" class="btn btn-light btn-sm">
                                        <i class="fas fa-arrow-left me-1"></i>Back to List
                                    </a>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="row g-4 needs-validation" novalidate>
                                        <div class="col-md-6">
                                            <div class="card h-100 settings-card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Feature Details</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label for="feature_name" class="form-label fw-medium">Feature Name</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-tag text-primary"></i></span>
                                                            <input type="text" id="feature_name" name="feature_name" class="form-control" required>
                                                            <div class="invalid-feedback">Please enter a feature name.</div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="feature_icon" class="form-label fw-medium">Feature Icon (FontAwesome class)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-icons text-primary"></i></span>
                                                            <input type="text" id="feature_icon" name="feature_icon" class="form-control" value="fas fa-landmark" required>
                                                            <div class="invalid-feedback">Please specify an icon class.</div>
                                                        </div>
                                                        <div class="form-text">
                                                            Example: fas fa-door-open, fas fa-book-reader, fas fa-desktop
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                        <label for="bg_color" class="form-label fw-medium">Background Color</label>
                                                            <select id="bg_color" name="bg_color" class="form-select">
                                                                <option value="bg-primary">Primary Blue</option>
                                                                <option value="bg-secondary">Secondary Gray</option>
                                                                <option value="bg-success">Success Green</option>
                                                                <option value="bg-danger">Danger Red</option>
                                                                <option value="bg-warning">Warning Yellow</option>
                                                                <option value="bg-info">Info Light Blue</option>
                                                                <option value="bg-light">Light Gray</option>
                                                                <option value="bg-dark">Dark Gray</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="text_color" class="form-label fw-medium">Text Color</label>
                                                            <select id="text_color" name="text_color" class="form-select">
                                                                <option value="text-white">White</option>
                                                                <option value="text-dark">Dark</option>
                                                                <option value="text-light">Light</option>
                                                                <option value="text-primary">Primary Blue</option>
                                                                <option value="text-success">Success Green</option>
                                                                <option value="text-danger">Danger Red</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="feature_status" class="form-label fw-medium">Status</label>
                                                        <select id="feature_status" name="feature_status" class="form-select">
                                                            <option value="Enable">Enable</option>
                                                            <option value="Disable">Disable</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="card h-100 settings-card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Position & Size</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="position_x" class="form-label fw-medium">Position X</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-arrows-alt-h text-primary"></i></span>
                                                                <input type="number" id="position_x" name="position_x" class="form-control" value="10" min="0" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify X position.</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="position_y" class="form-label fw-medium">Position Y</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-arrows-alt-v text-primary"></i></span>
                                                                <input type="number" id="position_y" name="position_y" class="form-control" value="10" min="0" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify Y position.</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="width" class="form-label fw-medium">Width</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-expand text-primary"></i></span>
                                                                <input type="number" id="width" name="width" class="form-control" value="120" min="50" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify width (min 50px).</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="height" class="form-label fw-medium">Height</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-expand-alt text-primary"></i></span>
                                                                <input type="number" id="height" name="height" class="form-control" value="120" min="50" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify height (min 50px).</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-4">
                                                        <label class="form-label fw-medium">Feature Preview</label>
														<?php 
														echo renderLibraryFeatures(
															'add',          // Mode - 'list', 'view', 'edit', or 'add'
															null,            // Specific feature to highlight (when in view/edit mode)
															$all_racks,      // All racks from the database
															$all_features,   // All library features
															'medium',        // Map size
															true             // Show controls
														);
														?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 text-end">
                                            <a href="setting.php?tab=features" class="btn btn-outline-secondary btn-modern me-2">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </a>
                                            <button type="submit" name="add_feature" class="btn btn-success btn-modern">
                                                <i class="fas fa-plus-circle me-2"></i>Add Feature
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit_feature' && $edit_feature): ?>
                        <!-- Edit Feature Form -->
                        <div class="p-4">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Library Feature</h5>
                                    <a href="setting.php?tab=features" class="btn btn-light btn-sm">
                                        <i class="fas fa-arrow-left me-1"></i>Back to List
                                    </a>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="row g-4 needs-validation" novalidate>
                                        <input type="hidden" name="feature_id" value="<?= htmlspecialchars($edit_feature['feature_id']) ?>">
                                        
                                        <div class="col-md-6">
                                            <div class="card h-100 settings-card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Feature Details</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label for="feature_name" class="form-label fw-medium">Feature Name</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-tag text-primary"></i></span>
                                                            <input type="text" id="feature_name" name="feature_name" class="form-control" 
                                                                value="<?= htmlspecialchars($edit_feature['feature_name']) ?>" required>
                                                            <div class="invalid-feedback">Please enter a feature name.</div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="feature_icon" class="form-label fw-medium">Feature Icon (FontAwesome class)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-icons text-primary"></i></span>
                                                            <input type="text" id="feature_icon" name="feature_icon" class="form-control" 
                                                                value="<?= htmlspecialchars($edit_feature['feature_icon']) ?>" required>
                                                            <div class="invalid-feedback">Please specify an icon class.</div>
                                                        </div>
                                                        <div class="form-text">
                                                            Example: fas fa-door-open, fas fa-book-reader, fas fa-desktop
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="bg_color" class="form-label fw-medium">Background Color</label>
                                                            <select id="bg_color" name="bg_color" class="form-select">
                                                                <option value="bg-primary" <?= $edit_feature['bg_color'] === 'bg-primary' ? 'selected' : '' ?>>Primary Blue</option>
                                                                <option value="bg-secondary" <?= $edit_feature['bg_color'] === 'bg-secondary' ? 'selected' : '' ?>>Secondary Gray</option>
                                                                <option value="bg-success" <?= $edit_feature['bg_color'] === 'bg-success' ? 'selected' : '' ?>>Success Green</option>
                                                                <option value="bg-danger" <?= $edit_feature['bg_color'] === 'bg-danger' ? 'selected' : '' ?>>Danger Red</option>
                                                                <option value="bg-warning" <?= $edit_feature['bg_color'] === 'bg-warning' ? 'selected' : '' ?>>Warning Yellow</option>
                                                                <option value="bg-info" <?= $edit_feature['bg_color'] === 'bg-info' ? 'selected' : '' ?>>Info Light Blue</option>
                                                                <option value="bg-light" <?= $edit_feature['bg_color'] === 'bg-light' ? 'selected' : '' ?>>Light Gray</option>
                                                                <option value="bg-dark" <?= $edit_feature['bg_color'] === 'bg-dark' ? 'selected' : '' ?>>Dark Gray</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="text_color" class="form-label fw-medium">Text Color</label>
                                                            <select id="text_color" name="text_color" class="form-select">
                                                                <option value="text-white" <?= $edit_feature['text_color'] === 'text-white' ? 'selected' : '' ?>>White</option>
                                                                <option value="text-dark" <?= $edit_feature['text_color'] === 'text-dark' ? 'selected' : '' ?>>Dark</option>
                                                                <option value="text-light" <?= $edit_feature['text_color'] === 'text-light' ? 'selected' : '' ?>>Light</option>
                                                                <option value="text-primary" <?= $edit_feature['text_color'] === 'text-primary' ? 'selected' : '' ?>>Primary Blue</option>
                                                                <option value="text-success" <?= $edit_feature['text_color'] === 'text-success' ? 'selected' : '' ?>>Success Green</option>
                                                                <option value="text-danger" <?= $edit_feature['text_color'] === 'text-danger' ? 'selected' : '' ?>>Danger Red</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="feature_status" class="form-label fw-medium">Status</label>
                                                        <select id="feature_status" name="feature_status" class="form-select">
                                                            <option value="Enable" <?= $edit_feature['feature_status'] === 'Enable' ? 'selected' : '' ?>>Enable</option>
                                                            <option value="Disable" <?= $edit_feature['feature_status'] === 'Disable' ? 'selected' : '' ?>>Disable</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="card h-100 settings-card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Position & Size</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="position_x" class="form-label fw-medium">Position X</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-arrows-alt-h text-primary"></i></span>
                                                                <input type="number" id="position_x" name="position_x" class="form-control" 
                                                                    value="<?= htmlspecialchars($edit_feature['position_x']) ?>" min="0" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify X position.</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="position_y" class="form-label fw-medium">Position Y</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-arrows-alt-v text-primary"></i></span>
                                                                <input type="number" id="position_y" name="position_y" class="form-control" 
                                                                    value="<?= htmlspecialchars($edit_feature['position_y']) ?>" min="0" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify Y position.</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="width" class="form-label fw-medium">Width</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-expand text-primary"></i></span>
                                                                <input type="number" id="width" name="width" class="form-control" 
                                                                    value="<?= htmlspecialchars($edit_feature['width']) ?>" min="50" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify width (min 50px).</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="height" class="form-label fw-medium">Height</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-expand-alt text-primary"></i></span>
                                                                <input type="number" id="height" name="height" class="form-control" 
                                                                    value="<?= htmlspecialchars($edit_feature['height']) ?>" min="50" required>
                                                                <span class="input-group-text">px</span>
                                                                <div class="invalid-feedback">Please specify height (min 50px).</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-4">
                                                        <label class="form-label fw-medium">Feature Preview</label>
														<?php 
														echo renderLibraryFeatures(
															'edit',          // Mode - 'list', 'view', 'edit', or 'add'
															$edit_feature,            // Specific feature to highlight (when in view/edit mode)
															$all_racks,      // All racks from the database
															$all_features,   // All library features
															'medium',        // Map size
															true             // Show controls
														);
														?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 text-end">
                                            <a href="setting.php?tab=features" class="btn btn-outline-secondary btn-modern me-2">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </a>
                                            <button type="submit" name="edit_feature" class="btn btn-primary btn-modern">
                                                <i class="fas fa-save me-2"></i>Update Feature
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view_feature' && $view_feature): ?>
                        <!-- View Feature Details -->
                        <div class="p-4">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center py-3">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Feature Details</h5>
                                    <a href="setting.php?tab=features" class="btn btn-light btn-sm">
                                        <i class="fas fa-arrow-left me-1"></i>Back to List
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="card h-100 settings-card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Feature Information</h6>
                                                </div>
                                                <div class="card-body">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <th width="40%" class="text-muted">Feature Name:</th>
                                                            <td><?= htmlspecialchars($view_feature['feature_name']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Icon:</th>
                                                            <td><i class="<?= htmlspecialchars($view_feature['feature_icon']) ?> me-2"></i> <?= htmlspecialchars($view_feature['feature_icon']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Colors:</th>
                                                            <td>
                                                                <span class="badge <?= htmlspecialchars($view_feature['bg_color']) ?> <?= htmlspecialchars($view_feature['text_color']) ?> p-2 me-2">Background</span>
                                                                <span class="badge bg-dark <?= htmlspecialchars($view_feature['text_color']) ?> p-2">Text</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Position:</th>
                                                            <td>X: <?= htmlspecialchars($view_feature['position_x']) ?>px, Y: <?= htmlspecialchars($view_feature['position_y']) ?>px</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Dimensions:</th>
                                                            <td>Width: <?= htmlspecialchars($view_feature['width']) ?>px, Height: <?= htmlspecialchars($view_feature['height']) ?>px</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Status:</th>
                                                            <td>
                                                                <?php if($view_feature['feature_status'] == 'Enable'): ?>
                                                                    <span class="badge bg-success p-2"><i class="fas fa-check-circle me-1"></i> Enabled</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger p-2"><i class="fas fa-times-circle me-1"></i> Disabled</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Created On:</th>
                                                            <td><?= date('F j, Y g:i A', strtotime($view_feature['created_on'])) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Last Updated:</th>
                                                            <td><?= date('F j, Y g:i A', strtotime($view_feature['updated_on'])) ?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="card h-100 settings-card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Feature Preview</h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php 
													echo renderLibraryFeatures(
														'view',          // Mode - 'list', 'view', 'edit', or 'add'
														$view_feature,            // Specific feature to highlight (when in view/edit mode)
														$all_racks,      // All racks from the database
														$all_features,   // All library features
														'medium',        // Map size
														true             // Show controls
													);
													?>
                                                    
                                                    <div class="d-flex justify-content-center gap-2 mt-4">
                                                        <a href="setting.php?tab=features&action=edit_feature&code=<?= $view_feature['feature_id'] ?>" class="btn btn-primary btn-modern">
                                                            <i class="fas fa-edit me-2"></i>Edit Feature
                                                        </a>
                                                        <?php if($view_feature['feature_status'] == 'Enable'): ?>
                                                            <a href="setting.php?tab=features&action=toggle_feature&status=Disable&code=<?= $view_feature['feature_id'] ?>" 
                                                               class="btn btn-outline-danger btn-modern" 
                                                               onclick="return confirm('Are you sure you want to disable this feature?')">
                                                                <i class="fas fa-times-circle me-2"></i>Disable
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="setting.php?tab=features&action=toggle_feature&status=Enable&code=<?= $view_feature['feature_id'] ?>" 
                                                               class="btn btn-outline-success btn-modern" 
                                                               onclick="return confirm('Are you sure you want to enable this feature?')">
                                                                <i class="fas fa-check-circle me-2"></i>Enable
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php else: ?>
                        <!-- Features List View -->
                        <div class="p-4">
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <h5 class="mb-3">Library Features</h5>
                                    <p class="text-muted mb-0">
                                        Manage physical locations and facilities within your library. Features will be displayed on the library map interface.
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <a href="setting.php?tab=features&action=add_feature" class="btn btn-success btn-modern">
                                        <i class="fas fa-plus-circle me-2"></i>Add New Feature
                                    </a>
                                </div>
                            </div>
                            
                            <div class="card shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="feature_table">
                                            <thead>
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th width="20%">Name</th>
                                                    <th width="10%">Icon</th>
                                                    <th width="15%">Color</th>
                                                    <th width="20%">Position</th>
                                                    <th width="10%">Status</th>
                                                    <th width="20%">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($all_features) > 0): ?>
                                                    <?php $counter = 1; foreach ($all_features as $feature): ?>
                                                        <tr>
                                                            <td><?= $counter++ ?></td>
                                                            <td><?= htmlspecialchars($feature['feature_name']) ?></td>
                                                            <td><i class="<?= htmlspecialchars($feature['feature_icon']) ?> fa-lg"></i></td>
                                                            <td>
                                                                <span class="badge <?= htmlspecialchars($feature['bg_color']) ?> <?= htmlspecialchars($feature['text_color']) ?> p-2">
                                                                    Sample Text
                                                                </span>
                                                            </td>
                                                            <td>
                                                                X: <?= htmlspecialchars($feature['position_x']) ?>px,<br>
                                                                Y: <?= htmlspecialchars($feature['position_y']) ?>px
                                                            </td>
                                                            <td>
                                                                <?php if($feature['feature_status'] == 'Enable'): ?>
                                                                    <span class="badge bg-success p-2">Enabled</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger p-2">Disabled</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="setting.php?tab=features&action=view_feature&code=<?= $feature['feature_id'] ?>" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="setting.php?tab=features&action=edit_feature&code=<?= $feature['feature_id'] ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Edit Feature">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <?php if($feature['feature_status'] == 'Enable'): ?>
                                                                        <a href="setting.php?tab=features&action=toggle_feature&status=Disable&code=<?= $feature['feature_id'] ?>" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Disable">
                                                                            <i class="fas fa-times-circle"></i>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <a href="setting.php?tab=features&action=toggle_feature&status=Enable&code=<?= $feature['feature_id'] ?>" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="Enable">
                                                                           <i class="fas fa-check-circle"></i>
                                                                       </a>
                                                                   <?php endif; ?>
                                                               </div>
                                                           </td>
                                                       </tr>
                                                   <?php endforeach; ?>
                                               <?php else: ?>
                                                   <tr>
                                                       <td colspan="7" class="text-center py-4">
                                                           <div class="d-flex flex-column align-items-center">
                                                               <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                                                               <p class="lead mb-1">No library features found</p>
                                                               <p class="text-muted mb-3">Add your first feature to begin mapping your library</p>
                                                               <a href="setting.php?tab=features&action=add_feature" class="btn btn-success btn-sm">
                                                                   <i class="fas fa-plus-circle me-2"></i>Add New Feature
                                                               </a>
                                                           </div>
                                                       </td>
                                                   </tr>
                                               <?php endif; ?>
                                           </tbody>
                                       </table>
                                   </div>
                               </div>
                           </div>
                           
                           <!-- Library Map Preview -->
							<div class="card shadow-sm">
								<div class="card-header bg-light py-3">
									<h5 class="mb-0">Library Map Preview</h5>
								</div>
								<div class="card-body p-0">
									<?php 
									// Call the renderLibraryFeatures function to display all features and racks
									echo renderLibraryFeatures(
										'list',          // Mode - 'list', 'view', 'edit', or 'add'
										null,            // Specific feature to highlight (when in view/edit mode)
										$all_racks,      // All racks from the database
										$all_features,   // All library features
										'medium',        // Map size
										true             // Show controls
									);
									?>
								</div>
								<div class="card-footer bg-light text-center py-2">
									<span class="text-muted"><i class="fas fa-info-circle me-1"></i> This is a preview of how your library map will appear to users</span>
								</div>
							</div>
                       </div>
                   <?php endif; ?>
               	</div>
				              
               <!-- Appearance Tab -->
               <div class="tab-pane fade <?= $active_tab === 'appearance' ? 'show active' : '' ?>" id="appearance" role="tabpanel">
                   <div class="p-4">
                       <div class="row mb-4">
                           <div class="col-md-8 mb-3 mb-md-0">
                               <h5 class="mb-2">System Appearance</h5>
                               <p class="text-muted mb-0">
                                   Customize the visual appearance of your library management system.
                               </p>
                           </div>
                       </div>
                       
                       <div class="row g-4">
                           <!-- Theme Settings -->
                           <div class="col-md-6">
                               <div class="card settings-card h-100">
                                   <div class="card-header bg-light py-3">
                                       <h5 class="mb-0"><i class="fas fa-palette me-2 text-primary"></i>Theme Settings</h5>
                                   </div>
                                   <div class="card-body">
                                       <div class="mb-4">
                                           <label class="form-label fw-medium">Color Theme</label>
                                           <div class="row g-3">
                                               <div class="col-6 col-xl-4">
                                                   <input type="radio" class="btn-check" name="theme_color" id="theme_blue" checked>
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="theme_blue">
                                                       <span class="d-inline-block rounded-circle bg-primary" style="width:25px; height:25px;"></span>
                                                       <span class="mt-2">Blue</span>
                                                   </label>
                                               </div>
                                               <div class="col-6 col-xl-4">
                                                   <input type="radio" class="btn-check" name="theme_color" id="theme_green">
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="theme_green">
                                                       <span class="d-inline-block rounded-circle bg-success" style="width:25px; height:25px;"></span>
                                                       <span class="mt-2">Green</span>
                                                   </label>
                                               </div>
                                               <div class="col-6 col-xl-4">
                                                   <input type="radio" class="btn-check" name="theme_color" id="theme_purple">
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="theme_purple">
                                                       <span class="d-inline-block rounded-circle bg-secondary" style="width:25px; height:25px;"></span>
                                                       <span class="mt-2">Purple</span>
                                                   </label>
                                               </div>
                                               <div class="col-6 col-xl-4">
                                                   <input type="radio" class="btn-check" name="theme_color" id="theme_red">
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="theme_red">
                                                       <span class="d-inline-block rounded-circle bg-danger" style="width:25px; height:25px;"></span>
                                                       <span class="mt-2">Red</span>
                                                   </label>
                                               </div>
                                               <div class="col-6 col-xl-4">
                                                   <input type="radio" class="btn-check" name="theme_color" id="theme_teal">
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="theme_teal">
                                                       <span class="d-inline-block rounded-circle bg-info" style="width:25px; height:25px;"></span>
                                                       <span class="mt-2">Teal</span>
                                                   </label>
                                               </div>
                                               <div class="col-6 col-xl-4">
                                                   <input type="radio" class="btn-check" name="theme_color" id="theme_dark">
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="theme_dark">
                                                       <span class="d-inline-block rounded-circle bg-dark" style="width:25px; height:25px;"></span>
                                                       <span class="mt-2">Dark</span>
                                                   </label>
                                               </div>
                                           </div>
                                       </div>
                                       
                                       <div class="mb-4">
                                           <label class="form-label fw-medium">UI Mode</label>
                                           <div class="row g-3">
                                               <div class="col-6">
                                                   <input type="radio" class="btn-check" name="theme_mode" id="mode_light" checked>
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="mode_light">
                                                       <i class="fas fa-sun fa-lg text-warning mb-2"></i>
                                                       <span>Light Mode</span>
                                                   </label>
                                               </div>
                                               <div class="col-6">
                                                   <input type="radio" class="btn-check" name="theme_mode" id="mode_dark">
                                                   <label class="btn w-100 border p-3 d-flex flex-column align-items-center" for="mode_dark">
                                                       <i class="fas fa-moon fa-lg text-primary mb-2"></i>
                                                       <span>Dark Mode</span>
                                                   </label>
                                               </div>
                                           </div>
                                       </div>
                                       
                                       <div class="mb-4">
                                           <label class="form-label fw-medium">Layout Style</label>
                                           <div class="form-check form-switch mb-2">
                                               <input class="form-check-input" type="checkbox" id="compact_sidebar">
                                               <label class="form-check-label" for="compact_sidebar">Compact Sidebar</label>
                                           </div>
                                           <div class="form-check form-switch mb-2">
                                               <input class="form-check-input" type="checkbox" id="show_breadcrumbs" checked>
                                               <label class="form-check-label" for="show_breadcrumbs">Show Breadcrumbs</label>
                                           </div>
                                           <div class="form-check form-switch">
                                               <input class="form-check-input" type="checkbox" id="fluid_container">
                                               <label class="form-check-label" for="fluid_container">Full Width Layout</label>
                                           </div>
                                       </div>
                                       
                                       <button type="button" class="btn btn-primary btn-modern" disabled>
                                           <i class="fas fa-save me-2"></i>Save Theme Settings
                                       </button>
                                   </div>
                               </div>
                           </div>
                           
                           <!-- Login Page Settings -->
                           <div class="col-md-6">
                               <div class="card settings-card h-100">
                                   <div class="card-header bg-light py-3">
                                       <h5 class="mb-0"><i class="fas fa-sign-in-alt me-2 text-primary"></i>Login Page</h5>
                                   </div>
                                   <div class="card-body">
                                       <div class="mb-4">
                                           <label class="form-label fw-medium">Login Page Logo</label>
                                           <div class="input-group mb-3">
                                               <input type="file" class="form-control" id="login_logo">
                                               <label class="input-group-text" for="login_logo">Upload</label>
                                           </div>
                                           <div class="form-text">Recommended size: 200x60px</div>
                                       </div>
                                       
                                       <div class="mb-4">
                                           <label class="form-label fw-medium">Login Background Image</label>
                                           <div class="input-group mb-3">
                                               <input type="file" class="form-control" id="login_bg">
                                               <label class="input-group-text" for="login_bg">Upload</label>
                                           </div>
                                       </div>
                                       
                                       <div class="mb-4">
                                           <label for="login_welcome_text" class="form-label fw-medium">Welcome Text</label>
                                           <textarea class="form-control" id="login_welcome_text" rows="3">Welcome to our Library Management System. Please log in to continue.</textarea>
                                       </div>
                                       
                                       <button type="button" class="btn btn-primary btn-modern" disabled>
                                           <i class="fas fa-save me-2"></i>Save Login Settings
                                       </button>
                                   </div>
                               </div>
                           </div>
                           
                           <!-- Custom CSS -->
                           <div class="col-12">
                               <div class="card settings-card">
                                   <div class="card-header bg-light py-3">
                                       <h5 class="mb-0"><i class="fas fa-code me-2 text-primary"></i>Custom CSS</h5>
                                   </div>
                                   <div class="card-body">
                                       <div class="mb-4">
                                           <label for="custom_css" class="form-label fw-medium">Custom CSS Code</label>
                                           <textarea class="form-control font-monospace" id="custom_css" rows="6" placeholder="/* Add your custom CSS here */"></textarea>
                                           <div class="form-text">
                                               Add custom CSS to override the default styles. Changes will apply to all pages.
                                           </div>
                                       </div>
                                       
                                       <button type="button" class="btn btn-primary btn-modern" disabled>
                                           <i class="fas fa-save me-2"></i>Save Custom CSS
                                       </button>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
               
               <!-- Notifications Tab -->
               <div class="tab-pane fade <?= $active_tab === 'notifications' ? 'show active' : '' ?>" id="notifications" role="tabpanel">
                   <div class="p-4">
                       <div class="row mb-4">
                           <div class="col-md-8 mb-3 mb-md-0">
                               <h5 class="mb-2">Notification Settings</h5>
                               <p class="text-muted mb-0">
                                   Configure how and when the system should send notifications to users and administrators.
                               </p>
                           </div>
                       </div>
                       
                       <div class="row g-4">
                           <!-- Email Notifications -->
                           <div class="col-md-6">
                               <div class="card settings-card h-100">
                                   <div class="card-header bg-light py-3">
                                       <h5 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Email Notifications</h5>
                                   </div>
                                   <div class="card-body">
                                       <div class="mb-4">
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="email_due_date" checked>
                                               <label class="form-check-label" for="email_due_date">Book Due Date Reminders</label>
                                               <div class="form-text">Send reminders before books are due</div>
                                           </div>
                                           
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="email_overdue" checked>
                                               <label class="form-check-label" for="email_overdue">Overdue Book Notifications</label>
                                               <div class="form-text">Send notifications when books are overdue</div>
                                           </div>
                                           
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="email_book_reserved" checked>
                                               <label class="form-check-label" for="email_book_reserved">Book Reservation Notifications</label>
                                               <div class="form-text">Notify users when reserved books become available</div>
                                           </div>
                                           
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="email_new_books">
                                               <label class="form-check-label" for="email_new_books">New Book Additions</label>
                                               <div class="form-text">Notify users about newly added books</div>
                                           </div>
                                       </div>
                                       
                                       <div class="mb-3">
                                           <label for="reminder_days" class="form-label fw-medium">Send Due Date Reminder</label>
                                           <select id="reminder_days" class="form-select">
                                               <option value="1">1 day before due date</option>
                                               <option value="2" selected>2 days before due date</option>
                                               <option value="3">3 days before due date</option>
                                               <option value="5">5 days before due date</option>
                                               <option value="7">1 week before due date</option>
                                           </select>
                                       </div>
                                       
                                       <button type="button" class="btn btn-primary btn-modern" disabled>
                                           <i class="fas fa-save me-2"></i>Save Email Settings
                                       </button>
                                   </div>
                               </div>
                           </div>
                           
                           <!-- System Notifications -->
                           <div class="col-md-6">
                               <div class="card settings-card h-100">
                                   <div class="card-header bg-light py-3">
                                       <h5 class="mb-0"><i class="fas fa-bell me-2 text-primary"></i>System Notifications</h5>
                                   </div>
                                   <div class="card-body">
                                       <div class="mb-4">
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="notify_new_user" checked>
                                               <label class="form-check-label" for="notify_new_user">New User Registration</label>
                                               <div class="form-text">Notify administrators when new users register</div>
                                           </div>
                                           
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="notify_book_issue" checked>
                                               <label class="form-check-label" for="notify_book_issue">Book Issue/Return</label>
                                               <div class="form-text">Show notifications for book issue and return actions</div>
                                           </div>
                                           
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="notify_fine_payment">
                                               <label class="form-check-label" for="notify_fine_payment">Fine Payments</label>
                                               <div class="form-text">Notify administrators about fine payments</div>
                                           </div>
                                           
                                           <div class="form-check form-switch mb-3">
                                               <input class="form-check-input" type="checkbox" id="notify_low_inventory">
                                               <label class="form-check-label" for="notify_low_inventory">Low Book Inventory</label>
                                               <div class="form-text">Alert when book copies are running low</div>
                                           </div>
                                       </div>
                                       
                                       <div class="mb-3">
                                           <label for="notification_display_time" class="form-label fw-medium">Notification Display Duration</label>
                                           <select id="notification_display_time" class="form-select">
                                               <option value="3000">3 seconds</option>
                                               <option value="5000" selected>5 seconds</option>
                                               <option value="7000">7 seconds</option>
                                               <option value="10000">10 seconds</option>
                                           </select>
                                       </div>
                                       
                                       <button type="button" class="btn btn-primary btn-modern" disabled>
                                           <i class="fas fa-save me-2"></i>Save System Notifications
                                       </button>
                                   </div>
                               </div>
                           </div>
                           
                           <!-- Email Templates -->
                           <div class="col-12">
                               <div class="card settings-card">
                                   <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                                       <h5 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Email Templates</h5>
                                       <div class="dropdown">
                                           <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="templateDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                               Select Template
                                           </button>
                                           <ul class="dropdown-menu" aria-labelledby="templateDropdown">
                                               <li><a class="dropdown-item" href="#">Due Date Reminder</a></li>
                                               <li><a class="dropdown-item" href="#">Overdue Notice</a></li>
                                               <li><a class="dropdown-item" href="#">Book Reservation</a></li>
                                               <li><a class="dropdown-item" href="#">Welcome Email</a></li>
                                               <li><a class="dropdown-item" href="#">Password Reset</a></li>
                                           </ul>
                                       </div>
                                   </div>
                                   <div class="card-body">
                                       <div class="mb-3">
                                           <label for="email_subject" class="form-label fw-medium">Email Subject</label>
                                           <input type="text" class="form-control" id="email_subject" value="Your Library Book is Due Soon">
                                       </div>
                                       
                                       <div class="mb-3">
                                           <label for="email_template" class="form-label fw-medium">Email Body</label>
                                           <textarea class="form-control" id="email_template" rows="8">
											Dear [User Name],

											This is a friendly reminder that the following book(s) are due to be returned soon:

											Book Title: [Book Title]
											Due Date: [Due Date]

											Please return the book(s) to the library on or before the due date to avoid any late fees.

											Thank you for using our library services!

											Best regards,
											[Library Name]
                                           </textarea>
                                       </div>
                                       
                                       <div class="mb-4">
                                           <label class="form-label fw-medium">Available Variables</label>
                                           <div class="d-flex flex-wrap gap-2">
                                               <span class="badge bg-light text-dark">[User Name]</span>
                                               <span class="badge bg-light text-dark">[Book Title]</span>
                                               <span class="badge bg-light text-dark">[Due Date]</span>
                                               <span class="badge bg-light text-dark">[Issue Date]</span>
                                               <span class="badge bg-light text-dark">[Library Name]</span>
                                               <span class="badge bg-light text-dark">[Fine Amount]</span>
                                           </div>
                                       </div>
                                       
                                       <button type="button" class="btn btn-primary btn-modern" disabled>
                                           <i class="fas fa-save me-2"></i>Save Template
                                       </button>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </div>
</main>

<!-- JavaScript for Settings Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
   // Form Validation
   const forms = document.querySelectorAll('.needs-validation');
   
   Array.from(forms).forEach(function(form) {
       form.addEventListener('submit', function(event) {
           if (!form.checkValidity()) {
               event.preventDefault();
               event.stopPropagation();
           }
           
           form.classList.add('was-validated');
       }, false);
   });
   
   // Initialize tooltips
   const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
   const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
       return new bootstrap.Tooltip(tooltipTriggerEl);
   });
   
   // DataTable initialization for features table
   if (document.getElementById('feature_table')) {
       $('#feature_table').DataTable({
			paging: true,
			pageLength: 10,
			responsive: true,
			scrollX: false,
			scrollY: '500px',       // Optional: Sets vertical scroll area (adjust height as needed)
			scrollCollapse: true,   // Collapse table height if fewer records
			autoWidth: false,
			columnDefs: [
					{ responsivePriority: 1, targets: 6 }, // Actions (highest priority)
				],
			language: {
				search: "<i class='fas fa-search'></i> Search:",
				lengthMenu: "Show _MENU_ entries",
				info: "Showing _START_ to _END_ of _TOTAL_ features"
			}
       });
   }
   
   // Library timezone select - set current value
   const timezoneSelect = document.getElementById('library_timezone');
   if (timezoneSelect) {
       const currentTimezone = '<?= htmlspecialchars($settings["library_timezone"] ?? "UTC") ?>';
       for (let i = 0; i < timezoneSelect.options.length; i++) {
           if (timezoneSelect.options[i].value === currentTimezone) {
               timezoneSelect.options[i].selected = true;
               break;
           }
       }
   }
   
   // Library currency select - set current value
   const currencySelect = document.getElementById('library_currency');
   if (currencySelect) {
       const currentCurrency = '<?= htmlspecialchars($settings["library_currency"] ?? "USD") ?>';
       for (let i = 0; i < currencySelect.options.length; i++) {
           if (currencySelect.options[i].value === currentCurrency) {
               currencySelect.options[i].selected = true;
               break;
           }
       }
   }
   
   // Feature form preview updates
   function updateFeaturePreview() {
       const nameInput = document.getElementById('feature_name');
       const iconInput = document.getElementById('feature_icon');
       const bgColorSelect = document.getElementById('bg_color');
       const textColorSelect = document.getElementById('text_color');
       const posXInput = document.getElementById('position_x');
       const posYInput = document.getElementById('position_y');
       const widthInput = document.getElementById('width');
       const heightInput = document.getElementById('height');
       const preview = document.getElementById('feature_preview');
       
       if (!preview || !nameInput || !iconInput || !bgColorSelect || !textColorSelect || !posXInput || !posYInput || !widthInput || !heightInput) {
           return;
       }
       
       // Update preview text
       const nameNode = preview.querySelector('span');
       if (nameNode) {
           nameNode.textContent = nameInput.value || 'Feature Name';
       }
       
       // Update icon
       const iconNode = preview.querySelector('i');
       if (iconNode) {
           iconNode.className = iconInput.value || 'fas fa-landmark fa-2x mb-2';
       }
       
       // Update colors
       preview.className = 'position-absolute p-3 d-flex flex-column justify-content-center align-items-center text-center ' + 
                         bgColorSelect.value + ' ' + textColorSelect.value;
       
       // Update position and size
       preview.style.left = posXInput.value + 'px';
       preview.style.top = posYInput.value + 'px';
       preview.style.width = widthInput.value + 'px';
       preview.style.height = heightInput.value + 'px';
   }
   
   // Add event listeners to form inputs for live preview
   const featureFormInputs = [
       'feature_name', 'feature_icon', 'bg_color', 'text_color', 
       'position_x', 'position_y', 'width', 'height'
   ];
   
   featureFormInputs.forEach(inputId => {
       const input = document.getElementById(inputId);
       if (input) {
           input.addEventListener('input', updateFeaturePreview);
           input.addEventListener('change', updateFeaturePreview);
       }
   });
   
   // Initialize preview on load
   updateFeaturePreview();
   
   // Feature map preview hover effect
   const features = document.querySelectorAll('.feature-preview');
   features.forEach(feature => {
       feature.addEventListener('mouseenter', function() {
           this.style.zIndex = '100';
           this.style.transform = 'scale(1.05)';
           this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
       });
       
       feature.addEventListener('mouseleave', function() {
           this.style.zIndex = '1';
           this.style.transform = 'scale(1)';
           this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
       });
   });
   // Fine calculation method toggle
   $('#fine_method').on('change', function() {
        const method = $(this).val();
        
        // Hide all sections first
        $('#daily-fine-section, #fixed-fine-section, #progressive-fine-section').addClass('d-none');
        
        // Show relevant section
        if (method === 'daily') {
            $('#daily-fine-section').removeClass('d-none');
        } else if (method === 'fixed') {
            $('#fixed-fine-section').removeClass('d-none');
        } else if (method === 'progressive') {
            $('#progressive-fine-section').removeClass('d-none');
        }
    }).trigger('change');
    
    // Calculate fine button
    $('#calculate-fine').on('click', function() {
        const dueDate = new Date($('#due_date').val());
        const returnDate = new Date($('#return_date').val());
        const numBooks = parseInt($('#num_books').val(), 10);
        
        // Get milliseconds difference and convert to days
        const diffTime = Math.abs(returnDate - dueDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        // Calculate fine based on method
        let finePerBook = 0;
        const fineMethod = $('#fine_method').val();
        const gracePeriod = parseInt($('#grace_period').val(), 10) || 0;
        const maxFine = parseFloat($('#max_fine_amount').val()) || 0;
        
        // Calculate effective overdue days after grace period
        const effectiveDays = Math.max(0, diffDays - gracePeriod);
        
        if (fineMethod === 'daily') {
            const dailyRate = parseFloat($('#daily_fine_rate').val()) || 0;
            finePerBook = dailyRate * effectiveDays;
        } else if (fineMethod === 'fixed') {
            finePerBook = effectiveDays > 0 ? parseFloat($('#fixed_fine_amount').val()) || 0 : 0;
        } else if (fineMethod === 'progressive') {
            const week1Rate = parseFloat($('#week1_rate').val()) || 0;
            const week2Rate = parseFloat($('#week2_rate').val()) || 0;
            const week3Rate = parseFloat($('#week3_rate').val()) || 0;
            
            if (effectiveDays <= 7) {
                finePerBook = week1Rate * effectiveDays;
            } else if (effectiveDays <= 14) {
                finePerBook = (week1Rate * 7) + (week2Rate * (effectiveDays - 7));
            } else {
                finePerBook = (week1Rate * 7) + (week2Rate * 7) + (week3Rate * (effectiveDays - 14));
            }
        }
        
        // Apply maximum fine if set
        if (maxFine > 0 && finePerBook > maxFine) {
            finePerBook = maxFine;
        }
        
        // Calculate total fine
        const totalFine = finePerBook * numBooks;
        
        // Update display
        $('#days-overdue').text(diffDays);
        $('#fine-per-book').text(finePerBook.toFixed(2));
        $('#total-fine').text(totalFine.toFixed(2) + ' ' + '<?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?>');
    });
});
</script>

<?php
include '../footer.php';
?>