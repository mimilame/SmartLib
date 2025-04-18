<?php
// Start with clean output buffer to prevent any whitespace issues
ob_start();
// Add these at the top of ajax_actions.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to the client
ini_set('log_errors', 1); // Log errors instead

// Make sure you have the database connection established
require_once '../database_connection.php'; // Adjust this to your actual file

// Make sure $connect variable is defined and working
if (!isset($connect) || $connect === null) {
    $response = [
        'success' => false,
        'message' => 'Database connection error'
    ];
    sendJsonResponse($response);
    exit;
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    // Check if action is set
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'update_review_status':
                if (isset($_POST['review_id']) && isset($_POST['status'])) {
                    $review_id = intval($_POST['review_id']);
                    $status = $_POST['status'];
                    
                    // Validate status
                    if (in_array($status, ['approved', 'rejected'])) {
                        if (updateReviewStatus($connect, $review_id, $status)) {
                            $response = [
                                'success' => true, 
                                'message' => 'Review status updated successfully'
                            ];
                        } else {
                            $response = [
                                'success' => false, 
                                'message' => 'Failed to update review status'
                            ];
                        }
                    } else {
                        $response = [
                            'success' => false, 
                            'message' => 'Invalid status value'
                        ];
                    }
                } else {
                    $response = [
                        'success' => false, 
                        'message' => 'Missing required parameters'
                    ];
                }
                break;
                
            case 'delete_review':
                if (isset($_POST['review_id'])) {
                    $review_id = intval($_POST['review_id']);
                    if (deleteReview($connect, $review_id)) {
                        $response = [
                            'success' => true, 
                            'message' => 'Review deleted successfully'
                        ];
                    } else {
                        $response = [
                            'success' => false, 
                            'message' => 'Failed to delete review'
                        ];
                    }
                } else {
                    $response = [
                        'success' => false, 
                        'message' => 'Missing review_id parameter'
                    ];
                }
                break;
                
            case 'get_review_details':
                if (isset($_POST['review_id'])) {
                    $review_id = intval($_POST['review_id']);
                    
                    // Query to get review details
                    $query = "SELECT r.*, u.user_name, b.book_name
                              FROM lms_book_review r
                              JOIN lms_user u ON r.user_id = u.user_id
                              JOIN lms_book b ON r.book_id = b.book_id
                              WHERE r.review_id = :review_id";
                    
                    $statement = $connect->prepare($query);
                    $statement->bindParam(':review_id', $review_id, PDO::PARAM_INT);
                    $statement->execute();
                    $review = $statement->fetch(PDO::FETCH_ASSOC);
                    
                    if ($review) {
                        // Get flags for this review
                        $query = "SELECT f.*, u.user_name
                                  FROM lms_review_flag f
                                  JOIN lms_user u ON f.user_id = u.user_id
                                  WHERE f.review_id = :review_id";
                        
                        $statement = $connect->prepare($query);
                        $statement->bindParam(':review_id', $review_id, PDO::PARAM_INT);
                        $statement->execute();
                        $flags = $statement->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Add flags to review
                        $review['flags'] = $flags;
                        $review['flag_count'] = count($flags);
                        
                        $response = [
                            'success' => true,
                            'review' => $review
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Review not found'
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Missing review_id parameter'
                    ];
                }
                break;
                
            case 'update_review_settings':
                if (isset($_POST['settings'])) {
                    try {
                        $settings = json_decode($_POST['settings'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // Validate settings
                            $validatedSettings = [
                                'moderate_reviews' => isset($settings['moderate_reviews']) ? (bool)$settings['moderate_reviews'] : true,
                                'allow_guest_reviews' => isset($settings['allow_guest_reviews']) ? (bool)$settings['allow_guest_reviews'] : false,
                                'reviews_per_page' => isset($settings['reviews_per_page']) ? max(1, intval($settings['reviews_per_page'])) : 10
                            ];
                            
                            if (updateReviewSettings($connect, $validatedSettings)) {
                                $response = [
                                    'success' => true,
                                    'message' => 'Settings updated successfully'
                                ];
                            } else {
                                $response = [
                                    'success' => false,
                                    'message' => 'Failed to update settings'
                                ];
                            }
                        } else {
                            $response = [
                                'success' => false,
                                'message' => 'Invalid JSON data'
                            ];
                        }
                    } catch (Exception $e) {
                        $response = [
                            'success' => false,
                            'message' => 'Error processing settings: ' . $e->getMessage()
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Missing settings parameter'
                    ];
                }
                break;
                
            default:
                $response = [
                    'success' => false,
                    'message' => 'Unknown action'
                ];
                break;
        }
    }
    
    // Send JSON response
    sendJsonResponse($response);
    exit;
}



// If not a POST request, redirect to index
header('Location: ../index.php');
exit;
?>