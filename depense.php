<?php
$conn = new mysqli("localhost", "root", "", "gestion_depenses");
if ($conn->connect_error) die("Erreur connexion");

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM depenses WHERE id=$id");
    header("Location: depense.php");
}

/* ================= AJAX DEPOT ================= */
if (isset($_POST['save_depot'])) {

    $mois = $_POST['mois'];
    $montant = $_POST['montant'];

    $check = $conn->query("SELECT * FROM depot_mensuel WHERE mois='$mois'");

    if ($check->num_rows > 0) {
        $conn->query("UPDATE depot_mensuel SET montant='$montant' WHERE mois='$mois'");
    } else {
        $conn->query("INSERT INTO depot_mensuel(mois, montant) VALUES('$mois','$montant')");
    }

    exit;
}

/* ================= INSERT ================= */
if (isset($_POST['submit'])) {

    $date = $_POST['date'];
    $description = $_POST['description'];
    $montant = $_POST['montant'];
    $remarque = $_POST['remarque'];

    $conn->query("INSERT INTO depenses(date, description, montant, remarque)
    VALUES('$date','$description','$montant','$remarque')");

    header("Location: depense.php");
}

/* ================= UPDATE ================= */
if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $montant = $_POST['montant'];
    $remarque = $_POST['remarque'];

    $conn->query("UPDATE depenses SET 
        date='$date',
        description='$description',
        montant='$montant',
        remarque='$remarque'
        WHERE id=$id");

    header("Location: depense.php");
}

/* ================= EDIT ================= */
$edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit = $conn->query("SELECT * FROM depenses WHERE id=$id")->fetch_assoc();
}

/* ================= FILTER ================= */
$filter_date = $_GET['filter_date'] ?? '';
$filter_desc = $_GET['filter_desc'] ?? '';

$sql = "SELECT * FROM depenses WHERE 1=1";
if ($filter_date) $sql .= " AND date='$filter_date'";
if ($filter_desc) $sql .= " AND description LIKE '%$filter_desc%'";
$sql .= " ORDER BY date ASC";

$result = $conn->query($sql);

/* ================= DEPOT ================= */
$depot = [];
$resDepot = $conn->query("SELECT * FROM depot_mensuel");
while ($d = $resDepot->fetch_assoc()) {
    $depot[$d['mois']] = $d['montant'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion Dépenses</title>

<style>
body{font-family:Arial;background:#eef2f7;padding:20px}

.container{display:flex;gap:20px}

.form-box,.table-box{
background:#fff;padding:20px;border-radius:10px;
box-shadow:0 3px 10px rgba(0,0,0,0.1)
}

.form-box{width:30%}
.table-box{width:70%}

.table-scroll{
max-height:450px;
overflow-y:auto;
border:1px solid #ddd
}

table{
width:100%;
border-collapse:collapse
}

th,td{
padding:8px;
border-bottom:1px solid #ddd;
text-align:center
}

th{
background:#007BFF;
color:white;
position:sticky;
top:0
}

input,textarea{
width:100%;
padding:8px;
margin-bottom:8px
}

button{
width:100%;
padding:10px;
border:none;
color:white;
cursor:pointer
}

.add{background:#007BFF}
.update{background:#28a745}

.edit{background:orange;color:white;padding:5px;border-radius:5px}
.delete{background:red;color:white;padding:5px;border-radius:5px}

.total{
background:#d9d9d9;
font-weight:bold
}

.depot{
background:#28a745;
color:white;
font-weight:bold
}
</style>
</head>

<body>

<div class="container">

<!-- FORM -->
<div class="form-box">

<h3><?= $edit ? "Modifier" : "Ajouter" ?></h3>

<form method="POST">

<?php if($edit){ ?>
<input type="hidden" name="id" value="<?= $edit['id'] ?>">
<?php } ?>

<input type="date" name="date" value="<?= $edit['date'] ?? '' ?>" required>

<input type="text" name="description" value="<?= $edit['description'] ?? '' ?>" required>

<input type="number" step="0.01" name="montant" required>

<textarea name="remarque"><?= $edit['remarque'] ?? '' ?></textarea>

<?php if($edit){ ?>
<button class="update" name="update">Modifier</button>
<?php } else { ?>
<button class="add" name="submit">Ajouter</button>
<?php } ?>

</form>

</div>

<!-- TABLE -->
<div class="table-box">

<h3>Liste des dépenses</h3>

<form method="GET">
<input type="date" name="filter_date" value="<?= $filter_date ?>">
<input type="text" name="filter_desc" value="<?= $filter_desc ?>">
<button>Filtrer</button>
</form>

<div class="table-scroll">

<table>

<tr>
<th>ID</th>
<th>Date</th>
<th>Description</th>
<th>Montant</th>
<th>Remarque</th>
<th>Action</th>
</tr>

<?php
$current_month = "";
$month_total = 0;
?>

<?php while($row = $result->fetch_assoc()) { ?>

<?php $month = date("Y-m", strtotime($row['date'])); ?>

<!-- SOUS-TOTAL FIN MOIS -->
<?php if($current_month != "" && $current_month != $month){ ?>
<tr class="total">
<td colspan="3">🧾 SOUS-TOTAL <?= $current_month ?></td>
<td colspan="3"><b><?= number_format($month_total,2) ?></b></td>
</tr>
<?php
$month_total = 0;
}
?>

<!-- DEPOT -->
<?php if($current_month != $month){ ?>
<tr class="depot">
<td colspan="3">💰 DEPOT <?= $month ?></td>
<td colspan="3">
<input type="number"
value="<?= $depot[$month] ?? 0 ?>"
onchange="saveDepot('<?= $month ?>', this.value)">
</td>
</tr>
<?php } ?>

<!-- LIGNE DEPENSE -->
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['date'] ?></td>
<td><?= $row['description'] ?></td>
<td><?= number_format($row['montant'],2) ?></td>
<td><?= $row['remarque'] ?></td>
<td>
<a class="edit" href="?edit=<?= $row['id'] ?>">Edit</a>
<a class="delete" href="?delete=<?= $row['id'] ?>">Del</a>
</td>
</tr>

<?php
$current_month = $month;
$month_total += $row['montant'];
?>

<?php } ?>

<!-- dernier mois -->
<?php if($current_month != "") { ?>
<tr class="total">
<td colspan="3">🧾 SOUS-TOTAL <?= $current_month ?></td>
<td colspan="3"><b><?= number_format($month_total,2) ?></b></td>
</tr>
<?php } ?>

</table>

</div>

</div>

</div>

<script>
function saveDepot(mois, montant){

let f = new FormData();
f.append("save_depot",1);
f.append("mois",mois);
f.append("montant",montant);

fetch("depense.php",{method:"POST",body:f});
}
</script>

</body>
</html>