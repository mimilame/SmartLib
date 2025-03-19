<?php
// book.php

include '../database_connection.php';
include '../function.php';

if (!is_admin_login()) {
    header('location:../admin_login.php');
    exit();
}

$message = '';
$error = '';
$alert = '';

// ADD BOOK
if (isset($_POST["add_book"])) {
    $formdata = array();

    // Book Name
    if (empty($_POST["book_name"])) {
        $error .= '<li>Book Name is required</li>';
    } else {
        $formdata['book_name'] = trim($_POST["book_name"]);
    }

    // Book Category
    if (empty($_POST["book_category"])) {
        $error .= '<li>Book Category is required</li>';
    } else {
        $formdata['book_category'] = trim($_POST["book_category"]);
    }

    // Book Author
    if (empty($_POST["book_author"])) {
        $error .= '<li>Book Author is required</li>';
    } else {
        $formdata['book_author'] = trim($_POST["book_author"]);
    }

    // Book Location Rack
    if (empty($_POST["book_location_rack"])) {
        $error .= '<li>Book Location Rack is required</li>';
    } else {
        $formdata['book_location_rack'] = trim($_POST["book_location_rack"]);
    }

    // Book ISBN Number
    if (empty($_POST["book_isbn_number"])) {
        $error .= '<li>Book ISBN Number is required</li>';
    } else {
        $formdata['book_isbn_number'] = trim($_POST["book_isbn_number"]);
    }

    // Book No. of Copy
    if (empty($_POST["book_no_of_copy"])) {
        $error .= '<li>Book No. of Copy is required</li>';
    } else {
        $formdata['book_no_of_copy'] = trim($_POST["book_no_of_copy"]);
    }

    if ($error == '') {
        // Insert book data
        $data = array(
            ':book_category'        => $formdata['book_category'],
            ':book_name'           => $formdata['book_name'],
            ':book_author'          => $formdata['book_author'],
            ':book_location_rack'            => $formdata['book_location_rack'],
            ':book_isbn_number'     => $formdata['book_isbn_number'],
            ':book_no_of_copy'      => $formdata['book_no_of_copy'],
            ':book_status'          => 'Available',
            ':book_added_on'        => get_date_time($connect),
            ':book_updated_on'      => get_date_time($connect)
        );

        $query = "
            INSERT INTO lms_book 
            (book_category, book_name, book_author, book_location_rack, book_isbn_number, book_no_of_copy, book_status, book_added_on, book_updated_on) 
            VALUES 
            (:book_category, :book_name, :book_author, :book_location_rack, :book_isbn_number, :book_no_of_copy, :book_status, :book_added_on, :book_updated_on)
        ";

        $statement = $connect->prepare($query);
        $statement->execute($data);
        set_flash_message('success', 'New Book Added Successfully');
        header('location:book.php?msg=add');
        exit();
    }
}

// DELETE/STATUS UPDATE
if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    $book_id = convert_data($_GET["code"], 'decrypt');
    $status = $_GET["status"];

    $data = array(
        ':book_status'      => $status,
        ':book_updated_on'  => get_date_time($connect),
        ':book_id'          => $book_id
    );

    $query = "
        UPDATE lms_book 
        SET book_status = :book_status, 
            book_updated_on = :book_updated_on 
        WHERE book_id = :book_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);
    $message = ($status == 'Active') ? 'Book Marked as Active' : 'Book Marked as Inactive';
    set_flash_message('success', $message);
    header('location:book.php?msg=' . strtolower($status));
    exit();
}
if (isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $query = "DELETE FROM lms_book WHERE book_id = :book_id";
    $statement = $connect->prepare($query);
    $statement->execute([':book_id' => $book_id]);
    echo "Success";
}

if (isset($_POST['book_ids'])) {
    $book_ids = $_POST['book_ids'];
    $placeholders = implode(',', array_fill(0, count($book_ids), '?'));
    $query = "DELETE FROM lms_book WHERE book_id IN ($placeholders)";
    $statement = $connect->prepare($query);
    $statement->execute($book_ids);
    echo "Success";
}

// FETCH BOOKS LIST
$query = "SELECT * FROM lms_book ORDER BY book_name DESC";
$statement = $connect->prepare($query);
$statement->execute();
$books = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';

// Check for flash messages
$success_message = get_flash_message('success');
if($success_message != '') {
    $alert = sweet_alert('success', $success_message);
}

// For form validation errors
if($error != '') {
    $alert_message = str_replace('<li>', '', $error);
    $alert_message = str_replace('</li>', '', $alert_message);
    $alert = sweet_alert('error', $alert_message);
}

