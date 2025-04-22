<?php
//function.php

function base_url()
{
	return 'http://localhost/SmartLib/';
}

function startUserSession($user_unique_id, $role_id, $user_email, $role_name, $user_name,$profile_img) {

    $_SESSION['user_unique_id'] = $user_unique_id;
    $_SESSION['role_id'] = $role_id;
	$_SESSION['email'] = $user_email;
    $_SESSION['role_name'] = $role_name;
	$_SESSION['user_name'] = $user_name; 
	$_SESSION['profile_img'] = $profile_img;

    error_log("Session started: " . print_r($_SESSION, true)); // Debugging
}

// Checks if the user is logged in with a given role type
function is_logged_in($type) {
    $role_map = [
        'admin'     => 1,
        'librarian' => 2,
        'faculty'   => 3,
        'student'   => 4,
        'visitor'   => 5
    ];

    if (!isset($_SESSION['role_id']) || !isset($role_map[$type])) {
        return false;
    }

    return $_SESSION['role_id'] === $role_map[$type];
}
function authenticate_admin() {
    // If not authenticated as admin or librarian, redirect to login page
    if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
        // Log the redirection attempt
        error_log("Unauthenticated admin/librarian access attempt. Redirecting to login.");
        
        // Clear session and destroy it
        $_SESSION = array();
        session_destroy();
        session_start();
        
        // Set a flash error message
        $_SESSION['error'] = "Please log in as an administrator or librarian.";
        
        // Redirect to login page
        header("Location: " . base_url() . "index.php");
        exit();
    }
}
// Authenticate faculty/student users
function authenticate_user() {
    // If not authenticated as faculty or student, redirect to login page
    if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [3, 4])) {
        // Log the redirection attempt
        error_log("Unauthenticated user access attempt. Redirecting to login.");
        
        // Clear session and destroy it
        $_SESSION = array();
        session_destroy();
        session_start();
        
        // Set a flash error message
        $_SESSION['error'] = "Please log in as a faculty or student.";
        
        // Redirect to login page
        header("Location: " . base_url() . "index.php");
        exit();
    }
}

// Authenticate visitor users
function authenticate_visitor() {
    // If not authenticated as visitor, redirect to login page
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
        // Log the redirection attempt
        error_log("Unauthenticated visitor access attempt. Redirecting to login.");
        
        // Clear session and destroy it
        $_SESSION = array();
        session_destroy();
        session_start();
        
        // Set a flash error message
        $_SESSION['error'] = "Please log in as a visitor.";
        
        // Redirect to login page
        header("Location: " . base_url() . "index.php");
        exit();
    }
}

// Gets landing page based on role_id
function get_role_landing_page($role_id) {
    $base_url = base_url();

    if (in_array($role_id, [1, 2])) {
        return $base_url . "admin/index.php";
    } elseif (in_array($role_id, [3, 4])) {
        return $base_url . "user/index.php";
    } elseif ($role_id == 5) {
        return $base_url . "guest/index.php";
    } else {
        return $base_url . "index.php";
    }
}

// Process login function
function process_login($connect, $email, $password) {

    // Query to search across all user types using UNION
    $query = "
        SELECT u.*, r.role_name
        FROM (
            SELECT admin_unique_id AS user_unique_id, admin_email AS email, admin_password AS password, role_id, admin_profile AS profile_img FROM lms_admin
            UNION ALL
            SELECT librarian_unique_id AS user_unique_id, librarian_email AS email, librarian_password AS password, role_id, librarian_profile AS profile_img FROM lms_librarian
            UNION ALL
            SELECT user_unique_id AS user_unique_id, user_email AS email, user_password AS password, role_id, user_profile AS profile_img FROM lms_user
        ) AS u
        INNER JOIN user_roles r ON u.role_id = r.role_id
        WHERE u.email = :email
    ";

    $statement = $connect->prepare($query);
    $statement->execute([':email' => $email]);
    
    if ($statement->rowCount() > 0) {
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        error_log("Stored password: " . $row['password']);
        
        // Verify password
        if (password_verify($password, $row['password'])) {
			$user_name = explode('@', $row['email'])[0]; // Extract username
			$user_email = $row['email'];
			$profile_img = $row['profile_img'];
            error_log("Extracted username: " . $user_name); // Debugging

            startUserSession($row['user_unique_id'], $row['role_id'],  $user_email, $row['role_name'], $user_name, $profile_img);
            return ['success' => true, 'role_id' => $row['role_id']];
        }
        
        return ['success' => false, 'message' => 'Wrong password'];
    }

    return ['success' => false, 'message' => 'Email address not found'];
}

// Function to process registration
function process_registration($connect, $formdata)
{
    // Allow only guests to register
    if ($formdata['role_id'] != 5) {
        return ['success' => false, 'message' => 'Only guests can register'];
    }

    // Check if email already exists
    $query = "
    SELECT * FROM lms_user 
    WHERE user_email = :email
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':email' => $formdata['user_email']]);

    if ($statement->rowCount() > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }

    // Generate unique ID with "U" prefix for guests
    $user_unique_id = 'U' . uniqid();

    // Secure password hashing
    $hashed_password = password_hash($formdata['user_password'], PASSWORD_DEFAULT);

    // Prepare data for insertion
    $data = [
        ':user_name'            => $formdata['user_name'],
        ':user_address'         => $formdata['user_address'],
        ':user_contact_no'      => $formdata['user_contact_no'],
        ':user_profile'         => $formdata['user_profile'],
        ':user_email'           => $formdata['user_email'],
        ':user_password'        => $hashed_password,
        ':user_verification_code' => md5(uniqid()),
        ':user_verification_status' => 'No',
        ':user_unique_id'       => $user_unique_id,
        ':user_status'          => 'Enable',
        ':user_created_on'      => get_date_time($connect),
        ':role_id'              => 5
    ];

    $query = "
    INSERT INTO lms_user 
    (user_name, user_address, user_contact_no, user_profile, user_email, user_password, user_verification_code, user_verification_status, user_unique_id, user_status, user_created_on, role_id) 
    VALUES (:user_name, :user_address, :user_contact_no, :user_profile, :user_email, :user_password, :user_verification_code, :user_verification_status, :user_unique_id, :user_status, :user_created_on, :role_id)
    ";
    $statement = $connect->prepare($query);

    if ($statement->execute($data)) {
        return [
            'success' => true,
            'user_unique_id' => $user_unique_id,
            'message' => 'Guest registered successfully'
        ];
    }

    return ['success' => false, 'message' => 'Registration failed'];
}

// Function to validate email
function validate_email($email)
{
    if(empty($email)) {
        return ['valid' => false, 'message' => 'Email Address is required'];
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid Email Address'];
    }
    
    return ['valid' => true];
}

// Function to send verification email
function send_verification_email($user_email, $user_name, $user_unique_id, $verification_code)
{
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'xxxx';  // SMTP username
        $mail->Password = 'xxxx';  // SMTP password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 80;
        
        $mail->setFrom('tutorial@webslesson.info', 'Webslesson');
        $mail->addAddress($user_email, $user_name);
        $mail->isHTML(true);
        $mail->Subject = 'Registration Verification for Library Management System';
        $mail->Body = '
            <p>Thank you for registering for Library Management System Demo & your Unique ID is <b>'.$user_unique_id.'</b> which will be used for issue book.</p>
            <p>This is a verification email, please click the link to verify your email address.</p>
            <p><a href="'.base_url().'verify.php?code='.$verification_code.'">Click to Verify</a></p>
            <p>Thank you...</p>
        ';
        
        $mail->send();
        return ['success' => true, 'message' => 'Verification Email sent to ' . $user_email];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}

// Function to process profile image upload
function process_profile_image_upload($file)
{
    if(empty($file['name'])) {
        return ['success' => false, 'message' => 'Please Select Profile Image'];
    }
    
    $img_name = $file['name'];
    $img_type = $file['type'];
    $tmp_name = $file['tmp_name'];
    $fileinfo = @getimagesize($tmp_name);
    
    if(!$fileinfo) {
        return ['success' => false, 'message' => 'Invalid image file'];
    }
    
    $width = $fileinfo[0];
    $height = $fileinfo[1];
    $image_size = $file['size'];
    $img_explode = explode(".", $img_name);
    $img_ext = strtolower(end($img_explode));
    $extensions = ["jpeg", "png", "jpg"];
    
    // Check file extension
    if(!in_array($img_ext, $extensions)) {
        return ['success' => false, 'message' => 'Invalid Image File (Only JPG, JPEG, and PNG allowed)'];
    }
    
    // Check file size (2MB limit)
    if($image_size > 2000000) {
        return ['success' => false, 'message' => 'Image size exceeds 2MB'];
    }
    
    // Check if image is portrait (height > width)
    if($height <= $width) {
        return ['success' => false, 'message' => 'Image must be portrait orientation (height greater than width)'];
    }
    
    // Set a minimum acceptable size
    if($width < 100 || $height < 150) {
        return ['success' => false, 'message' => 'Image is too small. Minimum dimensions: 100px width, 150px height'];
    }
    
    // Check aspect ratio if needed (e.g., for portrait, typically 3:4 or 2:3)
    $ratio = $height / $width;
    if($ratio < 1.2) { // Requiring at least a 6:5 ratio (slightly portrait)
        return ['success' => false, 'message' => 'Image must have a more pronounced portrait aspect ratio'];
    }
    
    // Generate new file name
    $new_img_name = time() . '-' . rand() . '.' . $img_ext;
    
    // Move uploaded file
    if(move_uploaded_file($tmp_name, "upload/".$new_img_name)) {
        return ['success' => true, 'file_name' => $new_img_name];
    } else {
        return ['success' => false, 'message' => 'Failed to upload image'];
    }
}
// Function to set flash message
function set_flash_message($key, $message)
{
    $_SESSION[$key] = $message;
}

// Function to get flash message
function get_flash_message($key)
{
    $message = '';
    if(isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
    }
    return $message;
}

// Function to generate sweet alert
function sweet_alert($type, $message)
{
    return '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: "' . $type . '",
            title: "' . ($type == "success" ? "Success" : "Error") . '",
            text: "' . $message . '",
            confirmButtonColor: "#3085d6",
            confirmButtonText: "OK"
        });
    });
    </script>
    ';
}

function validate_session() {
	// Start session if not already started
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// Check for session timeout (optional - set to 30 minutes)
	if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
		// Session expired, destroy it
		session_unset();
		session_destroy();
		session_start();
		$_SESSION['swal_type'] = 'warning';
		$_SESSION['swal_title'] = 'Session Expired';
		$_SESSION['swal_text'] = 'Your session has expired. Please log in again.';
		header("Location: " . base_url() . "index.php");
		exit();
	}

	// Update last activity time
	$_SESSION['last_activity'] = time();

	// Check if user is logged in but accessing the wrong section
	if (isset($_SESSION['role_id'])) {
		$current_path = $_SERVER['PHP_SELF'];
		$public_pages = ['/index.php', '/book.php', '/about_us.php', '/faqs.php'];
		$current_file = basename($current_path);
		
		// Detect which section user is trying to access
		$accessing_admin = (strpos($current_path, '/admin/') !== false);
		$accessing_user = (strpos($current_path, '/user/') !== false);
		$accessing_guest = (strpos($current_path, '/guest/') !== false);
		
		// Redirect if user is in the wrong section
		if ($accessing_admin && !in_array($_SESSION['role_id'], [1, 2])) {
			$_SESSION['swal_type'] = 'error';
			$_SESSION['swal_title'] = 'Access Denied';
			$_SESSION['swal_text'] = "You don't have permission to access the admin area.";
			header("Location: " . get_role_landing_page($_SESSION['role_id']));
			exit();
		}
		
		if ($accessing_user && !in_array($_SESSION['role_id'], [3, 4])) {
			$_SESSION['swal_type'] = 'error';
			$_SESSION['swal_title'] = 'Access Denied';
			$_SESSION['swal_text'] = "You don't have permission to access the user area.";
			header("Location: " . get_role_landing_page($_SESSION['role_id']));
			exit();
		}
		
		if ($accessing_guest && $_SESSION['role_id'] != 5) {
			$_SESSION['swal_type'] = 'error';
			$_SESSION['swal_title'] = 'Access Denied';
			$_SESSION['swal_text'] = "You don't have permission to access the guest area.";
			header("Location: " . get_role_landing_page($_SESSION['role_id']));
			exit();
		}
		
		// If accessing index.php while logged in, redirect to proper landing page
		if (isset($_SESSION['role_id']) && in_array('/' . $current_file, $public_pages)) {
			header("Location: " . get_role_landing_page($_SESSION['role_id']));
			exit();
		}
	}
}

function get_user_role()
{
    // Check if no session variables are set
    if (!isset($_SESSION['user_unique_id']) && !isset($_SESSION['librarian_unique_id']) && !isset($_SESSION['admin_unique_id'])) {
        return null; // No session means no role detected
    }

    // Determine unique ID for the logged-in user
    $unique_id = $_SESSION['user_unique_id'] ?? $_SESSION['librarian_unique_id'] ?? $_SESSION['admin_unique_id'] ?? null;

    // Return null if no unique ID exists
    if ($unique_id === null) {
        return null;
    }

    // Extract the first character prefix from the unique ID
    $prefix = strtoupper(substr($unique_id, 0, 1)); // Get the first letter (uppercase)

    // Map the prefix to a role name
    switch ($prefix) {
        case 'A':
            return 'admin';       // Admin
        case 'L':
            return 'librarian';   // Librarian
        case 'U':
            return 'visitor';     // Visitor
        case 'F':
            return 'faculty';     // Faculty
        case 'S':
            return 'student';     // Student
        default:
            return null;           // Guest or invalid role
    }
} 

function fetchRoleName($userUniqueId) {
	// Return null if the input is empty or invalid
    if (empty($userUniqueId)) {
        return null;
    }

    // Extract the prefix (first character) from the unique ID
    $prefix = strtoupper(substr($userUniqueId, 0, 1)); // Get the first letter (uppercase)
    return get_user_role($prefix);
}

