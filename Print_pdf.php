<?php 
require '../lang.php';
?>

<!DOCTYPE html>
<html lang="tr">
  
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>PDF</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('fonts/DejaVuSans.ttf');
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;      
            margin-top: 50px;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        #makeupDaysContainer {
            display: none;
        }

        input[type="radio"]:checked + label + #makeupDaysContainer {
            display: block;
        }
        
        h1 {
            font-size: 40px;
        }
        
        .headings {
            text-align: center;
            margin-top: 100px;
        }
        
        .logo {
            width: 50px;
            height: auto;
            position: absolute;
            top: 20px;
            left: 20px;
        }
    </style>
</head>
<body>

<div class="headings">
    <h1 class="mt-5"><?php echo __("Final International University")?></h1>
    <h2 class="mb-4"><?php echo __("Academic Personnel Form")?></h2>
</div>

<table>
<tr>
    <th rowspan="3" class="yes">
     <?php echo __("Identity Information")?>
    </th>

    <th class="table-cell">
        <?php echo __("Full Name")?>
    </th>
    <th>
     <?=htmlspecialchars($row['FullName'], ENT_QUOTES, 'UTF-8')?>
    </th>
  </tr>
  <tr>
    <th class="table-cell">
    <?php echo __("Passport no")?>
    </th>

    <th>
    <?php echo isset($row['passport_no']) ? htmlspecialchars($row['passport_no'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?>        
</th>
  </tr>
 
  <tr>
    <th class="table-cell">
    <?php echo __("Faculty/Department")?>
    </th>  
    <th>
    <?=htmlspecialchars(__($row['Unit']), ENT_QUOTES, 'UTF-8')?> 
    </th>  
  </tr>
    <tr>
    <th rowspan="2" class="yes">
      
    <?php echo __("Permission Type")?> 
    </th>
      
      <th colspan="2" class="table-cell5">
    <?php if ($row['input'] == "Annual-Leave"): ?>
    <div>
        <label><?php echo __("Annual-Leave")?></label>
    </div>
    <?php endif; ?>
    
    <?php if ($row['input'] == "excuse-leave"): ?>
    <div>
        <label><?php echo __("Excuse-leave")?></label>
    </div>
    <?php endif; ?>
    <?php if ($row['input'] == "unpaid-leave"): ?>
    <div>
        <label><?php echo __("Unpaid-leave")?></label>
    </div>
    <?php endif; ?>
    <?php if ($row['input'] == "sick-leave"): ?>
    <div>
        <label><?php echo __("Sick-leave")?></label>
    </div>
    <?php endif; ?>
    <?php if ($row['input'] == "other"): ?>
    <div>
        <label><?php echo __("Other")?></label>
    </div>

    <?php endif; ?>
</th>
  </tr>      
  <tr class="table-cell">
<th colspan="2" class="table-cell1">
<?php if ($row['input'] != "other"): ?>
  <?php echo __("N/A")?>
<?php else: ?>
  <label for="label6" class="label6"><?php echo __("Reason for requesting permission: ")?></label>
  <?=htmlspecialchars($row['RequestTest'], ENT_QUOTES, 'UTF-8')?> 
<?php endif; ?>
</th>
</tr>  
  <tr>
    <th rowspan="5" class="yes"><?php echo __("Permission Date and Address")?></th>
    <th class="table-cell2"><?php echo __("Permit start date")?></th>
    <th> <?=htmlspecialchars($row['PermitStartDate'], ENT_QUOTES, 'UTF-8')?> </th>            
  </tr>
  <tr>
    <th  class="table-cell2"> <?php echo __("Leave end date")?></th>
    <th> <?=htmlspecialchars($row['LeaveExpiryDate'], ENT_QUOTES, 'UTF-8')?> </th>        
  </tr>


  <tr>
    <th  class="table-cell2"><?php echo __("Person to represent")?> </th>
    <th><?=htmlspecialchars($row['PersonToRepresent'], ENT_QUOTES, 'UTF-8')?></th>
    
  </tr>


  <tr>
    <th  class="table-cell2"><?php echo __("Address")?></th>
    <th><?=htmlspecialchars($row['Address'], ENT_QUOTES, 'UTF-8')?></th>

  </tr>


  <tr>
    <th  class="table-cell2"><?php echo __("Phone Number")?></th>
    <th> <?=htmlspecialchars($row['Phone'], ENT_QUOTES, 'UTF-8')?></th>
    
  </tr>


  <tr>
<th colspan="3" class="table-cell3">
  <?php $classDuringLeave = trim($row['ClassDuringLeave']); ?>
<?php if ($classDuringLeave == 'yes' && (!empty($row['MakeUpDays']))): ?>
          <div>
          <?php echo __("I have classes during my leave of absence: yes")?><br><br>
          <?php echo __("Make up days: ")?><?=htmlspecialchars($row['MakeUpDays'], ENT_QUOTES, 'UTF-8')?>
        <br>        
      </div>
    <?php endif; ?>
    
    <?php if ($classDuringLeave == 'no'): ?>
        <div>
        <?php echo __("I have classes during my leave of absence: no")?>
        </div>
      <?php endif; ?>

   
</th>
</tr>

  <tr class="table-cell5">     
    <th colspan="3" class="table-cell3" class="date5">
      <label><?php echo __("Total days: ")?></label>
      <?=htmlspecialchars($row['Dayoff'], ENT_QUOTES, 'UTF-8')?>
      <br>
    
    </th>
  </tr>      
      <tr>

<th colspan="3" class="table-cell5">
    <?php if ($row['faculty_id'] == 1): ?>
    <div>
        <label><?php echo __("Dean : Prof. Dr. Orhan Gemikonakli (Engineering)")?></label>
    </div>
    <?php endif; ?>
    
    <?php if ($row['faculty_id'] == 2): ?>
    <div>
        <label><?php echo __("Dean : Prof. Dr. Mustafa Senol Tüzüm (Dentistry)")?></label>
    </div>
    <?php endif; ?>
    <?php if ($row['faculty_id'] == 3): ?>
    <div>
        <label><?php echo __("Dean : prof. Dr. Şermin Trigger (Pharmacy)")?></label>
    </div>
    <?php endif; ?>

    <?php if ($row['faculty_id'] == 4): ?>
    <div>
        <label><?php echo __("Dean : prof. Dr. Nazife Aydınoglu (Educational Sciences)")?></label>
    </div>
    <?php endif; ?>

    <?php if ($row['faculty_id'] == 5): ?>
    <div>
        <label><?php echo __("Dean : prof. Dr. Abdulkadir Ozer (Arts and Sciences)")?></label>
    </div>
    <?php endif; ?>

    <?php if ($row['faculty_id'] == 6): ?>
    <div>
        <label><?php echo __(" Dean : prof. Dr. Mehmet Merdan Hekimoğlu (Law)")?></label>
    </div>
    <?php endif; ?>

    <?php if ($row['faculty_id'] == 7): ?>
    <div>
        <label><?php echo __("Dean : Prof. Dr. Sule Aker (Economics and Administrative Sciences)")?></label>
    </div>
    <?php endif; ?>

    <?php if ($row['faculty_id'] == 8): ?>
    <div>
        <label><?php echo __("Dean :prof. Dr. Zeynep Ustun Onur (Architecture and Fine Arts)")?></label>
    </div>
    <?php endif; ?>

    <?php if ($row['faculty_id'] == 9): ?>
    <div>
        <label><?php echo __("Dean :prof. Dr. Oya Uygur Bayramicli (Health Sciences)")?></label>
    </div>
    <?php endif; ?>



    
</th>

</tr>
<tr>
<th colspan="3" class="table-cell5"> 
    <div style="margin-bottom: 10px;">
        <label><?php echo __("Department: ")?></label>
        <?=htmlspecialchars(__($row['Department']), ENT_QUOTES, 'UTF-8')?>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label><?php echo __("Human Resource: ")?></label>
        <?=htmlspecialchars(__($row['HumanResource']), ENT_QUOTES, 'UTF-8')?>
    </div>
    
    <div>
        <label><?php echo __("Rectorate: ")?></label>
        <?=htmlspecialchars(__($row['Rectorate']), ENT_QUOTES, 'UTF-8')?>
    </div>
</th>
</tr>
</table>
</body>
</html>