?>

<main class="container py-4" style="min-height: 700px;">
    <h1>Catalog Management</h1>

    <?php if (isset($_GET["action"]) && $_GET["action"] == 'add'): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-book"></i> Add</div>
            <div class="card-body">
                <form method="post">
                    <div class="mt-4 mb-3 text-center">
                        <input type="submit" name="add_book" class="btn btn-success" value="Add" />
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET["action"]) && $_GET["action"] == 'edit'): ?>

        <?php
        $book_id = convert_data($_GET["code"], 'decrypt');
        $query = "SELECT * FROM lms_book WHERE book_id = :book_id";
        $statement = $connect->prepare($query);
        $statement->execute([':book_id' => $book_id]);
        $book_result = $statement->fetch(PDO::FETCH_ASSOC);
        ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-book"></i> Edit Book Details</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="book_id" value="<?= $book_result['book_id']; ?>">
                    <div class="mt-4 mb-3 text-center">
                        <input type="submit" name="edit_book" class="btn btn-primary" value="Edit" />
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <i class="fas fa-table me-1"></i> Catalog Management
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="javascript:void(0);" onclick="openAddModal()" class="btn btn-success btn-sm float-end">Add</a>
                        <button type="button" id="delete_selected" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed " style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="select_all"></th>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Rack</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($books)): ?>
                            <?php foreach ($books as $row): ?>
                                <tr>
                                    <td><input type="checkbox" class="row-checkbox" value="<?= $row["book_id"]; ?>"></td>
                                    <td><?= $row["book_id"]; ?></td>
                                    <td><?= htmlspecialchars($row["book_name"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_author"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_category"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_location_rack"]); ?></td>
                                    <td>
                                        <?= $row["book_status"] === "Available"
                                            ? '<span class="badge bg-success">Available</span>'
                                            : '<span class="badge bg-danger">Not Available</span>'; ?>
                                        <div class="badge bg-<?= ($row['book_status'] === 'Enable') ? 'success' : 'danger'; ?>">
											<?= ($row['book_status'] === 'Enable') ? 'Active' : 'Inactive'; ?>
										</div>
                                    </td>
                                    <td class="text-center align-content-center">
                                        <button type="button" class="btn btn-info btn-sm mb-1" onclick="viewBook(\'' . convert_data($row["book_id"]) . '\')"><i class="fa fa-eye"></i></button>
                                        <a href="javascript:void(0);" onclick="openEditModal('<?= convert_data($row["book_id"]); ?>', '<?= htmlspecialchars($row["book_name"]); ?>')" class="btn btn-sm btn-primary">
											<i class="fa fa-edit"></i>
										</a>
                                        <button type="button" class="btn btn-danger btn-sm mb-1" onclick="delete_data('<?= convert_data($row["book_id"]); ?>')"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No Data Found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
    <!-- Add Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBookModalLabel">Add New Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="add_book_form">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="book_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="book_name" name="book_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="book_category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select js-example-basic-multiple" id="book_category" name="book_category[]" multiple="multiple" required>

                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="book_author" class="form-label">Author <span class="text-danger">*</span></label>
                                <select class="form-select s-example-basic-multiple" id="book_author" name="book_author" required>
                                    
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="book_location_rack" class="form-label">Location Rack <span class="text-danger">*</span></label>
                                <select class="form-select s-example-basic-single" id="book_location_rack" name="book_location_rack" required>
                                    
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="book_isbn_number" class="form-label">ISBN Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="book_isbn_number" name="book_isbn_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="book_no_of_copy" class="form-label">No. of Copies <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="book_no_of_copy" name="book_no_of_copy" min="1" value="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_book" class="btn btn-success">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookModalLabel">Edit Item Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="edit_book_form">
                    <div class="modal-body">
                        <input type="hidden" id="edit_book_id" name="book_id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_book_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_book_name" name="book_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_book_category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select js-example-basic-multiple" id="edit_book_category" name="book_category[]" multiple="multiple" required>
                                    
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_book_author" class="form-label">Author <span class="text-danger">*</span></label>
                                <select class="form-select js-example-basic-multiple" id="edit_book_author" name="book_author" required>
                                    
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_book_location_rack" class="form-label">Location Rack <span class="text-danger">*</span></label>
                                <select class="form-select js-example-basic-single" id="edit_book_location_rack" name="book_location_rack" required>
                                   
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_book_isbn_number" class="form-label">ISBN Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_book_isbn_number" name="book_isbn_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_book_no_of_copy" class="form-label">No. of Copies <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_book_no_of_copy" name="book_no_of_copy" min="1" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_book_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_book_status" name="book_status" required>
                                    <option value="Available">Available</option>
                                    <option value="Not Available">Not Available</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_book" class="btn btn-primary">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                    <input type="hidden" id="delete_book_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm_delete_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div class="modal fade" id="bulkDeleteConfirmModal" tabindex="-1" aria-labelledby="bulkDeleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkDeleteConfirmModalLabel">Confirm Bulk Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete all selected items? This action cannot be undone.</p>
                    <p id="selected_count" class="text-danger fw-bold"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm_bulk_delete_btn">Delete All Selected</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggle_status(code, status) {
    let newStatus = status === 'Enable' ? 'Disable' : 'Enable';
    let statusText = status === 'Enable' ? 'mark as Not Available' : 'mark as Available';
    
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to " + statusText + " this book?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, ' + statusText + '!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "book.php?action=delete&code=" + code + "&status=" + newStatus;
        }
    });
}
// Function to open edit modal
function openEditModal(id, name) {
    document.getElementById('edit_book_id').value = id;
    document.getElementById('edit_book_name').value = name;
    
    // Show the modal
    const editModal = new bootstrap.Modal(document.getElementById('editBookModal'));
    editModal.show();
}
// Function to open add modal
function openAddModal() {
    document.getElementById('add_book_id').value = '';
    document.getElementById('add_book_name').value = '';
    
    // Show the modal
    const addModal = new bootstrap.Modal(document.getElementById('addBookModal'));
    addModal.show();
}
function viewBook(bookId) {
  // Fetch book details using AJAX
  $.ajax({
    url: 'book.php',
    type: 'POST',
    data: { book_id: bookId },
    dataType: 'json',
    success: function(data) {
      // Populate modal with book details
      $('#bookName').text(data.book_name);
      $('#bookAuthor').text(data.book_author);
      $('#bookCategory').text(data.book_category);
      $('#bookISBN').text(data.book_isbn_number);
      $('#bookRack').text(data.book_location_rack);
      $('#bookCopies').text(data.book_no_of_copy);
      
      // Set status with appropriate badge
      if(data.book_status === 'Available') {
        $('#bookStatus').html('<span class="badge bg-success">Available</span>');
      } else {
        $('#bookStatus').html('<span class="badge bg-danger">Not Available</span>');
      }
      
      $('#bookAddedOn').text(data.book_added_on);
      
      // Set book image
      if(data.book_image && data.book_image !== 'no_image.jpg') {
        $('#bookImage').attr('src', '../uploads/' + data.book_image);
      } else {
        $('#bookImage').attr('src', '../uploads/no_image.jpg');
      }
      
      // Show the modal
      var bookModal = new bootstrap.Modal(document.getElementById('viewBookModal'));
      bookModal.show();
    },
    error: function() {
      alert('Error fetching book details');
    }
  });
}

