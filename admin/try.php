<?php
// Include necessary files

include '../database_connection.php';
include '../function.php';
include '../header.php';
include 'library_map_component.php';

// Define action handlers
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$error_message = '';
$success_message = '';
$setting_data = [];
$library_features = [];

// Get settings data from database
try {
    $query = "SELECT * FROM lms_setting LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute();
    $setting_data = $statement->fetch(PDO::FETCH_ASSOC);
    
    // Get library features
    $library_features = getLibraryFeatures($connect);
} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Settings update
    if (isset($_POST['update_settings'])) {
        try {
            $query = "
            UPDATE lms_setting SET 
                library_name = :library_name,
                library_address = :library_address,
                library_contact_number = :library_contact_number,
                library_email_address = :library_email_address,
                library_open_hours = :library_open_hours,
                library_total_book_issue_day = :library_total_book_issue_day,
                library_one_day_fine = :library_one_day_fine,
                library_issue_total_book_per_user = :library_issue_total_book_per_user,
                library_currency = :library_currency,
                library_timezone = :library_timezone
            WHERE setting_id = :setting_id
            ";
            
            $statement = $connect->prepare($query);
            $statement->execute([
                ':library_name' => $_POST['library_name'],
                ':library_address' => $_POST['library_address'],
                ':library_contact_number' => $_POST['library_contact_number'],
                ':library_email_address' => $_POST['library_email_address'],
                ':library_open_hours' => $_POST['library_open_hours'],
                ':library_total_book_issue_day' => $_POST['library_total_book_issue_day'],
                ':library_one_day_fine' => $_POST['library_one_day_fine'],
                ':library_issue_total_book_per_user' => $_POST['library_issue_total_book_per_user'],
                ':library_currency' => $_POST['library_currency'],
                ':library_timezone' => $_POST['library_timezone'],
                ':setting_id' => $setting_data['setting_id']
            ]);
            
            // Handle logo upload if provided
            if (isset($_FILES['library_logo']) && $_FILES['library_logo']['name'] != '') {
                $target_dir = "../uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $target_file = $target_dir . "logo_" . time() . "." . pathinfo($_FILES['library_logo']['name'], PATHINFO_EXTENSION);
                
                if (move_uploaded_file($_FILES['library_logo']['tmp_name'], $target_file)) {
                    $logo_name = basename($target_file);
                    
                    $query = "UPDATE lms_setting SET library_logo = :library_logo WHERE setting_id = :setting_id";
                    $statement = $connect->prepare($query);
                    $statement->execute([
                        ':library_logo' => $logo_name,
                        ':setting_id' => $setting_data['setting_id']
                    ]);
                }
            }
            
            $success_message = "Library settings updated successfully!";
            
            // Refresh data
            $statement = $connect->prepare("SELECT * FROM lms_setting LIMIT 1");
            $statement->execute();
            $setting_data = $statement->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error_message = "Error updating settings: " . $e->getMessage();
        }
    }
    
    // Library feature add/edit
    elseif (isset($_POST['add_feature']) || isset($_POST['update_feature'])) {
        $feature_data = [
            ':feature_name' => $_POST['feature_name'],
            ':feature_icon' => $_POST['feature_icon'],
            ':position_x' => $_POST['position_x'],
            ':position_y' => $_POST['position_y'],
            ':width' => $_POST['width'],
            ':height' => $_POST['height'],
            ':bg_color' => $_POST['bg_color'],
            ':text_color' => $_POST['text_color'],
            ':feature_status' => $_POST['feature_status'],
            ':updated_on' => date('Y-m-d H:i:s')
        ];
        
        if (isset($_POST['add_feature'])) {
            $feature_data[':created_on'] = date('Y-m-d H:i:s');
            $result = addLibraryFeature($connect, $feature_data);
        } else {
            $feature_data[':feature_id'] = $_POST['feature_id'];
            $result = updateLibraryFeature($connect, $feature_data);
        }
        
        if (isset($result['status']) && $result['status']) {
            $success_message = $result['message'];
        } else {
            $error_message = isset($result['message']) ? $result['message'] : "An error occurred";
        }
        
        // Refresh features
        $library_features = getLibraryFeatures($connect);
    }
    
    // Feature delete
    elseif (isset($_POST['delete_feature'])) {
        if (deleteLibraryFeature($connect, $_POST['feature_id'])) {
            $success_message = "Feature deleted successfully!";
        } else {
            $error_message = "Error deleting feature";
        }
        
        // Refresh features
        $library_features = getLibraryFeatures($connect);
    }
}

