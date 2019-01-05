<?php
session_start();
require "staffauth.php";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo $PageTitle; ?></title>
<link rel="stylesheet" type="text/css" href="styles.css">
<link rel="icon" type="image/ico" href="favicon.ico">
<?php echo isset($script) ? $script : "" ; // Echo header script if one exists (JavaScript Validation etc)?>
</head>

<body>
<div class="navbar">
    <div class="menu" style="width: 10%;"><a href="staffhome"><p>Corral</p></a>
    </div><div class="menu" style="width: 15%;"><a href="#"><p>Users</p></a>
        <ul>
            <li><a href="studentlist"><p>Students</p></a></li>
            <li><a href="stafflist"><p>Staff</p></a></li>
        </ul>
    </div><div class="menu" style="width: 15%;"><a href="#"><p>Units</p></a>
        <ul>
            <li><a href="unit"><p>Add Unit</p></a></li>
            <li><a href="unitlist"><p>View Units</p></a></li>
        </ul>
    </div><div class="menu" style="width: 15%;"><a href="#"><p>Projects</p></a>
        <ul>
            <li><a href="project?number="><p>Add Project</p></a></li>
            <li><a href="projectlist"><p>View Projects</p></a></li>
            <li><a href="grouplist"><p>Project Groups</p></a></li>
        </ul>
    </div><div class="menu" style="width: 15%;"><a href ="#"><p>Admin</p></a>
        <ul>
            <li><a href="datamgmt"><p>Manage Corral Data</p></a></li>
            <li><a href="begin" target="_blank"><p>Begin Sort</p></a></li>
            <li><a href="sortedgroups"><p>Sort Results</p></a></li>
        </ul>
    </div><div class="menuGap" style="width: 20%;">
        <form action="search" method="get">
            <input type="text" placeholder="Search.." name="search" class='updateBox'>
        </form>
    </div><div class="menuGap" style="width: 10%;">
        <form action="logout" method="get">
            <button class="updateButton">Logout</button>
        </form>
    </div>
</div>
<div class="main">
