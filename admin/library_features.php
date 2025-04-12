<?php
// library_features.php

include '../database_connection.php';
include '../function.php';

$message = ''; 

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
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

    header('location:library_features.php?msg=' . strtolower($status) . '');
    exit;
}

// ADD feature (Form Submit)
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
    
    header('location:library_features.php?msg=add');
    exit;
}

// EDIT feature (Form Submit)
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

    header('location:library_features.php?msg=edit');
    exit;
}

// SELECT all features
$query = "SELECT * FROM lms_library_features ORDER BY feature_id ASC";
$statement = $connect->prepare($query);
$statement->execute();
$all_features = $statement->fetchAll(PDO::FETCH_ASSOC);

// Get rack data for map preview
$query = "SELECT * FROM lms_location_rack ORDER BY location_rack_id ASC";
$statement = $connect->prepare($query);
$statement->execute();
$all_racks = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<div class="container-fluid px-4">
    <h1 class="my-4"><i class="fas fa-map-marked-alt me-2"></i>Library Features Management</h1>

    <?php if (isset($_GET["msg"])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Feature Disabled',
                text: 'The library feature has been successfully disabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done',
                timer: 2000,
                timerProgressBar: true
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Feature Enabled',
                text: 'The library feature has been successfully enabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done',
                timer: 2000,
                timerProgressBar: true
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Feature Added',
                text: 'The library feature was added successfully!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done',
                timer: 2000,
                timerProgressBar: true
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Feature Updated',
                text: 'The library feature was updated successfully!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done',
                timer: 2000,
                timerProgressBar: true
            });
        <?php endif; ?>

        // Remove ?msg=... from the URL without reloading the page
        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    });
    </script>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
        <!-- Add Feature Form -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Library Feature</h5>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Feature Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="feature_name" class="form-label">Feature Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input type="text" id="feature_name" name="feature_name" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="feature_icon" class="form-label">Feature Icon (FontAwesome class)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-icons"></i></span>
                                        <input type="text" id="feature_icon" name="feature_icon" class="form-control" value="fas fa-landmark" required>
                                    </div>
                                    <div class="form-text">
                                        Example: fas fa-door-open, fas fa-book-reader, fas fa-desktop
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="bg_color" class="form-label">Background Color</label>
                                        <select id="bg_color" name="bg_color" class="form-select">
                                            <option value="primary">Primary (Blue)</option>
                                            <option value="secondary" selected>Secondary (Gray)</option>
                                            <option value="success">Success (Green)</option>
                                            <option value="danger">Danger (Red)</option>
                                            <option value="warning">Warning (Yellow)</option>
                                            <option value="info">Info (Light Blue)</option>
                                            <option value="dark">Dark (Black)</option>
                                            <option value="light">Light (White)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="text_color" class="form-label">Text Color</label>
                                        <select id="text_color" name="text_color" class="form-select">
                                            <option value="white" selected>White</option>
                                            <option value="dark">Dark</option>
                                            <option value="light">Light</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="feature_status" class="form-label">Status</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                        <select name="feature_status" id="feature_status" class="form-select">
                                            <option value="Enable" selected>Enable</option>
                                            <option value="Disable">Disable</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Position & Size</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="position_x" class="form-label">X Position (px)</label>
                                        <input type="number" id="position_x" name="position_x" class="form-control" value="5" min="0" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="position_y" class="form-label">Y Position (px)</label>
                                        <input type="number" id="position_y" name="position_y" class="form-control" value="5" min="0" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="width" class="form-label">Width (px)</label>
                                        <input type="number" id="width" name="width" class="form-control" value="150" min="20" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="height" class="form-label">Height (px)</label>
                                        <input type="number" id="height" name="height" class="form-control" value="40" min="20" required>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">Preview</h6>
                                            <div class="position-relative" style="height: 200px; border: 1px solid #ccc; background-color: #f5f5f5; overflow: hidden;">
                                                <div id="preview-feature" class="position-absolute d-flex justify-content-center align-items-center bg-secondary text-white" 
                                                     style="width: 150px; height: 40px; top: 5px; left: 5px; font-size: 0.8rem; border-radius: 4px;">
                                                    <i class="fas fa-landmark me-2"></i>
                                                    <span id="preview-name">Feature Name</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4 d-flex justify-content-end">
                        <button type="submit" name="add_feature" class="btn btn-success me-2">
                            <i class="fas fa-save me-2"></i>Add Feature
                        </button>
                        <a href="library_features.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>
        <?php
        $id = $_GET['code'];
        $query = "SELECT * FROM lms_library_features WHERE feature_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $feature = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (!$feature) {
            header('location:library_features.php');
            exit;
        }
        ?>

        <!-- Edit Feature Form -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Library Feature</h5>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="feature_id" value="<?= $feature['feature_id'] ?>">
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Feature Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="feature_name" class="form-label">Feature Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input type="text" id="feature_name" name="feature_name" class="form-control" value="<?= htmlspecialchars($feature['feature_name']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="feature_icon" class="form-label">Feature Icon (FontAwesome class)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-icons"></i></span>
                                        <input type="text" id="feature_icon" name="feature_icon" class="form-control" value="<?= htmlspecialchars($feature['feature_icon']) ?>" required>
                                    </div>
                                    <div class="form-text">
                                        Example: fas fa-door-open, fas fa-book-reader, fas fa-desktop
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="bg_color" class="form-label">Background Color</label>
                                        <select id="bg_color" name="bg_color" class="form-select">
                                            <option value="primary" <?= $feature['bg_color'] == 'primary' ? 'selected' : '' ?>>Primary (Blue)</option>
                                            <option value="secondary" <?= $feature['bg_color'] == 'secondary' ? 'selected' : '' ?>>Secondary (Gray)</option>
                                            <option value="success" <?= $feature['bg_color'] == 'success' ? 'selected' : '' ?>>Success (Green)</option>
                                            <option value="danger" <?= $feature['bg_color'] == 'danger' ? 'selected' : '' ?>>Danger (Red)</option>
                                            <option value="warning" <?= $feature['bg_color'] == 'warning' ? 'selected' : '' ?>>Warning (Yellow)</option>
                                            <option value="info" <?= $feature['bg_color'] == 'info' ? 'selected' : '' ?>>Info (Light Blue)</option>
                                            <option value="dark" <?= $feature['bg_color'] == 'dark' ? 'selected' : '' ?>>Dark (Black)</option>
                                            <option value="light" <?= $feature['bg_color'] == 'light' ? 'selected' : '' ?>>Light (White)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="text_color" class="form-label">Text Color</label>
                                        <select id="text_color" name="text_color" class="form-select">
                                            <option value="white" <?= $feature['text_color'] == 'white' ? 'selected' : '' ?>>White</option>
                                            <option value="dark" <?= $feature['text_color'] == 'dark' ? 'selected' : '' ?>>Dark</option>
                                            <option value="light" <?= $feature['text_color'] == 'light' ? 'selected' : '' ?>>Light</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="feature_status" class="form-label">Status</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                        <select name="feature_status" id="feature_status" class="form-select">
                                            <option value="Enable" <?= $feature['feature_status'] == 'Enable' ? 'selected' : '' ?>>Enable</option>
                                            <option value="Disable" <?= $feature['feature_status'] == 'Disable' ? 'selected' : '' ?>>Disable</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Position & Size</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="position_x" class="form-label">X Position (px)</label>
                                        <input type="number" id="position_x" name="position_x" class="form-control" value="<?= $feature['position_x'] ?>" min="0" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="position_y" class="form-label">Y Position (px)</label>
                                        <input type="number" id="position_y" name="position_y" class="form-control" value="<?= $feature['position_y'] ?>" min="0" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="width" class="form-label">Width (px)</label>
                                        <input type="number" id="width" name="width" class="form-control" value="<?= $feature['width'] ?>" min="20" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="height" class="form-label">Height (px)</label>
                                        <input type="number" id="height" name="height" class="form-control" value="<?= $feature['height'] ?>" min="20" required>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">Preview</h6>
                                            <div class="position-relative" style="height: 200px; border: 1px solid #ccc; background-color: #f5f5f5; overflow: hidden;">
                                                <div id="preview-feature" class="position-absolute d-flex justify-content-center align-items-center bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?>" 
                                                     style="width: <?= $feature['width'] ?>px; height: <?= $feature['height'] ?>px; top: <?= $feature['position_y'] ?>px; left: <?= $feature['position_x'] ?>px; font-size: 0.8rem; border-radius: 4px;">
                                                    <i class="<?= $feature['feature_icon'] ?> me-2"></i>
                                                    <span id="preview-name"><?= htmlspecialchars($feature['feature_name']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4 d-flex justify-content-end">
                        <button type="submit" name="edit_feature" class="btn btn-primary me-2">
                            <i class="fas fa-save me-2"></i>Update Feature
                        </button>
                        <a href="library_features.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
        <?php
        $id = $_GET['code'];
        $query = "SELECT * FROM lms_library_features WHERE feature_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $feature = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (!$feature) {
            header('location:library_features.php');
            exit;
        }
        ?>

        <!-- View Feature Details -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Feature Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <th width="30%"><i class="fas fa-hashtag me-2"></i>ID</th>
                                    <td><?= htmlspecialchars($feature['feature_id']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-tag me-2"></i>Feature Name</th>
                                    <td><?= htmlspecialchars($feature['feature_name']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-icons me-2"></i>Icon</th>
                                    <td>
                                        <i class="<?= htmlspecialchars($feature['feature_icon']) ?> me-2"></i>
                                        <?= htmlspecialchars($feature['feature_icon']) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-palette me-2"></i>Colors</th>
                                    <td>
                                        <span class="badge bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?> me-2">
                                            Background: <?= ucfirst($feature['bg_color']) ?>
                                        </span>
                                        <span class="badge bg-dark">
                                            Text: <?= ucfirst($feature['text_color']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-map-marker-alt me-2"></i>Position</th>
                                    <td>X: <?= $feature['position_x'] ?>px, Y: <?= $feature['position_y'] ?>px</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-expand me-2"></i>Size</th>
                                    <td>Width: <?= $feature['width'] ?>px, Height: <?= $feature['height'] ?>px</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-toggle-on me-2"></i>Status</th>
                                    <td>
                                        <?php if($feature['feature_status'] === 'Enable'): ?>
                                            <span class="badge bg-success">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar me-2"></i>Created On</th>
                                    <td><?= date('M d, Y h:i A', strtotime($feature['created_on'])) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar-check me-2"></i>Updated On</th>
                                    <td><?= date('M d, Y h:i A', strtotime($feature['updated_on'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-3 d-flex justify-content-end">
                            <a href="library_features.php?action=edit&code=<?= $feature['feature_id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                            <a href="library_features.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-map me-2"></i>Preview on Library Map</h5>
                    </div>
                    <div class="card-body">
                        <!-- Preview on Map -->
                        <div class="position-relative bg-light" style="height: 400px; border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">
                            <!-- Grid Background -->
                            <div style="width: 100%; height: 100%; background-image: linear-gradient(#e9ecef 1px, transparent 1px), linear-gradient(90deg, #e9ecef 1px, transparent 1px); background-size: 50px 50px;">
                                <!-- Feature Preview -->
                                <div class="position-absolute d-flex justify-content-center align-items-center bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?>" 
                                    style="width: <?= $feature['width'] ?>px; height: <?= $feature['height'] ?>px; top: <?= $feature['position_y'] ?>px; left: <?= $feature['position_x'] ?>px; font-size: 0.8rem; border-radius: 4px; z-index: 80;">
                                    <i class="<?= $feature['feature_icon'] ?> me-2"></i>
                                    <span><?= htmlspecialchars($feature['feature_name']) ?></span>
                                </div>
                                
                                <!-- Example Rack -->
                                <div class="position-absolute d-flex justify-content-center align-items-center bg-secondary text-white opacity-75" 
                                    style="width: 50px; height: 50px; top: 200px; left: 200px; border-radius: 4px; z-index: 70;">
                                    <i class="fas fa-archive"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="row">            
            <div class="col-md-4">
                <!-- Library Map Preview with Features -->
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-map me-2"></i>Library Map Preview with Features</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Include library map component
                        include 'library_map_component.php';
                        // Render the map in list mode with all active features
                        renderLibraryMap('list', null, $all_racks, 500, null, 
                            array_filter($all_features, function($f) {
                                return $f['feature_status'] === 'Enable';
                            })
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <!-- Default Features List View -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Library Features</h5>
                        <div>
                            <a href="library_features.php?action=add" class="btn btn-light btn-sm">
                                <i class="fas fa-plus-circle me-1"></i>Add New Feature
                            </a>
                        </div>
                    </div>
                    <div class="card-body">                
                        <table id="dataTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Icon</th>
                                    <th>Feature Name</th>
                                    <th>Position</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_features as $feature): ?>
                                <tr>
                                    <td><?= $feature['feature_id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?> p-2 rounded me-2">
                                                <i class="<?= $feature['feature_icon'] ?>"></i>
                                            </div>
                                            <small class="text-muted"><?= $feature['feature_icon'] ?></small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($feature['feature_name']) ?></td>
                                    <td>X: <?= $feature['position_x'] ?>, Y: <?= $feature['position_y'] ?></td>
                                    <td>W: <?= $feature['width'] ?>, H: <?= $feature['height'] ?></td>
                                    <td>
                                        <?php if($feature['feature_status'] === 'Enable'): ?>
                                            <span class="badge bg-success">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="library_features.php?action=view&code=<?= $feature['feature_id'] ?>" class="btn btn-info btn-sm me-1" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="library_features.php?action=edit&code=<?= $feature['feature_id'] ?>" class="btn btn-primary btn-sm me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($feature['feature_status'] === 'Enable'): ?>
                                                <a href="library_features.php?action=delete&status=Disable&code=<?= $feature['feature_id'] ?>" class="btn btn-danger btn-sm disable-button" title="Disable">
                                                    <i class="fas fa-toggle-off"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="library_features.php?action=delete&status=Enable&code=<?= $feature['feature_id'] ?>" class="btn btn-success btn-sm enable-button" title="Enable">
                                                    <i class="fas fa-toggle-on"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Preview script for add/edit feature
document.addEventListener('DOMContentLoaded', function() {
    // Get form elements for preview
    const featureName = document.getElementById('feature_name');
    const featureIcon = document.getElementById('feature_icon');
    const positionX = document.getElementById('position_x');
    const positionY = document.getElementById('position_y');
    const width = document.getElementById('width');
    const height = document.getElementById('height');
    const bgColor = document.getElementById('bg_color');
    const textColor = document.getElementById('text_color');
    
    // Preview elements
    const previewFeature = document.getElementById('preview-feature');
    const previewName = document.getElementById('preview-name');
    
    // Check if we're on add/edit page
    if (featureName && previewFeature) {
        // Update preview on input change
        [featureName, featureIcon, positionX, positionY, width, height, bgColor, textColor].forEach(elem => {
            if (elem) {
                elem.addEventListener('input', updatePreview);
            }
        });
        
        function updatePreview() {
            if (previewName) previewName.textContent = featureName.value;
            
            if (previewFeature) {
                // Update icon if it exists as first child
                const iconElem = previewFeature.querySelector('i');
                if (iconElem && featureIcon) {
                    iconElem.className = featureIcon.value + ' me-2';
                }
                
                // Update position and size
                if (positionX) previewFeature.style.left = positionX.value + 'px';
                if (positionY) previewFeature.style.top = positionY.value + 'px';
                if (width) previewFeature.style.width = width.value + 'px';
                if (height) previewFeature.style.height = height.value + 'px';
                
                // Update colors
                if (bgColor && textColor) {
                    // Remove existing color classes
                    previewFeature.className = previewFeature.className
                        .replace(/bg-\w+/g, '')
                        .replace(/text-\w+/g, '')
                        .trim();
                    
                    // Add new color classes
                    previewFeature.classList.add('bg-' + bgColor.value);
                    previewFeature.classList.add('text-' + textColor.value);
                }
            }
        }
    }
    
    // Initialize DataTable if available
    $(document).ready(function() {
        $('#dataTable').DataTable({
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: 0 }, // ID
                { responsivePriority: 2, targets: 2 }, // Feature Name
                { responsivePriority: 3, targets: 6 }, // Action
                { 
                    targets: 6, // Action column (now correct index)
                    orderable: false,
                    searchable: false,
                    className: 'no-wrap' // Prevent line breaks
                },
                { 
                    targets: [1, 3, 4, 5], // Less important columns
                    responsivePriority: 4 
                }
            ],
            order: [[0, 'asc']],
            language: {
                emptyTable: "No features available"
            },
            scrollY: '400px',
            scrollX: true, // Added for horizontal scrolling
            scrollCollapse: true,
            paging: true,
            fixedHeader: true, // Optional: keeps headers visible
            dom: '<"top"lf>rt<"bottom"ip>', // Custom layout
            initComplete: function() {
                // Initialize Bootstrap tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    });
    
    // SweetAlert confirmation for disable/enable actions
    document.querySelectorAll('.disable-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            
            Swal.fire({
                title: 'Disable Feature?',
                text: "This feature will be hidden from the library map.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, disable it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
    
    document.querySelectorAll('.enable-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            
            Swal.fire({
                title: 'Enable Feature?',
                text: "This feature will be visible on the library map.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, enable it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
});
</script>

<?php
include '../footer.php';
?>