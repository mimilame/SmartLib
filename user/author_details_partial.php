<?php
// author_details_partial.php - Displays books by a specific author
include '../database_connection.php';
include '../function.php';

// Get author ID from URL parameter
$author_id = isset($_GET['author_id']) ? intval($_GET['author_id']) : 0;

if ($author_id <= 0) {
    echo '<div class="alert alert-danger m-3">Invalid author ID.</div>';
    exit;
}

// Get author details
$query = "SELECT * FROM lms_author WHERE author_id = :author_id";
$statement = $connect->prepare($query);
$statement->bindParam(':author_id', $author_id);
$statement->execute();
$author = $statement->fetch(PDO::FETCH_ASSOC);

if (!$author) {
    echo '<div class="alert alert-danger m-3">Author not found.</div>';
    exit;
}

// Get author's books with availability information
$books = getBooksByAuthor($connect, $author_id);

// Get total books count by this author
$query = "SELECT COUNT(*) as total_books FROM lms_book_author WHERE author_id = :author_id";
$statement = $connect->prepare($query);
$statement->bindParam(':author_id', $author_id);
$statement->execute();
$total_books_result = $statement->fetch(PDO::FETCH_ASSOC);
$total_books = $total_books_result['total_books'];

// Get total borrows of author's books
$query = "SELECT COUNT(*) as total_borrows 
          FROM lms_issue_book ib
          JOIN lms_book_author ba ON ib.book_id = ba.book_id
          WHERE ba.author_id = :author_id";
$statement = $connect->prepare($query);
$statement->bindParam(':author_id', $author_id);
$statement->execute();
$total_borrows_result = $statement->fetch(PDO::FETCH_ASSOC);
$total_borrows = $total_borrows_result['total_borrows'];

// Base URL for images
$base_url = base_url();
$authorData = ['author_id' => $author['author_id'], 'author_profile' => $author['author_profile'] ?? ''];
$authorImgPath = getAuthorImagePath($authorData);
$authorImgUrl = str_replace('../', $base_url, $authorImgPath);
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Author Profile Column (Sticky) -->
        <div class="col-md-4 bg-light">
            <div class="sticky-top" style="top: 1rem;">
                <div class="p-4 text-center">
                    <img src="<?php echo  $authorImgUrl; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="img-fluid rounded-circle shadow" style="max-height: 250px; width: 250px; object-fit: cover;">
                    
                    <div class="mt-4">
                        <h2 class="fw-bold"><?php echo htmlspecialchars($author['author_name']); ?></h2>
                        
                        <button class="btn btn-outline-primary w-100 mt-3" data-bs-dismiss="modal">
                            <i class="bi bi-arrow-left"></i> Back to Catalog
                        </button>
                    </div>
                    
                    <hr>
                    
                    <div class="text-start">
                        <h6 class="fw-bold">Author Information</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Total Books:</span>
                                <span class="text-muted"><?php echo $total_books; ?></span>
                            </li>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Total Borrows:</span>
                                <span class="text-muted"><?php echo $total_borrows; ?></span>
                            </li>
                            <?php if (!empty($author['author_country'])): ?>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Country:</span>
                                <span class="text-muted"><?php echo htmlspecialchars($author['author_country']); ?></span>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($author['author_dob'])): ?>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Date of Birth:</span>
                                <span class="text-muted"><?php echo date('F j, Y', strtotime($author['author_dob'])); ?></span>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($author['author_added_on'])): ?>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Added on:</span>
                                <span class="text-muted"><?php echo date('F j, Y', strtotime($author['author_added_on'])); ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Author Books Column (Scrollable) -->
        <div class="col-md-8">
            <div class="p-4">
                <h3 class="mb-4">Biography</h3>
                
                <?php if (!empty($author['author_bio'])): ?>
                    <p class="lead"><?php echo nl2br(htmlspecialchars($author['author_bio'])); ?></p>
                <?php else: ?>
                    <p class="text-muted">No biography available for this author.</p>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <h3 class="mb-4">Books by <?php echo htmlspecialchars($author['author_name']); ?></h3>
                
                <?php if (!empty($books)): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($books as $book): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm">
                                    <?php 
                                    $bookImgPath = getBookImagePath($book);
                                    $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                                    
                                    ?>
                                    <img src="<?php echo $bookImgUrl; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($book['book_name']); ?>" style="height: 180px; object-fit: cover;">
                                    <div class="card-body">
                                        <span class="fw-bold"><?php echo htmlspecialchars($book['book_name']); ?></span>
                                        <p class="card-text small text-muted"><?php echo htmlspecialchars($book['category_name']); ?></p>
                                        
                                        <?php
                                        // Calculate availability based on book info
                                        $availability = getBookAvailability($connect, $book['book_id'], $book['book_no_of_copy']);
                                        $is_available = $availability['is_available'];
                                        ?>
                                        
                                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-auto">
                                            <span class="badge mx-sm-auto <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $is_available ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                            <button class="btn btn-sm btn-primary book-details-btn badge mx-sm-auto mt-sm-1" data-book-id="<?php echo $book['book_id']; ?>">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No books found for this author.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script for navigation handling -->
<script>
// This will ensure the script runs when this content is loaded
(function() {

    // Handle book detail button clicks
    var bookButtons = document.querySelectorAll('.book-details-btn');
    bookButtons.forEach(function(btn) {
        btn.onclick = function(e) {
            e.preventDefault();
            var bookId = this.getAttribute('data-book-id');
            // Load book details and replace the modal content
            fetch('book_details_partial.php?book_id=' + bookId)
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    document.querySelector('.modal-body').innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error loading book details:', error);
                });
        };
    });
})();
</script>