<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }

  // Block unauthorized users from accessing the page
  if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] != 'admin') {
      http_response_code(403);
      die('Forbidden');
    }
  } else {
    http_response_code(403);
    die('Forbidden');
  }


 ?>

<!DOCTYPE html>
<script>
</script>
<html>
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function () 
    {
        $('#Blog_table').DataTable();
    });
    document.addEventListener("DOMContentLoaded", function() 
    {
      const table = document.getElementById('Blog_table');
      const selectedTable = document.getElementById('CloneBook_table');
      table.addEventListener('click', function(event) 
      {
        const target = event.target;
        if (target.type === 'checkbox' && target.classList.contains('rowCheckbox')) 
        {
          const row = target.parentNode.parentNode;
          const rowData = Array.from(row.cells)
            .slice(0, -1)
            .map(cell => cell.textContent);
          if (target.checked) {
            if (!isRowPresent(rowData)) 
            {
              addRowToSelectedTable(rowData);
            }
          } 
          else 
          {
            removeRowFromSelectedTable(rowData);
          }
        }
      });
      function isRowPresent(rowData) 
      {
        const rows = selectedTable.getElementsByTagName('tr');
        for (let i = 1; i < rows.length; i++) { // Start from index 1 to exclude the header row
          const cells = rows[i].getElementsByTagName('td');
          const existingRowData = Array.from(cells)
          .slice(0, -1)
          .map(cell => cell.textContent);
          if (JSON.stringify(existingRowData) === JSON.stringify(rowData)) 
          {
            return true;
          }
        }
        return false;
      }
      function addRowToSelectedTable(rowData) 
      {
        const newRow = selectedTable.insertRow();
        for (let i = 0; i < rowData.length; i++) 
        {
          const cell = newRow.insertCell();
          cell.textContent = rowData[i];
        }
        const quantityCell = newRow.insertCell();
        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.min = 1;
        quantityInput.max = 10;
        quantityInput.value = 1;
        quantityCell.appendChild(quantityInput);
      }
      function removeRowFromSelectedTable(rowData) 
      {
        const rows = selectedTable.getElementsByTagName('tr');
        for (let i = 0; i < rows.length; i++) 
        { 
          const cells = rows[i].getElementsByTagName('td');
          const existingRowData = Array.from(cells)
            .slice(0, -1)
            .map(cell => cell.textContent);
          if (JSON.stringify(existingRowData) === JSON.stringify(rowData)) {
            selectedTable.deleteRow(i);
            break;
          }
        }
      }
    });
    
    </script>
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
    <header class="inverse">
      <div class="container">
        <h2><span class="accent-text">Manage Shipment</span></h2>
      </div>
    </header>

    <div>
      <a href="viewshipments.php" style="color:blue;">View Orders</a>
    <div>
    <div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
      <table id="Blog_table" class="display compact">
        <thead>
        <tr style='background-color:lightgray; color:black'>
          <th style='width:10%' align='left'>ID</th>
          <th style='width:25%' align='left'>Title</th>
          <th style='width:25%' align='left'>Author</th>
          <th style='width:15%' align='left'>Publisher</th>
          <th style='width:7%'  align='left'>Price</th>
          <th style='width:10%' align='left'>Grade Level</th>
          <th style='width:5%'  align='center'>Option</th>
        </tr>
        </thead>
        <tbody>
<!-- Populating table with data from the database-->
<?php
  require 'db_configuration.php';
  // Create connection
  $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
  // Check connection
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }
  $sql = "SELECT * FROM `books` where available=1;";
  $result = $conn->query($sql);

  $sqlSchool = "SELECT * FROM `schools`";
  $sqlSchool = $conn->query($sqlSchool);

  $sqlUser = "SELECT * FROM `books` where available=1;";
  $sqlUser = $conn->query($sqlUser);

  if ($result->num_rows > 0) 
  {
    // Create table with data from each row
    while($row = $result->fetch_assoc()) 
    {
        //$Editable = $row["id"].'*'.$row["User_Id"].'*'.$row["id"].'*'.$row["Status"];
        echo "<tr>
                  <td align='left'>". $row["id"]."</td>
                  <td align='left'>". $row["title"]."</td> 
                  <td align='left'>". $row["author"]."</td> 
                  <td align='left'>". $row["publisher"]."</td> 
                  <td align='left'>". $row["price"]."</td> 
                  <td align='left'>". $row["grade_level"]."</td> 
                  <td  align='center'>
                    <input type='checkbox' class='rowCheckbox' style='background-color:blue; color:white; border:solid 0px; border-radius:5px'/>
                  </td>
                  </tr>";
        // <a href='admin_edit_blog.php?query=".$row['SchoolUser_Id']."'><input type='button' style='width:200px; height:44px; background-color:blue; color:white; border:solid 0px; border-radius:5px'  value='Edit'/></a>

    }
  } else {
    echo "0 results";
  }
  $conn->close();
    		?>
        </tbody>
      </table>
