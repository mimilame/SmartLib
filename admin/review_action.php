<?php

// Include necessary files
require_once('../database_connection.php'); // Your database connection
require_once('../function.php'); // Your database functions

header('Content-Type: application/json');
// Make sure error reporting doesn't output to browser in production
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Check if request is AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Function to send JSON response for AJAX requests
function sendJsonResponse($success, $message) {
    global $isAjax;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit;
    }
    
    // For non-AJAX requests, use session messages
    if ($success) {
        $_SESSION['success_message'] = $message;
    } else {
        $_SESSION['error_message'] = $message;
    }
    
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
    exit;
}

// Update the review action handling code
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Handle flag review action - Admin/Librarian only
    if ($action === 'flag_review') {
        // Check if review_id and reason are provided
        if (!isset($_POST['review_id']) || !isset($_POST['flag_reason']) || empty($_POST['flag_reason'])) {
            sendJsonResponse(false, "Missing required information for flagging a review");
            exit;
        }
        
        // Set up necessary variables
        $review_id = intval($_POST['review_id']);
        $reason = trim($_POST['flag_reason']);
        
        // Get the unique ID directly from the session
        $user_unique_id = $_SESSION['user_unique_id'] ?? null;
        
        if ($user_unique_id) {
            // Check if user has permission to flag (admin or librarian)
            $prefix = strtoupper(substr($user_unique_id, 0, 1));
            
            if ($prefix === 'A' || $prefix === 'L') {
                // Flag the review using the full unique_id
                $result_flag = flagReview($connect, $review_id, $user_unique_id, $reason);
                
                if ($result_flag) {
                    sendJsonResponse(true, "Review has been flagged successfully.");
                } else {
                    sendJsonResponse(false, "Unable to flag this review or review doesn't exist.");
                }
            } else {
                sendJsonResponse(false, "Only admins and librarians can flag reviews");
            }
        } else {
            sendJsonResponse(false, "You must be logged in to flag a review");
        }
    }
    // Handle review actions
    else if ($action === 'manage_review') {
        if (!isset($_POST['review_id']) || !isset($_POST['review_action'])) {
            sendJsonResponse(false, "Missing required information for managing review");
            exit;
        }
        
        $review_id = intval($_POST['review_id']);
        $review_action = $_POST['review_action'];
        $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
        
        $success = false;
        $message = "Action could not be completed.";
        
        try {
            switch ($review_action) {
                case 'approve':
                    $success = approveReview($connect, $review_id);
                    $message = "Review has been approved and is now visible to users.";
                    break;
                    
                case 'reject':
                    $success = rejectReview($connect, $review_id, $remarks);
                    $message = "Review has been rejected.";
                    break;
                    
                case 'delete':
                    $success = deleteReview($connect, $review_id);
                    $message = "Review has been permanently deleted.";
                    break;
                    
                default:
                    sendJsonResponse(false, "Invalid review action.");
                    exit;
            }
            
            if ($success) {
                sendJsonResponse(true, $message);
            } else {
                sendJsonResponse(false, "Unable to process your request. Database operation failed.");
            }
        } catch (Exception $e) {
            error_log('Error in manage_review: ' . $e->getMessage());
            sendJsonResponse(false, "An error occurred while processing your request.");
        }
    }
    
    // Handle review settings update
    else if ($action === 'update_review_settings') {
        try {
            // Get form data with validation
            $settings = [
                'moderate_reviews' => isset($_POST['moderate_reviews']) ? 1 : 0,
                'allow_guest_reviews' => isset($_POST['allow_guest_reviews']) ? 1 : 0,
                'reviews_per_page' => max(1, intval($_POST['reviews_per_page'] ?? 10))
            ];
            
            // Update settings
            $result = updateReviewSettings($connect, $settings);
            
            // Send response
            if ($result) {
                sendJsonResponse(true, "Review settings updated successfully");
            } else {
                sendJsonResponse(false, "Error updating review settings");
            }
        } catch (Exception $e) {
            error_log('Error in update_review_settings: ' . $e->getMessage());
            sendJsonResponse(false, "An error occurred while updating settings.");
        }
    }
    
    // If action is not recognized
    else {
        sendJsonResponse(false, "Unknown action requested");
    }
} else {
    sendJsonResponse(false, "No action specified");
}