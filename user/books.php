<?php
// books.php in VISITOR
    include '../database_connection.php';
    include '../function.php';
    include '../header.php';
    authenticate_user();
	
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
?>


        <div class="book-slide">
            <div class="book js-flickity" data-flickity-options='{ "wrapAround": true }'>
			<?php
				// Query to fetch all books
				$books = getPaginatedBooks($connect, 10, 0); // Use the function instead of direct query
				$base_url = base_url();
				// Color classes for alternating styles
				$colors = ['pink', 'blue', 'purple', 'yellow', 'dark-purp'];
				
				// Loop through each book
				foreach($books as $index => $book) {
					// Use modulo to cycle through colors
					$colorClass = $colors[$index % count($colors)];
					
					// Use the getBookImagePath function for dynamic image path
					$bookImgPath = getBookImagePath($book);
							
					// Remove the leading "../" from the path for browser display
					$bookImgUrl = str_replace('../', $base_url, $bookImgPath);
					
					// Start the book cell div
					echo '<div class="book-cell ' . $colorClass . '">';
					
					// Book image div
					echo '<div class="book-img">';
					echo '<img src="' . $bookImgUrl . '" alt="' . htmlspecialchars($book['book_name']) . '" class="book-photo">';
					echo '</div>';
					
					// Book content div
					echo '<div class="book-content">';
					echo '<div class="book-title">' . htmlspecialchars($book['book_name']) . '</div>';
					echo '<div class="book-author">by ' . htmlspecialchars($book['book_author']) . '</div>';
					
					// Rating div
					echo '<div class="rate">';
					echo '<fieldset class="rating ' . $colorClass . '">';
					
					// Get the average rating for this book
					$averageRating = getBookAverageRating($connect, $book['book_id']);

					// Generate rating inputs with unique IDs 
					$baseId = 'star' . (($index * 5) + 1); 
					for($i = 5; $i >= 1; $i--) { 
						$starId = $baseId . $i; 
						$labelClass = 'full'; 
						$checked = ($i <= round($averageRating)) ? 'checked="checked"' : '';
						echo '<input type="checkbox" ' . $checked . ' id="' . $starId . '" name="rating" value="' . $i . '" />'; 
						echo '<label class="' . $labelClass . '" for="' . $starId . '"></label>'; 
					} 
					// Get the number of people who rated this book
					$bookReviews = getBookReviews($connect, $book['book_id']);
					$reviewCount = count($bookReviews);
					$votersText = $reviewCount . ' ' . ($reviewCount == 1 ? 'rating' : 'ratings');
					
					echo '</fieldset>';
					echo '<span class="book-voters card-vote">' . $votersText . ' | ' . $book['book_no_of_copy'] . ' copies</span>';
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

        <div class="main-wrapper px-5">
            <div class="books-of">
                <div class="week">
                    <div class="author-title">Author of the week</div>
					<?php 
						// Use the getTopAuthorsWithBooks function instead of direct query
						$authors = getTopAuthorsWithBooks($connect, 5);
						$base_url = base_url();
						
						foreach($authors as $author) {
							// Use the new function to get the profile image path
							$authorImgPath = getAuthorImagePath($author);
							
							// Remove the leading "../" from the path for browser display
							$authorImgUrl = str_replace('../', $base_url, $authorImgPath);
							
							echo '<div class="author">
									<img src="' . $authorImgUrl . '" alt="' . htmlspecialchars($author['author_name']) . '" class="author-img">
									<div class="author-name">' . htmlspecialchars($author['author_name']) . '</div>
								  </div>';
						}
					?>
                </div>

                <div class="week year">
                    <div class="author-title">Books of the year</div>
					<?php
						// Use the getPopularBooks function instead of direct query
						$topBooks = getPopularBooks($connect, 5);
						$base_url = base_url();

						foreach($topBooks as $book) {
							// You'll need to fetch the book details
							$bookQuery = "SELECT book_img, book_author FROM lms_book WHERE book_id = :book_id";
							$bookStmt = $connect->prepare($bookQuery);
							$bookStmt->bindParam(':book_id', $book['book_id'], PDO::PARAM_INT);
							$bookStmt->execute();
							$bookDetails = $bookStmt->fetch(PDO::FETCH_ASSOC);
							
							// Merge book details with the book array
							$book = array_merge($book, $bookDetails);
							
							// Use the getBookImagePath function for dynamic image path
							$bookImgPath = getBookImagePath($book);
							
							// Remove the leading "../" from the path for browser display
							$bookImgUrl = str_replace('../', $base_url, $bookImgPath);

							echo '<div class="year-book">
								<img src="' . $bookImgUrl . '" alt="' . htmlspecialchars($book['book_name']) . '" class="year-book-img">
								<div class="year-book-content">
									<div class="year-book-name">' . htmlspecialchars($book['book_name']) . '</div>
									<div class="year-book-author">by ' . htmlspecialchars($bookDetails['book_author']) . '</div>
								</div>
							</div>';
						}
					?>
                </div>
            </div>

            <div class="popular-books">
                <div class="main-menu">
                    <div class="genre">Popular by Genre</div>
                    <div class="book-types">
						<?php  
							// Get the currently selected category (default to 'all')
							$current_category = isset($_GET['category']) ? $_GET['category'] : 'all';
							
							// Use the getAllCategories function 
							$categories = getAllCategories($connect);
							
							// Display "All Genres" link
							$all_active = ($current_category == 'all') ? 'active' : '';
							echo '<a href="?category=all" class="book-type ' . $all_active . '">All Genres</a>';
							
							// Display category links
							foreach($categories as $category) {
								$active = ($current_category == $category['category_id']) ? 'active' : '';
								echo '<a href="?category=' . $category['category_id'] . '" class="book-type ' . $active . '">' . 
									htmlspecialchars($category['category_name']) . '</a>'; 
							} 
						?>
                    </div>
                </div>

                <div class="book-cards">
					<?php
						// Get the category filter from URL
						$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
						
						// Base query
						$query = "SELECT b.book_id, b.book_name, b.book_author, b.book_img, c.category_name,
								COUNT(ib.issue_book_id) as borrow_count, b.book_no_of_copy
								FROM lms_book b
								LEFT JOIN lms_issue_book ib ON b.book_id = ib.book_id
								JOIN lms_category c ON b.category_id = c.category_id
								WHERE b.book_status = 'Enable'";
						
						// Add category filter if not 'all'
						if($category_filter !== 'all') {
							$query .= " AND b.category_id = :category_id";
						}
						
						$query .= " GROUP BY b.book_id
								ORDER BY borrow_count DESC, b.book_name ASC
								LIMIT 6";
						
						$statement = $connect->prepare($query);
						
						// Bind parameter if filtering by category
						if($category_filter !== 'all') {
							$statement->bindParam(':category_id', $category_filter);
						}
						
						$statement->execute();
						$books = $statement->fetchAll(PDO::FETCH_ASSOC);
						
						// Display books
						if(count($books) > 0) {
							foreach($books as $book) {
								$base_url = base_url();
								// Use the getBookImagePath function for dynamic image path
								$bookImgPath = getBookImagePath($book);
								// Remove the leading "../" from the path for browser display
								$bookImgUrl = str_replace('../', $base_url, $bookImgPath);
								
								echo '<div class="book-card">
										<div class="content-wrapper m-0 d-flex">
											<img src="' . $bookImgUrl . '" alt="' . htmlspecialchars($book['book_name']) . '" class="book-card-img">
											<div class="card-content">
												<div class="book-name">' . htmlspecialchars($book['book_name']) . '</div>
												<div class="book-by">by ' . htmlspecialchars($book['book_author']) . '</div>
												<div class="rate">
													<fieldset class="rating book-rate">';
													
									// Get the average rating for this book
									$averageRating = getBookAverageRating($connect, $book['book_id']);

									// Generate unique star rating inputs
									for($i = 5; $i >= 1; $i--) {
										$starId = 'card-star-' . $book['book_id'] . '-' . $i;
										$checked = ($i <= round($averageRating)) ? 'checked="checked"' : '';
										echo '<input type="checkbox" ' . $checked . ' id="' . $starId . '" name="rating" value="' . $i . '">
											<label class="full" for="' . $starId . '"></label>';
									}

									// Get the number of people who rated this book
									$bookReviews = getBookReviews($connect, $book['book_id']);
									$reviewCount = count($bookReviews);
									$votersText = $reviewCount . ' ' . ($reviewCount == 1 ? 'rating' : 'ratings');
															
									echo '</fieldset>
										<span class="book-voters card-vote">' . $votersText . ' | ' . $book['book_no_of_copy'] . ' copies</span>
										</div>
									<div class="book-sum card-sum">' . htmlspecialchars($book['category_name']) . ' | Borrowed: ' . $book['borrow_count'] . ' times</div>
									</div>
									</div>
								</div>';
							}
						} else {
							echo '<div class="no-books">No books found in this category.</div>';
						}
					?>
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



<?php

include '../footer.php';

?>