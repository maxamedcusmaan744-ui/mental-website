<?php
// ================= DATABASE CONNECTION =================
if (!class_exists('Connection')) {

    class Connection {

        private $host = 'localhost';
        private $db_name = 'mental_health_support';
        private $user_name = 'root';
        private $password = '';

        public $db;

        public function __construct() {

            try {

                $this->db = new PDO(
                    "mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4",
                    $this->user_name,
                    $this->password
                );

                $this->db->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

            } catch(PDOException $e){

                die("DB connection failed: ".$e->getMessage());

            }
        }
    }
}


if(!isset($conn)){

    $database = new Connection();
    $conn = $database->db;

}


// ================= GET NURSES =================
// nurses.user_id points to users.user_id

$stmt = $conn->prepare("
    SELECT
        n.*,
        u.full_name,
        u.phone,
        u.profile_pic,
        d.department_name

    FROM nurses n

    INNER JOIN users u
    ON n.user_id = u.user_id

    LEFT JOIN departments d
    ON n.department_id = d.department_id

    ORDER BY n.nurse_id DESC
");


$stmt->execute();

$nurses = $stmt->fetchAll(PDO::FETCH_ASSOC);


$totalNurses = count($nurses);

$morningNurses = count(array_filter($nurses, fn($n) => $n['shift'] === 'Morning'));

?>



<style>

:root {

--orange-main:#f97316;
--orange-dark:#ea580c;
--orange-light:#fb923c;
--text-dark:#0f172a;
--text-muted:#64748b;

--card-bg:rgba(255,255,255,.55);
--card-border:rgba(255,255,255,.7);

--tr-bg:rgba(255,255,255,.7);
--tr-hover:rgba(249,115,22,.06);

--shadow-soft: 0 8px 32px rgba(15,23,42,.06);
--shadow-hover: 0 16px 40px rgba(234,88,12,.15);

}



[data-bs-theme="dark"]{

--card-bg:rgba(15,23,42,.55);

--card-border:rgba(255,255,255,.08);

--text-dark:#f8fafc;

--text-muted:#94a3b8;

--tr-bg:rgba(30,41,59,.6);
--tr-hover:rgba(249,115,22,.08);

--shadow-soft: 0 8px 32px rgba(0,0,0,.25);

}



/* ================= CARD ================= */

.glass-card{

position:relative;
background:var(--card-bg)!important;

backdrop-filter:blur(18px) saturate(160%);
-webkit-backdrop-filter:blur(18px) saturate(160%);

border:1px solid var(--card-border)!important;
border-radius:26px;

box-shadow:var(--shadow-soft);

overflow:hidden;

transition:transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s ease;

}

.glass-card::before{

content:"";
position:absolute;
inset:0;
background:linear-gradient(135deg, rgba(249,115,22,.08), transparent 55%);
opacity:0;
transition:opacity .35s ease;
pointer-events:none;

}

.glass-card:hover{

transform:translateY(-6px) scale(1.01);
box-shadow:var(--shadow-hover);

}

.glass-card:hover::before{
opacity:1;
}

.glass-card:active{
transform:translateY(-2px) scale(.99);
}



/* ================= MODAL ================= */

.modal-content-glass{

background:var(--card-bg)!important;

backdrop-filter:blur(30px) saturate(180%);
-webkit-backdrop-filter:blur(30px) saturate(180%);

border-radius:28px;

border:1px solid var(--card-border)!important;

box-shadow:0 24px 64px rgba(15,23,42,.25);

animation: modalPop .35s cubic-bezier(.22,1,.36,1);

}

@keyframes modalPop{
from{opacity:0; transform:scale(.96) translateY(8px);}
to{opacity:1; transform:scale(1) translateY(0);}
}

.modal-header-glass{

background:linear-gradient(135deg, rgba(249,115,22,.08), rgba(249,115,22,0));

}



/* ================= ICON ================= */

.stats-icon{

width:54px;
height:54px;
border-radius:16px;

display:flex;
align-items:center;
justify-content:center;

transition:transform .35s cubic-bezier(.22,1,.36,1);

}

.glass-card:hover .stats-icon{
transform:rotate(-6deg) scale(1.08);
}

.bg-hospital-orange{

background:linear-gradient(135deg, rgba(251,146,60,.18), rgba(234,88,12,.12))!important;
color:var(--orange-dark)!important;

}



/* ================= SEARCH ================= */

.staff-search{

background:var(--tr-bg);
border:1px solid var(--card-border);
border-radius:14px;
padding:.6rem 1rem .6rem 2.6rem;
font-size:.9rem;
color:var(--text-dark);
transition:.25s;

}

.staff-search:focus{

outline:none;
border-color:var(--orange-light);
box-shadow:0 0 0 4px rgba(249,115,22,.12);

}

.search-wrap{
position:relative;
}

.search-wrap i{
position:absolute;
left:.9rem;
top:50%;
transform:translateY(-50%);
color:var(--text-muted);
font-size:.9rem;
}



/* ================= TABLE ================= */


.custom-table{

border-collapse:separate;
border-spacing:0 10px;

}



.custom-table thead th{

font-size:.7rem;
letter-spacing:.06em;
text-transform:uppercase;
color:var(--text-muted);
border:none;
font-weight:700;
padding:0 1rem .5rem 1rem;

}



.custom-table tbody tr{

background:var(--tr-bg)!important;
transition:.25s ease;

}

.custom-table tbody tr:hover{

background:var(--tr-hover)!important;
transform:translateY(-2px);
box-shadow:0 6px 18px rgba(15,23,42,.06);

}



.custom-table tbody td{

padding:.9rem 1rem;
border:none;
vertical-align:middle;
color:var(--text-dark);

}



.custom-table tbody tr td:first-child{

border-radius:16px 0 0 16px;
padding-left:1.25rem;

}


.custom-table tbody tr td:last-child{

border-radius:0 16px 16px 0;
padding-right:1.25rem;

}



/* ================= IMAGE ================= */


.user-img-wrap{
position:relative;
display:inline-block;
}

.user-img{

width:44px;
height:44px;
border-radius:13px;
object-fit:cover;
border:2px solid white;
box-shadow:0 2px 8px rgba(15,23,42,.12);

}

.status-dot{

position:absolute;
bottom:-2px;
right:-2px;
width:12px;
height:12px;
border-radius:50%;
border:2px solid var(--tr-bg);

}

.status-dot.active{ background:#10b981; }
.status-dot.inactive{ background:#94a3b8; }



/* ================= STATUS PILL ================= */


.status-pill{

padding:6px 14px;
border-radius:20px;
font-size:.72rem;
font-weight:700;
display:inline-flex;
align-items:center;
gap:6px;

}


.status-pill::before{
content:"";
width:6px;
height:6px;
border-radius:50%;
}


.bg-success-subtle{

background:rgba(16,185,129,.14)!important;
color:#059669!important;

}

.bg-success-subtle::before{ background:#10b981; }

[data-bs-theme="dark"] .bg-success-subtle{ color:#34d399!important; }


.bg-secondary-subtle{

background:rgba(100,116,139,.14)!important;
color:#64748b!important;

}

.bg-secondary-subtle::before{ background:#94a3b8; }

[data-bs-theme="dark"] .bg-secondary-subtle{ color:#94a3b8!important; }

.bg-warning-subtle{

background:rgba(245,158,11,.16)!important;
color:#d97706!important;

}

.bg-warning-subtle::before{ background:#f59e0b; }

[data-bs-theme="dark"] .bg-warning-subtle{ color:#fbbf24!important; }

.bg-info-subtle{

background:rgba(14,165,233,.14)!important;
color:#0284c7!important;

}

.bg-info-subtle::before{ background:#0ea5e9; }

[data-bs-theme="dark"] .bg-info-subtle{ color:#38bdf8!important; }



.live-pulse{

animation:pulse 1.8s ease-in-out infinite;

}


@keyframes pulse{

0%,100%{opacity:1; transform:scale(1);}
50%{opacity:.45; transform:scale(1.3);}

}


/* Badge with count */
.count-badge{

background:var(--orange-dark);
color:#fff;
font-size:.68rem;
font-weight:700;
padding:2px 9px;
border-radius:20px;
margin-left:8px;

}


/* Empty state */
.empty-state{
padding:3rem 1rem;
color:var(--text-muted);
}

.empty-state i{
font-size:2.5rem;
opacity:.35;
display:block;
margin-bottom:.75rem;
}


/* Scrollbar polish inside modal */
.table-responsive::-webkit-scrollbar{ height:6px; width:6px; }
.table-responsive::-webkit-scrollbar-thumb{
background:rgba(234,88,12,.3);
border-radius:10px;
}

</style>




<!-- ================= CARD ================= -->


<div class="col-xl-3 col-md-6">


<div class="card glass-card p-4 border-0 h-100"

data-bs-toggle="modal"

data-bs-target="#nursesModal"

style="cursor:pointer;">



<div class="d-flex justify-content-between">


<div>

<p class="text-muted small fw-bold text-uppercase mb-1">

Total Nurses

</p>


<h2 class="fw-bold mb-1"

style="color:var(--text-dark)">

<?=number_format($totalNurses)?>

</h2>


<span class="small fw-bold"

style="color:var(--orange-dark)">

<i class="bi bi-circle-fill live-pulse"

style="font-size:8px"></i>

Live Database

</span>


</div>



<div class="stats-icon bg-hospital-orange">

<i class="bi bi-person-heart fs-4"></i>

</div>



</div>


</div>


</div>





<!-- ================= MODAL ================= -->


<div class="modal fade"

id="nursesModal"

tabindex="-1">


<div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">


<div class="modal-content modal-content-glass">



<div class="modal-header modal-header-glass px-4 py-3"

style="border-bottom:1px solid var(--card-border)">



<div class="d-flex align-items-center gap-2">


<div class="stats-icon bg-hospital-orange"

style="width:42px;height:42px;border-radius:13px">


<i class="bi bi-person-heart"></i>


</div>



<div>

<h5 class="modal-title fw-bold mb-0"

style="color:var(--text-dark)">

Nurses

<span class="count-badge"><?=number_format($totalNurses)?></span>

</h5>

<small class="text-muted"><?=number_format($morningNurses)?> morning shift</small>

</div>



</div>



<button class="btn-close"

data-bs-dismiss="modal"></button>



</div>



<div class="px-4 pt-3">

<div class="search-wrap">

<i class="bi bi-search"></i>

<input type="text"

id="nursesSearchInput"

class="staff-search w-100"

placeholder="Search by nurse name, department, qualification or shift...">

</div>

</div>




<div class="modal-body p-4 pt-3">


<div class="table-responsive">


<table class="table text-center custom-table" id="nursesTable">


<thead>


<tr>

<th>Profile</th>

<th>Name</th>

<th>Phone</th>

<th>Department</th>

<th>Qualification</th>

<th>Experience</th>

<th>Shift</th>

<th>Hire Date</th>

<th>Salary</th>


</tr>


</thead>



<tbody>



<?php if(empty($nurses)): ?>


<tr>

<td colspan="9">

<div class="empty-state">

<i class="bi bi-person-x"></i>

No nurses found.

</div>

</td>

</tr>


<?php else: ?>



<?php foreach($nurses as $n): ?>


<?php

$profilePic = $n['profile_pic'] ?? '';

if(!empty($profilePic) &&
file_exists(__DIR__."/../forms/uploads/".$profilePic)){


if(basename($_SERVER['PHP_SELF'])=="home.php"){

$imgFinal="forms/uploads/".$profilePic;

}else{

$imgFinal="../forms/uploads/".$profilePic;

}


}else{


$imgFinal="https://ui-avatars.com/api/?background=1b2a4a&color=fff&bold=true&name=".urlencode($n['full_name']);


}

$shift = $n['shift'] ?? 'Morning';

if($shift === 'Morning'){
    $shiftClass = 'bg-success-subtle';
}elseif($shift === 'Afternoon'){
    $shiftClass = 'bg-warning-subtle';
}else{
    $shiftClass = 'bg-info-subtle';
}

$departmentName = $n['department_name'] ?? 'No Department';
$hireDate = !empty($n['hire_date']) ? date('M d, Y', strtotime($n['hire_date'])) : 'N/A';
$salary = number_format((float)($n['salary'] ?? 0), 2);

?>



<tr>


<td>

<span class="user-img-wrap">

<img class="user-img"

src="<?=htmlspecialchars($imgFinal)?>">

<span class="status-dot active"></span>

</span>

</td>



<td class="fw-semibold text-start">

<?=htmlspecialchars($n['full_name'])?>

</td>



<td>

<?=htmlspecialchars($n['phone'])?>

</td>



<td>

<?=htmlspecialchars($departmentName)?>

</td>



<td class="text-start">

<?=htmlspecialchars($n['qualification'])?>

</td>



<td>

<?=number_format((int)($n['experience_years'] ?? 0))?> Years

</td>



<td>

<span class="status-pill <?=$shiftClass?>">

<?=htmlspecialchars($shift)?>

</span>

</td>



<td>

<?=htmlspecialchars($hireDate)?>

</td>



<td>

$<?=htmlspecialchars($salary)?>

</td>


</tr>




<?php endforeach; ?>


<?php endif; ?>


</tbody>


</table>


</div>


</div>



</div>


</div>


</div>



<script>
(function(){
  var input = document.getElementById('nursesSearchInput');
  if(!input) return;

  input.addEventListener('input', function(){
    var q = this.value.trim().toLowerCase();
    var rows = document.querySelectorAll('#nursesTable tbody tr');

    rows.forEach(function(row){
      var text = row.innerText.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  });
})();
</script>
