<?php
$currentPage = 'booking';
$rooms = [];

try {
    require_once __DIR__ . '/../../config/db.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT room_id, room_name, room_type, location, capacity, description, price_per_day, resource_status FROM rooms ORDER BY room_name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $rooms[] = [
                'id' => (int)$row['room_id'],
                'name' => $row['room_name'] ?: 'Unnamed Room',
                'type' => strtolower((string)($row['room_type'] ?? 'room')),
                'location' => $row['location'] ?: 'Unknown location',
                'capacity' => (int)($row['capacity'] ?? 0),
                'description' => $row['description'] ?: 'No description provided.',
                'cost' => (float)($row['price_per_day'] ?? 0),
                'available' => (($row['resource_status'] ?? 'available') === 'available'),
                'image' => 'room_image.php?id=' . (int)$row['room_id'],
            ];
        }
    }
} catch (Throwable $e) {
    $rooms = [];
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Room Availability - UNIRESERVE</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--primary-color:#8b1538;--primary-hover:#a01d48;--text-dark:#333;--text-light:#666;--border-light:#e0e0e0;--white:#fff;--bg-light:#f5f5f5;--success:#388e3c;--danger:#d32f2f}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Roboto','Oxygen','Ubuntu',sans-serif;color:var(--text-dark);background:var(--bg-light)}
.breadcrumb{padding:16px 30px;background:var(--white);border-bottom:1px solid var(--border-light);font-size:13px;color:var(--text-light)}
.breadcrumb a{color:var(--primary-color);text-decoration:none}
.main-container{display:flex;max-width:1400px;margin:30px auto;gap:30px;padding:0 30px}
.sidebar{flex:0 0 250px}.filter-section{background:var(--white);padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.filter-title{font-size:16px;font-weight:700;margin-bottom:16px}.select-input,.sort-select{width:100%;padding:10px;border:1px solid var(--border-light);border-radius:4px;font-size:14px;background:var(--white)}
.btn-apply,.btn-reset{width:100%;padding:12px;border-radius:4px;font-weight:600;cursor:pointer;transition:all .3s}.btn-apply{background:var(--primary-color);color:var(--white);border:none;margin-bottom:12px}.btn-reset{background:var(--white);border:1px solid var(--border-light)}
.content-area{flex:1}.content-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.content-title{font-size:28px;font-weight:700}
.room-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}.room-card{background:var(--white);border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.room-image{position:relative;height:200px;background:#ddd}.room-image img{width:100%;height:100%;object-fit:cover}.room-label{position:absolute;top:12px;left:12px;background:rgba(0,0,0,.7);color:#fff;padding:6px 12px;border-radius:4px;font-size:12px}
.room-badge{position:absolute;top:12px;right:12px;color:#fff;padding:6px 12px;border-radius:20px;font-size:12px}.room-badge.available{background:var(--success)}.room-badge.unavailable{background:var(--danger)}
.room-content{padding:20px}.room-name{font-size:18px;font-weight:700;margin:0 0 8px}.room-capacity{font-size:13px;color:var(--text-light);margin-bottom:12px}.room-description{font-size:13px;color:var(--text-light);margin-bottom:16px;line-height:1.5}
.room-footer{display:flex;gap:12px;padding-top:16px;border-top:1px solid var(--border-light)}.btn-view-details,.btn-book{flex:1;padding:10px;border-radius:4px;font-weight:600;font-size:13px;cursor:pointer;text-align:center;text-decoration:none}
.btn-view-details{border:1px solid var(--primary-color);color:var(--primary-color)}.btn-book{background:var(--primary-color);color:#fff;border:none}.btn-book:disabled{background:#ccc;cursor:not-allowed}
.no-results{background:var(--white);border-radius:8px;padding:40px;text-align:center;display:none}
</style></head><body>
<?php include '../../includes/header.php'; ?>
<div class="breadcrumb"><a href="/homepage.php">Home</a><span> > </span><span>Room Availability</span></div>
<div class="main-container"><div class="sidebar">
<div class="filter-section"><div class="filter-title">Room Type</div><select class="select-input" id="room-type-filter"><option value="">All Types</option></select></div>
<div class="filter-section"><div class="filter-title">Location</div><select class="select-input" id="location-filter"><option value="">All Locations</option></select></div>
<div class="filter-section"><div class="filter-title">Capacity</div><select class="select-input" id="capacity-filter"><option value="">All</option><option value="1-5">1-5</option><option value="6-15">6-15</option><option value="16-50">16-50</option><option value="50+">50+</option></select></div>
<button class="btn-apply" onclick="applyFilters()">Apply Filters</button><button class="btn-reset" onclick="resetFilters()">Reset All</button>
</div>
<div class="content-area"><div class="content-header"><div><h2 class="content-title">Available Rooms</h2><p>Showing <span id="room-count">0</span> rooms</p></div><div><select class="sort-select" onchange="sortRooms(this.value)"><option value="relevance">Relevance</option><option value="price-low">Price (Low to High)</option><option value="price-high">Price (High to Low)</option><option value="capacity">Capacity</option></select></div></div>
<div class="room-grid" id="room-grid"></div><div class="no-results" id="no-results">No rooms found.</div></div></div>
<?php include '../../includes/footer.php'; ?>
<script>
const allRooms = <?php echo json_encode($rooms, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
let filteredRooms = [...allRooms];
function renderOptions(){
 const t=document.getElementById('room-type-filter'); const l=document.getElementById('location-filter');
 [...new Set(allRooms.map(r=>r.type).filter(Boolean))].sort().forEach(v=>{const o=document.createElement('option');o.value=v;o.textContent=v.replace(/\b\w/g,m=>m.toUpperCase());t.appendChild(o);});
 [...new Set(allRooms.map(r=>r.location).filter(Boolean))].sort().forEach(v=>{const o=document.createElement('option');o.value=v;o.textContent=v;l.appendChild(o);});
}
function capacityMatch(cap,val){if(!val) return true; if(val==='1-5')return cap<=5; if(val==='6-15')return cap>=6&&cap<=15; if(val==='16-50')return cap>=16&&cap<=50; return cap>50;}
function displayRooms(){const g=document.getElementById('room-grid'),e=document.getElementById('no-results'); if(!filteredRooms.length){g.style.display='none';e.style.display='block';document.getElementById('room-count').textContent='0';return;} g.style.display='grid';e.style.display='none'; g.innerHTML=filteredRooms.map(r=>`<div class="room-card"><div class="room-image">${r.image?`<img src="${r.image}" alt="${r.name}" loading="lazy" decoding="async">`:`<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;">🏫</div>`}<span class="room-label">${(r.type||'room').toUpperCase()}</span><span class="room-badge ${r.available?'available':'unavailable'}">${r.available?'Available':'Unavailable'}</span></div><div class="room-content"><h3 class="room-name">${r.name}</h3><div class="room-capacity">👥 ${r.capacity||'-'} people · ${r.location}</div><p class="room-description">${r.description}</p><div class="room-footer"><a class="btn-view-details" href="facilities_detail.php?type=room&id=${r.id}&name=${encodeURIComponent(r.name)}">View Details</a><button class="btn-book" ${!r.available?'disabled':''} onclick="window.location.href='booking.php?resource_type=room&room_id=${r.id}&resource_name='+encodeURIComponent(r.name)">${r.available?'Book Room':'Unavailable'}</button></div></div></div>`).join(''); document.getElementById('room-count').textContent=filteredRooms.length;}
function applyFilters(){const t=document.getElementById('room-type-filter').value,l=document.getElementById('location-filter').value,c=document.getElementById('capacity-filter').value; filteredRooms=allRooms.filter(r=>(!t||r.type===t)&&(!l||r.location===l)&&capacityMatch(r.capacity,c));displayRooms();}
function resetFilters(){document.getElementById('room-type-filter').value='';document.getElementById('location-filter').value='';document.getElementById('capacity-filter').value='';filteredRooms=[...allRooms];displayRooms();}
function sortRooms(s){if(s==='price-low')filteredRooms.sort((a,b)=>a.cost-b.cost); else if(s==='price-high')filteredRooms.sort((a,b)=>b.cost-a.cost); else if(s==='capacity')filteredRooms.sort((a,b)=>a.capacity-b.capacity); else filteredRooms=[...allRooms];displayRooms();}
function normalizeType(v){return (v||'').toLowerCase().replace(/[-_]+/g,' ').replace(/\s+/g,' ').trim();}
function findMatchingType(raw){
 const target=normalizeType(raw);
 if(!target) return '';
 const types=[...new Set(allRooms.map(r=>r.type).filter(Boolean))];
 const exact=types.find(t=>normalizeType(t)===target);
 if(exact) return exact;
 const partial=types.find(t=>normalizeType(t).includes(target)||target.includes(normalizeType(t)));
 return partial||'';
}
function initFromQuery(){
 const params=new URLSearchParams(window.location.search);
 const queryType=params.get('roomType')||params.get('category')||'';
 const matchedType=findMatchingType(queryType);
 if(matchedType){
   document.getElementById('room-type-filter').value=matchedType;
   applyFilters();
   return;
 }
 displayRooms();
}
document.addEventListener('DOMContentLoaded',()=>{renderOptions();initFromQuery();});
</script></body></html>