</div>
<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
      <table id="CloneBook_table" class="display compact" border=1 style="width:100%">
  <thead>
    <tr>
      <td colspan="7">Book List for Shipment</td>
    </tr>
    <tr>
      <td align='left'>ID</td>
      <td align='left'>Title</td>
      <td align='left'>Author</td>
      <td align='left'>Publisher</td>
      <td align='left'>Price</td>
      <td align='left'>Grade Level</td>
      <td align='left'>Quantity</td>
    </tr>
  </thead>
      </table>
</div>

<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
<center>
    <form action='admin_addassignschooluserrole.php' method='POST'>
    <table id="" class="display compact" style="width:50%" cellpadding=5  border=1>
  <tr style="background-color:darkgray; color:black; font-weight:bold"><td colspan="2">Select School</td></tr>
  <tr>
  <td>Select School</td>
  <td>
    <select id="ddlSchool" name="ddlSchool">
      <option>Select</option>
      <?php
        if ($sqlSchool->num_rows > 0) 
        {
          // Create table with data from each row
          while($row = $sqlSchool->fetch_assoc()) 
          {
            $School = $row["name"].' '.$row["type"].'('.$row["category"].')'; 
            $School_ID_TYPE = $row["id"].'*'.$row["type"];
            echo " <option value='".$School_ID_TYPE."'>".$School."</option>";
          }
        }
      ?>
    </select>
  </td>
  </tr>
  <tr>
  <td>Shipment Date</td>
  <td>
   <input type="date" id="shipDate" name="shipDate" style="width:76%; padding:10px">
  </td>
  </tr>
 <tr>
  <td></td>
  <td>
    <input type="button" value="Ship Now" name="Save" id="Save">
    <input type="button"  value="Cancel">
  </td>
  </tr>
</table>
      </form>
 </center>
</div>
  </body>
<script>
$(document).ready(function () 
{
        $('#Save').click(function()
        {
          var formDate =new FormData();
          var school_id = document.getElementById("ddlSchool").value;
          var ship_date   = document.getElementById("shipDate").value;
          if(school_id=="Select" || ship_date=="")
          {
            if(school_id=="Select")
            {
              document.getElementById("ddlSchool").focus();
              document.getElementById("ddlSchool").style.border="solid 1px red";
              
              return false;
            }
            if(ship_date=="")
            {
              document.getElementById("shipDate").focus();
              document.getElementById("ddlSchool").style.border="solid 1px green";
              document.getElementById("shipDate").style.border="solid 1px red";
              return false;
            }
          }
          else
          {
            const selectedTable = document.getElementById('CloneBook_table');
            if(selectedTable.rows.length>2)
            {
              var schoolarr = school_id.split('*');
              const data = [];
              for (let i = 2; i < selectedTable.rows.length; i++)
              {
                const row = selectedTable.rows[i];
                const rowData = 
                {
                  Book_ID       : row.cells[0].textContent,
                  Book_Price    : row.cells[4].textContent,
                  School_ID     : schoolarr[0],
                  SchoolType    : schoolarr[1],
                  Quantity      : parseInt(row.cells[6].querySelector('input[type="number"]').value),
                  ShipmentDate  : ship_date
                };
                data.push(rowData);
              }
              const url = "addshipment.php";
              const xhr = new XMLHttpRequest();
              xhr.open("POST", url);
              xhr.setRequestHeader("Content-Type", "application/json");
              xhr.onload = function() 
              {
                if (xhr.status === 200) 
                {
                  //const response = JSON.parse(xhr.responseText);
                  alert("your book information has been shipped to school");
                  window.location.reload();
                } 
                else 
                {
                  console.error("Error:", xhr.status);
                }
              };
              xhr.onerror = function() 
              {
                console.error("Request failed.");
              };
              xhr.send(JSON.stringify(data));
            }
          }
        });
});

</script>
</html>
