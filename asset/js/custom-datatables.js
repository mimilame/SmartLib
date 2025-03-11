/**
 * User Management Table Initialization
 * This script is specifically for the user management table
 */

// Make sure the script runs after the DOM is fully loaded and the table exists
window.addEventListener('DOMContentLoaded', event => {

	var dataTable = document.getElementById('dataTable');

	if(dataTable)
	{
		new simpleDatatables.DataTable(dataTable);
	}

});
$(document).ready(function() {	

    var table = $('#dataTable');
        var existingTable = $('#dataTable').DataTable();

        if (existingTable) {
          existingTable.destroy();
        }
        
        // Initialize with your specific configuration
        $('#dataTable').DataTable({
          responsive: {
            details: {
              type: 'column',
              target: 'tr'
            }
          },
          columnDefs: [
            // Add a column for the expand/collapse button
            {
              className: 'dtr-control',
              orderable: false,
              targets: 0
            },
            // Adjust priorities based on the new column ordering
            { responsivePriority: 1, targets: [0, 1, 2, 10] }, // Control column, ID, Name, Action
            { responsivePriority: 2, targets: [3, 5] },        // Email, Contact
            { responsivePriority: 3, targets: [7] },           // Verification 
            { responsivePriority: 10000, targets: [4, 6, 8, 9] } // Less important columns
          ],
          order: [[1, 'asc']], // Sort by the second column (ID) instead of first
          autoWidth: false,
          language: {
            emptyTable: "No data available"
          }
        });
        
        
    
    
    // Try these different approaches to ensure the table initializes:
  

    
    // 2. Using window.onload as a fallback
    window.addEventListener('load', function() {
      // Small delay to ensure everything is fully loaded
      setTimeout(initDataTable, 100);
    });
    
    // 3. If the page is already loaded, try to initialize directly
    if (document.readyState === 'complete') {
      setTimeout(initDataTable, 200);
    }
  })();