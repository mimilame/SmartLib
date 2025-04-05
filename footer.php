<?php
//footer.php
    $query = "SELECT * FROM lms_setting LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);
?>
        <?php if (is_logged_in('admin') || is_logged_in('librarian')) { ?>

        
        </main>
        <?php } elseif (is_logged_in('faculty') || is_logged_in('student') || is_logged_in('visitor')) { ?>
                <footer class="footer bg-dark text-white pt-1 pb-1 mt-1 position-fixed bottom-0 w-100 d-flex flex-wrap gap-3 align-items-center justify-content-center">
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
            </div>
        <?php 
        } else { 
            // Default footer for login/registration or when no user is logged in
            
        ?>
                <footer class="footer bg-dark text-white pt-1 pb-1 mt-1 position-fixed bottom-0 w-100 d-flex flex-wrap gap-3 align-items-center justify-content-center">
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
            </div>
        <?php 
        }
        ?>
        
        <script src="../asset/js/scripts.js"></script>
        <script src="../asset/js/dataTables-simple-demo.js"></script>
        <script src="asset/js/scripts.js"></script>
        <script src="asset/js/dataTables-simple-demo.js"></script>
        <!-- Bootstrap Bundle JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

        <!-- Simple DataTables -->
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
        <!-- DataTables Responsive Extension -->
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

        <!-- Bootstrap Bundle (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script src="../asset/js/custom-dataTables.js"></script>
        <script src="asset/js/custom-dataTables.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flickity/1.0.0/flickity.pkgd.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.js" ></script>

        <script>
            $(document).ready(function() {
                $('.js-example-basic-single').select2();
                $('.js-example-basic-multiple').select2();
            });
        </script>

    </body>

</html>

<?php 

ob_end_flush();
?>