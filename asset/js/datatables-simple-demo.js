// Simple-DataTables
// https://github.com/fiduswriter/Simple-DataTables/wiki


window.addEventListener('DOMContentLoaded', event => {

	var datatablesSimple = document.getElementById('datatablesSimple');

	if(datatablesSimple)
	{
		new simpleDatatables.DataTable(datatablesSimple);
	}

});

$(document).ready(function() {
    // Get the table
    var table = $('#dataTable');
    
    // Get the total number of columns dynamically
    var totalColumns = table.find('thead th').length;
    
    // Define the important columns by their index or data attribute
    var importantColumns = [
        0,  // ID column (always first)
        1,  // Name column (always second)
        totalColumns - 1  // Action column (always last)
    ];
    
    // Define medium priority columns (adjust as needed)
    var mediumPriorityColumns = [2, 4]; // Email and Contact by default
    
    // Define low priority columns (adjust as needed)
    var lowPriorityColumns = [6]; // Verification status
    
    // Create an array of all column indices
    var allColumns = Array.from({length: totalColumns}, (_, i) => i);
    
    // Calculate which columns should be hidden first (all except important, medium and low priority)
    var hideFirstColumns = allColumns.filter(function(colIndex) {
        return !importantColumns.includes(colIndex) && 
               !mediumPriorityColumns.includes(colIndex) && 
               !lowPriorityColumns.includes(colIndex);
    });
    
    // Create columnDefs array
    var columnDefs = [
        { responsivePriority: 1, targets: importantColumns },
        { responsivePriority: 2, targets: mediumPriorityColumns },
        { responsivePriority: 3, targets: lowPriorityColumns },
        { responsivePriority: 10000, targets: hideFirstColumns }
    ];
    
    // Initialize DataTable with dynamic configuration
    table.DataTable({
        responsive: {
            details: {
                type: 'column',
                renderer: function(api, rowIdx, columns) {
                    var data = $.map(columns, function(col, i) {
                        return col.hidden ?
                            '<tr data-dt-row="'+col.rowIndex+'" data-dt-column="'+col.columnIndex+'">' +
                                '<td class="fw-bold">'+col.title+':</td> ' +
                                '<td>'+col.data+'</td>' +
                            '</tr>' :
                            '';
                    }).join('');
                    
                    return data ?
                        $('<table class="table table-sm table-bordered"></table>').append(data) :
                        false;
                }
            }
        },
        autoWidth: false,
        columnDefs: columnDefs,
        language: {
            emptyTable: "No data available"
        }
    });
});