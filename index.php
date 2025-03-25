<?php
	include 'database_connection.php';
	include 'function.php';

	redirect_logged_in_user();

	// Initialize variables for form processing
	$message = '';
	$success = '';

	// Process login form submission
	if(isset($_POST["login_button"])) {
		// Validate email
		$email_validation = validate_email($_POST["email"] ?? '');
		
		if(!$email_validation['valid']) {
			set_flash_message('error', $email_validation['message']);
		}
		else if(empty($_POST['password'])) {
			set_flash_message('error', 'Password is required');
		}
		else {
			$result = process_login(
				$connect,
				trim($_POST["email"]),
				trim($_POST['password'])
			);
			
			if($result['success']) {
				if($result['user_type'] == 'admin') {
					header('location:admin/index.php');
				} else {
					header('location:issue_book_details.php');
				}
				exit;
			} else {
				set_flash_message('error', $result['message']);
			}
		}
	}

	// Process user registration form submission
	if(isset($_POST["register_button"])) {
		$formdata = array();
		$message = '';
		
		// Validate email
		$email_validation = validate_email($_POST["user_email"] ?? '');
		if(!$email_validation['valid']) {
			$message = $email_validation['message'];
		} else {
			$formdata['user_email'] = trim($_POST['user_email']);
		}
		
		// Validate other required fields
		if(empty($_POST["user_password"])) {
			$message .= 'Password is required';
		} else {
			$formdata['user_password'] = trim($_POST['user_password']);
		}
		
		if(empty($_POST['user_name'])) {
			$message .= 'User Name is required';
		} else {
			$formdata['user_name'] = trim($_POST['user_name']);
		}
		
		if(empty($_POST['user_address'])) {
			$message .= 'User Address Detail is required';
		} else {
			$formdata['user_address'] = trim($_POST['user_address']);
		}
		
		if(empty($_POST['user_contact_no'])) {
			$message .= 'User Contact Number Detail is required';
		} else {
			$formdata['user_contact_no'] = trim($_POST['user_contact_no']);
		}
		
		// Process profile image upload
		$image_result = process_profile_image_upload($_FILES['user_profile']);
		if(!$image_result['success']) {
			$message .= $image_result['message'];
		} else {
			$formdata['user_profile'] = $image_result['file_name'];
		}
		
		// If no validation errors, process registration
		if($message == '') {
			$registration_result = process_registration($connect, $formdata);
			
			if($registration_result['success']) {
				// Send verification email
				$email_result = send_verification_email(
					$formdata['user_email'],
					$formdata['user_name'],
					$registration_result['user_unique_id'],
					$registration_result['verification_code']
				);
				
				if($email_result['success']) {
					set_flash_message('success', $email_result['message']);
				} else {
					set_flash_message('error', $email_result['message']);
				}
			} else {
				set_flash_message('error', $registration_result['message']);
			}
		} else {
			set_flash_message('error', $message);
		}
		
		// Redirect to avoid form resubmission
		header('Location: ' . $_SERVER['PHP_SELF']);
		exit;
	}

	// Get any flash messages at the beginning of the page
	$error_message = get_flash_message('error');
	$success_message = get_flash_message('success');

	include 'head.php';

?>
<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>
<div class="bg1"></div>
<div class="bg1 bg2"></div>
<div class="bg1 bg3"></div>