// Function to generate a unique user ID without role-based database lookup
function generate_unique_id($connect, $role_prefix) {
    $unique_id = '';
    $exists = true;

    while ($exists) {
        $random_digits = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $unique_id = $role_prefix . $random_digits;

        // Check if the ID already exists
        $query = "SELECT COUNT(*) FROM lms_user WHERE user_unique_id = :user_unique_id";
        $statement = $connect->prepare($query);
        $statement->execute([':user_unique_id' => $unique_id]);
        $exists = $statement->fetchColumn() > 0; // Repeat if ID exists
    }

    return $unique_id;
}
function get_user_details_by_unique_id($unique_id, $connect) {
    // Determine user type based on unique ID prefix
    $prefix = substr($unique_id, 0, 1);
    
    try {
        switch ($prefix) {
            case 'A': // Admin
                $query = "SELECT 
                    admin_id AS user_id, 
                    admin_email AS user_email, 
                    'Admin' AS role_id, 
                    admin_unique_id AS user_unique_id
                FROM lms_admin 
                WHERE admin_unique_id = :unique_id";
                break;
            
            case 'L': // Librarian
                $query = "SELECT 
                    librarian_id AS user_id, 
                    librarian_email AS user_email, 
                    librarian_name AS user_name,
                    'Librarian' AS role_id, 
                    librarian_unique_id AS user_unique_id
                FROM lms_librarian 
                WHERE librarian_unique_id = :unique_id";
                break;
            
            case 'F': // Faculty
                $query = "SELECT 
                    user_id, 
                    user_email, 
                    user_name,
                    'Faculty' AS role_id, 
                    user_unique_id
                FROM lms_user 
                WHERE user_unique_id = :unique_id AND role_id = 3";
                break;
            
            case 'S': // Student
                $query = "SELECT 
                    user_id, 
                    user_email, 
                    user_name,
                    'Student' AS role_id, 
                    user_unique_id
                FROM lms_user 
                WHERE user_unique_id = :unique_id AND role_id = 4";
                break;
            
            case 'U': // Guest User
                $query = "SELECT 
                    user_id, 
                    user_email, 
                    user_name,
                    'Guest' AS role_id, 
                    user_unique_id
                FROM lms_user 
                WHERE user_unique_id = :unique_id AND role_id = 5";
                break;
            
            default:
                return null;
        }
        
        $statement = $connect->prepare($query);
        $statement->bindParam(':unique_id', $unique_id, PDO::PARAM_STR);
        $statement->execute();
        
        return $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error
        error_log("User lookup error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get complete user details based on unique ID
 * @param string $unique_id User's unique identifier
 * @param PDO $connect Database connection
 * @return array User details
 */
function get_complete_user_details($unique_id, $connect) {
    // Determine user type based on unique ID prefix
    $prefix = substr($unique_id, 0, 1);
    
    $details = [];
    
    try {
        switch ($prefix) {
            case 'A': // Admin
                $query = "SELECT 
                    admin_id AS id, 
                    admin_email AS email, 
                    admin_password AS password,
                    admin_profile AS profile_image,
                    admin_unique_id AS unique_id,
                    1 AS role_id,
                    'Administrator' AS role_name,
                    'Administrator' AS name,
                    NULL AS address,
                    NULL AS contact_no,
                    NULL AS created_on,
                    NULL AS updated_on,
                    'admin' AS table_prefix
                FROM lms_admin 
                WHERE admin_unique_id = :unique_id";
                break;
            
            case 'L': // Librarian
                $query = "SELECT 
                    librarian_id AS id, 
                    librarian_email AS email, 
                    librarian_password AS password,
                    librarian_name AS name,
                    librarian_address AS address,
                    librarian_contact_no AS contact_no,
                    librarian_profile AS profile_image,
                    librarian_unique_id AS unique_id,
                    librarian_status AS status,
                    2 AS role_id,
                    'Librarian' AS role_name,
                    lib_created_on AS created_on,
                    lib_updated_on AS updated_on,
                    'librarian' AS table_prefix
                FROM lms_librarian 
                WHERE librarian_unique_id = :unique_id";
                break;
            
            default: // User (Faculty, Student, Visitor)
                $query = "SELECT 
                    user_id AS id, 
                    user_email AS email, 
                    user_password AS password,
                    user_name AS name,
                    user_address AS address,
                    user_contact_no AS contact_no,
                    user_profile AS profile_image,
                    user_unique_id AS unique_id,
                    user_status AS status,
                    role_id,
                    CASE
                        WHEN role_id = 3 THEN 'Faculty'
                        WHEN role_id = 4 THEN 'Student'
                        WHEN role_id = 5 THEN 'Guest'
                        ELSE 'User'
                    END AS role_name,
                    user_created_on AS created_on,
                    user_updated_on AS updated_on,
                    'user' AS table_prefix
                FROM lms_user 
                WHERE user_unique_id = :unique_id";
                break;
        }
        
        $statement = $connect->prepare($query);
        $statement->bindParam(':unique_id', $unique_id, PDO::PARAM_STR);
        $statement->execute();
        
        $details = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Set default profile image if not available
        if (empty($details['profile_image'])) {
            switch ($details['role_id']) {
                case 1:
                    $details['profile_image'] = 'admin.jpg';
                    break;
                case 2:
                    $details['profile_image'] = 'librarian.jpg';
                    break;
                default:
                    $details['profile_image'] = 'user.jpg';
                    break;
            }
        }
        
        return $details;
    } catch (PDOException $e) {
        error_log("User lookup error: " . $e->getMessage());
        return null;
    }
}

function set_timezone($connect)
{
	$query = "
	SELECT library_timezone FROM lms_setting 
	LIMIT 1
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		date_default_timezone_set($row["library_timezone"]);
	}
}

function get_date_time($connect)
{
	set_timezone($connect);

	return date("Y-m-d H:i:s",  STRTOTIME(date('h:i:sa')));
}
function get_one_day_fines($connect)
{
	$output = 0;
	$query = "
	SELECT library_one_day_fine FROM lms_setting 
	LIMIT 1
	";
	$result = $connect->query($query);
	foreach($result as $row)
	{
		$output = $row["library_one_day_fine"];
	}
	return $output;
}

function get_currency_symbol($connect)
{
	$output = '';
	$query = "
	SELECT library_currency FROM lms_setting 
	LIMIT 1
	";
	$result = $connect->query($query);
	foreach($result as $row)
	{
		$currency_data = currency_array();
		foreach($currency_data as $currency)
		{
			if($currency["code"] == $row['library_currency'])
			{
				$output = '<span style="font-family: DejaVu Sans;">' . $currency["symbol"] . '</span>&nbsp;';
			}
		}		
	}
	return $output;
}

function get_book_issue_limit_per_user($connect)
{
	$output = '';
	$query = "
	SELECT library_issue_total_book_per_user FROM lms_setting 
	LIMIT 1
	";
	$result = $connect->query($query);
	foreach($result as $row)
	{
		$output = $row["library_issue_total_book_per_user"];
	}
	return $output;
}

function get_total_book_issue_per_user($connect, $user_unique_id)
{
	$output = 0;

	$query = "
	SELECT COUNT(issue_book_id) AS Total FROM lms_issue_book 
	WHERE user_id = '".$user_unique_id."' 
	AND book_issue_status = 'Issued'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$output = $row["Total"];
	}
	return $output;
}

function get_total_book_issue_day($connect)
{
	$output = 0;

	$query = "
	SELECT library_total_book_issue_day FROM lms_setting 
	LIMIT 1
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$output = $row["library_total_book_issue_day"];
	}
	return $output;
}

function convert_data($string, $action = 'encrypt')
{
	$encrypt_method = "AES-256-CBC";
	$secret_key = 'AA74CDCC2BBRT935136HH7B63C27'; // user define private key
	$secret_iv = '5fgf5HJ5g27'; // user define secret key
	$key = hash('sha256', $secret_key);
	$iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo
	if ($action == 'encrypt') 
	{
		$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	    $output = base64_encode($output);
	} 
	else if ($action == 'decrypt') 
	{
		$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	}
	return $output;
}

function Currency_list()
{
	$html = '
		<option value="">Select Currency</option>
	';
	$data = currency_array();
	foreach($data as $row)
	{
		$html .= '<option value="'.$row["code"].'">'.$row["name"].'</option>';
	}
	return $html;
}

function fill_author($connect)
{
	$query = "
	SELECT author_name FROM lms_author 
	WHERE author_status = 'Enable' 
	ORDER BY author_name ASC
	";

	$result = $connect->query($query);

	$output = '<option value="">Select Author</option>';

	foreach($result as $row)
	{
		$output .= '<option value="'.$row["author_name"].'">'.$row["author_name"].'</option>';
	}

	return $output;
}

function fill_category($connect)
{
	$query = "
	SELECT category_name FROM lms_category 
	WHERE category_status = 'Enable' 
	ORDER BY category_name ASC
	";

	$result = $connect->query($query);

	$output = '<option value="">Select Category</option>';

	foreach($result as $row)
	{
		$output .= '<option value="'.$row["category_name"].'">'.$row["category_name"].'</option>';
	}

	return $output;
}

function fill_location_rack($connect)
{
	$query = "
	SELECT location_rack_name FROM lms_location_rack 
	WHERE location_rack_status = 'Enable' 
	ORDER BY location_rack_name ASC
	";

	$result = $connect->query($query);

	$output = '<option value="">Select Location Rack</option>';

	foreach($result as $row)
	{
		$output .= '<option value="'.$row["location_rack_name"].'">'.$row["location_rack_name"].'</option>';
	}

	return $output;
}

