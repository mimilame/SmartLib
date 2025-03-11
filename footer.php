                <?php
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
                <footer class="pt-3 mt-4 text-muted text-center border-top">
                    &copy; <?php echo date('Y'); ?>
                </footer>
            </div>
        </main>
                <?php 
                }
                ?>

        <script src="./asset/js/scripts.js"></script>
        <script src="./asset/js/datatables-simple-demo.js"></script>
        <!-- Bootstrap Bundle JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

        <!-- Simple DataTables -->
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
        <!-- DataTables Responsive Extension -->
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

        <!-- Bootstrap Bundle (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        
        <script src="./asset/js/custom-datatables.js"></script>
    </body>

</html>