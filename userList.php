<?php 
session_start();
if (!isset($_SESSION['SuperAdminName'])) {
    // Redirect the user to the index page or login page
    header("Location: ../index.php");
    exit(); // Stop further execution
}
require_once '../lang.php';
require_once '../navbar.php';
include("../configuration/configuration.php");

$conn = new mysqli($servername, $username, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT user_id, fullName, faculty_id, Role FROM users1";
$stmt = $conn->prepare($sql);
$stmt->execute();

// Bind the results
$stmt->bind_result($user_id, $fullName, $faculty_id, $role);

// Fetch the results
$stmt->fetch();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Users List') ?></title>
    <style>
        @media (min-width: 1203px) {
            .main-content {
                margin-left: 300px;
                width: calc(100% - 300px);
                margin-top:50px;
            }
        }
        section{
            padding-top:20px;
        }
        h5{
            text-align:center;
            padding-top:10px;
            padding-bottom:10px;
        }
        #example {
            visibility: visible
            ;
        }
        .dataTables_wrapper {
            min-height: 300px;
        }
        .card-title{
            text-align:center;
        }
        .button-spacing {
            margin-right: 10px;
        }
        .export-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .export-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
<section class="main-content">
    <div class="container mt-5">
        <?php 
        // Display success message for deletion
        if (isset($_GET['deleted']) && $_GET['deleted'] === 'success') {
            echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    User deleted successfully.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                  </div>";
        }
        ?>
        
        <div class="card mt-4 mb-4" style="background-color: #f8f9fa; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px;">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        <h5 class="card-title"><?php echo __("Users List");?></h5>
                        
                        <!-- Export Buttons -->
                        <div class="d-flex justify-content-end mb-3">
                            <button onclick="exportToCSV()" class="export-btn button-spacing">
                                <i class="fa-solid fa-file-csv"></i><?php echo __("Export CSV");?> 
                            </button>
                            <button onclick="exportToExcel()" class="export-btn button-spacing">
                                <i class="fa-solid fa-file-excel"></i> <?php echo __("Export Excel");?> 
                            </button>
                            <button onclick="exportToPDF()" class="export-btn">
                                <i class="fa-solid fa-file-pdf"></i><?php echo __("Export PDF");?> 
                            </button>
                        </div>
                        
                        <div class="container">
                            <table id="example" class="table table-striped nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th><?php echo __("User ID");?></th>
                                        <th><?php echo __("Full Name");?></th>
                                        <th><?php echo __("Faculty ID");?></th>
                                        <th><?php echo __("Role");?></th>
                                        <th><?php echo __("Actions");?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Re-open connection and fetch results
                                $conn = new mysqli($servername, $username, $password, $database);
                                $sql = "SELECT user_id, fullName, faculty_id, Role FROM users1";
                                $result = $conn->query($sql);

                               if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["user_id"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row["fullName"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row["faculty_id"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars(__($row["Role"] ?? '')) . "</td>"; // Translates "Role" like "Admin", "Student", etc.

        echo '<td class="action-buttons">';
        echo '<a href="../Form_1/userInfoEdit.php?id=' . htmlspecialchars($row["user_id"] ?? '') . '" class="btn btn-dark custom-btn button-spacing text-light"><i class="fa-solid fa-pen-to-square"></i></a>';
        echo '<a href="../Form_1/delete.php?table=users1&id=' . htmlspecialchars($row["user_id"] ?? '') . '" class="btn btn-dark custom-btn text-light" onclick="return confirm(\'' . __('Are you sure you want to delete this user?') . '\')"><i class="fa-solid fa-trash"></i></a>';
        echo '</td>';
        echo "</tr>";
    }
} else {
    echo "<tr>";
    for ($i = 0; $i < 5; $i++) {
        echo "<td></td>";
    }
    echo "</tr>";
}

                                $conn->close();
                                ?>     
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="../DataTable/datatable.js"></script>

<script>
    const translatedUsersList = <?= json_encode(__('Users List')) ?>;
// Export functions
function exportToCSV() {
    const table = document.getElementById('example');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    // Get headers (excluding action column)
    const headerRow = rows[0];
    const headers = [];
    for (let i = 0; i < headerRow.cells.length - 1; i++) {
        headers.push(headerRow.cells[i].textContent.trim());
    }
    csv.push(headers.join(','));
    
    // Get data rows (excluding action column)
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const rowData = [];
        for (let j = 0; j < row.cells.length - 1; j++) {
            rowData.push('"' + row.cells[j].textContent.trim() + '"');
        }
        if (rowData.some(cell => cell !== '""')) {
            csv.push(rowData.join(','));
        }
    }
    
    downloadFile(csv.join('\n'), 'users_list.csv', 'text/csv');
}

function exportToExcel() {
    const table = document.getElementById('example');
    const rows = table.querySelectorAll('tr');
    let data = [];
    
    // Process each row (excluding action column)
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const rowData = [];
        for (let j = 0; j < row.cells.length - 1; j++) {
            rowData.push(row.cells[j].textContent.trim());
        }
        if (i === 0 || rowData.some(cell => cell !== '')) {
            data.push(rowData);
        }
    }
    
    // Create workbook
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Users");
    XLSX.writeFile(wb, "users_list.xlsx");
}

function exportToPDF() {
    const table = document.getElementById('example');
    const rows = table.querySelectorAll('tr');
    
    // Prepare data for PDF
    const headers = [];
    const headerRow = rows[0];
    for (let i = 0; i < headerRow.cells.length - 1; i++) {
        headers.push(headerRow.cells[i].textContent.trim());
    }
    
    const data = [];
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const rowData = [];
        for (let j = 0; j < row.cells.length - 1; j++) {
            rowData.push(row.cells[j].textContent.trim());
        }
        if (rowData.some(cell => cell !== '')) {
            data.push(rowData);
        }
    }
    
    const docDefinition = {
        content: [
            { text: translatedUsersList, style: 'header' },
            {
                table: {
                    headerRows: 1,
                    widths: ['auto', '*', 'auto', 'auto'],
                    body: [headers, ...data]
                }
            }
        ],
        styles: {
            header: {
                fontSize: 18,
                bold: true,
                margin: [0, 0, 0, 20]
            }
        }
    };
    
    pdfMake.createPdf(docDefinition).download('users_list.pdf');
}

function downloadFile(content, fileName, contentType) {
    const a = document.createElement('a');
    const file = new Blob([content], { type: contentType });
    a.href = URL.createObjectURL(file);
    a.download = fileName;
    a.click();
}

// Auto-hide success message after 5 seconds
setTimeout(function() {
    $('.alert-success').fadeOut('slow');
}, 5000);

// Clear URL parameters after showing message
if (window.location.search.includes('deleted=success')) {
    setTimeout(function() {
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    }, 100);
}
</script>

<!-- Excel export library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</body>
</html>