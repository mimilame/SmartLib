<?php
if (session_status() == PHP_SESSION_NONE) {
    // Set the cookie parameters to ensure session persistence
    $lifetime = 86400; // 24 hours in seconds
    $path = '/';
    $domain = '';
    $secure = false;
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    session_start();
    
    error_log("Session started with path: " . session_save_path());
}
	include 'database_connection.php';
	include 'function.php';	
	include 'header.php';
	
	// Fetch library settings
	$query = "SELECT * FROM lms_setting LIMIT 1";
	$statement = $connect->prepare($query);
	$statement->execute();
	$row = $statement->fetch(PDO::FETCH_ASSOC);
	
	$library_name = isset($row['library_name']) ? $row['library_name'] : 'Library Management System';
	
	// Validate if role_id exists before accessing
	$role_id = $_SESSION['role_id'] ?? null;
	$user_type = 'visitor'; // Default user type
	
	if ($role_id) {
		// Fetch user type based on role_id
		$query = "SELECT role_name FROM user_roles WHERE role_id = :role_id LIMIT 1";
		$statement = $connect->prepare($query);
		$statement->bindParam(':role_id', $role_id, PDO::PARAM_INT);
		$statement->execute();
		$result = $statement->fetch(PDO::FETCH_ASSOC);
	
		$user_type = $result['role_name'] ?? 'visitor';
	}
	
	$page_title = $library_name . " - " . ucfirst($user_type); 


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
			// Log the login attempt
			error_log("Login Attempt - Email: " . trim($_POST["email"]));
			
			$result = process_login(
				$connect,
				trim($_POST["email"]),
				trim($_POST['password'])
			);
			
			// Log the login result
			error_log("Login Result: " . ($result['success'] ? 'Success' : 'Failure'));
			error_log("Session Role ID: " . ($_SESSION['role_id'] ?? 'Not Set'));
			
			if($result['success']) {
				// Get the user's role from session and redirect accordingly
				$roleId = $_SESSION['role_id'] ?? null;
				
				// Log the role ID before redirection
				error_log("Redirecting with Role ID: " . $roleId);
				
				redirect_logged_in_user($roleId);
			} else {
				set_flash_message('error', $result['message']);
			}
		}
	}

	// Process user registration form submission (ONLY for Visitors)
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
			// Don't trim passwords - could be intentional spaces
			$formdata['user_password'] = $_POST['user_password'];
		}
		
		if(empty($_POST['user_name'])) {
			$message .= 'User Name is required';
		} else {
			$formdata['user_name'] = trim($_POST['user_name']);
		}
		
		if(empty($_POST['user_address'])) {
			$message .= 'User Address is required';
		} else {
			$formdata['user_address'] = trim($_POST['user_address']);
		}
		
		if(empty($_POST['user_contact_no'])) {
			$message .= 'User Contact Number is required';
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
		// **Automatically Assign Visitor Role (role_id = 5)**
		$formdata['role_id'] = 5;
		
		// If no validation errors, process registration
		if($message == '') {
			$registration_result = process_registration($connect, $formdata);
			
			if($registration_result['success']) {
				$_SESSION['user_unique_id'] = $registration_result['user_unique_id'];
				$_SESSION['role_id'] = 5; // Set session role as visitor
				// Redirect visitors after registration
				redirect_logged_in_user(5);
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

	$query = "
	SELECT * FROM lms_book 
    WHERE book_status = 'Enable' 
    ORDER BY book_id DESC
	";

	$statement = $connect->prepare($query);

	$statement->execute();

?>
<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>
<div class="bg1"></div>
<div class="bg1 bg2"></div>
<div class="bg1 bg3"></div>
<?php
	// Display SweetAlert messages if they exist
	if(!empty($error_message)) {
		echo sweet_alert('error', $error_message);
	}

	if(!empty($success_message)) {
		echo sweet_alert('success', $success_message);
	}
?>

	<div class="modal-overlay" id="authModal">
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
									<img src="asset/img/logo.png" height="40" class="me-2">
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
									<img src="asset/img/logo.png" height="40" class="me-2">
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
	</div>

    <div class="custom-bg">
        <div class="book-slide">
            <div class="book js-flickity" data-flickity-options='{ "wrapAround": true }'>
			<?php
				// Query to fetch all books
				$query = "SELECT * FROM lms_book WHERE book_status = 'Enable' ORDER BY book_id ASC";
				$statement = $connect->prepare($query);
				$statement->execute();
				$books = $statement->fetchAll(PDO::FETCH_ASSOC);
				// Color classes for alternating styles - matching the CSS classes we set
				$colors = ['pink', 'blue', 'purple', 'yellow', 'dark-purp'];
				// Loop through each book
				foreach($books as $index => $book) {
					// Use modulo to cycle through colors (0-based index % number of colors)
					$colorClass = $colors[$index % count($colors)];
					
					// Get book cover image path (use placeholder if not available)
					$book_img = !empty($book['book_img']) ? 'asset/img/' . $book['book_img'] : 'asset/img/book_placeholder.png';
					
					// Start the book cell div - added color class here
					echo '<div class="book-cell ' . $colorClass . '">';
					
					// Book image div
					echo '<div class="book-img">';
					echo '<img src="' . $book_img . '" alt="' . htmlspecialchars($book['book_name']) . '" class="book-photo">';
					echo '</div>';
					
					// Book content div
					echo '<div class="book-content">';
					echo '<div class="book-title">' . htmlspecialchars($book['book_name']) . '</div>';
					echo '<div class="book-author">by ' . htmlspecialchars($book['book_author']) . '</div>';
					
					// Rating div
					echo '<div class="rate">';
					echo '<fieldset class="rating ' . $colorClass . '">';
					
					// Generate rating inputs with unique IDs
					$baseId = 'star' . (($index * 5) + 1);
					for($i = 5; $i >= 1; $i--) {
						$starId = $baseId . $i;
						$labelClass = 'full1';
						echo '<input type="checkbox" id="' . $starId . '" name="rating" value="' . $i . '" />';
						echo '<label class="' . $labelClass . '" for="' . $starId . '"></label>';
					}
					
					echo '</fieldset>';
					echo '<span class="book-voters">' . $book['book_no_of_copy'] . ' copies</span>';
					echo '</div>';
					
					// Book summary and see button
					echo '<div class="book-sum">ISBN: ' . htmlspecialchars($book['book_isbn_number']) . ' / Location: ' . 
						htmlspecialchars($book['book_location_rack']) . '</div>';
					
					$seeClass = 'book-see book-' . $colorClass;
					echo '<div class="' . $seeClass . '" data-book-id="' . $book['book_id'] . '">See The Book</div>';
					
					echo '</div>'; // End book-content
					echo '</div>'; // End book-cell
				}
				
				?>
               
            </div>
        </div>

        <div class="main-wrapper">
            <div class="books-of">
                <div class="week">
                    <div class="author-title">Author of the week</div>
                    <div class="author">
                        <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1400&q=80" alt="" class="author-img">
                        <div class="author-name">Sebastian Jeremy</div>
                    </div>
                    <div class="author">
                        <img src="https://images.unsplash.com/photo-1586297098710-0382a496c814?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1650&q=80" alt="" class="author-img">
                        <div class="author-name">Jonathan Doe</div>
                    </div>
                    <!-- Additional author elements would follow the same pattern -->
                </div>

                <div class="week year">
                    <div class="author-title">Books of the year</div>
                    <div class="year-book">
                        <img src="https://images-na.ssl-images-amazon.com/images/I/A1kNdYXw0GL.jpg" alt="" class="year-book-img">
                        <div class="year-book-content">
                            <div class="year-book-name">Disappearing Earth</div>
                            <div class="year-book-author">by Julia Phillips</div>
                        </div>
                    </div>
                    <!-- Additional year-book elements would follow the same pattern -->
                </div>
            </div>

            <div class="popular-books">
                <div class="main-menu">
                    <div class="genre">Popular by Genre</div>
                    <div class="book-types">
                        <a href="#" class="book-type active"> All Genres</a>
                        <a href="#" class="book-type"> Business</a>
                        <a href="#" class="book-type"> Science</a>
                        <a href="#" class="book-type"> Fiction</a>
                        <a href="#" class="book-type"> Philosophy</a>
                        <a href="#" class="book-type"> Biography</a>
                    </div>
                </div>

                <div class="book-cards">
                    <div class="book-card">
                        <div class="d-flex">
                            <img src="https://imagesvc.meredithcorp.io/v3/mm/image?url=https%3A%2F%2Fstatic.onecms.io%2Fwp-content%2Fuploads%2Fsites%2F6%2F2019%2F07%2Fchances-are-1-2000.jpg&q=85" alt="" class="book-card-img">
                            <div class="card-content">
                                <div class="book-name">Changes Are</div>
                                <div class="book-by">by Richard Russo</div>
                                <div class="rate">
                                    <fieldset class="rating book-rate">
                                        <input type="checkbox" id="star-c1" name="rating" value="5">
                                        <label class="full" for="star-c1"></label>
                                        <!-- Additional rating inputs -->
                                    </fieldset>
                                    <span class="book-voters card-vote">1.987 voters</span>
                                </div>
                                <div class="book-sum card-sum">Readers of all ages and walks of life have drawn inspiration and empowerment from Elizabeth Gilbert's books for years. </div>
                            </div>
                        </div>
                        <div class="likes">
                            <div class="like-profile">
                                <img src="https://randomuser.me/api/portraits/women/63.jpg" alt="" class="like-img">
                            </div>
                            <!-- Additional like profiles -->
                            <div class="like-name"><span>Samantha William</span> and <span>2 other friends</span> like this</div>
                        </div>
                    </div>
                    <!-- Additional book-card elements would follow the same pattern -->
                </div>
            </div>
        </div>

        <div class="mt-5 pt-4" id="front-page-home-sections">    
			<div id="education-soul-call-to-action" class="home-section home-section-call-to-action">
				<div class="container">
					<div class="cta-content">
						<h2 class="section-title">Western Mindanao State University</h2>
						<div class="cta-content-text">
										</div><!-- .cta-content-text -->
					</div><!-- .cta-content -->
					<div class="cta-buttons" style="position: relative; top: 5em;">
						<a href="http://apply.wmsu.edu.ph/admission/" class="custom-button cta-btn">Freshmen Registration</a>
						<a href="http://register.wmsu.edu.ph/encoding/" class="custom-button button-secondary cta-btn">Old Student Registration</a>
					</div><!-- .cta-buttons -->
				</div><!-- .container -->
			</div>
        </div>

        <section class="py-5">
            <div class="container px-4 px-lg-5 mt-2 mb-4">
                <div class="row gx-4 gx-lg-5 row-cols-12 row-cols-md-12 row-cols-xl-12 justify-content-center">
                    <div class="row align-items-stretch g-4 py-5">
                        <div class="col">
							<div class="card card-cover h-100 overflow-hidden text-white bg-dark rounded-5 shadow-lg" style="background-image: url('unsplash-photo-1.jpg');">
								<div class="d-flex align-items-start justify-content-between">
		
										<!-- Left Section: Logo and Title -->
										<div class="d-flex flex-column align-items-center p-4" style="flex: 1;">
											<img src="asset\img\campus_logo.png" alt="School Logo" class="logo" />
											<h1 class="logo-name text-white mt-3">WMSU ESU CURUAN</h1>
											<h2 class="faq-title text-white">Frequently Asked Questions</h2>
										</div>

										<!-- Right Section: Accordion -->
										<div class="accordion-container p-4" style="flex: 2;">
											<div class="accordion" id="libraryFaqAccordion">
											<div class="accordion-item">
												<h2 class="accordion-header" id="faqHeading1">
												<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
													What are the library’s operating hours?
												</button>
												</h2>
												<div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#libraryFaqAccordion">
												<div class="accordion-body">
													The library is open Monday to Friday, 8:00 AM to 6:00 PM, and Saturday, 9:00 AM to 1:00 PM.
												</div>
												</div>
											</div>

											<div class="accordion-item">
												<h2 class="accordion-header" id="faqHeading2">
												<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
													How can I borrow books?
												</button>
												</h2>
												<div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#libraryFaqAccordion">
												<div class="accordion-body">
													Students and faculty can borrow books using their library card. Visit the front desk for assistance.
												</div>
												</div>
											</div>
											<div class="accordion-item">
												<h2 class="accordion-header" id="faqHeading2">
												<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
													What if I return a book late?
												</button>
												</h2>
												<div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#libraryFaqAccordion">
												<div class="accordion-body">
													Late returns incur a fee of PHP 10 per day. Please ensure timely returns to avoid penalties.
												</div>
												</div>
											</div>
											<!-- Add other accordion items here as necessary -->
											</div>
										</div>
									</div>
								</div>
							</div>

                        </div>
                        <!-- Additional column divs -->
                    </div>
                </div>
            </div>
        </section>





<script>
const index = document.querySelector('.index');
const toggleBox = document.querySelector('.toggle-box');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');
const authModal = document.getElementById("authModal");
const regBtn = document.querySelector(".regbtn");
const logBtn = document.querySelector(".logbtn");

// Open modal
regBtn.addEventListener("click", () => {
    authModal.style.display = "flex";
    document.querySelector(".index").classList.add("active");
});

logBtn.addEventListener("click", () => {
    authModal.style.display = "flex";
    document.querySelector(".index").classList.remove("active");
});

// Close modal when clicking outside the form
authModal.addEventListener("click", (e) => {
    if (e.target === authModal) {
        authModal.style.display = "none";
    }
});


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
<script src="./asset/js/background.js"></script>
<script src="./asset/js/background.min.js"></script>

<?php

include 'footer.php';

?>