// Get rack data for map display
$racks = [];
try {
    $query = "SELECT * FROM lms_location_rack";
    $statement = $connect->prepare($query);
    $statement->execute();
    $racks = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist, ignore
}

// Check if library features table exists, create if not
try {
    $query = "SELECT 1 FROM lms_library_features LIMIT 1";
    $connect->query($query);
} catch (PDOException $e) {
    createLibraryFeaturesTable($connect);
}
?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-cogs me-2"></i> Library Settings
            </h1>
            <p class="mb-0 text-muted">Manage your library system settings and environment</p>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="false">
                <i class="fas fa-sliders-h me-2"></i>General Settings
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="map-tab" data-bs-toggle="tab" data-bs-target="#map" type="button" role="tab" aria-controls="map" aria-selected="true">
                <i class="fas fa-map-marked-alt me-2"></i>Library Map
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="false">
                <i class="fas fa-puzzle-piece me-2"></i>Library Features
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fines-tab" data-bs-toggle="tab" data-bs-target="#fines" type="button" role="tab" aria-controls="fines" aria-selected="false">
                <i class="fas fa-money-bill-wave me-2"></i>Fine Management
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content bg-white p-4 shadow-sm rounded-bottom" id="settingsTabContent">
        <!-- General Settings Tab -->
        <div class="tab-pane fade " id="general" role="tabpanel" aria-labelledby="general-tab">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-gradient-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Library Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="library_name" class="form-label">Library Name</label>
                                    <input type="text" class="form-control" id="library_name" name="library_name" value="<?= htmlspecialchars($setting_data['library_name'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="library_address" class="form-label">Address</label>
                                    <textarea class="form-control" id="library_address" name="library_address" rows="3"><?= htmlspecialchars($setting_data['library_address'] ?? '') ?></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="library_contact_number" class="form-label">Contact Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control" id="library_contact_number" name="library_contact_number" value="<?= htmlspecialchars($setting_data['library_contact_number'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="library_email_address" class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="library_email_address" name="library_email_address" value="<?= htmlspecialchars($setting_data['library_email_address'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="library_open_hours" class="form-label">Opening Hours</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                        <input type="text" class="form-control" id="library_open_hours" name="library_open_hours" value="<?= htmlspecialchars($setting_data['library_open_hours'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-gradient-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>System Configuration</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="library_currency" class="form-label">Currency</label>
                                        <select class="form-select" id="library_currency" name="library_currency">
                                            <option value="PHP" <?= (isset($setting_data['library_currency']) && $setting_data['library_currency'] == 'PHP') ? 'selected' : '' ?>>PHP (₱)</option>
                                            <option value="USD" <?= (isset($setting_data['library_currency']) && $setting_data['library_currency'] == 'USD') ? 'selected' : '' ?>>USD ($)</option>
                                            <option value="EUR" <?= (isset($setting_data['library_currency']) && $setting_data['library_currency'] == 'EUR') ? 'selected' : '' ?>>EUR (€)</option>
                                            <option value="GBP" <?= (isset($setting_data['library_currency']) && $setting_data['library_currency'] == 'GBP') ? 'selected' : '' ?>>GBP (£)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="library_timezone" class="form-label">Timezone</label>
                                        <select class="form-select" id="library_timezone" name="library_timezone">
                                            <?php
                                            $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                                            foreach ($timezones as $timezone) {
                                                $selected = (isset($setting_data['library_timezone']) && $setting_data['library_timezone'] == $timezone) ? 'selected' : '';
                                                echo '<option value="' . $timezone . '" ' . $selected . '>' . $timezone . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="library_total_book_issue_day" class="form-label">Issue Period (Days)</label>
                                        <input type="number" class="form-control" id="library_total_book_issue_day" name="library_total_book_issue_day" value="<?= htmlspecialchars($setting_data['library_total_book_issue_day'] ?? '') ?>" min="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="library_one_day_fine" class="form-label">Daily Fine</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></span>
                                            <input type="number" class="form-control" id="library_one_day_fine" name="library_one_day_fine" value="<?= htmlspecialchars($setting_data['library_one_day_fine'] ?? '') ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="library_issue_total_book_per_user" class="form-label">Max Books per User</label>
                                        <input type="number" class="form-control" id="library_issue_total_book_per_user" name="library_issue_total_book_per_user" value="<?= htmlspecialchars($setting_data['library_issue_total_book_per_user'] ?? '') ?>" min="1">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="library_logo" class="form-label">Library Logo</label>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <input type="file" class="form-control" id="library_logo" name="library_logo" accept="image/*">
                                            <div class="form-text">Recommended size: 200x60 pixels</div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <?php if (isset($setting_data['library_logo']) && !empty($setting_data['library_logo'])): ?>
                                                <img src="../uploads/<?= htmlspecialchars($setting_data['library_logo']) ?>" alt="Library Logo" class="img-thumbnail" style="max-height: 60px;">
                                            <?php else: ?>
                                                <div class="bg-light p-3 rounded text-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end mt-4">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Library Map Tab -->
        <div class="tab-pane fade show active" id="map" role="tabpanel" aria-labelledby="map-tab">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-map me-2"></i>Library Floor Map
                                <small class="ms-2 opacity-75">Visualize rack locations and library features</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Map Legend:</strong>
                                    <div class="d-flex flex-wrap gap-3 mt-2">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-archive"></i>
                                            </div>
                                            <span>Active Rack</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger text-white d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-archive"></i>
                                            </div>
                                            <span>Disabled Rack</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-info text-white d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-archive"></i>
                                            </div>
                                            <span>Selected Rack</span>
                                        </div>
                                        <?php foreach ($library_features as $feature): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?> d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="<?= $feature['feature_icon'] ?>"></i>
                                            </div>
                                            <span><?= htmlspecialchars($feature['feature_name']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Library Map -->
                            <div class="mb-4">
                                <?php renderLibraryMap('list', null, $racks, 500, null, $library_features); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Library Features Tab -->
        <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
            <div class="row">
                <div class="col-lg-5">
                    <!-- Feature Form -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0" id="feature-form-title">
                                <i class="fas fa-plus-circle me-2"></i>Add New Feature
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" id="feature-form">
                                <input type="hidden" id="feature_id" name="feature_id">
                                
                                <div class="mb-3">
                                    <label for="feature_name" class="form-label">Feature Name</label>
                                    <input type="text" class="form-control" id="feature_name" name="feature_name" required>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="feature_icon" class="form-label">Icon</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i id="icon-preview" class="fas fa-landmark"></i></span>
                                            <input type="text" class="form-control" id="feature_icon" name="feature_icon" value="fas fa-landmark" required>
                                        </div>
                                        <div class="form-text">Example: fas fa-book, fas fa-door-open</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="feature_status" class="form-label">Status</label>
                                        <select class="form-select" id="feature_status" name="feature_status">
                                            <option value="Enable">Enable</option>
                                            <option value="Disable">Disable</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="bg_color" class="form-label">Background Color</label>
                                        <select class="form-select" id="bg_color" name="bg_color">
                                            <option value="primary">Primary (Blue)</option>
                                            <option value="secondary">Secondary (Gray)</option>
                                            <option value="success">Success (Green)</option>
                                            <option value="danger">Danger (Red)</option>
                                            <option value="warning">Warning (Yellow)</option>
                                            <option value="info">Info (Light Blue)</option>
                                            <option value="dark">Dark (Black)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="text_color" class="form-label">Text Color</label>
                                        <select class="form-select" id="text_color" name="text_color">
                                            <option value="white">White</option>
                                            <option value="dark">Dark</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="position_x" class="form-label">X Position</label>
                                        <input type="number" class="form-control" id="position_x" name="position_x" value="50" min="0" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="position_y" class="form-label">Y Position</label>
                                        <input type="number" class="form-control" id="position_y" name="position_y" value="50" min="0" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="width" class="form-label">Width</label>
                                        <input type="number" class="form-control" id="width" name="width" value="100" min="20" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="height" class="form-label">Height</label>
                                        <input type="number" class="form-control" id="height" name="height" value="40" min="20" required>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" id="reset-form" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                    <div>
                                        <button type="submit" id="add-feature-btn" name="add_feature" class="btn btn-primary">
                                            <i class="fas fa-plus-circle me-1"></i> Add Feature
                                        </button>
                                        <button type="submit" id="update-feature-btn" name="update_feature" class="btn btn-success d-none">
                                            <i class="fas fa-save me-1"></i> Update Feature
                                        </button>
                                        <button type="submit" id="delete-feature-btn" name="delete_feature" class="btn btn-danger d-none" onclick="return confirm('Are you sure you want to delete this feature?')">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Feature Preview -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-eye me-2"></i>Feature Preview
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="p-3 bg-light rounded d-flex justify-content-center align-items-center">
                                <div id="feature-preview" class="d-flex justify-content-center align-items-center bg-primary text-white" style="width: 100px; height: 40px; border-radius: 4px;">
                                    <i class="fas fa-landmark me-2"></i>
                                    <span id="preview-text">Feature</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <!-- Features List -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Library Features
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($library_features)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>No library features defined yet. Create your first one!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Feature</th>
                                                <th>Position</th>
                                                <th>Size</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($library_features as $feature): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?> d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                                <i class="<?= $feature['feature_icon'] ?>"></i>
                                                            </div>
                                                            <span><?= htmlspecialchars($feature['feature_name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>X: <?= $feature['position_x'] ?>, Y: <?= $feature['position_y'] ?></td>
                                                    <td><?= $feature['width'] ?> x <?= $feature['height'] ?></td>
                                                    <td>
                                                    <span class="badge bg-<?= ($feature['feature_status'] == 'Enable') ? 'success' : 'danger' ?>">
                                                            <?= $feature['feature_status'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-info edit-feature" 
                                                            data-id="<?= $feature['feature_id'] ?>"
                                                            data-name="<?= htmlspecialchars($feature['feature_name']) ?>"
                                                            data-icon="<?= htmlspecialchars($feature['feature_icon']) ?>"
                                                            data-pos-x="<?= $feature['position_x'] ?>"
                                                            data-pos-y="<?= $feature['position_y'] ?>"
                                                            data-width="<?= $feature['width'] ?>"
                                                            data-height="<?= $feature['height'] ?>"
                                                            data-bg-color="<?= $feature['bg_color'] ?>"
                                                            data-text-color="<?= $feature['text_color'] ?>"
                                                            data-status="<?= $feature['feature_status'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Feature Map Preview -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-map me-2"></i>Features Map Preview
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="bg-light position-relative rounded" style="height: 300px;">
                                <?php foreach ($library_features as $feature): ?>
                                    <?php if ($feature['feature_status'] == 'Enable'): ?>
                                    <div class="position-absolute d-flex justify-content-center align-items-center bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?>" 
                                         style="width: <?= $feature['width'] ?>px; height: <?= $feature['height'] ?>px; 
                                                left: <?= $feature['position_x'] ?>px; top: <?= $feature['position_y'] ?>px; 
                                                border-radius: 4px; z-index: 10;">
                                        <i class="<?= $feature['feature_icon'] ?> me-2"></i>
                                        <span><?= htmlspecialchars($feature['feature_name']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fine Management Tab -->
        <div class="tab-pane fade" id="fines" role="tabpanel" aria-labelledby="fines-tab">
            <div class="row">
                <div class="col-lg-6">
                    <!-- Fine Settings -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-money-bill-wave me-2"></i>Fine Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="fine_method" class="form-label">Fine Calculation Method</label>
                                    <select class="form-select" id="fine_method" name="fine_method">
                                        <option value="daily" <?= (isset($setting_data['fine_method']) && $setting_data['fine_method'] == 'daily') ? 'selected' : '' ?>>Daily Rate</option>
                                        <option value="fixed" <?= (isset($setting_data['fine_method']) && $setting_data['fine_method'] == 'fixed') ? 'selected' : '' ?>>Fixed Amount</option>
                                        <option value="progressive" <?= (isset($setting_data['fine_method']) && $setting_data['fine_method'] == 'progressive') ? 'selected' : '' ?>>Progressive Rate</option>
                                    </select>
                                    <div class="form-text">Choose how fines will be calculated for overdue books.</div>
                                </div>
                                
                                <div id="daily-fine-section" class="mb-3">
                                    <label for="daily_fine_rate" class="form-label">Daily Fine Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></span>
                                        <input type="number" class="form-control" id="daily_fine_rate" name="daily_fine_rate" value="<?= htmlspecialchars($setting_data['library_one_day_fine'] ?? '') ?>" step="0.01" min="0">
                                        <span class="input-group-text">per day</span>
                                    </div>
                                    <div class="form-text">The amount charged for each day a book is overdue.</div>
                                </div>
                                
                                <div id="fixed-fine-section" class="mb-3 d-none">
                                    <label for="fixed_fine_amount" class="form-label">Fixed Fine Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></span>
                                        <input type="number" class="form-control" id="fixed_fine_amount" name="fixed_fine_amount" value="<?= htmlspecialchars($setting_data['fixed_fine_amount'] ?? '') ?>" step="0.01" min="0">
                                    </div>
                                    <div class="form-text">A one-time fine applied regardless of how many days the book is overdue.</div>
                                </div>
                                
                                <div id="progressive-fine-section" class="mb-3 d-none">
                                    <label class="form-label">Progressive Fine Rates</label>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="mb-2">
                                                <label for="week1_rate" class="form-label">First week (per day)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></span>
                                                    <input type="number" class="form-control" id="week1_rate" name="week1_rate" value="<?= htmlspecialchars($setting_data['week1_rate'] ?? '') ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label for="week2_rate" class="form-label">Second week (per day)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></span>
                                                    <input type="number" class="form-control" id="week2_rate" name="week2_rate" value="<?= htmlspecialchars($setting_data['week2_rate'] ?? '') ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                            <div>
                                                <label for="week3_rate" class="form-label">After two weeks (per day)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></span>
                                                    <input type="number" class="form-control" id="week3_rate" name="week3_rate" value="<?= htmlspecialchars($setting_data['week3_rate'] ?? '') ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text">Different rates applied based on how long the book is overdue.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="grace_period" class="form-label">Grace Period (Days)</label>
                                    <input type="number" class="form-control" id="grace_period" name="grace_period" value="<?= htmlspecialchars($setting_data['grace_period'] ?? '0') ?>" min="0">
                                    <div class="form-text">Number of days after due date before fines start accumulating.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="max_fine_amount" class="form-label">Maximum Fine Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></span>
                                        <input type="number" class="form-control" id="max_fine_amount" name="max_fine_amount" value="<?= htmlspecialchars($setting_data['max_fine_amount'] ?? '') ?>" step="0.01" min="0">
                                    </div>
                                    <div class="form-text">The maximum fine that can be charged per book (0 for no limit).</div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="apply_weekends" name="apply_weekends" value="1" <?= (isset($setting_data['apply_weekends']) && $setting_data['apply_weekends'] == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="apply_weekends">
                                        Apply fines for weekends
                                    </label>
                                    <div class="form-text">If checked, fines will accumulate on weekends.</div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="auto_fine_calculation" name="auto_fine_calculation" value="1" <?= (isset($setting_data['auto_fine_calculation']) && $setting_data['auto_fine_calculation'] == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="auto_fine_calculation">
                                        Enable automatic fine calculation
                                    </label>
                                    <div class="form-text">If checked, fines will be automatically calculated when books are returned.</div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" name="update_fine_settings" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Fine Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <!-- Fine Calculation Preview -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>Fine Calculator
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Use this tool to calculate potential fines based on your settings.</p>
                            
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" value="<?= date('Y-m-d') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="return_date" class="form-label">Return Date</label>
                                <input type="date" class="form-control" id="return_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="num_books" class="form-label">Number of Books</label>
                                <input type="number" class="form-control" id="num_books" value="1" min="1">
                            </div>
                            
                            <div class="text-center mb-3">
                                <button type="button" id="calculate-fine" class="btn btn-primary">
                                    <i class="fas fa-calculator me-2"></i>Calculate Fine
                                </button>
                            </div>
                            
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Estimated Fine:</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-0">Days overdue: <span id="days-overdue">0</span></p>
                                            <p class="mb-0">Fine per book: <span id="fine-per-book">0.00</span> <?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></p>
                                        </div>
                                        <div class="text-end">
                                            <h4 id="total-fine" class="mb-0 text-danger">0.00 <?= isset($setting_data['library_currency']) ? $setting_data['library_currency'] : 'PHP' ?></h4>
                                            <small class="text-muted">Total fine amount</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fine Policies -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>Fine Policies
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="fine_policy" class="form-label">Fine Policy Text</label>
                                    <textarea class="form-control" id="fine_policy" name="fine_policy" rows="6"><?= htmlspecialchars($setting_data['fine_policy'] ?? 'Library fines are charged for overdue items. The current rate is '.$setting_data['library_one_day_fine'].' '.$setting_data['library_currency'].' per day per item. Please return your items on time to avoid fines.') ?></textarea>
                                    <div class="form-text">This text will be displayed to users regarding fine policies.</div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_fine_policy" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Policy
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Settings Page -->
<script>
$(document).ready(function() {
    // Enable select2 for timezone dropdown
    $('#library_timezone').select2({
        placeholder: "Select a timezone",
        theme: "bootstrap-5"
    });
    
    // Feature icon preview
    $('#feature_icon').on('input', function() {
        updatePreview();
    });
    
    // Feature name preview
    $('#feature_name').on('input', function() {
        updatePreview();
    });
    
    // Color previews
    $('#bg_color, #text_color').on('change', function() {
        updatePreview();
    });
    
    // Map position and size updates
    $('#position_x, #position_y, #width, #height').on('input', function() {
        updatePreview();
    });
    
    // Initialize preview
    updatePreview();
    
    // Function to update preview
    function updatePreview() {
        // Update icon preview
        const iconClass = $('#feature_icon').val();
        $('#icon-preview').attr('class', iconClass);
        
        // Update feature preview
        const featureName = $('#feature_name').val() || 'Feature';
        const bgColor = $('#bg_color').val();
        const textColor = $('#text_color').val();
        const width = $('#width').val() + 'px';
        const height = $('#height').val() + 'px';
        
        $('#preview-text').text(featureName);
        $('#feature-preview').attr('class', `d-flex justify-content-center align-items-center bg-${bgColor} text-${textColor}`);
        $('#feature-preview').css({
            'width': width,
            'height': height
        });
        
        // Update icon in preview
        $('#feature-preview i').attr('class', iconClass + ' me-2');
    }
    
    // Edit feature button click
    $('.edit-feature').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const icon = $(this).data('icon');
        const posX = $(this).data('pos-x');
        const posY = $(this).data('pos-y');
        const width = $(this).data('width');
        const height = $(this).data('height');
        const bgColor = $(this).data('bg-color');
        const textColor = $(this).data('text-color');
        const status = $(this).data('status');
        
        // Populate form
        $('#feature_id').val(id);
        $('#feature_name').val(name);
        $('#feature_icon').val(icon);
        $('#position_x').val(posX);
        $('#position_y').val(posY);
        $('#width').val(width);
        $('#height').val(height);
        $('#bg_color').val(bgColor);
        $('#text_color').val(status);
        $('#feature_status').val(status);
        
        // Update form UI
        $('#feature-form-title').html('<i class="fas fa-edit me-2"></i>Edit Feature');
        $('#add-feature-btn').addClass('d-none');
        $('#update-feature-btn').removeClass('d-none');
        $('#delete-feature-btn').removeClass('d-none');
        
        // Update preview
        updatePreview();
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $("#feature-form").offset().top - 100
        }, 200);
    });
    
    // Reset form button
    $('#reset-form').on('click', function() {
        $('#feature-form')[0].reset();
        $('#feature_id').val('');
        $('#feature-form-title').html('<i class="fas fa-plus-circle me-2"></i>Add New Feature');
        $('#add-feature-btn').removeClass('d-none');
        $('#update-feature-btn').addClass('d-none');
        $('#delete-feature-btn').addClass('d-none');
        updatePreview();
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
// Include footer
require_once '../footer.php';
?>
