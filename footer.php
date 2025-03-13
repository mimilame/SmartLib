<?php
    $query = "SELECT * FROM lms_setting LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if(is_admin_login())
    {
?>
        </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            
                    </div>
                </footer>
            </div>
        </div>
                <?php
                }
                else
                {
                ?>
                <footer class="bg-dark text-white pt-4 pb-1 mt-5">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><?php echo isset($row['library_name']) ? $row['library_name'] : 'Library Management System'; ?></h5>
                                <p>A complete solution for managing WMSU ESU CURUAN library resources efficiently.</p>
                                <span class="py-3">Open Hours: </span>
                            </div>
                            <div class="col-md-3">
                                <address class="mb-0">
                                    <!-- Address with Font Awesome icon -->
                                    <p><i class="fa fa-map-marker-alt"></i> 
                                        <?php echo isset($row["library_address"]) ? $row["library_address"] : 'Address not available'; ?>
                                    </p>

                                    <!-- Email with Font Awesome icon -->
                                    <p><i class="fa fa-envelope"></i> 
                                        <?php echo isset($row["library_email_address"]) ? $row["library_email_address"] : 'Email not available'; ?>
                                    </p>

                                    <!-- Phone with Font Awesome icon -->
                                    <p><i class="fa fa-phone"></i> 
                                        <?php echo isset($row["library_contact_number"]) ? $row["library_contact_number"] : 'Contact number not available'; ?>
                                    </p>
                                </address>
                            </div>

                        </div>
                    </div>
                </footer>
            </div>
        
                <?php 
                }
                ?>
        </main>
        
        <script src="./asset/js/scripts.js"></script>
        <script src="./asset/js/dataTables-simple-demo.js"></script>
        <!-- Bootstrap Bundle JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

        <!-- Simple DataTables -->
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
        <!-- DataTables Responsive Extension -->
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

        <!-- Bootstrap Bundle (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        
        <script src="./asset/js/custom-dataTables.js"></script>


    </body>

</html>