<main class="landing-page">
	<?php
	// Display SweetAlert messages if they exist
	if(!empty($error_message)) {
		echo sweet_alert('error', $error_message);
	}

	if(!empty($success_message)) {
		echo sweet_alert('success', $success_message);
	}
	?>

    <section class="index">
        <div class="toggle-box">
            <article class="toggle-panel toggle-left">
                <div class="p-3">
					<div class="mx-5">
						<h2 class="h3">Explore Our Collection</h2>
						<p class="mb-5">Browse through hundreds of books, journals, and digital resources in our extensive catalog. From classic literature to the latest research papers, we have resources for every reader.</p>
						<ul class="row gap-4 justify-content-between mb-5 list-unstyled">
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
								<div class="d-inline-flex flex-column align-items-center">
									<i class="fas fa-book-open text-warning fa-3x"></i>
									<span class="mt-2">Over 500 books</span>
								</div>
								</div>
							</li>
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
								<div class="d-inline-flex flex-column align-items-center">
									<i class="fas fa-laptop-code text-warning fa-3x"></i>
									<span class="mt-2">Digital resources</span>
								</div>
								</div>
							</li>
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
								<div class="d-inline-flex flex-column align-items-center">
									<i class="fas fa-newspaper text-warning fa-3x"></i>
									<span class="mt-2">Academic journals</span>
								</div>
								</div>
							</li>
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
								<div class="d-inline-flex flex-column align-items-center">
									<i class="fas fa-flask text-warning fa-3x"></i>
									<span class="mt-2">Research materials</span>
								</div>
								</div>
							</li>
						</ul>
						<div class="h5">
							<p>Don't have an account?
								<span class="register-btn text-light h6 text-decoration-underline">Register</span>
							</p>
						</div>
					</div>
                </div>
            </article>
			<div class="toggle-panel toggle-right">
				<div class="p-3">
					<div class="mx-5">
						<h1 class="h2 mb-5">Register with us to start your library experience!</h1>
						<ul class="row gap-4 justify-content-between mb-5 list-unstyled">
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
									<div class="d-inline-flex flex-column align-items-center">
										<i class="fas fa-search text-warning fa-3x"></i>
										<span class="mt-2">Search Availability</span>
									</div>
								</div>
							</li>
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
									<div class="d-inline-flex flex-column align-items-center">
										<i class="fas fa-tasks text-warning fa-3x"></i>
										<span class="mt-2">Manage Issued Books</span>
									</div>
								</div>
							</li>
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
									<div class="d-inline-flex flex-column align-items-center">
										<i class="fas fa-bell text-warning fa-3x"></i>
										<span class="mt-2">Return Due Reminders</span>
									</div>
								</div>
							</li>
							<li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
								<div class="card-body">
									<div class="d-inline-flex flex-column align-items-center">
										<i class="fas fa-receipt text-warning fa-3x"></i>
										<span class="mt-2">Library Transactions</span>
									</div>
								</div>
							</li>
						</ul>

						<div class="h5">
							<p>Already have an account? 
								<span class="login-btn text-light h6 text-decoration-underline">Login</span>
							</p>
						</div>
					</div>
				</div>
            </div>
        </div>

		
		<div class="form-box login" id="loginForm">
			<header class="w-50 d-inline bg-light z-index-99">
				<div class="mx-5">
					<nav class="navbar navbar-expand-lg navbar-light">
						<div class="container-fluid">
							<a class="navbar-brand" href="index.php">
								<img src="asset/img/logo-removebg.png" height="40" class="me-2">
								<span class="fw-bold">SmartLib</span>
							</a>
						</div>
					</nav>
				</div>
			</header>
            <form action="#" method="POST" class="my-auto p-3">
                <h1>Login</h1>
				<div class="w-75 mx-5 mx-auto">
					<div class="input-box mb-3">
						<input type="text" name="email" placeholder="Email" required>
						<i class='bx bxs-user'></i>
					</div>
					<div class="input-box mb-3">
						<input type="password" name="password" placeholder="Password" required>
						<i class='bx bxs-lock-alt' ></i>
					</div>
				</div>
                <div class="forgot-link">
                    <a href="#">Forgot Password?</a>
                </div>
                <button type="submit" name="login_button" class="btn btn-primary">Sign in</button>
            </form>
		</div>

		<div class="form-box register" id="signupForm">	
			<header class="w-50 d-inline bg-light z-index-99">
				<div class="mx-5">
					<nav class="navbar navbar-expand-lg navbar-light">
						<div class="container-fluid">
							<a class="navbar-brand" href="index.php">
								<img src="asset/img/logo-removebg.png" height="40" class="me-2">
								<span class="fw-bold">SmartLib</span>
							</a>
						</div>
					</nav>
				</div>
			</header>
			<form action="" method="POST" enctype="multipart/form-data" class="my-auto p-3">
				<h1>Register</h1>
				<div class="w-75 mx-5 mx-auto">
					<div class="input-box mb-3">
						<input type="text" class="form-control" name="user_name" id="user_name" placeholder="Full name" required>
					</div>
					<div class="input-box mb-3">
						<input type="email" class="form-control" name="user_email" id="user_email" placeholder="Email" required>
					</div>
					<div class="input-box mb-3">
						<input type="password" class="form-control" name="user_password" id="signup_password" placeholder="Password" required>
					</div>
					<div class="input-box mb-3">
						<input type="text" class="form-control" name="user_contact_no" id="user_contact_no" placeholder="Contact Number" required>
					</div>
					<div class="input-box mb-3">
						<textarea class="form-control" name="user_address" id="user_address" rows="2" placeholder="Address" required></textarea>
					</div>
					<div class="input-box mb-3">
						<input type="file" class="form-control" name="user_profile" id="user_profile" placeholder="Profile Image" required>
						<small class="text-muted">Only .jpg & .png (height greater than width, max 2MB)</small>
					</div>
				</div>
				<button type="submit" name="register_button" class="btn btn-primary">Sign Up</button>
			</form>
		</div>
          
    </section>

    




<script>
const index = document.querySelector('.index');
const toggleBox = document.querySelector('.toggle-box');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');

// Function to handle the transition overlay effect
function handleTransition() {
  toggleBox.classList.add('transitioning');
  
  setTimeout(function() {
    toggleBox.classList.remove('transitioning');
  }, 1800);
}

// Register button click
registerBtn.addEventListener('click', () => {
  handleTransition();
  index.classList.add('active');
});

// Login button click
loginBtn.addEventListener('click', () => {
  handleTransition();
  index.classList.remove('active');
});

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/1.20.3/TimelineLite.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/87/three.min.js"></script>
<script src="./asset/js/background.js"></script>
<script src="./asset/js/background.min.js"></script>

<?php

include 'footer.php';

?>