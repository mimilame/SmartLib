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
	include '../database_connection.php';
	include '../function.php';	
	include '../header.php';
	
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
                        <div class="content-d-flex">
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

</script>
<script src="./asset/js/background.js"></script>
<script src="./asset/js/background.min.js"></script>

<?php

include 'footer.php';

?>