<?php
//footer.php
    $query = "SELECT * FROM lms_setting LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);


?>
        <?php if ($role_id == 1 || $role_id == 2): ?>

        
            </main>
        <?php elseif ($role_id == 3 || $role_id == 4 || $role_id == 5): ?>
            
            </main>
                <footer class="bg-dark text-white pt-1 pb-1 mt-1 w-100 d-flex flex-wrap gap-3 align-items-center justify-content-center">
                    <div class="mb-0 d-flex gap-2 align-items-center">
                        <span class="py-3">Open Hours: 8am-4pm MON-FRI</span>
                    </div>
                    <address class="mb-0 d-flex gap-2 align-items-center">
                        <!-- Address with Font Awesome icon -->
                        <p class="m-0"><i class="fa fa-map-marker-alt"></i> 
                            <?php echo isset($row["library_address"]) ? $row["library_address"] : 'Address not available'; ?>
                        </p>
                    </address>
                    <address class="mb-0 d-flex gap-2 align-items-center">
                        <!-- Email with Font Awesome icon -->
                        <p class="m-0"><i class="fa fa-envelope"></i> 
                            <?php echo isset($row["library_email_address"]) ? $row["library_email_address"] : 'Email not available'; ?>
                        </p>
                    </address>
                    <address class="mb-0 d-flex gap-2 align-items-center">
                        <!-- Phone with Font Awesome icon -->
                        <p class="m-0"><i class="fa fa-phone"></i> 
                            <?php echo isset($row["library_contact_number"]) ? $row["library_contact_number"] : 'Contact number not available'; ?>
                        </p>
                    </address>
                </footer>
            
        <?php 
        else: 
            // Default footer for login/registration or when no user is logged in
            
        ?>
            </main>
                <footer class="bg-dark text-white pt-1 pb-1 mt-1 w-100 d-flex flex-wrap gap-3 align-items-center justify-content-center">
                    <div class="mb-0 d-flex gap-2 align-items-center">
                        <span class="py-3">Open Hours: 8am-4pm MON-FRI</span>
                    </div>
                    <address class="mb-0 d-flex gap-2 align-items-center">
                        <!-- Address with Font Awesome icon -->
                        <p class="m-0"><i class="fa fa-map-marker-alt"></i> 
                            <?php echo isset($row["library_address"]) ? $row["library_address"] : 'Address not available'; ?>
                        </p>
                    </address>
                    <address class="mb-0 d-flex gap-2 align-items-center">
                        <!-- Email with Font Awesome icon -->
                        <p class="m-0"><i class="fa fa-envelope"></i> 
                            <?php echo isset($row["library_email_address"]) ? $row["library_email_address"] : 'Email not available'; ?>
                        </p>
                    </address>
                    <address class="mb-0 d-flex gap-2 align-items-center">
                        <!-- Phone with Font Awesome icon -->
                        <p class="m-0"><i class="fa fa-phone"></i> 
                            <?php echo isset($row["library_contact_number"]) ? $row["library_contact_number"] : 'Contact number not available'; ?>
                        </p>
                    </address>
                </footer>
            
        <?php endif; ?>


        <?php if(isset($_SESSION['swal_type'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: '<?php echo $_SESSION['swal_type']; ?>',
                        title: '<?php echo $_SESSION['swal_title']; ?>',
                        text: '<?php echo $_SESSION['swal_text']; ?>',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                });
            </script>
        <?php
                // Clear the flash message after displaying
                unset($_SESSION['swal_type']);
                unset($_SESSION['swal_title']);
                unset($_SESSION['swal_text']);
            endif;
        ?>

        <!-- footer.php -->

        <!-- Bootstrap Bundle (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <!-- jQuery (needed for many plugins like DataTables and Select2) -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

        <!-- Select2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <!-- Flickity -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flickity/1.0.0/flickity.pkgd.js"></script>

        <!-- Custom Scripts (Load these last to avoid conflicts and ensure all libs are loaded first) -->
        <script src="../asset/js/scripts.js"></script>
        <script src="asset/js/scripts.js"></script> <!-- Avoid duplication if this is the same as the line above -->


        <!-- Initialize DataTables & Select2 -->
        <script>
            $(document).ready(function() {

                // Initialize Select2
                $('.js-example-basic-single').select2();
                $('.js-example-basic-multiple').select2();
            });
        </script>
    </body>

</html>

<?php 

ob_end_flush();
?>