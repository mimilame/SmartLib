<?php
// submit_review.php - Handles book review submission and validation

// Include necessary files
include '../database_connection.php';
include '../function.php';

// Initialize response array
$response = array(
    'status' => 'error',
    'message' => 'An unknown error occurred.'
);

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'You must be logged in to submit a review.';
        echo json_encode($response);
        exit;
    }
    
    // Validate inputs
    if (!isset($_POST['book_id']) || !isset($_POST['rating']) || !isset($_POST['review_text'])) {
        $response['message'] = 'Missing required fields.';
        echo json_encode($response);
        exit;
    }
    
    $book_id = intval($_POST['book_id']);
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);
    
    // Validate book_id
    if ($book_id <= 0) {
        $response['message'] = 'Invalid book ID.';
        echo json_encode($response);
        exit;
    }
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $response['message'] = 'Rating must be between 1 and 5.';
        echo json_encode($response);
        exit;
    }
    
    // Validate review text
    if (empty($review_text) || strlen($review_text) < 10) {
        $response['message'] = 'Please enter a review with at least 10 characters.';
        echo json_encode($response);
        exit;
    }
    
    // Check if book exists and is enabled
    $query = "SELECT book_id, book_name FROM lms_book WHERE book_id = :book_id AND book_status = 'Enable'";
    $statement = $connect->prepare($query);
    $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $statement->execute();
    $book = $statement->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $response['message'] = 'The book does not exist or is not available for review.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has borrowed and returned this book
    $query = "SELECT issue_book_id FROM lms_issue_book 
              WHERE book_id = :book_id 
              AND user_id = :user_id 
              AND issue_book_status = 'Return' 
              LIMIT 1";
              
    $statement = $connect->prepare($query);
    $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $statement->execute();
    
    if ($statement->rowCount() === 0) {
        $response['message'] = 'You can only review books that you have borrowed and returned.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has already reviewed this book
    $query = "SELECT review_id FROM lms_book_review 
              WHERE book_id = :book_id 
              AND user_id = :user_id 
              LIMIT 1";
              
    $statement = $connect->prepare($query);
    $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $statement->execute();
    
    if ($statement->rowCount() > 0) {
        $response['message'] = 'You have already submitted a review for this book.';
        echo json_encode($response);
        exit;
    }
    
    // All validations passed, insert the review
    try {
        $connect->beginTransaction();
        
        // Insert the review
        $query = "INSERT INTO lms_book_review (book_id, user_id, rating, review_text, status) 
                  VALUES (:book_id, :user_id, :rating, :review_text, 'pending')";
                  
        $statement = $connect->prepare($query);
        $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
        $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $statement->bindParam(':rating', $rating, PDO::PARAM_INT);
        $statement->bindParam(':review_text', $review_text, PDO::PARAM_STR);
        $statement->execute();
        
        $review_id = $connect->lastInsertId();
        
        // Create notification for librarians/admins about new review
        $notification_message = "New review for '{$book['book_name']}' is pending approval.";
        
        $query = "INSERT INTO lms_notification 
                  (notification_type, notification_message, notification_status, notification_created_on) 
                  VALUES (:type, :message, 'unread', NOW())";
                  
        $statement = $connect->prepare($query);
        $statement->bindParam(':type', $notification_type);
        $statement->bindParam(':message', $notification_message);
        
        $notification_type = 'review';
        $statement->execute();
        
        $connect->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'Your review has been submitted and is pending approval by a librarian.';
        $response['review_id']
