<?php
$currentPage = 'facilities';
$facilities = [];

try {
    require_once __DIR__ . '/../../config/db.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT facility_id, facility_name, facility_type, location, capacity, description, price_per_day, resource_status FROM facilities WHERE LOWER(TRIM(facility_name)) <> 't06' ORDER BY facility_name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $facilities[] = [
                'id' => (int)$row['facility_id'],
                'name' => $row['facility_name'] ?: 'Unnamed Facility',
                'type' => strtolower((string)($row['facility_type'] ?? 'facility')),
                'location' => $row['location'] ?: 'Unknown location',
                'capacity' => (int)($row['capacity'] ?? 0),
                'description' => $row['description'] ?: 'No description provided.',
                'cost' => (float)($row['price_per_day'] ?? 0),
                'available' => (($row['resource_status'] ?? 'available') === 'available'),
                'image' => 'facility_image.php?id=' . (int)$row['facility_id'],
            ];
        }
    }
} catch (Throwable $e) {
    $facilities = [];
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Facilities - UNIRESERVE</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--primary-color:#8b1538;--primary-hover:#a01d48;--accent-color:#ffc107;--text-dark:#333;--text-light:#666;--border-light:#e0e0e0;--white:#fff;--bg-light:#f5f5f5;--success:#388e3c;--warning:#ff9800;--danger:#d32f2f}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Roboto','Oxygen','Ubuntu',sans-serif;color:var(--text-dark);background:var(--bg-light)}
.breadcrumb{padding:16px 30px;background:var(--white);border-bottom:1px solid var(--border-light);font-size:13px;color:var(--text-light)}
.breadcrumb a{color:var(--primary-color);text-decoration:none;cursor:pointer}
.breadcrumb a:hover{text-decoration:underline}
.main-container{display:flex;max-width:1400px;margin:30px auto;gap:30px;padding:0 30px}
.sidebar{flex:0 0 250px}
.filter-section{background:var(--white);padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.filter-title{font-size:16px;font-weight:700;color:var(--text-dark);margin-bottom:16px;text-transform:uppercase;letter-spacing:.5px}
.filter-options{display:flex;flex-direction:column;gap:12px}
.checkbox-item{display:flex;align-items:center;gap:8px}
.checkbox-item input[type="checkbox"]{width:18px;height:18px;cursor:pointer;accent-color:var(--primary-color)}
.checkbox-item label{font-size:14px;color:var(--text-dark);cursor:pointer;margin:0}
.select-input,.sort-select{width:100%;padding:10px;border:1px solid var(--border-light);border-radius:4px;font-size:14px;background:var(--white);cursor:pointer}
.btn-apply,.btn-reset{width:100%;padding:12px;border-radius:4px;font-weight:600;cursor:pointer;transition:all .3s}
.btn-apply{background:var(--primary-color);color:var(--white);border:none;margin-bottom:12px}
.btn-apply:hover{background:var(--primary-hover)}
.btn-reset{background:var(--white);color:var(--text-dark);border:1px solid var(--border-light)}
.btn-reset:hover{border-color:var(--primary-color);color:var(--primary-color)}
.content-area{flex:1}
.content-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.content-title{font-size:28px;font-weight:700;color:var(--text-dark)}
.content-subtitle{font-size:14px;color:var(--text-light);margin-bottom:8px}
.sort-container{display:flex;align-items:center;gap:12px}.sort-order-btn{padding:10px 14px;border:1px solid var(--primary-color);background:var(--white);color:var(--primary-color);border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}.sort-order-btn:hover{background:var(--primary-color);color:#fff}
.sort-label{font-size:14px;color:var(--text-light);font-weight:600}
.room-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.room-card{background:var(--white);border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);transition:all .3s ease}
.room-card:hover{transform:translateY(-4px);box-shadow:0 8px 16px rgba(0,0,0,.12)}
.room-image{position:relative;height:200px;background:#ddd;overflow:hidden}
.room-image img{width:100%;height:100%;object-fit:cover}
.room-label{position:absolute;top:12px;left:12px;background:rgba(0,0,0,.7);color:#fff;padding:6px 12px;border-radius:4px;font-size:12px;font-weight:600}
.room-badge{position:absolute;top:12px;right:12px;color:#fff;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600}
.room-badge.available{background:var(--success)}
.room-badge.unavailable{background:var(--danger)}
.room-content{padding:20px}
.room-name{font-size:18px;font-weight:700;margin:0 0 8px}
.room-capacity{font-size:13px;color:var(--text-light);margin-bottom:12px}
.room-description{font-size:13px;color:var(--text-light);margin-bottom:16px;line-height:1.5}
.room-footer{display:flex;gap:12px;padding-top:16px;border-top:1px solid var(--border-light)}
.btn-view-details,.btn-book{flex:1;padding:10px;border-radius:4px;font-weight:600;font-size:13px;cursor:pointer}
.btn-view-details{border:1px solid var(--primary-color);color:var(--primary-color);text-decoration:none;text-align:center}
.btn-book{background:var(--primary-color);color:#fff;border:none}
.btn-book:disabled{background:#ccc;cursor:not-allowed}
.no-results{background:var(--white);border-radius:8px;padding:40px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.no-results-icon{font-size:48px;margin-bottom:12px}
</style></head>
<body>
<?php include '../../includes/header.php'; ?>
<div class="breadcrumb"><a href="/homepage.php">Home</a><span> > </span><span>Facilities</span></div>
<div class="main-container">
<div class="sidebar">
<div class="filter-section"><div class="filter-title">Facility Type</div><select class="select-input" id="facility-type-filter"><option value="">All Types</option></select></div>
<button class="btn-apply" onclick="applyFilters()">Apply Filters</button><button class="btn-reset" onclick="resetFilters()">Reset All</button>
</div>
<div class="content-area"><div class="content-header"><div><h2 class="content-title">Available Facilities</h2><p class="content-subtitle">Showing <span id="facility-count">0</span> facilities</p></div>
<div class="sort-container"><label class="sort-label">Sort by:</label><select class="sort-select" id="facility-sort-field" onchange="sortFacilities(this.value)"><option value="relevance">Relevance</option><option value="name">Name</option><option value="price">Price</option><option value="capacity">Capacity</option></select><button type="button" class="sort-order-btn" id="facility-sort-order" onclick="toggleFacilitySortDirection()">Ascending ↑</button></div></div>
<div class="room-grid" id="facility-grid"></div>
<div class="no-results" id="no-results" style="display:none;"><div class="no-results-icon">🏢</div><div style="font-size:20px;font-weight:700;color:var(--text-dark);margin-bottom:8px;">No facilities found</div></div>
</div></div>
<?php include '../../includes/footer.php'; ?>
<script>
const allFacilities = <?php echo json_encode($facilities, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
let filteredFacilities = [...allFacilities];
let currentFacilitySortField = 'relevance';
let facilitySortDirection = 'asc';
const facilityOriginalOrder = new Map(allFacilities.map((facility, index) => [facility.id, index]));

function normalizeFilterValue(value) {
  return String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function renderTypeOptions(){
  const s=document.getElementById('facility-type-filter');
  [...new Set(allFacilities.map(f=>f.type).filter(Boolean))].sort().forEach(t=>{const o=document.createElement('option');o.value=t;o.textContent=t.replace(/\b\w/g,m=>m.toUpperCase());s.appendChild(o);});
  applyFacilityTypeFromUrl();
}

function applyFacilityTypeFromUrl(){
  const queryType = new URLSearchParams(window.location.search).get('facility_type');
  if(!queryType) return;
  const filter = document.getElementById('facility-type-filter');
  const target = normalizeFilterValue(queryType);
  const match = [...filter.options].find(option => normalizeFilterValue(option.value) === target || normalizeFilterValue(option.value).includes(target) || target.includes(normalizeFilterValue(option.value)));
  if(match) filter.value = match.value;
}

function displayFacilities(){
 const grid=document.getElementById('facility-grid'); const empty=document.getElementById('no-results');
 if(!filteredFacilities.length){grid.style.display='none'; empty.style.display='block'; document.getElementById('facility-count').textContent='0'; return;}
 grid.style.display='grid'; empty.style.display='none';
 grid.innerHTML=filteredFacilities.map(f=>`<div class="room-card"><div class="room-image">${f.image?`<img src="${f.image}" alt="${f.name}" loading="lazy" decoding="async">`:`<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;">🏢</div>`}<span class="room-label">${(f.type||'facility').toUpperCase()}</span><span class="room-badge ${f.available?'available':'unavailable'}">${f.available?'Available':'Unavailable'}</span></div><div class="room-content"><h3 class="room-name">${f.name}</h3><div class="room-capacity">📍 ${f.location}</div><p class="room-description">${f.description}</p><div class="room-footer"><a class="btn-view-details" href="facilities_detail.php?type=facility&id=${f.id}&name=${encodeURIComponent(f.name)}">View Details</a><button class="btn-book" ${!f.available?'disabled':''} onclick="window.location.href='booking.php?resource_type=facility&facility_id=${f.id}&resource_name='+encodeURIComponent(f.name)">${f.available?'Book Facility':'Unavailable'}</button></div></div></div>`).join('');
 document.getElementById('facility-count').textContent=filteredFacilities.length;
}
function applyFilters(){const t=document.getElementById('facility-type-filter').value;filteredFacilities=allFacilities.filter(f=>!t||f.type===t);applyFacilitySort();}
function resetFilters(){document.getElementById('facility-type-filter').value='';filteredFacilities=[...allFacilities];currentFacilitySortField='relevance';facilitySortDirection='asc';document.getElementById('facility-sort-field').value='relevance';updateFacilitySortButton();displayFacilities();}
function sortFacilities(field){currentFacilitySortField=field;applyFacilitySort();}
function toggleFacilitySortDirection(){facilitySortDirection=facilitySortDirection==='asc'?'desc':'asc';updateFacilitySortButton();applyFacilitySort();}
function updateFacilitySortButton(){document.getElementById('facility-sort-order').textContent=facilitySortDirection==='asc'?'Ascending ↑':'Descending ↓';}
function applyFacilitySort(){
 const direction=facilitySortDirection==='asc'?1:-1;
 filteredFacilities.sort((a,b)=>{
   if(currentFacilitySortField==='name') return String(a.name||'').localeCompare(String(b.name||''))*direction;
   if(currentFacilitySortField==='price') return ((Number(a.cost)||0)-(Number(b.cost)||0))*direction;
   if(currentFacilitySortField==='capacity') return ((Number(a.capacity)||0)-(Number(b.capacity)||0))*direction;
   return ((facilityOriginalOrder.get(a.id)||0)-(facilityOriginalOrder.get(b.id)||0))*direction;
 });
 displayFacilities();
}
document.addEventListener('DOMContentLoaded',()=>{renderTypeOptions();applyFilters();updateFacilitySortButton();});
</script>
</body></html>