$(document).ready(function () {
    $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [{
            className: 'dtr-control',
            orderable: false,
            targets: 0
        },
            { responsivePriority: 1, targets: [0, 1] },         // ID, Book Name (highest priority)
            { responsivePriority: 2, targets: [2, 5] },         // Author, Status
            { responsivePriority: 3, targets: [6] },            // Action column
            { responsivePriority: 10000, targets: [3, 4] }      // Category, Rack (lowest priority)
        ],
        order: [[0, 'asc']],
        autoWidth: false,
        language: {
            emptyTable: "No books available"
        }
    });
        // Select/Deselect All Checkboxes
        $('#select_all').on('click', function () {
        $('.row-checkbox').prop('checked', this.checked);
    });

    // Delete Single Row
    $('.delete-btn').on('click', function () {
        let book_id = $(this).data('id');
        if (confirm('Are you sure you want to delete this book?')) {
            $.ajax({
                url: 'delete_book.php',
                type: 'POST',
                data: { book_id: book_id },
                success: function (response) {
                    location.reload();
                }
            });
        }
    });

    // Bulk Delete Selected Rows
    $('#delete_selected').on('click', function () {
        let selectedBooks = [];
        $('.row-checkbox:checked').each(function () {
            selectedBooks.push($(this).val());
        });

        if (selectedBooks.length === 0) {
            alert('No books selected!');
            return;
        }

        if (confirm('Are you sure you want to delete selected books?')) {
            $.ajax({
                url: 'delete_book.php',
                type: 'POST',
                data: { book_ids: selectedBooks },
                success: function (response) {
                    location.reload();
                }
            });
        }
    });

});

</script>

<?php include '../footer.php'; ?>