function Count_total_issue_book_number($connect)
{
	$total = 0;

	$query = "SELECT COUNT(issue_book_id) AS Total FROM lms_issue_book";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function Count_total_returned_book_number($connect)
{
	$total = 0;

	$query = "
	SELECT COUNT(issue_book_id) AS Total FROM lms_issue_book 
	WHERE issue_book_status = 'Returned'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function Count_total_not_returned_book_number($connect)
{
	$total = 0;

	$query = "
	SELECT COUNT(issue_book_id) AS Total FROM lms_issue_book 
	WHERE issue_book_status = 'Lost'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function Count_total_fines($connect)
{
    $total_fines = 0;

    $query = "
    SELECT COUNT(fines_id) AS Total FROM lms_fines
    ";

    $result = $connect->query($query);

    foreach ($result as $row)
    {
        $total_fines = $row["Total"];
    }

    return $total_fines;
}

function Count_fines_paid_today($connect)
{
    $total_fines_paid_today = 0; // Default value is 0

    $query = "
    SELECT SUM(fines_amount) AS Total 
    FROM lms_fines
    WHERE fines_status = 'Paid' AND DATE(fines_updated_on) = CURDATE()
    ";

    $result = $connect->query($query);

    // Check if the query returned a result and if the total is not null
    if ($result) {
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['Total'] !== null) {
            $total_fines_paid_today = $row['Total'];
        }
    }

    return $total_fines_paid_today;
}


function Count_total_payments_made($connect)
{
    $total_payments = 0;

	$query = "
    SELECT COUNT(user_id) AS Total FROM lms_fines 
    WHERE fines_status = 'Paid'
    ";

    $result = $connect->query($query);

    foreach($result as $row)
    {
        $total_payments = $row["Total"];
    }

    return $total_payments;
}

function Count_total_fines_received($connect)
{
	$total = 0;

	$query = "
	SELECT SUM(fines_amount) AS Total FROM lms_fines 
	WHERE fines_status = 'Paid'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function Count_total_fines_outstanding($connect)
{
	$total = 0;

	$query = "
	SELECT SUM(fines_amount) AS Total FROM lms_fines 
	WHERE fines_status = 'Unpaid'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function Count_total_users_with_fines($connect)
{
    $total_users = 0;

    $query = "
    SELECT COUNT(user_id) AS Total FROM lms_fines 
    WHERE fines_status = 'Unpaid'
    ";

    $result = $connect->query($query);

    foreach($result as $row)
    {
        $total_users = $row["Total"];
    }

    return $total_users;
}

function Count_total_book_number($connect)
{
	$total = 0;

	$query = "
	SELECT COUNT(book_id) AS Total FROM lms_book 
	WHERE book_status = 'Enable'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function Count_total_author_number($connect)
{
	$total = 0;

	$query = "
	SELECT COUNT(author_id) AS Total FROM lms_author 
	WHERE author_status = 'Enable'
	";

	$result  = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function Count_total_category_number($connect)
{
	$total = 0;

	$query = "
	SELECT COUNT(category_id) AS Total FROM lms_category 
	WHERE category_status = 'Enable'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}
	return $total;
}

function Count_total_location_rack_number($connect)
{
	$total = 0;

	$query = "
	SELECT COUNT(location_rack_id) AS Total FROM lms_location_rack 
	WHERE location_rack_status = 'Enable'
	";

	$result = $connect->query($query);

	foreach($result as $row)
	{
		$total = $row["Total"];
	}

	return $total;
}

function currency_array()
{
	$currencies = array(
		array('code'=> 'ALL',
		'countryname'=> 'Albania',
		'name'=> 'Albanian lek',
		'symbol'=> 'L'),

		array('code'=> 'AFN',
			'countryname'=> 'Afghanistan',
			'name'=> 'Afghanistan Afghani',
			'symbol'=> '&#1547;'),

		array('code'=> 'ARS',
			'countryname'=> 'Argentina',
			'name'=> 'Argentine Peso',
			'symbol'=> '&#36;'),

		array('code'=> 'AWG',
			'countryname'=> 'Aruba',
			'name'=> 'Aruban florin',
			'symbol'=> '&#402;'),

		array('code'=> 'AUD',
			'countryname'=> 'Australia',
			'name'=> 'Australian Dollar',
			'symbol'=> '&#65;&#36;'),

		array('code'=> 'AZN',
			'countryname'=> 'Azerbaijan',
			'name'=> 'Azerbaijani Manat',
			'symbol'=> '&#8380;'),

		array('code'=> 'BSD',
			'countryname'=> 'The Bahamas',
			'name'=> 'Bahamas Dollar',
			'symbol'=> '&#66;&#36;'),

		array('code'=> 'BBD',
			'countryname'=> 'Barbados',
			'name'=> 'Barbados Dollar',
			'symbol'=> '&#66;&#100;&#115;&#36;'),

		array('code'=> 'BDT',
			'countryname'=> 'People\'s Republic of Bangladesh',
			'name'=> 'Bangladeshi taka',
			'symbol'=> '&#2547;'),

		array('code'=> 'BYN',
			'countryname'=> 'Belarus',
			'name'=> 'Belarus Ruble',
			'symbol'=> '&#66;&#114;'),

		array('code'=> 'BZD',
			'countryname'=> 'Belize',
			'name'=> 'Belize Dollar',
			'symbol'=> '&#66;&#90;&#36;'),

		array('code'=> 'BMD',
			'countryname'=> 'British Overseas Territory of Bermuda',
			'name'=> 'Bermudian Dollar',
			'symbol'=> '&#66;&#68;&#36;'),

		array('code'=> 'BOP',
			'countryname'=> 'Bolivia',
			'name'=> 'Boliviano',
			'symbol'=> '&#66;&#115;'),

		array('code'=> 'BAM',
			'countryname'=> 'Bosnia and Herzegovina',
			'name'=> 'Bosnia-Herzegovina Convertible Marka',
			'symbol'=> '&#75;&#77;'),

		array('code'=> 'BWP',
			'countryname'=> 'Botswana',
			'name'=> 'Botswana pula',
			'symbol'=> '&#80;'),

		array('code'=> 'BGN',
			'countryname'=> 'Bulgaria',
			'name'=> 'Bulgarian lev',
			'symbol'=> '&#1083;&#1074;'),

		array('code'=> 'BRL',
			'countryname'=> 'Brazil',
			'name'=> 'Brazilian real',
			'symbol'=> '&#82;&#36;'),

		array('code'=> 'BND',
			'countryname'=> 'Sultanate of Brunei',
			'name'=> 'Brunei dollar',
			'symbol'=> '&#66;&#36;'),

		array('code'=> 'KHR',
			'countryname'=> 'Cambodia',
			'name'=> 'Cambodian riel',
			'symbol'=> '&#6107;'),

		array('code'=> 'CAD',
			'countryname'=> 'Canada',
			'name'=> 'Canadian dollar',
			'symbol'=> '&#67;&#36;'),

		array('code'=> 'KYD',
			'countryname'=> 'Cayman Islands',
			'name'=> 'Cayman Islands dollar',
			'symbol'=> '&#36;'),

		array('code'=> 'CLP',
			'countryname'=> 'Chile',
			'name'=> 'Chilean peso',
			'symbol'=> '&#36;'),

		array('code'=> 'CNY',
			'countryname'=> 'China',
			'name'=> 'Chinese Yuan Renminbi',
			'symbol'=> '&#165;'),

		array('code'=> 'COP',
			'countryname'=> 'Colombia',
			'name'=> 'Colombian peso',
			'symbol'=> '&#36;'),

		array('code'=> 'CRC',
			'countryname'=> 'Costa Rica',
			'name'=> 'Costa Rican colón',
			'symbol'=> '&#8353;'),

		array('code'=> 'HRK',
			'countryname'=> 'Croatia',
			'name'=> 'Croatian kuna',
			'symbol'=> '&#107;&#110;'),

		array('code'=> 'CUP',
			'countryname'=> 'Cuba',
			'name'=> 'Cuban peso',
			'symbol'=> '&#8369;'),

		array('code'=> 'CZK',
			'countryname'=> 'Czech Republic',
			'name'=> 'Czech koruna',
			'symbol'=> '&#75;&#269;'),

		array('code'=> 'DKK',
			'countryname'=> 'Denmark, Greenland, and the Faroe Islands',
			'name'=> 'Danish krone',
			'symbol'=> '&#107;&#114;'),

		array('code'=> 'DOP',
			'countryname'=> 'Dominican Republic',
			'name'=> 'Dominican peso',
			'symbol'=> '&#82;&#68;&#36;'),

		array('code'=> 'XCD',
			'countryname'=> 'Antigua and Barbuda, Commonwealth of Dominica, Grenada, Montserrat, St. Kitts and Nevis, Saint Lucia and St. Vincent and the Grenadines',
			'name'=> 'Eastern Caribbean dollar',
			'symbol'=> '&#36;'),

		array('code'=> 'EGP',
			'countryname'=> 'Egypt',
			'name'=> 'Egyptian pound',
			'symbol'=> '&#163;'),

		array('code'=> 'SVC',
			'countryname'=> 'El Salvador',
			'name'=> 'Salvadoran colón',
			'symbol'=> '&#36;'),

		array('code'=> 'EEK',
			'countryname'=> 'Estonia',
			'name'=> 'Estonian kroon',
			'symbol'=> '&#75;&#114;'),

		array('code'=> 'EUR',
			'countryname'=> 'European Union, Italy, Belgium, Bulgaria, Croatia, Cyprus, Czechia, Denmark, Estonia, Finland, France, Germany, Greece, Hungary, Ireland, Latvia, Lithuania, Luxembourg, Malta, Netherlands, Poland, Portugal, Romania, Slovakia, Slovenia, Spain, Sweden',
			'name'=> 'Euro',
			'symbol'=> '&#8364;'),

		array('code'=> 'FKP',
			'countryname'=> 'Falkland Islands',
			'name'=> 'Falkland Islands (Malvinas) Pound',
			'symbol'=> '&#70;&#75;&#163;'),

		array('code'=> 'FJD',
			'countryname'=> 'Fiji',
			'name'=> 'Fijian dollar',
			'symbol'=> '&#70;&#74;&#36;'),

		array('code'=> 'GHC',
			'countryname'=> 'Ghana',
			'name'=> 'Ghanaian cedi',
			'symbol'=> '&#71;&#72;&#162;'),

		array('code'=> 'GIP',
			'countryname'=> 'Gibraltar',
			'name'=> 'Gibraltar pound',
			'symbol'=> '&#163;'),

		array('code'=> 'GTQ',
			'countryname'=> 'Guatemala',
			'name'=> 'Guatemalan quetzal',
			'symbol'=> '&#81;'),

		array('code'=> 'GGP',
			'countryname'=> 'Guernsey',
			'name'=> 'Guernsey pound',
			'symbol'=> '&#81;'),

		array('code'=> 'GYD',
			'countryname'=> 'Guyana',
			'name'=> 'Guyanese dollar',
			'symbol'=> '&#71;&#89;&#36;'),

		array('code'=> 'HNL',
			'countryname'=> 'Honduras',
			'name'=> 'Honduran lempira',
			'symbol'=> '&#76;'),

		array('code'=> 'HKD',
			'countryname'=> 'Hong Kong',
			'name'=> 'Hong Kong dollar',
			'symbol'=> '&#72;&#75;&#36;'),

		array('code'=> 'HUF',
			'countryname'=> 'Hungary',
			'name'=> 'Hungarian forint',
			'symbol'=> '&#70;&#116;'),

		array('code'=> 'ISK',
			'countryname'=> 'Iceland',
			'name'=> 'Icelandic króna',
			'symbol'=> '&#237;&#107;&#114;'),

		array('code'=> 'INR',
			'countryname'=> 'India',
			'name'=> 'Indian rupee',
			'symbol'=> '&#8377;'),

		array('code'=> 'IDR',
			'countryname'=> 'Indonesia',
			'name'=> 'Indonesian rupiah',
			'symbol'=> '&#82;&#112;'),

		array('code'=> 'IRR',
			'countryname'=> 'Iran',
			'name'=> 'Iranian rial',
			'symbol'=> '&#65020;'),

		array('code'=> 'IMP',
			'countryname'=> 'Isle of Man',
			'name'=> 'Manx pound',
			'symbol'=> '&#163;'),

		array('code'=> 'ILS',
			'countryname'=> 'Israel, Palestinian territories of the West Bank and the Gaza Strip',
			'name'=> 'Israeli Shekel',
			'symbol'=> '&#8362;'),

		array('code'=> 'JMD',
			'countryname'=> 'Jamaica',
			'name'=> 'Jamaican dollar',
			'symbol'=> '&#74;&#36;'),

		array('code'=> 'JPY',
			'countryname'=> 'Japan',
			'name'=> 'Japanese yen',
			'symbol'=> '&#165;'),

		array('code'=> 'JEP',
			'countryname'=> 'Jersey',
			'name'=> 'Jersey pound',
			'symbol'=> '&#163;'),

		array('code'=> 'KZT',
			'countryname'=> 'Kazakhstan',
			'name'=> 'Kazakhstani tenge',
			'symbol'=> '&#8376;'),

		array('code'=> 'KPW',
			'countryname'=> 'North Korea',
			'name'=> 'North Korean won',
			'symbol'=> '&#8361;'),

		array('code'=> 'KPW',
			'countryname'=> 'South Korea',
			'name'=> 'South Korean won',
			'symbol'=> '&#8361;'),

		array('code'=> 'KGS',
			'countryname'=> 'Kyrgyz Republic',
			'name'=> 'Kyrgyzstani som',
			'symbol'=> '&#1083;&#1074;'),

		array('code'=> 'LAK',
			'countryname'=> 'Laos',
			'name'=> 'Lao kip',
			'symbol'=> '&#8365;'),

		array('code'=> 'LAK',
			'countryname'=> 'Laos',
			'name'=> 'Latvian lats',
			'symbol'=> '&#8364;'),

		array('code'=> 'LVL',
			'countryname'=> 'Laos',
			'name'=> 'Latvian lats',
			'symbol'=> '&#8364;'),

		array('code'=> 'LBP',
			'countryname'=> 'Lebanon',
			'name'=> 'Lebanese pound',
			'symbol'=> '&#76;&#163;'),

		array('code'=> 'LRD',
			'countryname'=> 'Liberia',
			'name'=> 'Liberian dollar',
			'symbol'=> '&#76;&#68;&#36;'),

		array('code'=> 'LTL',
			'countryname'=> 'Lithuania',
			'name'=> 'Lithuanian litas',
			'symbol'=> '&#8364;'),

		array('code'=> 'MKD',
			'countryname'=> 'North Macedonia',
			'name'=> 'Macedonian denar',
			'symbol'=> '&#1076;&#1077;&#1085;'),

		array('code'=> 'MYR',
			'countryname'=> 'Malaysia',
			'name'=> 'Malaysian ringgit',
			'symbol'=> '&#82;&#77;'),

		array('code'=> 'MUR',
			'countryname'=> 'Mauritius',
			'name'=> 'Mauritian rupee',
			'symbol'=> '&#82;&#115;'),

		array('code'=> 'MXN',
			'countryname'=> 'Mexico',
			'name'=> 'Mexican peso',
			'symbol'=> '&#77;&#101;&#120;&#36;'),

		array('code'=> 'MNT',
			'countryname'=> 'Mongolia',
			'name'=> 'Mongolian tögrög',
			'symbol'=> '&#8366;'),

		array('code'=> 'MZN',
			'countryname'=> 'Mozambique',
			'name'=> 'Mozambican metical',
			'symbol'=> '&#77;&#84;'),

		array('code'=> 'NAD',
			'countryname'=> 'Namibia',
			'name'=> 'Namibian dollar',
			'symbol'=> '&#78;&#36;'),

		array('code'=> 'NPR',
			'countryname'=> 'Federal Democratic Republic of Nepal',
			'name'=> 'Nepalese rupee',
			'symbol'=> '&#82;&#115;&#46;'),

		array('code'=> 'ANG',
			'countryname'=> 'Curaçao and Sint Maarten',
			'name'=> 'Netherlands Antillean guilder',
			'symbol'=> '&#402;'),

		array('code'=> 'NZD',
			'countryname'=> 'New Zealand, the Cook Islands, Niue, the Ross Dependency, Tokelau, the Pitcairn Islands',
			'name'=> 'New Zealand dollar',
			'symbol'=> '&#36;'),
		
		array('code'=> 'NIO',
			'countryname'=> 'Nicaragua',
			'name'=> 'Nicaraguan córdoba',
			'symbol'=> '&#67;&#36;'),

		array('code'=> 'NGN',
			'countryname'=> 'Nigeria',
			'name'=> 'Nigerian naira',
			'symbol'=> '&#8358;'),

		array('code'=> 'NOK',
			'countryname'=> 'Norway and its dependent territories',
			'name'=> 'Norwegian krone',
			'symbol'=> '&#107;&#114;'),

		array('code'=> 'OMR',
			'countryname'=> 'Oman',
			'name'=> 'Omani rial',
			'symbol'=> '&#65020;'),

		array('code'=> 'PKR',
			'countryname'=> 'Pakistan',
			'name'=> 'Pakistani rupee',
			'symbol'=> '&#82;&#115;'),

		array('code'=> 'PAB',
			'countryname'=> 'Panama',
			'name'=> 'Panamanian balboa',
			'symbol'=> '&#66;&#47;&#46;'),

		array('code'=> 'PYG',
			'countryname'=> 'Paraguay',
			'name'=> 'Paraguayan Guaraní',
			'symbol'=> '&#8370;'),

		array('code'=> 'PEN',
			'countryname'=> 'Peru',
			'name'=> 'Sol',
			'symbol'=> '&#83;&#47;&#46;'),

		array('code'=> 'PHP',
			'countryname'=> 'Philippines',
			'name'=> 'Philippine peso',
			'symbol'=> '&#8369;'),

		array('code'=> 'PLN',
			'countryname'=> 'Poland',
			'name'=> 'Polish złoty',
			'symbol'=> '&#122;&#322;'),

		array('code'=> 'QAR',
			'countryname'=> 'State of Qatar',
			'name'=> 'Qatari Riyal',
			'symbol'=> '&#65020;'),

		array('code'=> 'RON',
			'countryname'=> 'Romania',
			'name'=> 'Romanian leu (Leu românesc)',
			'symbol'=> '&#76;'),

		array('code'=> 'RUB',
			'countryname'=> 'Russian Federation, Abkhazia and South Ossetia, Donetsk and Luhansk',
			'name'=> 'Russian ruble',
			'symbol'=> '&#8381;'),

		array('code'=> 'SHP',
			'countryname'=> 'Saint Helena, Ascension and Tristan da Cunha',
			'name'=> 'Saint Helena pound',
			'symbol'=> '&#163;'),

		array('code'=> 'SAR',
			'countryname'=> 'Saudi Arabia',
			'name'=> 'Saudi riyal',
			'symbol'=> '&#65020;'),

		array('code'=> 'RSD',
			'countryname'=> 'Serbia',
			'name'=> 'Serbian dinar',
			'symbol'=> '&#100;&#105;&#110;'),

		array('code'=> 'SCR',
			'countryname'=> 'Seychelles',
			'name'=> 'Seychellois rupee',
			'symbol'=> '&#82;&#115;'),

		array('code'=> 'SGD',
			'countryname'=> 'Singapore',
			'name'=> 'Singapore dollar',
			'symbol'=> '&#83;&#36;'),

		array('code'=> 'SBD',
			'countryname'=> 'Solomon Islands',
			'name'=> 'Solomon Islands dollar',
			'symbol'=> '&#83;&#73;&#36;'),

		array('code'=> 'SOS',
			'countryname'=> 'Somalia',
			'name'=> 'Somali shilling',
			'symbol'=> '&#83;&#104;&#46;&#83;&#111;'),

		array('code'=> 'ZAR',
			'countryname'=> 'South Africa',
			'name'=> 'South African rand',
			'symbol'=> '&#82;'),

		array('code'=> 'LKR',
			'countryname'=> 'Sri Lanka',
			'name'=> 'Sri Lankan rupee',
			'symbol'=> '&#82;&#115;'),
	
		array('code'=> 'SEK',
			'countryname'=> 'Sweden',
			'name'=> 'Swedish krona',
			'symbol'=> '&#107;&#114;'),


		array('code'=> 'CHF',
			'countryname'=> 'Switzerland',
			'name'=> 'Swiss franc',
			'symbol'=> '&#67;&#72;&#102;'),

		array('code'=> 'SRD',
			'countryname'=> 'Suriname',
			'name'=> 'Suriname Dollar',
			'symbol'=> '&#83;&#114;&#36;'),

		array('code'=> 'SYP',
			'countryname'=> 'Syria',
			'name'=> 'Syrian pound',
			'symbol'=> '&#163;&#83;'),

		array('code'=> 'TWD',
			'countryname'=> 'Taiwan',
			'name'=> 'New Taiwan dollar',
			'symbol'=> '&#78;&#84;&#36;'),

		array('code'=> 'THB',
			'countryname'=> 'Thailand',
			'name'=> 'Thai baht',
			'symbol'=> '&#3647;'),


		array('code'=> 'TTD',
			'countryname'=> 'Trinidad and Tobago',
			'name'=> 'Trinidad and Tobago dollar',
			'symbol'=> '&#84;&#84;&#36;'),

		array('code'=> 'TRY',
			'countryname'=> 'Turkey, Turkish Republic of Northern Cyprus',
			'name'=> 'Turkey Lira',
			'symbol'=> '&#8378;'),

		array('code'=> 'TVD',
			'countryname'=> 'Tuvalu',
			'name'=> 'Tuvaluan dollar',
			'symbol'=> '&#84;&#86;&#36;'),

		array('code'=> 'UAH',
			'countryname'=> 'Ukraine',
			'name'=> 'Ukrainian hryvnia',
			'symbol'=> '&#8372;'),

		array('code'=> 'GBP',
			'countryname'=> 'United Kingdom, Jersey, Guernsey, the Isle of Man, Gibraltar, South Georgia and the South Sandwich Islands, the British Antarctic Territory, and Tristan da Cunha',
			'name'=> 'Pound sterling',
			'symbol'=> '&#163;'),

		array('code'=> 'UGX',
			'countryname'=> 'Uganda',
			'name'=> 'Ugandan shilling',
			'symbol'=> '&#85;&#83;&#104;'),

		array('code'=> 'USD',
			'countryname'=> 'United States',
			'name'=> 'United States dollar',
			'symbol'=> '&#36;'),

		array('code'=> 'UYU',
			'countryname'=> 'Uruguayan',
			'name'=> 'Peso Uruguayolar',
			'symbol'=> '&#36;&#85;'),

		array('code'=> 'UZS',
			'countryname'=> 'Uzbekistan',
			'name'=> 'Uzbekistani soʻm',
			'symbol'=> '&#1083;&#1074;'),

		array('code'=> 'VEF',
			'countryname'=> 'Venezuela',
			'name'=> 'Venezuelan bolívar',
			'symbol'=> '&#66;&#115;'),

		array('code'=> 'VND',
			'countryname'=> 'Vietnam',
			'name'=> 'Vietnamese dong (Đồng)',
			'symbol'=> '&#8363;'),

		array('code'=> 'VND',
			'countryname'=> 'Yemen',
			'name'=> 'Yemeni rial',
			'symbol'=> '&#65020;'),

		array('code'=> 'ZWD',
			'countryname'=> 'Zimbabwe',
			'name'=> 'Zimbabwean dollar',
			'symbol'=> '&#90;&#36;'),
	);
	
	return $currencies;
}
function Timezone_list()
{
	$timezones = array(
		'America/Adak' => '(GMT-10:00) America/Adak (Hawaii-Aleutian Standard Time)',
		'America/Atka' => '(GMT-10:00) America/Atka (Hawaii-Aleutian Standard Time)',
		'America/Anchorage' => '(GMT-9:00) America/Anchorage (Alaska Standard Time)',
		'America/Juneau' => '(GMT-9:00) America/Juneau (Alaska Standard Time)',
		'America/Nome' => '(GMT-9:00) America/Nome (Alaska Standard Time)',
		'America/Yakutat' => '(GMT-9:00) America/Yakutat (Alaska Standard Time)',
		'America/Dawson' => '(GMT-8:00) America/Dawson (Pacific Standard Time)',
		'America/Ensenada' => '(GMT-8:00) America/Ensenada (Pacific Standard Time)',
		'America/Los_Angeles' => '(GMT-8:00) America/Los_Angeles (Pacific Standard Time)',
		'America/Tijuana' => '(GMT-8:00) America/Tijuana (Pacific Standard Time)',
		'America/Vancouver' => '(GMT-8:00) America/Vancouver (Pacific Standard Time)',
		'America/Whitehorse' => '(GMT-8:00) America/Whitehorse (Pacific Standard Time)',
		'Canada/Pacific' => '(GMT-8:00) Canada/Pacific (Pacific Standard Time)',
		'Canada/Yukon' => '(GMT-8:00) Canada/Yukon (Pacific Standard Time)',
		'Mexico/BajaNorte' => '(GMT-8:00) Mexico/BajaNorte (Pacific Standard Time)',
		'America/Boise' => '(GMT-7:00) America/Boise (Mountain Standard Time)',
		'America/Cambridge_Bay' => '(GMT-7:00) America/Cambridge_Bay (Mountain Standard Time)',
		'America/Chihuahua' => '(GMT-7:00) America/Chihuahua (Mountain Standard Time)',
		'America/Dawson_Creek' => '(GMT-7:00) America/Dawson_Creek (Mountain Standard Time)',
		'America/Denver' => '(GMT-7:00) America/Denver (Mountain Standard Time)',
		'America/Edmonton' => '(GMT-7:00) America/Edmonton (Mountain Standard Time)',
		'America/Hermosillo' => '(GMT-7:00) America/Hermosillo (Mountain Standard Time)',
		'America/Inuvik' => '(GMT-7:00) America/Inuvik (Mountain Standard Time)',
		'America/Mazatlan' => '(GMT-7:00) America/Mazatlan (Mountain Standard Time)',
		'America/Phoenix' => '(GMT-7:00) America/Phoenix (Mountain Standard Time)',
		'America/Shiprock' => '(GMT-7:00) America/Shiprock (Mountain Standard Time)',
		'America/Yellowknife' => '(GMT-7:00) America/Yellowknife (Mountain Standard Time)',
		'Canada/Mountain' => '(GMT-7:00) Canada/Mountain (Mountain Standard Time)',
		'Mexico/BajaSur' => '(GMT-7:00) Mexico/BajaSur (Mountain Standard Time)',
		'America/Belize' => '(GMT-6:00) America/Belize (Central Standard Time)',
		'America/Cancun' => '(GMT-6:00) America/Cancun (Central Standard Time)',
		'America/Chicago' => '(GMT-6:00) America/Chicago (Central Standard Time)',
		'America/Costa_Rica' => '(GMT-6:00) America/Costa_Rica (Central Standard Time)',
		'America/El_Salvador' => '(GMT-6:00) America/El_Salvador (Central Standard Time)',
		'America/Guatemala' => '(GMT-6:00) America/Guatemala (Central Standard Time)',
		'America/Knox_IN' => '(GMT-6:00) America/Knox_IN (Central Standard Time)',
		'America/Managua' => '(GMT-6:00) America/Managua (Central Standard Time)',
		'America/Menominee' => '(GMT-6:00) America/Menominee (Central Standard Time)',
		'America/Merida' => '(GMT-6:00) America/Merida (Central Standard Time)',
		'America/Mexico_City' => '(GMT-6:00) America/Mexico_City (Central Standard Time)',
		'America/Monterrey' => '(GMT-6:00) America/Monterrey (Central Standard Time)',
		'America/Rainy_River' => '(GMT-6:00) America/Rainy_River (Central Standard Time)',
		'America/Rankin_Inlet' => '(GMT-6:00) America/Rankin_Inlet (Central Standard Time)',
		'America/Regina' => '(GMT-6:00) America/Regina (Central Standard Time)',
		'America/Swift_Current' => '(GMT-6:00) America/Swift_Current (Central Standard Time)',
		'America/Tegucigalpa' => '(GMT-6:00) America/Tegucigalpa (Central Standard Time)',
		'America/Winnipeg' => '(GMT-6:00) America/Winnipeg (Central Standard Time)',
		'Canada/Central' => '(GMT-6:00) Canada/Central (Central Standard Time)',
		'Canada/East-Saskatchewan' => '(GMT-6:00) Canada/East-Saskatchewan (Central Standard Time)',
		'Canada/Saskatchewan' => '(GMT-6:00) Canada/Saskatchewan (Central Standard Time)',
		'Chile/EasterIsland' => '(GMT-6:00) Chile/EasterIsland (Easter Is. Time)',
		'Mexico/General' => '(GMT-6:00) Mexico/General (Central Standard Time)',
		'America/Atikokan' => '(GMT-5:00) America/Atikokan (Eastern Standard Time)',
		'America/Bogota' => '(GMT-5:00) America/Bogota (Colombia Time)',
		'America/Cayman' => '(GMT-5:00) America/Cayman (Eastern Standard Time)',
		'America/Coral_Harbour' => '(GMT-5:00) America/Coral_Harbour (Eastern Standard Time)',
		'America/Detroit' => '(GMT-5:00) America/Detroit (Eastern Standard Time)',
		'America/Fort_Wayne' => '(GMT-5:00) America/Fort_Wayne (Eastern Standard Time)',
		'America/Grand_Turk' => '(GMT-5:00) America/Grand_Turk (Eastern Standard Time)',
		'America/Guayaquil' => '(GMT-5:00) America/Guayaquil (Ecuador Time)',
		'America/Havana' => '(GMT-5:00) America/Havana (Cuba Standard Time)',
		'America/Indianapolis' => '(GMT-5:00) America/Indianapolis (Eastern Standard Time)',
		'America/Iqaluit' => '(GMT-5:00) America/Iqaluit (Eastern Standard Time)',
		'America/Jamaica' => '(GMT-5:00) America/Jamaica (Eastern Standard Time)',
		'America/Lima' => '(GMT-5:00) America/Lima (Peru Time)',
		'America/Louisville' => '(GMT-5:00) America/Louisville (Eastern Standard Time)',
		'America/Montreal' => '(GMT-5:00) America/Montreal (Eastern Standard Time)',
		'America/Nassau' => '(GMT-5:00) America/Nassau (Eastern Standard Time)',
		'America/New_York' => '(GMT-5:00) America/New_York (Eastern Standard Time)',
		'America/Nipigon' => '(GMT-5:00) America/Nipigon (Eastern Standard Time)',
		'America/Panama' => '(GMT-5:00) America/Panama (Eastern Standard Time)',
		'America/Pangnirtung' => '(GMT-5:00) America/Pangnirtung (Eastern Standard Time)',
		'America/Port-au-Prince' => '(GMT-5:00) America/Port-au-Prince (Eastern Standard Time)',
		'America/Resolute' => '(GMT-5:00) America/Resolute (Eastern Standard Time)',
		'America/Thunder_Bay' => '(GMT-5:00) America/Thunder_Bay (Eastern Standard Time)',
		'America/Toronto' => '(GMT-5:00) America/Toronto (Eastern Standard Time)',
		'Canada/Eastern' => '(GMT-5:00) Canada/Eastern (Eastern Standard Time)',
		'America/Caracas' => '(GMT-4:-30) America/Caracas (Venezuela Time)',
		'America/Anguilla' => '(GMT-4:00) America/Anguilla (Atlantic Standard Time)',
		'America/Antigua' => '(GMT-4:00) America/Antigua (Atlantic Standard Time)',
		'America/Aruba' => '(GMT-4:00) America/Aruba (Atlantic Standard Time)',
		'America/Asuncion' => '(GMT-4:00) America/Asuncion (Paraguay Time)',
		'America/Barbados' => '(GMT-4:00) America/Barbados (Atlantic Standard Time)',
		'America/Blanc-Sablon' => '(GMT-4:00) America/Blanc-Sablon (Atlantic Standard Time)',
		'America/Boa_Vista' => '(GMT-4:00) America/Boa_Vista (Amazon Time)',
		'America/Campo_Grande' => '(GMT-4:00) America/Campo_Grande (Amazon Time)',
		'America/Cuiaba' => '(GMT-4:00) America/Cuiaba (Amazon Time)',
		'America/Curacao' => '(GMT-4:00) America/Curacao (Atlantic Standard Time)',
		'America/Dominica' => '(GMT-4:00) America/Dominica (Atlantic Standard Time)',
		'America/Eirunepe' => '(GMT-4:00) America/Eirunepe (Amazon Time)',
		'America/Glace_Bay' => '(GMT-4:00) America/Glace_Bay (Atlantic Standard Time)',
		'America/Goose_Bay' => '(GMT-4:00) America/Goose_Bay (Atlantic Standard Time)',
		'America/Grenada' => '(GMT-4:00) America/Grenada (Atlantic Standard Time)',
		'America/Guadeloupe' => '(GMT-4:00) America/Guadeloupe (Atlantic Standard Time)',
		'America/Guyana' => '(GMT-4:00) America/Guyana (Guyana Time)',
		'America/Halifax' => '(GMT-4:00) America/Halifax (Atlantic Standard Time)',
		'America/La_Paz' => '(GMT-4:00) America/La_Paz (Bolivia Time)',
		'America/Manaus' => '(GMT-4:00) America/Manaus (Amazon Time)',
		'America/Marigot' => '(GMT-4:00) America/Marigot (Atlantic Standard Time)',
		'America/Martinique' => '(GMT-4:00) America/Martinique (Atlantic Standard Time)',
		'America/Moncton' => '(GMT-4:00) America/Moncton (Atlantic Standard Time)',
		'America/Montserrat' => '(GMT-4:00) America/Montserrat (Atlantic Standard Time)',
		'America/Port_of_Spain' => '(GMT-4:00) America/Port_of_Spain (Atlantic Standard Time)',
		'America/Porto_Acre' => '(GMT-4:00) America/Porto_Acre (Amazon Time)',
		'America/Porto_Velho' => '(GMT-4:00) America/Porto_Velho (Amazon Time)',
		'America/Puerto_Rico' => '(GMT-4:00) America/Puerto_Rico (Atlantic Standard Time)',
		'America/Rio_Branco' => '(GMT-4:00) America/Rio_Branco (Amazon Time)',
		'America/Santiago' => '(GMT-4:00) America/Santiago (Chile Time)',
		'America/Santo_Domingo' => '(GMT-4:00) America/Santo_Domingo (Atlantic Standard Time)',
		'America/St_Barthelemy' => '(GMT-4:00) America/St_Barthelemy (Atlantic Standard Time)',
		'America/St_Kitts' => '(GMT-4:00) America/St_Kitts (Atlantic Standard Time)',
		'America/St_Lucia' => '(GMT-4:00) America/St_Lucia (Atlantic Standard Time)',
		'America/St_Thomas' => '(GMT-4:00) America/St_Thomas (Atlantic Standard Time)',
		'America/St_Vincent' => '(GMT-4:00) America/St_Vincent (Atlantic Standard Time)',
		'America/Thule' => '(GMT-4:00) America/Thule (Atlantic Standard Time)',
		'America/Tortola' => '(GMT-4:00) America/Tortola (Atlantic Standard Time)',
		'America/Virgin' => '(GMT-4:00) America/Virgin (Atlantic Standard Time)',
		'Antarctica/Palmer' => '(GMT-4:00) Antarctica/Palmer (Chile Time)',
		'Atlantic/Bermuda' => '(GMT-4:00) Atlantic/Bermuda (Atlantic Standard Time)',
		'Atlantic/Stanley' => '(GMT-4:00) Atlantic/Stanley (Falkland Is. Time)',
		'Brazil/Acre' => '(GMT-4:00) Brazil/Acre (Amazon Time)',
		'Brazil/West' => '(GMT-4:00) Brazil/West (Amazon Time)',
		'Canada/Atlantic' => '(GMT-4:00) Canada/Atlantic (Atlantic Standard Time)',
		'Chile/Continental' => '(GMT-4:00) Chile/Continental (Chile Time)',
		'America/St_Johns' => '(GMT-3:-30) America/St_Johns (Newfoundland Standard Time)',
		'Canada/Newfoundland' => '(GMT-3:-30) Canada/Newfoundland (Newfoundland Standard Time)',
		'America/Araguaina' => '(GMT-3:00) America/Araguaina (Brasilia Time)',
		'America/Bahia' => '(GMT-3:00) America/Bahia (Brasilia Time)',
		'America/Belem' => '(GMT-3:00) America/Belem (Brasilia Time)',
		'America/Buenos_Aires' => '(GMT-3:00) America/Buenos_Aires (Argentine Time)',
		'America/Catamarca' => '(GMT-3:00) America/Catamarca (Argentine Time)',
		'America/Cayenne' => '(GMT-3:00) America/Cayenne (French Guiana Time)',
		'America/Cordoba' => '(GMT-3:00) America/Cordoba (Argentine Time)',
		'America/Fortaleza' => '(GMT-3:00) America/Fortaleza (Brasilia Time)',
		'America/Godthab' => '(GMT-3:00) America/Godthab (Western Greenland Time)',
		'America/Jujuy' => '(GMT-3:00) America/Jujuy (Argentine Time)',
		'America/Maceio' => '(GMT-3:00) America/Maceio (Brasilia Time)',
		'America/Mendoza' => '(GMT-3:00) America/Mendoza (Argentine Time)',
		'America/Miquelon' => '(GMT-3:00) America/Miquelon (Pierre & Miquelon Standard Time)',
		'America/Montevideo' => '(GMT-3:00) America/Montevideo (Uruguay Time)',
		'America/Paramaribo' => '(GMT-3:00) America/Paramaribo (Suriname Time)',
		'America/Recife' => '(GMT-3:00) America/Recife (Brasilia Time)',
		'America/Rosario' => '(GMT-3:00) America/Rosario (Argentine Time)',
		'America/Santarem' => '(GMT-3:00) America/Santarem (Brasilia Time)',
		'America/Sao_Paulo' => '(GMT-3:00) America/Sao_Paulo (Brasilia Time)',
		'Antarctica/Rothera' => '(GMT-3:00) Antarctica/Rothera (Rothera Time)',
		'Brazil/East' => '(GMT-3:00) Brazil/East (Brasilia Time)',
		'America/Noronha' => '(GMT-2:00) America/Noronha (Fernando de Noronha Time)',
		'Atlantic/South_Georgia' => '(GMT-2:00) Atlantic/South_Georgia (South Georgia Standard Time)',
		'Brazil/DeNoronha' => '(GMT-2:00) Brazil/DeNoronha (Fernando de Noronha Time)',
		'America/Scoresbysund' => '(GMT-1:00) America/Scoresbysund (Eastern Greenland Time)',
		'Atlantic/Azores' => '(GMT-1:00) Atlantic/Azores (Azores Time)',
		'Atlantic/Cape_Verde' => '(GMT-1:00) Atlantic/Cape_Verde (Cape Verde Time)',
		'Africa/Abidjan' => '(GMT+0:00) Africa/Abidjan (Greenwich Mean Time)',
		'Africa/Accra' => '(GMT+0:00) Africa/Accra (Ghana Mean Time)',
		'Africa/Bamako' => '(GMT+0:00) Africa/Bamako (Greenwich Mean Time)',
		'Africa/Banjul' => '(GMT+0:00) Africa/Banjul (Greenwich Mean Time)',
		'Africa/Bissau' => '(GMT+0:00) Africa/Bissau (Greenwich Mean Time)',
		'Africa/Casablanca' => '(GMT+0:00) Africa/Casablanca (Western European Time)',
		'Africa/Conakry' => '(GMT+0:00) Africa/Conakry (Greenwich Mean Time)',
		'Africa/Dakar' => '(GMT+0:00) Africa/Dakar (Greenwich Mean Time)',
		'Africa/El_Aaiun' => '(GMT+0:00) Africa/El_Aaiun (Western European Time)',
		'Africa/Freetown' => '(GMT+0:00) Africa/Freetown (Greenwich Mean Time)',
		'Africa/Lome' => '(GMT+0:00) Africa/Lome (Greenwich Mean Time)',
		'Africa/Monrovia' => '(GMT+0:00) Africa/Monrovia (Greenwich Mean Time)',
		'Africa/Nouakchott' => '(GMT+0:00) Africa/Nouakchott (Greenwich Mean Time)',
		'Africa/Ouagadougou' => '(GMT+0:00) Africa/Ouagadougou (Greenwich Mean Time)',
		'Africa/Sao_Tome' => '(GMT+0:00) Africa/Sao_Tome (Greenwich Mean Time)',
		'Africa/Timbuktu' => '(GMT+0:00) Africa/Timbuktu (Greenwich Mean Time)',
		'America/Danmarkshavn' => '(GMT+0:00) America/Danmarkshavn (Greenwich Mean Time)',
		'Atlantic/Canary' => '(GMT+0:00) Atlantic/Canary (Western European Time)',
		'Atlantic/Faeroe' => '(GMT+0:00) Atlantic/Faeroe (Western European Time)',
		'Atlantic/Faroe' => '(GMT+0:00) Atlantic/Faroe (Western European Time)',
		'Atlantic/Madeira' => '(GMT+0:00) Atlantic/Madeira (Western European Time)',
		'Atlantic/Reykjavik' => '(GMT+0:00) Atlantic/Reykjavik (Greenwich Mean Time)',
		'Atlantic/St_Helena' => '(GMT+0:00) Atlantic/St_Helena (Greenwich Mean Time)',
		'Europe/Belfast' => '(GMT+0:00) Europe/Belfast (Greenwich Mean Time)',
		'Europe/Dublin' => '(GMT+0:00) Europe/Dublin (Greenwich Mean Time)',
		'Europe/Guernsey' => '(GMT+0:00) Europe/Guernsey (Greenwich Mean Time)',
		'Europe/Isle_of_Man' => '(GMT+0:00) Europe/Isle_of_Man (Greenwich Mean Time)',
		'Europe/Jersey' => '(GMT+0:00) Europe/Jersey (Greenwich Mean Time)',
		'Europe/Lisbon' => '(GMT+0:00) Europe/Lisbon (Western European Time)',
		'Europe/London' => '(GMT+0:00) Europe/London (Greenwich Mean Time)',
		'Africa/Algiers' => '(GMT+1:00) Africa/Algiers (Central European Time)',
		'Africa/Bangui' => '(GMT+1:00) Africa/Bangui (Western African Time)',
		'Africa/Brazzaville' => '(GMT+1:00) Africa/Brazzaville (Western African Time)',
		'Africa/Ceuta' => '(GMT+1:00) Africa/Ceuta (Central European Time)',
		'Africa/Douala' => '(GMT+1:00) Africa/Douala (Western African Time)',
		'Africa/Kinshasa' => '(GMT+1:00) Africa/Kinshasa (Western African Time)',
		'Africa/Lagos' => '(GMT+1:00) Africa/Lagos (Western African Time)',
		'Africa/Libreville' => '(GMT+1:00) Africa/Libreville (Western African Time)',
		'Africa/Luanda' => '(GMT+1:00) Africa/Luanda (Western African Time)',
		'Africa/Malabo' => '(GMT+1:00) Africa/Malabo (Western African Time)',
		'Africa/Ndjamena' => '(GMT+1:00) Africa/Ndjamena (Western African Time)',
		'Africa/Niamey' => '(GMT+1:00) Africa/Niamey (Western African Time)',
		'Africa/Porto-Novo' => '(GMT+1:00) Africa/Porto-Novo (Western African Time)',
		'Africa/Tunis' => '(GMT+1:00) Africa/Tunis (Central European Time)',
		'Africa/Windhoek' => '(GMT+1:00) Africa/Windhoek (Western African Time)',
		'Arctic/Longyearbyen' => '(GMT+1:00) Arctic/Longyearbyen (Central European Time)',
		'Atlantic/Jan_Mayen' => '(GMT+1:00) Atlantic/Jan_Mayen (Central European Time)',
		'Europe/Amsterdam' => '(GMT+1:00) Europe/Amsterdam (Central European Time)',
		'Europe/Andorra' => '(GMT+1:00) Europe/Andorra (Central European Time)',
		'Europe/Belgrade' => '(GMT+1:00) Europe/Belgrade (Central European Time)',
		'Europe/Berlin' => '(GMT+1:00) Europe/Berlin (Central European Time)',
		'Europe/Bratislava' => '(GMT+1:00) Europe/Bratislava (Central European Time)',
		'Europe/Brussels' => '(GMT+1:00) Europe/Brussels (Central European Time)',
		'Europe/Budapest' => '(GMT+1:00) Europe/Budapest (Central European Time)',
		'Europe/Copenhagen' => '(GMT+1:00) Europe/Copenhagen (Central European Time)',
		'Europe/Gibraltar' => '(GMT+1:00) Europe/Gibraltar (Central European Time)',
		'Europe/Ljubljana' => '(GMT+1:00) Europe/Ljubljana (Central European Time)',
		'Europe/Luxembourg' => '(GMT+1:00) Europe/Luxembourg (Central European Time)',
		'Europe/Madrid' => '(GMT+1:00) Europe/Madrid (Central European Time)',
		'Europe/Malta' => '(GMT+1:00) Europe/Malta (Central European Time)',
		'Europe/Monaco' => '(GMT+1:00) Europe/Monaco (Central European Time)',
		'Europe/Oslo' => '(GMT+1:00) Europe/Oslo (Central European Time)',
		'Europe/Paris' => '(GMT+1:00) Europe/Paris (Central European Time)',
		'Europe/Podgorica' => '(GMT+1:00) Europe/Podgorica (Central European Time)',
		'Europe/Prague' => '(GMT+1:00) Europe/Prague (Central European Time)',
		'Europe/Rome' => '(GMT+1:00) Europe/Rome (Central European Time)',
		'Europe/San_Marino' => '(GMT+1:00) Europe/San_Marino (Central European Time)',
		'Europe/Sarajevo' => '(GMT+1:00) Europe/Sarajevo (Central European Time)',
		'Europe/Skopje' => '(GMT+1:00) Europe/Skopje (Central European Time)',
		'Europe/Stockholm' => '(GMT+1:00) Europe/Stockholm (Central European Time)',
		'Europe/Tirane' => '(GMT+1:00) Europe/Tirane (Central European Time)',
		'Europe/Vaduz' => '(GMT+1:00) Europe/Vaduz (Central European Time)',
		'Europe/Vatican' => '(GMT+1:00) Europe/Vatican (Central European Time)',
		'Europe/Vienna' => '(GMT+1:00) Europe/Vienna (Central European Time)',
		'Europe/Warsaw' => '(GMT+1:00) Europe/Warsaw (Central European Time)',
		'Europe/Zagreb' => '(GMT+1:00) Europe/Zagreb (Central European Time)',
		'Europe/Zurich' => '(GMT+1:00) Europe/Zurich (Central European Time)',
		'Africa/Blantyre' => '(GMT+2:00) Africa/Blantyre (Central African Time)',
		'Africa/Bujumbura' => '(GMT+2:00) Africa/Bujumbura (Central African Time)',
		'Africa/Cairo' => '(GMT+2:00) Africa/Cairo (Eastern European Time)',
		'Africa/Gaborone' => '(GMT+2:00) Africa/Gaborone (Central African Time)',
		'Africa/Harare' => '(GMT+2:00) Africa/Harare (Central African Time)',
		'Africa/Johannesburg' => '(GMT+2:00) Africa/Johannesburg (South Africa Standard Time)',
		'Africa/Kigali' => '(GMT+2:00) Africa/Kigali (Central African Time)',
		'Africa/Lubumbashi' => '(GMT+2:00) Africa/Lubumbashi (Central African Time)',
		'Africa/Lusaka' => '(GMT+2:00) Africa/Lusaka (Central African Time)',
		'Africa/Maputo' => '(GMT+2:00) Africa/Maputo (Central African Time)',
		'Africa/Maseru' => '(GMT+2:00) Africa/Maseru (South Africa Standard Time)',
		'Africa/Mbabane' => '(GMT+2:00) Africa/Mbabane (South Africa Standard Time)',
		'Africa/Tripoli' => '(GMT+2:00) Africa/Tripoli (Eastern European Time)',
		'Asia/Amman' => '(GMT+2:00) Asia/Amman (Eastern European Time)',
		'Asia/Beirut' => '(GMT+2:00) Asia/Beirut (Eastern European Time)',
		'Asia/Damascus' => '(GMT+2:00) Asia/Damascus (Eastern European Time)',
		'Asia/Gaza' => '(GMT+2:00) Asia/Gaza (Eastern European Time)',
		'Asia/Istanbul' => '(GMT+2:00) Asia/Istanbul (Eastern European Time)',
		'Asia/Jerusalem' => '(GMT+2:00) Asia/Jerusalem (Israel Standard Time)',
		'Asia/Nicosia' => '(GMT+2:00) Asia/Nicosia (Eastern European Time)',
		'Asia/Tel_Aviv' => '(GMT+2:00) Asia/Tel_Aviv (Israel Standard Time)',
		'Europe/Athens' => '(GMT+2:00) Europe/Athens (Eastern European Time)',
		'Europe/Bucharest' => '(GMT+2:00) Europe/Bucharest (Eastern European Time)',
		'Europe/Chisinau' => '(GMT+2:00) Europe/Chisinau (Eastern European Time)',
		'Europe/Helsinki' => '(GMT+2:00) Europe/Helsinki (Eastern European Time)',
		'Europe/Istanbul' => '(GMT+2:00) Europe/Istanbul (Eastern European Time)',
		'Europe/Kaliningrad' => '(GMT+2:00) Europe/Kaliningrad (Eastern European Time)',
		'Europe/Kiev' => '(GMT+2:00) Europe/Kiev (Eastern European Time)',
		'Europe/Mariehamn' => '(GMT+2:00) Europe/Mariehamn (Eastern European Time)',
		'Europe/Minsk' => '(GMT+2:00) Europe/Minsk (Eastern European Time)',
		'Europe/Nicosia' => '(GMT+2:00) Europe/Nicosia (Eastern European Time)',
		'Europe/Riga' => '(GMT+2:00) Europe/Riga (Eastern European Time)',
		'Europe/Simferopol' => '(GMT+2:00) Europe/Simferopol (Eastern European Time)',
		'Europe/Sofia' => '(GMT+2:00) Europe/Sofia (Eastern European Time)',
		'Europe/Tallinn' => '(GMT+2:00) Europe/Tallinn (Eastern European Time)',
		'Europe/Tiraspol' => '(GMT+2:00) Europe/Tiraspol (Eastern European Time)',
		'Europe/Uzhgorod' => '(GMT+2:00) Europe/Uzhgorod (Eastern European Time)',
		'Europe/Vilnius' => '(GMT+2:00) Europe/Vilnius (Eastern European Time)',
		'Europe/Zaporozhye' => '(GMT+2:00) Europe/Zaporozhye (Eastern European Time)',
		'Africa/Addis_Ababa' => '(GMT+3:00) Africa/Addis_Ababa (Eastern African Time)',
		'Africa/Asmara' => '(GMT+3:00) Africa/Asmara (Eastern African Time)',
		'Africa/Asmera' => '(GMT+3:00) Africa/Asmera (Eastern African Time)',
		'Africa/Dar_es_Salaam' => '(GMT+3:00) Africa/Dar_es_Salaam (Eastern African Time)',
		'Africa/Djibouti' => '(GMT+3:00) Africa/Djibouti (Eastern African Time)',
		'Africa/Kampala' => '(GMT+3:00) Africa/Kampala (Eastern African Time)',
		'Africa/Khartoum' => '(GMT+3:00) Africa/Khartoum (Eastern African Time)',
		'Africa/Mogadishu' => '(GMT+3:00) Africa/Mogadishu (Eastern African Time)',
		'Africa/Nairobi' => '(GMT+3:00) Africa/Nairobi (Eastern African Time)',
		'Antarctica/Syowa' => '(GMT+3:00) Antarctica/Syowa (Syowa Time)',
		'Asia/Aden' => '(GMT+3:00) Asia/Aden (Arabia Standard Time)',
		'Asia/Baghdad' => '(GMT+3:00) Asia/Baghdad (Arabia Standard Time)',
		'Asia/Bahrain' => '(GMT+3:00) Asia/Bahrain (Arabia Standard Time)',
		'Asia/Kuwait' => '(GMT+3:00) Asia/Kuwait (Arabia Standard Time)',
		'Asia/Qatar' => '(GMT+3:00) Asia/Qatar (Arabia Standard Time)',
		'Europe/Moscow' => '(GMT+3:00) Europe/Moscow (Moscow Standard Time)',
		'Europe/Volgograd' => '(GMT+3:00) Europe/Volgograd (Volgograd Time)',
		'Indian/Antananarivo' => '(GMT+3:00) Indian/Antananarivo (Eastern African Time)',
		'Indian/Comoro' => '(GMT+3:00) Indian/Comoro (Eastern African Time)',
		'Indian/Mayotte' => '(GMT+3:00) Indian/Mayotte (Eastern African Time)',
		'Asia/Tehran' => '(GMT+3:30) Asia/Tehran (Iran Standard Time)',
		'Asia/Baku' => '(GMT+4:00) Asia/Baku (Azerbaijan Time)',
		'Asia/Dubai' => '(GMT+4:00) Asia/Dubai (Gulf Standard Time)',
		'Asia/Muscat' => '(GMT+4:00) Asia/Muscat (Gulf Standard Time)',
		'Asia/Tbilisi' => '(GMT+4:00) Asia/Tbilisi (Georgia Time)',
		'Asia/Yerevan' => '(GMT+4:00) Asia/Yerevan (Armenia Time)',
		'Europe/Samara' => '(GMT+4:00) Europe/Samara (Samara Time)',
		'Indian/Mahe' => '(GMT+4:00) Indian/Mahe (Seychelles Time)',
		'Indian/Mauritius' => '(GMT+4:00) Indian/Mauritius (Mauritius Time)',
		'Indian/Reunion' => '(GMT+4:00) Indian/Reunion (Reunion Time)',
		'Asia/Kabul' => '(GMT+4:30) Asia/Kabul (Afghanistan Time)',
		'Asia/Aqtau' => '(GMT+5:00) Asia/Aqtau (Aqtau Time)',
		'Asia/Aqtobe' => '(GMT+5:00) Asia/Aqtobe (Aqtobe Time)',
		'Asia/Ashgabat' => '(GMT+5:00) Asia/Ashgabat (Turkmenistan Time)',
		'Asia/Ashkhabad' => '(GMT+5:00) Asia/Ashkhabad (Turkmenistan Time)',
		'Asia/Dushanbe' => '(GMT+5:00) Asia/Dushanbe (Tajikistan Time)',
		'Asia/Karachi' => '(GMT+5:00) Asia/Karachi (Pakistan Time)',
		'Asia/Oral' => '(GMT+5:00) Asia/Oral (Oral Time)',
		'Asia/Samarkand' => '(GMT+5:00) Asia/Samarkand (Uzbekistan Time)',
		'Asia/Tashkent' => '(GMT+5:00) Asia/Tashkent (Uzbekistan Time)',
		'Asia/Yekaterinburg' => '(GMT+5:00) Asia/Yekaterinburg (Yekaterinburg Time)',
		'Indian/Kerguelen' => '(GMT+5:00) Indian/Kerguelen (French Southern & Antarctic Lands Time)',
		'Indian/Maldives' => '(GMT+5:00) Indian/Maldives (Maldives Time)',
		'Asia/Calcutta' => '(GMT+5:30) Asia/Calcutta (India Standard Time)',
		'Asia/Colombo' => '(GMT+5:30) Asia/Colombo (India Standard Time)',
		'Asia/Kolkata' => '(GMT+5:30) Asia/Kolkata (India Standard Time)',
		'Asia/Katmandu' => '(GMT+5:45) Asia/Katmandu (Nepal Time)',
		'Antarctica/Mawson' => '(GMT+6:00) Antarctica/Mawson (Mawson Time)',
		'Antarctica/Vostok' => '(GMT+6:00) Antarctica/Vostok (Vostok Time)',
		'Asia/Almaty' => '(GMT+6:00) Asia/Almaty (Alma-Ata Time)',
		'Asia/Bishkek' => '(GMT+6:00) Asia/Bishkek (Kirgizstan Time)',
		'Asia/Dacca' => '(GMT+6:00) Asia/Dacca (Bangladesh Time)',
		'Asia/Dhaka' => '(GMT+6:00) Asia/Dhaka (Bangladesh Time)',
		'Asia/Novosibirsk' => '(GMT+6:00) Asia/Novosibirsk (Novosibirsk Time)',
		'Asia/Omsk' => '(GMT+6:00) Asia/Omsk (Omsk Time)',
		'Asia/Qyzylorda' => '(GMT+6:00) Asia/Qyzylorda (Qyzylorda Time)',
		'Asia/Thimbu' => '(GMT+6:00) Asia/Thimbu (Bhutan Time)',
		'Asia/Thimphu' => '(GMT+6:00) Asia/Thimphu (Bhutan Time)',
		'Indian/Chagos' => '(GMT+6:00) Indian/Chagos (Indian Ocean Territory Time)',
		'Asia/Rangoon' => '(GMT+6:30) Asia/Rangoon (Myanmar Time)',
		'Indian/Cocos' => '(GMT+6:30) Indian/Cocos (Cocos Islands Time)',
		'Antarctica/Davis' => '(GMT+7:00) Antarctica/Davis (Davis Time)',
		'Asia/Bangkok' => '(GMT+7:00) Asia/Bangkok (Indochina Time)',
		'Asia/Ho_Chi_Minh' => '(GMT+7:00) Asia/Ho_Chi_Minh (Indochina Time)',
		'Asia/Hovd' => '(GMT+7:00) Asia/Hovd (Hovd Time)',
		'Asia/Jakarta' => '(GMT+7:00) Asia/Jakarta (West Indonesia Time)',
		'Asia/Krasnoyarsk' => '(GMT+7:00) Asia/Krasnoyarsk (Krasnoyarsk Time)',
		'Asia/Phnom_Penh' => '(GMT+7:00) Asia/Phnom_Penh (Indochina Time)',
		'Asia/Pontianak' => '(GMT+7:00) Asia/Pontianak (West Indonesia Time)',
		'Asia/Saigon' => '(GMT+7:00) Asia/Saigon (Indochina Time)',
		'Asia/Vientiane' => '(GMT+7:00) Asia/Vientiane (Indochina Time)',
		'Indian/Christmas' => '(GMT+7:00) Indian/Christmas (Christmas Island Time)',
		'Antarctica/Casey' => '(GMT+8:00) Antarctica/Casey (Western Standard Time (Australia))',
		'Asia/Brunei' => '(GMT+8:00) Asia/Brunei (Brunei Time)',
		'Asia/Choibalsan' => '(GMT+8:00) Asia/Choibalsan (Choibalsan Time)',
		'Asia/Chongqing' => '(GMT+8:00) Asia/Chongqing (China Standard Time)',
		'Asia/Chungking' => '(GMT+8:00) Asia/Chungking (China Standard Time)',
		'Asia/Harbin' => '(GMT+8:00) Asia/Harbin (China Standard Time)',
		'Asia/Hong_Kong' => '(GMT+8:00) Asia/Hong_Kong (Hong Kong Time)',
		'Asia/Irkutsk' => '(GMT+8:00) Asia/Irkutsk (Irkutsk Time)',
		'Asia/Kashgar' => '(GMT+8:00) Asia/Kashgar (China Standard Time)',
		'Asia/Kuala_Lumpur' => '(GMT+8:00) Asia/Kuala_Lumpur (Malaysia Time)',
		'Asia/Kuching' => '(GMT+8:00) Asia/Kuching (Malaysia Time)',
		'Asia/Macao' => '(GMT+8:00) Asia/Macao (China Standard Time)',
		'Asia/Macau' => '(GMT+8:00) Asia/Macau (China Standard Time)',
		'Asia/Makassar' => '(GMT+8:00) Asia/Makassar (Central Indonesia Time)',
		'Asia/Manila' => '(GMT+8:00) Asia/Manila (Philippines Time)',
		'Asia/Shanghai' => '(GMT+8:00) Asia/Shanghai (China Standard Time)',
		'Asia/Singapore' => '(GMT+8:00) Asia/Singapore (Singapore Time)',
		'Asia/Taipei' => '(GMT+8:00) Asia/Taipei (China Standard Time)',
		'Asia/Ujung_Pandang' => '(GMT+8:00) Asia/Ujung_Pandang (Central Indonesia Time)',
		'Asia/Ulaanbaatar' => '(GMT+8:00) Asia/Ulaanbaatar (Ulaanbaatar Time)',
		'Asia/Ulan_Bator' => '(GMT+8:00) Asia/Ulan_Bator (Ulaanbaatar Time)',
		'Asia/Urumqi' => '(GMT+8:00) Asia/Urumqi (China Standard Time)',
		'Australia/Perth' => '(GMT+8:00) Australia/Perth (Western Standard Time (Australia))',
		'Australia/West' => '(GMT+8:00) Australia/West (Western Standard Time (Australia))',
		'Australia/Eucla' => '(GMT+8:45) Australia/Eucla (Central Western Standard Time (Australia))',
		'Asia/Dili' => '(GMT+9:00) Asia/Dili (Timor-Leste Time)',
		'Asia/Jayapura' => '(GMT+9:00) Asia/Jayapura (East Indonesia Time)',
		'Asia/Pyongyang' => '(GMT+9:00) Asia/Pyongyang (Korea Standard Time)',
		'Asia/Seoul' => '(GMT+9:00) Asia/Seoul (Korea Standard Time)',
		'Asia/Tokyo' => '(GMT+9:00) Asia/Tokyo (Japan Standard Time)',
		'Asia/Yakutsk' => '(GMT+9:00) Asia/Yakutsk (Yakutsk Time)',
		'Australia/Adelaide' => '(GMT+9:30) Australia/Adelaide (Central Standard Time (South Australia))',
		'Australia/Broken_Hill' => '(GMT+9:30) Australia/Broken_Hill (Central Standard Time (South Australia/New South Wales))',
		'Australia/Darwin' => '(GMT+9:30) Australia/Darwin (Central Standard Time (Northern Territory))',
		'Australia/North' => '(GMT+9:30) Australia/North (Central Standard Time (Northern Territory))',
		'Australia/South' => '(GMT+9:30) Australia/South (Central Standard Time (South Australia))',
		'Australia/Yancowinna' => '(GMT+9:30) Australia/Yancowinna (Central Standard Time (South Australia/New South Wales))',
		'Antarctica/DumontDUrville' => '(GMT+10:00) Antarctica/DumontDUrville (Dumont-d\'Urville Time)',
		'Asia/Sakhalin' => '(GMT+10:00) Asia/Sakhalin (Sakhalin Time)',
		'Asia/Vladivostok' => '(GMT+10:00) Asia/Vladivostok (Vladivostok Time)',
		'Australia/ACT' => '(GMT+10:00) Australia/ACT (Eastern Standard Time (New South Wales))',
		'Australia/Brisbane' => '(GMT+10:00) Australia/Brisbane (Eastern Standard Time (Queensland))',
		'Australia/Canberra' => '(GMT+10:00) Australia/Canberra (Eastern Standard Time (New South Wales))',
		'Australia/Currie' => '(GMT+10:00) Australia/Currie (Eastern Standard Time (New South Wales))',
		'Australia/Hobart' => '(GMT+10:00) Australia/Hobart (Eastern Standard Time (Tasmania))',
		'Australia/Lindeman' => '(GMT+10:00) Australia/Lindeman (Eastern Standard Time (Queensland))',
		'Australia/Melbourne' => '(GMT+10:00) Australia/Melbourne (Eastern Standard Time (Victoria))',
		'Australia/NSW' => '(GMT+10:00) Australia/NSW (Eastern Standard Time (New South Wales))',
		'Australia/Queensland' => '(GMT+10:00) Australia/Queensland (Eastern Standard Time (Queensland))',
		'Australia/Sydney' => '(GMT+10:00) Australia/Sydney (Eastern Standard Time (New South Wales))',
		'Australia/Tasmania' => '(GMT+10:00) Australia/Tasmania (Eastern Standard Time (Tasmania))',
		'Australia/Victoria' => '(GMT+10:00) Australia/Victoria (Eastern Standard Time (Victoria))',
		'Australia/LHI' => '(GMT+10:30) Australia/LHI (Lord Howe Standard Time)',
		'Australia/Lord_Howe' => '(GMT+10:30) Australia/Lord_Howe (Lord Howe Standard Time)',
		'Asia/Magadan' => '(GMT+11:00) Asia/Magadan (Magadan Time)',
		'Antarctica/McMurdo' => '(GMT+12:00) Antarctica/McMurdo (New Zealand Standard Time)',
		'Antarctica/South_Pole' => '(GMT+12:00) Antarctica/South_Pole (New Zealand Standard Time)',
		'Asia/Anadyr' => '(GMT+12:00) Asia/Anadyr (Anadyr Time)',
		'Asia/Kamchatka' => '(GMT+12:00) Asia/Kamchatka (Petropavlovsk-Kamchatski Time)'
	);

	$html = '<option value="">Select Timezone</option>';
	foreach($timezones as $keys => $values)
	{
		$html .= '<option value="'.$keys.'">'.$values.'</option>';
	}
	
	return $html;
}

function getBookImagePath($book) {
    $book_img = $book['book_img'];
    
    // If there's an image specified in the database, use it without checking file_exists
    if (!empty($book_img)) {
        return '../upload/' . $book_img;
    }
    
    // Otherwise use the default
    return '../asset/img/book_placeholder.png';
}
function getAuthorImagePath($author) {
	$author_profile = $author['author_profile'];
    // If empty, use default
    if (!empty($author_profile)) {
        return '../upload/' . $author_profile;
    }
    
    // If image was provided but doesn't exist, fallback to default
    return '../asset/img/author.jpg';
}

function getBookStatusStats($connect) {
    return $connect->query("
        SELECT issue_book_status as status, COUNT(*) as count 
        FROM lms_issue_book 
        GROUP BY issue_book_status
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getOverdueBooks($connect) {
    return $connect->query("
        SELECT COUNT(*) as count 
        FROM lms_issue_book 
        WHERE issue_book_status = 'Overdue'
    ")->fetch(PDO::FETCH_ASSOC);
}

function getMonthlyStats($connect) {
    return $connect->query("
        SELECT 
            DATE_FORMAT(issue_date, '%b %Y') as month,
            COUNT(CASE WHEN issue_book_status = 'Issue' THEN 1 END) as issued,
            COUNT(CASE WHEN issue_book_status = 'Return' THEN 1 END) as returned,
            COUNT(CASE WHEN issue_book_status = 'Not Return' THEN 1 END) as lost
        FROM lms_issue_book
        WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
        ORDER BY issue_date
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getPopularBooks($connect, $limit = 10) {
    return $connect->query("
        SELECT ib.book_id, b.book_name, COUNT(ib.book_id) AS issue_count 
        FROM lms_issue_book ib 
        INNER JOIN lms_book b ON ib.book_id = b.book_id 
        GROUP BY ib.book_id, b.book_name 
        ORDER BY issue_count DESC 
        LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getCategoryStats($connect) {
    return $connect->query("
        SELECT c.category_name, COUNT(b.book_id) as book_count
        FROM lms_category c
        LEFT JOIN lms_book b ON c.category_id = b.category_id
        WHERE c.category_status = 'Enable'
        GROUP BY c.category_id, c.category_name
        ORDER BY book_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getUserRoleStats($connect) {
    return $connect->query("
        SELECT r.role_name, COUNT(u.user_id) as user_count 
        FROM user_roles r 
        LEFT JOIN lms_user u ON r.role_id = u.role_id 
        WHERE u.user_status = 'Enable' 
        GROUP BY r.role_id, r.role_name
        ORDER BY user_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getActiveBorrowers($connect, $limit = 10) {
    return $connect->query("
        SELECT u.user_name, u.user_unique_id, COUNT(i.issue_book_id) as borrow_count 
        FROM lms_user u 
        JOIN lms_issue_book i ON u.user_id = i.user_id 
        GROUP BY u.user_id, u.user_name, u.user_unique_id
        ORDER BY borrow_count DESC 
        LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getRecentTransactions($connect, $limit = 50) {
    return $connect->query("
        SELECT b.book_name, u.user_name, i.issue_date, i.return_date, i.expected_return_date, i.issue_book_status 
        FROM lms_issue_book i 
        JOIN lms_book b ON i.book_id = b.book_id 
        JOIN lms_user u ON i.user_id = u.user_id 
        ORDER BY i.issue_date DESC
        LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getOverdueBooksList($connect) {
    return $connect->query("
        SELECT b.book_name, u.user_name, u.user_email, i.issue_date, i.expected_return_date,
               DATEDIFF(CURDATE(), i.expected_return_date) as days_overdue
        FROM lms_issue_book i 
        JOIN lms_book b ON i.book_id = b.book_id 
        JOIN lms_user u ON i.user_id = u.user_id 
        WHERE i.issue_book_status = 'Issue' 
        AND i.expected_return_date < CURDATE()
        ORDER BY days_overdue DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getAuthorTimeStats($connect) {
    return $connect->query("
        (SELECT 
            'week' as time_period,
            a.author_id,
            a.author_name,
            COUNT(ib.issue_book_id) as borrow_count
        FROM 
            lms_author a
        JOIN 
            lms_book_author ba ON a.author_id = ba.author_id
        JOIN 
            lms_book b ON ba.book_id = b.book_id
        JOIN 
            lms_issue_book ib ON b.book_id = ib.book_id
        WHERE 
            a.author_status = 'Enable' AND
            ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
        GROUP BY 
            a.author_id, a.author_name
        ORDER BY 
            borrow_count DESC
        LIMIT 5)

        UNION ALL

        (SELECT 
            'month' as time_period,
            a.author_id,
            a.author_name,
            COUNT(ib.issue_book_id) as borrow_count
        FROM 
            lms_author a
        JOIN 
            lms_book_author ba ON a.author_id = ba.author_id
        JOIN 
            lms_book b ON ba.book_id = b.book_id
        JOIN 
            lms_issue_book ib ON b.book_id = ib.book_id
        WHERE 
            a.author_status = 'Enable' AND
            ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        GROUP BY 
            a.author_id, a.author_name
        ORDER BY 
            borrow_count DESC
        LIMIT 5)

        UNION ALL

        (SELECT 
            'year' as time_period,
            a.author_id,
            a.author_name,
            COUNT(ib.issue_book_id) as borrow_count
        FROM 
            lms_author a
        JOIN 
            lms_book_author ba ON a.author_id = ba.author_id
        JOIN 
            lms_book b ON ba.book_id = b.book_id
        JOIN 
            lms_issue_book ib ON b.book_id = ib.book_id
        WHERE 
            a.author_status = 'Enable' AND
            ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
        GROUP BY 
            a.author_id, a.author_name
        ORDER BY 
            borrow_count DESC
        LIMIT 5)
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getTopAuthors($connect, $limit = 15) {
    return $connect->query("
        SELECT 
            a.author_id,
            a.author_name,
            GROUP_CONCAT(DISTINCT b.book_name ORDER BY b.book_name ASC) as unique_books_borrowed,
            COUNT(ib.issue_book_id) as total_borrows,
            SUM(CASE WHEN ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) as week_borrows,
            SUM(CASE WHEN ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as month_borrows,
            SUM(CASE WHEN ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1 ELSE 0 END) as year_borrows
        FROM 
            lms_author a
        JOIN 
            lms_book_author ba ON a.author_id = ba.author_id
        JOIN 
            lms_book b ON ba.book_id = b.book_id
        JOIN 
            lms_issue_book ib ON b.book_id = ib.book_id
        WHERE 
            a.author_status = 'Enable'
        GROUP BY 
            a.author_id, a.author_name
        ORDER BY 
            total_borrows DESC
        LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function getAuthorTopBooks($connect) {
    return $connect->query("
        SELECT * FROM (
            SELECT * FROM (
                SELECT 
                    'week' AS time_period,
                    a.author_id,
                    a.author_name,
                    b.book_name,
                    COUNT(ib.issue_book_id) AS borrow_count
                FROM 
                    lms_author a
                JOIN 
                    lms_book_author ba ON a.author_id = ba.author_id
                JOIN 
                    lms_book b ON ba.book_id = b.book_id
                JOIN 
                    lms_issue_book ib ON b.book_id = ib.book_id
                WHERE 
                    a.author_status = 'Enable'
                    AND ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
                GROUP BY 
                    a.author_id, a.author_name, b.book_id, b.book_name
                ORDER BY 
                    borrow_count DESC
                LIMIT 15
            ) AS weekly

            UNION ALL

            SELECT * FROM (
                SELECT 
                    'month' AS time_period,
                    a.author_id,
                    a.author_name,
                    b.book_name,
                    COUNT(ib.issue_book_id) AS borrow_count
                FROM 
                    lms_author a
                JOIN 
                    lms_book_author ba ON a.author_id = ba.author_id
                JOIN 
                    lms_book b ON ba.book_id = b.book_id
                JOIN 
                    lms_issue_book ib ON b.book_id = ib.book_id
                WHERE 
                    a.author_status = 'Enable'
                    AND ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                GROUP BY 
                    a.author_id, a.author_name, b.book_id, b.book_name
                ORDER BY 
                    borrow_count DESC
                LIMIT 15
            ) AS monthly

            UNION ALL

            SELECT * FROM (
                SELECT 
                    'year' AS time_period,
                    a.author_id,
                    a.author_name,
                    b.book_name,
                    COUNT(ib.issue_book_id) AS borrow_count
                FROM 
                    lms_author a
                JOIN 
                    lms_book_author ba ON a.author_id = ba.author_id
                JOIN 
                    lms_book b ON ba.book_id = b.book_id
                JOIN 
                    lms_issue_book ib ON b.book_id = ib.book_id
                WHERE 
                    a.author_status = 'Enable'
                    AND ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                GROUP BY 
                    a.author_id, a.author_name, b.book_id, b.book_name
                ORDER BY 
                    borrow_count DESC
                LIMIT 15
            ) AS yearly
        ) AS combined_data
        ORDER BY time_period, borrow_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}


function formatAuthorTimeStats($authorTimeStats) {
    $weeklyAuthors = array_filter($authorTimeStats, function($item) { 
        return $item['time_period'] == 'week'; 
    });
    
    $monthlyAuthors = array_filter($authorTimeStats, function($item) { 
        return $item['time_period'] == 'month'; 
    });
    
    $yearlyAuthors = array_filter($authorTimeStats, function($item) { 
        return $item['time_period'] == 'year'; 
    });
    
    return [
        'weekly' => array_values($weeklyAuthors),
        'monthly' => array_values($monthlyAuthors),
        'yearly' => array_values($yearlyAuthors)
    ];
}


function groupAuthorTopBooks($authorTopBooks, $booksPerAuthor = 3) {
    $authorBooksMap = [];
    foreach ($authorTopBooks as $book) {
        if (!isset($authorBooksMap[$book['author_id']])) {
            $authorBooksMap[$book['author_id']] = [];
        }
        if (count($authorBooksMap[$book['author_id']]) < $booksPerAuthor) {
            $authorBooksMap[$book['author_id']][] = $book;
        }
    }
    return $authorBooksMap;
}
// Function to get paginated books 
function getPaginatedBooks($connect, $limit = 10, $offset = 0, $category_id = null) {
    $params = [];
    $query = "SELECT b.*, c.category_name, 
			GROUP_CONCAT(a.author_name SEPARATOR ', ') as authors
			FROM lms_book b
			LEFT JOIN lms_category c ON b.category_id = c.category_id
			LEFT JOIN lms_book_author ba ON b.book_id = ba.book_id
			LEFT JOIN lms_author a ON ba.author_id = a.author_id
			WHERE b.book_status = 'Enable'
			GROUP BY b.book_id
			";
    
    if ($category_id !== null) {
        $query .= " AND b.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }
    
    $query .= " ORDER BY b.book_id DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $statement = $connect->prepare($query);
    
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $statement->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $statement->bindValue($key, $value);
        }
    }
    
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Function to count total books - add this to function.php
function countTotalBooks($connect, $category_id = null) {
    $query = "SELECT COUNT(*) as total FROM lms_book WHERE book_status = 'Enable'";
    $params = [];
    
    if ($category_id !== null) {
        $query .= " AND category_id = :category_id";
        $params[':category_id'] = $category_id;
    }
    
    $statement = $connect->prepare($query);
    
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value);
    }
    
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Function to get books by author - add this to function.php
function getBooksByAuthor($connect, $author_id, $limit = 5) {
    $query = "SELECT b.* 
              FROM lms_book b
              JOIN lms_book_author ba ON b.book_id = ba.book_id
              WHERE ba.author_id = :author_id
              AND b.book_status = 'Enable'
              ORDER BY b.book_id DESC
              LIMIT :limit";
              
    $statement = $connect->prepare($query);
    $statement->bindParam(':author_id', $author_id, PDO::PARAM_INT);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all categories - add this to function.php
function getAllCategories($connect) {
    $query = "SELECT category_id, category_name 
              FROM lms_category 
              WHERE category_status = 'Enable' 
              ORDER BY category_name";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
// Get author of the week using the existing function from function.php
function getTopAuthorsWithBooks($connect, $limit = 5) {
    $query = "SELECT 
        a.author_id,
        a.author_name,
        a.author_profile,
        COUNT(DISTINCT ba.book_id) as book_count 
    FROM 
        lms_author a
    JOIN 
        lms_book_author ba ON a.author_id = ba.author_id
    JOIN 
        lms_book b ON ba.book_id = b.book_id 
    WHERE 
        a.author_status = 'Enable' AND
        b.book_status = 'Enable'
    GROUP BY 
        a.author_id, a.author_name, a.author_profile
    ORDER BY 
        book_count DESC
    LIMIT :limit";
    
    $statement = $connect->prepare($query);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    
    $authors = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // For each author, get their top books
    foreach ($authors as &$author) {
        $author['books'] = getBooksByAuthor($connect, $author['author_id'], 3);
    }
    
    return $authors;
}
// Function to get detailed book information
function getBookDetails($connect, $book_id) {
	$query = "SELECT b.*, c.category_name 
			FROM lms_book b
			LEFT JOIN lms_category c ON b.category_id = c.category_id
			WHERE b.book_id = :book_id AND b.book_status = 'Enable'
			LIMIT 1";
	
	$statement = $connect->prepare($query);
	$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
	$statement->execute();
	
	return $statement->fetch(PDO::FETCH_ASSOC);
}
// Function to get book authors
function getBookAuthors($connect, $book_id) {
	$query = "SELECT a.* 
			FROM lms_author a
			JOIN lms_book_author ba ON a.author_id = ba.author_id
			WHERE ba.book_id = :book_id AND a.author_status = 'Enable'
			ORDER BY a.author_name";
	
	$statement = $connect->prepare($query);
	$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
	$statement->execute();
	
	return $statement->fetchAll(PDO::FETCH_ASSOC);
}
function getBookAvailability($connect, $book_id, $total_copies) {
    $query = "SELECT COUNT(*) as borrowed_copies 
             FROM lms_issue_book 
             WHERE book_id = :book_id 
             AND (issue_book_status = 'Issue' OR issue_book_status = 'Not Return')";
    $statement = $connect->prepare($query);
    $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    
    $borrowed_copies = $result['borrowed_copies'];
    $available_copies = $total_copies - $borrowed_copies;
    $is_available = $available_copies > 0;
    
    return [
        'borrowed_copies' => $borrowed_copies,
        'available_copies' => $available_copies,
        'is_available' => $is_available
    ];
}
function getBookBorrowCount($connect, $book_id) {
    $query = "SELECT COUNT(*) as borrow_count 
             FROM lms_issue_book 
             WHERE book_id = :book_id";
    $statement = $connect->prepare($query);
    $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    return $result['borrow_count'];
}
// Function to get book borrow history
function getBookBorrowHistory($connect, $book_id, $limit = 10) {
	$query = "SELECT ib.*, u.user_name 
			FROM lms_issue_book ib
			JOIN lms_user u ON ib.user_id = u.user_id
			WHERE ib.book_id = :book_id
			ORDER BY ib.issue_date DESC
			LIMIT :limit";
	
	$statement = $connect->prepare($query);
	$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
	$statement->bindParam(':limit', $limit, PDO::PARAM_INT);
	$statement->execute();
	
	return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get similar books by category
function getSimilarBooksByCategory($connect, $category_id, $current_book_id, $limit = 5) {
	$query = "SELECT b.*, c.category_name 
			FROM lms_book b
			LEFT JOIN lms_category c ON b.category_id = c.category_id
			WHERE b.category_id = :category_id 
			AND b.book_id != :current_book_id 
			AND b.book_status = 'Enable'
			ORDER BY RAND()
			LIMIT :limit";
	
	$statement = $connect->prepare($query);
	$statement->bindParam(':category_id', $category_id, PDO::PARAM_INT);
	$statement->bindParam(':current_book_id', $current_book_id, PDO::PARAM_INT);
	$statement->bindParam(':limit', $limit, PDO::PARAM_INT);
	$statement->execute();
	
	return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get books by the same author
function getBooksBySameAuthor($connect, $book_id, $limit = 5) {
	$query = "SELECT DISTINCT b.*, c.category_name 
			FROM lms_book b
			JOIN lms_book_author ba1 ON b.book_id = ba1.book_id
			JOIN lms_book_author ba2 ON ba1.author_id = ba2.author_id
			LEFT JOIN lms_category c ON b.category_id = c.category_id
			WHERE ba2.book_id = :book_id 
			AND b.book_id != :book_id 
			AND b.book_status = 'Enable'
			ORDER BY RAND()
			LIMIT :limit";
	
	$statement = $connect->prepare($query);
	$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
	$statement->bindParam(':limit', $limit, PDO::PARAM_INT);
	$statement->execute();
	
	return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function getBookReviews($connect, $book_id, $limit = 5) {
    // Get reviews with ratings for a specific book
    $query = "SELECT r.*, u.user_name, r.rating
              FROM lms_book_review r
              JOIN lms_user u ON r.user_id = u.user_id
              JOIN lms_book b ON r.book_id = b.book_id
              WHERE r.book_id = :book_id 
              AND b.book_status = 'Enable'
              AND r.status = 'approved'
              ORDER BY r.created_at DESC
              LIMIT :limit";
    
    try {
        $statement = $connect->prepare($query);
        $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If the table doesn't exist or there's an error, return an empty array
        return [];
    }
}

// Get book average rating - modified to check book status
function getBookAverageRating($connect, $book_id) {
    $query = "SELECT AVG(r.rating) as average_rating 
              FROM lms_book_review r
              JOIN lms_book b ON r.book_id = b.book_id
              WHERE r.book_id = :book_id 
              AND r.rating IS NOT NULL
              AND b.book_status = 'Enable'";
    
    try {
        $statement = $connect->prepare($query);
        $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
        $statement->execute();
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result['average_rating'] ? round($result['average_rating'], 1) : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function generateRandomDigits() {
    return str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
}

// Function to get a book by ID
function getBookById($connect, $book_id) {
    $query = "SELECT * FROM lms_book WHERE book_id = :book_id";
    $statement = $connect->prepare($query);
    $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
}

// Get highest rated books - modified to use ISBN and ensure book is enabled
function getHighestRatedBooks($connect, $limit = 5) {
    $query = "SELECT b.book_id, b.book_name, b.book_isbn_number, b.book_author, 
                     AVG(r.rating) as average_rating, COUNT(r.review_id) as review_count
              FROM lms_book b
              JOIN lms_book_review r ON b.book_id = r.book_id
              WHERE r.status = 'approved' 
              AND r.rating IS NOT NULL
              AND b.book_status = 'Enable'
              GROUP BY b.book_id, b.book_name, b.book_isbn_number, b.book_author
              HAVING review_count >= 3
              ORDER BY average_rating DESC
              LIMIT :limit";
              
    $statement = $connect->prepare($query);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    
    $books = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the ratings
    foreach ($books as &$book) {
        $book['average_rating'] = number_format($book['average_rating'], 1);
    }
    
    return $books;
}

// Get lowest rated books - modified to use ISBN and ensure book is enabled
function getLowestRatedBooks($connect, $limit = 5) {
    $query = "SELECT b.book_id, b.book_name, b.book_isbn_number, b.book_author, 
                     AVG(r.rating) as average_rating, COUNT(r.review_id) as review_count
              FROM lms_book b
              JOIN lms_book_review r ON b.book_id = r.book_id
              WHERE r.status = 'approved' 
              AND r.rating IS NOT NULL
              AND b.book_status = 'Enable'
              GROUP BY b.book_id, b.book_name, b.book_isbn_number, b.book_author
              HAVING review_count >= 3
              ORDER BY average_rating ASC
              LIMIT :limit";
              
    $statement = $connect->prepare($query);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    
    $books = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the ratings
    foreach ($books as &$book) {
        $book['average_rating'] = number_format($book['average_rating'], 1);
    }
    
    return $books;
}

// Get most reviewed books - modified to ensure book is enabled
function getMostReviewedBooks($connect, $limit = 5) {
    $query = "SELECT b.book_id, b.book_name, b.book_isbn_number, b.book_author, 
                     COUNT(r.review_id) as review_count
              FROM lms_book b
              JOIN lms_book_review r ON b.book_id = r.book_id
              WHERE r.status = 'approved'
              AND b.book_status = 'Enable'
              GROUP BY b.book_id, b.book_name, b.book_isbn_number, b.book_author
              ORDER BY review_count DESC
              LIMIT :limit";
              
    $statement = $connect->prepare($query);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Get most active reviewers
function getMostActiveReviewers($connect, $limit = 5) {
    $query = "SELECT u.user_id, u.user_name, COUNT(r.review_id) as review_count
              FROM lms_user u
              JOIN lms_book_review r ON u.user_id = r.user_id
              WHERE r.status = 'approved'
              GROUP BY u.user_id, u.user_name
              ORDER BY review_count DESC
              LIMIT :limit";
              
    $statement = $connect->prepare($query);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
// Add a new function to search books by ISBN
function getBookByISBN($connect, $isbn) {
    $query = "SELECT * FROM lms_book WHERE book_isbn = :isbn AND book_status = 'Enable'";
    $statement = $connect->prepare($query);
    $statement->bindParam(':isbn', $isbn, PDO::PARAM_STR);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
}

// Get review settings
function getReviewSettings($connect) {
    try {
        $query = "SELECT * FROM lms_review_settings LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute();
        
        if ($statement->rowCount() > 0) {
            return $statement->fetch(PDO::FETCH_ASSOC);
        } else {
            // Default settings if none found
            return [
                'moderate_reviews' => true,
                'allow_guest_reviews' => false,
                'reviews_per_page' => 10
            ];
        }
    } catch (PDOException $e) {
        // Create table if it doesn't exist
        $query = "CREATE TABLE IF NOT EXISTS lms_review_settings (
                  setting_id INT AUTO_INCREMENT PRIMARY KEY,
                  moderate_reviews BOOLEAN NOT NULL DEFAULT TRUE,
                  allow_guest_reviews BOOLEAN NOT NULL DEFAULT FALSE,
                  reviews_per_page INT NOT NULL DEFAULT 10
                 )";
        $connect->exec($query);
        
        // Insert default settings
        $query = "INSERT INTO lms_review_settings 
                  (moderate_reviews, allow_guest_reviews, reviews_per_page) 
                  VALUES (1, 0, 10)";
        $connect->exec($query);
        
        // Return default settings
        return [
            'moderate_reviews' => true,
            'allow_guest_reviews' => false,
            'reviews_per_page' => 10
        ];
    }
}

// Get monthly review trends for the past year
function getMonthlyReviewTrends($connect) {
    $query = "SELECT 
                DATE_FORMAT(r.created_at, '%Y-%m') as month,
                COUNT(*) as review_count,
                AVG(r.rating) as average_rating
              FROM lms_book_review r
              JOIN lms_book b ON r.book_id = b.book_id
              WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              AND b.book_status = 'Enable'
              GROUP BY DATE_FORMAT(r.created_at, '%Y-%m')
              ORDER BY month ASC";
    
    $statement = $connect->prepare($query);
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent reviews
function getRecentReviews($connect, $limit = 6) {
    $query = "SELECT r.*, u.user_name, b.book_name, b.book_img, b.book_isbn_number
              FROM lms_book_review r
              JOIN lms_user u ON r.user_id = u.user_id
              JOIN lms_book b ON r.book_id = b.book_id
              WHERE b.book_status = 'Enable'
              ORDER BY r.created_at DESC
              LIMIT :limit";

    $statement = $connect->prepare($query);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function canManageReviews($user_unique_id) {
    if (empty($user_unique_id)) {
        return false;
    }
    
    $prefix = strtoupper(substr($user_unique_id, 0, 1));
    return ($prefix === 'A' || $prefix === 'L'); // Admin or Librarian
}

// Get pending reviews - modified to ensure book is enabled
function getPendingReviews($connect) {
    $query = "SELECT 
                r.*,
                b.book_name,
                u.user_name AS reviewer_name,
                CASE 
                    WHEN SUBSTRING(r.flagged_by, 1, 1) = 'A' THEN 
                        (SELECT SUBSTRING_INDEX(admin_email, '@', 1) FROM lms_admin WHERE admin_unique_id = r.flagged_by)
                    WHEN SUBSTRING(r.flagged_by, 1, 1) = 'L' THEN 
                        (SELECT SUBSTRING_INDEX(librarian_email, '@', 1) FROM lms_librarian WHERE librarian_unique_id = r.flagged_by)
                    ELSE 'Unknown'
                END AS flagged_by_name
              FROM lms_book_review r
              JOIN lms_book b ON r.book_id = b.book_id
              JOIN lms_user u ON r.user_id = u.user_id
              WHERE r.status = 'pending'
              ORDER BY r.flagged_at DESC";
              
    $statement = $connect->prepare($query);
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Get flagged reviews
function getFlaggedReviews($connect) {
    $query = "SELECT 
                r.*,
                b.book_name,
                u.user_name AS reviewer_name,
                CASE 
                    WHEN SUBSTRING(r.flagged_by, 1, 1) = 'A' THEN 
                        (SELECT SUBSTRING_INDEX(admin_email, '@', 1) FROM lms_admin WHERE admin_unique_id = r.flagged_by)
                    WHEN SUBSTRING(r.flagged_by, 1, 1) = 'L' THEN 
                        (SELECT SUBSTRING_INDEX(librarian_email, '@', 1) FROM lms_librarian WHERE librarian_unique_id = r.flagged_by)
                    ELSE 'Unknown'
                END AS flagged_by_name
              FROM lms_book_review r
              JOIN lms_book b ON r.book_id = b.book_id
              JOIN lms_user u ON r.user_id = u.user_id
              WHERE r.status = 'flagged'
              ORDER BY r.flagged_at DESC";
             
    $statement = $connect->prepare($query);
    $statement->execute();
   
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
// Update review settings
function updateReviewSettings($connect, $settings) {
    try {
        // Check if settings exist
        $query = "SELECT COUNT(*) FROM lms_review_settings";
        $statement = $connect->prepare($query);
        $statement->execute();
        $count = $statement->fetchColumn();
        
        if ($count > 0) {
            // Update existing settings
            $query = "UPDATE lms_review_settings SET 
                      moderate_reviews = :moderate_reviews,
                      allow_guest_reviews = :allow_guest_reviews,
                      reviews_per_page = :reviews_per_page";
        } else {
            // Insert new settings
            $query = "INSERT INTO lms_review_settings 
                      (moderate_reviews, allow_guest_reviews, reviews_per_page) 
                      VALUES (:moderate_reviews, :allow_guest_reviews, :reviews_per_page)";
        }
        
        $statement = $connect->prepare($query);
        $statement->bindParam(':moderate_reviews', $settings['moderate_reviews'], PDO::PARAM_BOOL);
        $statement->bindParam(':allow_guest_reviews', $settings['allow_guest_reviews'], PDO::PARAM_BOOL);
        $statement->bindParam(':reviews_per_page', $settings['reviews_per_page'], PDO::PARAM_INT);
        return $statement->execute();
    } catch (PDOException $e) {
        // Create table if it doesn't exist
        $query = "CREATE TABLE lms_review_settings (
                  setting_id INT AUTO_INCREMENT PRIMARY KEY,
                  moderate_reviews BOOLEAN NOT NULL DEFAULT TRUE,
                  allow_guest_reviews BOOLEAN NOT NULL DEFAULT FALSE,
                  reviews_per_page INT NOT NULL DEFAULT 10
                 )";
        $connect->exec($query);
        
        // Try again
        return updateReviewSettings($connect, $settings);
    }
}

// Update review status (approve/reject)
function updateReviewStatus($connect, $review_id, $status) {
    $query = "UPDATE lms_book_review SET status = :status WHERE review_id = :review_id";
    $statement = $connect->prepare($query);
    $statement->bindParam(':status', $status, PDO::PARAM_STR);
    $statement->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    return $statement->execute();
}

// Permanently delete a review from the review table
function permanentlyDeleteReview($connect, $review_id) {
    $query = "DELETE FROM lms_book_review WHERE review_id = :review_id";
    $statement = $connect->prepare($query);
    $statement->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    return $statement->execute();
}

// Flag a review
function flagReview($connect, $review_id, $user_unique_id, $reason) {
    // First check if this review exists
    $check_query = "SELECT review_id, status FROM lms_book_review 
                    WHERE review_id = :review_id";
    
    $check_stmt = $connect->prepare($check_query);
    $check_stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        return false; // Review doesn't exist
    }
    
    $review = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update the review with flagged status
    $query = "UPDATE lms_book_review 
              SET status = 'flagged', 
                  remarks = :reason,
                  flagged_by = :user_unique_id,
                  flagged_at = CURRENT_TIMESTAMP
              WHERE review_id = :review_id";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
    $stmt->bindParam(':user_unique_id', $user_unique_id, PDO::PARAM_STR);
    
    return $stmt->execute();
}

// Approve a review
function approveReview($connect, $review_id) {
    try {
        // First check if this review exists and get its current status
        $check_query = "SELECT status FROM lms_book_review WHERE review_id = :review_id";
        $check_stmt = $connect->prepare($check_query);
        $check_stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            return false; // Review doesn't exist
        }
        
        // Update the review with approved status
        $query = "UPDATE lms_book_review 
                SET status = 'approved',
                    remarks = NULL,
                    flagged_by = NULL,
                    flagged_at = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE review_id = :review_id";
        
        $stmt = $connect->prepare($query);
        $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Database error in approveReview: ' . $e->getMessage());
        return false;
    }
}

// Reject a review
function rejectReview($connect, $review_id, $reason = '') {
    $query = "UPDATE lms_book_review 
              SET status = 'rejected',
                  remarks = :reason,
                  updated_at = CURRENT_TIMESTAMP
              WHERE review_id = :review_id";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
    
    return $stmt->execute();
}

// Permanently delete a review
function deleteReview($connect, $review_id) {
    $query = "DELETE FROM lms_book_review WHERE review_id = :review_id";
    $statement = $connect->prepare($query);
    $statement->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    
    return $statement->execute();
}

// Get all reviews with filtering options
function getReviews($connect, $status = null, $limit = 10) {
    $query = "SELECT 
                r.*,
                b.book_name,
                u.user_name AS reviewer_name,
                CASE 
                    WHEN SUBSTRING(r.flagged_by, 1, 1) = 'A' THEN 
                        (SELECT SUBSTRING_INDEX(admin_email, '@', 1) FROM lms_admin WHERE admin_unique_id = r.flagged_by)
                    WHEN SUBSTRING(r.flagged_by, 1, 1) = 'L' THEN 
                        (SELECT SUBSTRING_INDEX(librarian_email, '@', 1) FROM lms_librarian WHERE librarian_unique_id = r.flagged_by)
                    ELSE NULL
                END AS flagged_by_name
              FROM lms_book_review r
              JOIN lms_book b ON r.book_id = b.book_id
              JOIN lms_user u ON r.user_id = u.user_id";
    
    // Add status filter if provided
    if ($status) {
        $query .= " WHERE r.status = :status";
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    // Add limit if provided
    if ($limit > 0) {
        $query .= " LIMIT :limit";
    }
    
    $statement = $connect->prepare($query);
    
    if ($status) {
        $statement->bindParam(':status', $status, PDO::PARAM_STR);
    }
    
    if ($limit > 0) {
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    }
    
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
