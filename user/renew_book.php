<?php
// renew_book.php - Handle book renewal requests via AJAX
include '../database_connection.php';
include '../function.php';
authenticate_user();

$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Check if this is a POST request and action is 'renew'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'renew') {
    // Validate issue book ID
    if (isset($_POST['issue_book_id']) && is_numeric($_POST['issue_book_id'])) {
        $issue_book_id = intval($_POST['issue_book_id']);
        $user_id = $_SESSION['user_id'];
        
        try {
            // Start transaction
            $connect->beginTransaction();
            
            // Check if the book belongs to the current user and is eligible for renewal
            $query = "SELECT ib.*, b.book_name, 
                      DATEDIFF(ib.expected_return_date, CURRENT_DATE()) as days_remaining 
                      FROM lms_issue_book ib
                      JOIN lms_book b ON ib.book_id = b.book_id
                      WHERE ib.issue_book_id = :issue_book_id 
                      AND ib.user_id = :user_id
                      AND ib.issue_book_status = 'Issue'";
            
            $statement = $connect->prepare($query);
            $statement->bindParam(':issue_book_id', $issue_book_id, PDO::PARAM_INT);
            $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $statement->execute();
            
            $book_data = $statement->fetch(PDO::FETCH_ASSOC);
            
            if ($book_data) {
                // Check if book is eligible for renewal (you can add more conditions)
                // For example, only allow renewal if less than 5 days remaining
                if ($book_data['days_remaining'] <= 5) {
                    // Calculate new return date (14 days from now)
                    $new_return_date = date('Y-m-d', strtotime('+14 days'));
                    
                    // Update the expected return date
                    $update_query = "UPDATE lms_issue_book 
                                    SET expected_return_date = :new_date,
                                    renewal_count = renewal_count + 1,
                                    last_renewal_date = CURRENT_DATE()
                                    WHERE issue_book_id = :issue_book_id";
                    
                    $update_statement = $connect->prepare($update_query);
                    $update_statement->bindParam(':new_date', $new_return_date, PDO::PARAM_STR);
                    $update_statement->bindParam(':issue_book_id', $issue_book_id, PDO::PARAM_INT);
                    $update_statement->execute();
                    
                    // Log the renewal in renewal history table
                    $log_query = "INSERT INTO lms_renewal_history 
                                 (issue_book_id, user_id, previous_return_date, new_return_date, renewal_date)
                                 VALUES (:issue_book_id, :user_id, :prev_date, :new_date, CURRENT_DATE())";
                    
                    $log_statement = $connect->prepare($log_query);
                    $log_statement->bindParam(':issue_book_id', $issue_book_id, PDO::PARAM_INT);
                    $log_statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $log_statement->bindParam(':prev_date', $book_data['expected_return_date'], PDO::PARAM_STR);
                    $log_statement->bindParam(':new_date', $new_return_date, PDO::PARAM_STR);
                    $log_statement->execute();
                    
                    // Commit the transaction
                    $connect->commit();
                    
                    // Return success response
                    $response = [
                        'success' => true,
                        'message' => 'Book renewed successfully',
                        'new_return_date' => date('M d, Y', strtotime($new_return_date)),
                        'book_name' => $book_data['book_name']
                    ];
                } else {
                    $response['message'] = 'This book is not eligible for renewal yet. You can only renew when 5 or fewer days remain until the due date.';
                }
            } else {
                $response['message'] = 'Book not found or not eligible for renewal';
            }
        } catch (PDOException $e) {
            // Roll back the transaction on error
            $connect->rollBack();
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>