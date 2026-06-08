<?php
$currentPage = 'room';
$rooms = [];

try {
    require_once __DIR__ . '/../../config/db.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT room_id, room_name, room_code, room_type, location, capacity, description, price_per_day, resource_status FROM rooms ORDER BY room_name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $rooms[] = [
                'id' => (int)$row['room_id'],
                'name' => $row['room_name'] ?: 'Unnamed Room',
                'type' => strtolower((string)($row['room_type'] ?? 'room')),
                'location' => $row['location'] ?: 'Unknown location',
                'code' => (string)($row['room_code'] ?? ''),
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
.main-container{display:flex;width:100%;max-width:1400px;margin:30px auto;gap:30px;padding:0 30px;align-items:flex-start}
.sidebar{flex:0 0 250px}.filter-section{background:var(--white);padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.filter-title{font-size:16px;font-weight:700;margin-bottom:16px}.select-input,.sort-select{width:100%;padding:10px;border:1px solid var(--border-light);border-radius:4px;font-size:14px;background:var(--white)}.sort-controls{display:flex;align-items:center;gap:10px}.sort-order-btn{padding:10px 14px;border:1px solid var(--primary-color);background:var(--white);color:var(--primary-color);border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}.sort-order-btn:hover{background:var(--primary-color);color:#fff}
.btn-apply,.btn-reset{width:100%;padding:12px;border-radius:4px;font-weight:600;cursor:pointer;transition:all .3s}.btn-apply{background:var(--primary-color);color:var(--white);border:none;margin-bottom:12px}.btn-reset{background:var(--white);border:1px solid var(--border-light)}
.content-area{flex:1 1 auto;min-width:0;width:100%}.content-header{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:24px}.content-title{font-size:28px;font-weight:700}
.room-grid{display:grid;width:100%;grid-template-columns:repeat(auto-fill,minmax(min(100%,300px),1fr));gap:24px;align-items:start}.room-card{width:100%;min-width:0;background:var(--white);border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.room-image{position:relative;height:200px;background:#ddd}.room-image img{width:100%;height:100%;object-fit:cover}.room-label{position:absolute;top:12px;left:12px;background:rgba(0,0,0,.7);color:#fff;padding:6px 12px;border-radius:4px;font-size:12px}
.room-badge{position:absolute;top:12px;right:12px;color:#fff;padding:6px 12px;border-radius:20px;font-size:12px}.room-badge.available{background:var(--success)}.room-badge.unavailable{background:var(--danger)}
.room-content{padding:20px}.room-name{font-size:18px;font-weight:700;margin:0 0 8px}.room-capacity{font-size:13px;color:var(--text-light);margin-bottom:12px}.room-description{font-size:13px;color:var(--text-light);margin-bottom:16px;line-height:1.5}
.room-footer{display:flex;gap:12px;padding-top:16px;border-top:1px solid var(--border-light)}.btn-view-details,.btn-book{flex:1;padding:10px;border-radius:4px;font-weight:600;font-size:13px;cursor:pointer;text-align:center;text-decoration:none}
.btn-view-details{border:1px solid var(--primary-color);color:var(--primary-color)}.btn-book{background:var(--primary-color);color:#fff;border:none}.btn-book:disabled{background:#ccc;cursor:not-allowed}
.no-results{background:var(--white);border-radius:8px;padding:40px;text-align:center;display:none}

.nav-link {
    color: white;
    text-decoration: none;
    position: relative;
    padding-bottom: 8px;
}

.nav-link.active {
    color: #ffc107 !important;
    border-bottom: 2px solid #ffc107;
    padding-bottom: 5px;
}


.nav-link.active::after {
    content: '';
    position: absolute;
    left: -5px;
    bottom: -5px;
    width: calc(100% + 10px);
    height: 3px;
    background: #ffc107;
}

/* Mobile Responsive */
@media screen and (max-width: 768px) {

    .main-container {
        flex-direction: column;
        width: 100%;
        margin: 20px auto;
        padding: 0 15px;
        gap: 20px;
    }

    .sidebar {
        flex: none;
        width: 100%;
    }

    .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .sort-controls {
        width: 100%;
    }

    .sort-select,
    .sort-order-btn {
        width: 100%;
    }

    .room-grid {
        grid-template-columns: 1fr;
    }

    .room-footer {
        flex-direction: column;
    }

    .btn-view-details,
    .btn-book {
        width: 100%;
    }

    .content-title {
        font-size: 24px;
    }

    .breadcrumb {
        padding: 12px 15px;
    }
}
</style></head><body>
<?php include '../../includes/header.php'; ?>
<div class="breadcrumb"><a href="/homepage.php">Home</a><span> > </span><span>Room Availability</span></div>
<div class="main-container"><div class="sidebar">
<div class="filter-section"><div class="filter-title">Room Type</div><select class="select-input" id="room-type-filter"><option value="">All Types</option></select></div>
<div class="filter-section"><div class="filter-title">Location</div><select class="select-input" id="location-filter"><option value="">All Locations</option></select></div>
<div class="filter-section"><div class="filter-title">Capacity</div><select class="select-input" id="capacity-filter"><option value="">All</option><option value="1-5">1-5</option><option value="6-15">6-15</option><option value="16-50">16-50</option><option value="50+">50+</option></select></div>
<button class="btn-apply" onclick="applyFilters()">Apply Filters</button><button class="btn-reset" onclick="resetFilters()">Reset All</button>
</div>
<div class="content-area"><div class="content-header"><div><h2 class="content-title">Available Rooms</h2><p>Showing <span id="room-count">0</span> rooms</p></div><div class="sort-controls"><select class="sort-select" id="room-sort-field" onchange="sortRooms(this.value)"><option value="relevance">Relevance</option><option value="name">Name</option><option value="price">Price</option><option value="capacity">Capacity</option></select><button type="button" class="sort-order-btn" id="room-sort-order" onclick="toggleRoomSortDirection()">Ascending ↑</button></div></div>
<div class="room-grid" id="room-grid"></div><div class="no-results" id="no-results">No rooms found.</div></div></div>
<?php include '../../includes/footer.php'; ?>
<script>
const allRooms = <?php echo json_encode($rooms, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
let filteredRooms = [...allRooms];
let currentRoomSortField = 'relevance';
let roomSortDirection = 'asc';
let facilityQueryFilter = '';
const roomOriginalOrder = new Map(allRooms.map((room, index) => [room.id, index]));

function normalizeFilterValue(value) {
    return String(value || '').trim().toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function textIncludesNormalized(source, keyword) {
    const s = normalizeFilterValue(source);
    const k = normalizeFilterValue(keyword);
    if (!k) return true;
    return s === k || s.includes(k) || k.includes(s);
}

function titleCase(value) {
    return String(value || '').replace(/-/g, ' ').replace(/\b\w/g, m => m.toUpperCase());
}

function renderOptions(){
 const typeSelect=document.getElementById('room-type-filter');
 const locationSelect=document.getElementById('location-filter');
 [...new Set(allRooms.map(r=>r.type).filter(Boolean))].sort().forEach(v=>{const o=document.createElement('option');o.value=v;o.textContent=titleCase(v);typeSelect.appendChild(o);});
 [...new Set(allRooms.map(r=>r.location).filter(Boolean))].sort().forEach(v=>{const o=document.createElement('option');o.value=v;o.textContent=v;locationSelect.appendChild(o);});
 applyUrlFilters();
}

function setSelectByNormalizedValue(selectId, queryValue) {
    if (!queryValue) return false;
    const select = document.getElementById(selectId);
    const target = normalizeFilterValue(queryValue);
    const match = [...select.options].find(option => {
        const optionValue = normalizeFilterValue(option.value);
        return optionValue === target || optionValue.includes(target) || target.includes(optionValue);
    });
    if (match) {
        select.value = match.value;
        return true;
    }
    return false;
}

function applyUrlFilters() {
    const params = new URLSearchParams(window.location.search);
    const roomType = params.get('room_type') || params.get('type') || params.get('category');
    const location = params.get('location');
    const facility = params.get('facility') || params.get('building') || params.get('search');
    setSelectByNormalizedValue('room-type-filter', roomType);
    const matchedLocation = setSelectByNormalizedValue('location-filter', location || facility);
    facilityQueryFilter = !matchedLocation ? (facility || location || '') : '';
}

function capacityMatch(cap,val){
    cap = Number(cap) || 0;
    if(!val) return true;
    if(val==='1-5')return cap>=1&&cap<=5;
    if(val==='6-15')return cap>=6&&cap<=15;
    if(val==='16-50')return cap>=16&&cap<=50;
    return cap>50;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
}

function displayRooms(){
 const grid=document.getElementById('room-grid'), empty=document.getElementById('no-results');
 document.getElementById('room-count').textContent=filteredRooms.length;
 if(!filteredRooms.length){grid.style.display='none';empty.style.display='block';grid.innerHTML='';return;}
 grid.style.display='grid';empty.style.display='none';
 grid.innerHTML=filteredRooms.map(r=>{
    const name = escapeHtml(r.name);
    const location = escapeHtml(r.location);
    const description = escapeHtml(r.description);
    const type = escapeHtml((r.type||'room').toUpperCase());
    const image = r.image ? `<img src="${escapeHtml(r.image)}" alt="${name}" loading="lazy" decoding="async">` : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;">🏫</div>`;
    return `<div class="room-card"><div class="room-image">${image}<span class="room-label">${type}</span><span class="room-badge ${r.available?'available':'unavailable'}">${r.available?'Available':'Unavailable'}</span></div><div class="room-content"><h3 class="room-name">${name}</h3><div class="room-capacity">👥 ${Number(r.capacity)||'-'} people · ${location}</div><p class="room-description">${description}</p><div class="room-footer"><a class="btn-view-details" href="facility_details.php?type=room&id=${encodeURIComponent(r.id)}&name=${encodeURIComponent(r.name)}">View Details</a><button class="btn-book" ${!r.available?'disabled':''} data-resource-type="room" data-resource-id="${Number(r.id)}" data-resource-name="${escapeHtml(String(r.name || ''))}">${r.available?'Book Room':'Unavailable'}</button></div></div></div>`;
 }).join('');

    grid.querySelectorAll('.btn-book:not([disabled])').forEach(button => {
        button.addEventListener('click', () => {
            navigateToBooking(
                String(button.dataset.resourceType || 'room'),
                Number(button.dataset.resourceId || 0),
                String(button.dataset.resourceName || '')
            );
        });
    });
}


function applyFilters(){
 const typeValue=document.getElementById('room-type-filter').value;
 const locationValue=document.getElementById('location-filter').value;
 const capacityValue=document.getElementById('capacity-filter').value;
 const facilityValue=facilityQueryFilter;
 filteredRooms=allRooms.filter(room=>{
    const typeOk = !typeValue || textIncludesNormalized(room.type, typeValue);
    const locationOk = !locationValue || textIncludesNormalized(room.location, locationValue);
    const capacityOk = capacityMatch(room.capacity, capacityValue);
    const facilityOk = !facilityValue || [room.name, room.code, room.location, room.type, room.description].some(value => textIncludesNormalized(value, facilityValue));
    return typeOk && locationOk && capacityOk && facilityOk;
 });
 applyRoomSort();
}

function resetFilters(){
 document.getElementById('room-type-filter').value='';
 document.getElementById('location-filter').value='';
 document.getElementById('capacity-filter').value='';
 facilityQueryFilter='';
 filteredRooms=[...allRooms];
 currentRoomSortField='relevance';
 roomSortDirection='asc';
 document.getElementById('room-sort-field').value='relevance';
 updateRoomSortButton();
 displayRooms();
 if (window.history && window.history.replaceState) window.history.replaceState({}, document.title, window.location.pathname);
}

function sortRooms(field){currentRoomSortField=field;applyRoomSort();}
function toggleRoomSortDirection(){roomSortDirection=roomSortDirection==='asc'?'desc':'asc';updateRoomSortButton();applyRoomSort();}
function updateRoomSortButton(){document.getElementById('room-sort-order').textContent=roomSortDirection==='asc'?'Ascending ↑':'Descending ↓';}
function applyRoomSort(){
 const direction=roomSortDirection==='asc'?1:-1;
 filteredRooms.sort((a,b)=>{
   if(currentRoomSortField==='name') return String(a.name||'').localeCompare(String(b.name||''))*direction;
   if(currentRoomSortField==='price') return ((Number(a.cost)||0)-(Number(b.cost)||0))*direction;
   if(currentRoomSortField==='capacity') return ((Number(a.capacity)||0)-(Number(b.capacity)||0))*direction;
   return ((roomOriginalOrder.get(a.id)||0)-(roomOriginalOrder.get(b.id)||0))*direction;
 });
 displayRooms();
}

function navigateToBooking(resourceType, resourceId, resourceName) {
    const params = new URLSearchParams({
        resource_type: resourceType,
        resource_id: String(resourceId),
        resource_name: resourceName || '',
    });
    window.location.href = `booking.php?${params.toString()}`;
}

document.addEventListener('DOMContentLoaded',()=>{
    renderOptions();
    applyFilters();
    updateRoomSortButton();
    ['room-type-filter','location-filter','capacity-filter'].forEach(id=>{
        const element=document.getElementById(id);
        element.addEventListener('change', applyFilters);
    });
});
</script></body></html>
