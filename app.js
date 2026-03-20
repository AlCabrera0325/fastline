let currentLocation = { city: '', barangay: '', latitude: null, longitude: null };
let currentTab  = 'police';
let favorites   = [];
let allHotlines = [];

const locationData = {
    cities: [
        { id: 'san_fernando', name: 'City of San Fernando' },
        { id: 'angeles',      name: 'Angeles City'         },
        { id: 'mabalacat',    name: 'Mabalacat City'       },
        { id: 'mexico',       name: 'Mexico'               },
        { id: 'lubao',        name: 'Lubao'                },
        { id: 'apalit',       name: 'Apalit'               },
        { id: 'guagua',       name: 'Guagua'               },
        { id: 'porac',        name: 'Porac'                },
        { id: 'bacolor',      name: 'Bacolor'              },
        { id: 'candaba',      name: 'Candaba'              },
        { id: 'floridablanca',name: 'Floridablanca'        },
        { id: 'arayat',       name: 'Arayat'               },
        { id: 'san_simon',    name: 'San Simon'            },
        { id: 'sto_tomas',    name: 'Sto. Tomas'           },
        { id: 'san_luis',     name: 'San Luis'             }
    ],
    barangays: {
        san_fernando: ['Del Carmen','Del Pilar','Del Rosario','Dolores','Juliana','Lara','Lazatin','Maimpis','Saguin','Sindalan','Sto. Rosario','San Agustin'],
        angeles:      ['Anunas','Balibago','Capaya','Claro M. Recto','Cutcut','Pampang','Pulung Maragul','Santo Cristo','Santo Rosario','Sapang Bato','Salapungan','Virgen Delos Remedios'],
        mabalacat:    ['Atlu-Bola','Bundagul','Cacutud','Dapdap','Dau','Dolores','Duquit','Lakandula','Mabiga','Marcos Village','Paralaya','San Francisco','Sapang Bato','Santo Rosario'],
        mexico:       ['Acli','Anao','Buenavista','Camuning','Divisoria','Lagundi','Masamat','Pandacaqui','Santo Rosario','San Antonio'],
        lubao:        ['Lourdes','San Isidro','Santa Cruz','Sto. Tomas','Prado Siongco','San Nicolas'],
        apalit:       ['San Juan','San Vicente','Sulipan','Balucuc','Cansinala'],
        guagua:       ['Ascomo','Bancal','San Juan','San Miguel','San Nicolas','San Pedro'],
        porac:        ['Babo Pangulo','Dolores','Manibaug Libutad','Manibaug Paralaya','Sta. Cruz'],
        bacolor:      ['Balas','Cabambangan','Calibutbut','San Antonio','San Isidro'],
        candaba:      ['Bahay Pare','San Agustin','Sta. Lutgarda','Sto. Rosario','Vizal San Pablo'],
        floridablanca:['Anon','Apalit','Basa Air Base','Camba','Nabuclod','Poblacion'],
        arayat:       ['Plazang Luma','San Juan Bano','San Matias','Sto. Nino de Arayat','Gatiawin'],
        san_simon:    ['Concepcion','San Isidro','San Jose','San Nicolas','Sto. Tomas'],
        sto_tomas:    ['San Matias','San Vicente','Sta. Cruz'],
        san_luis:     ['San Agustin','San Carlos','San Isidro','Sta. Lucia','Sta. Monica']
    }
};

// ── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async function () {
    populateCitySelect();
    await loadFavoritesFromServer();
    await loadHotlines();

    document.getElementById('useCurrentLocation').addEventListener('click', requestCurrentLocation);
    document.getElementById('useManualLocation').addEventListener('click', showManualLocationForm);
    document.getElementById('citySelect').addEventListener('change', updateBarangaySelect);
    document.getElementById('confirmLocation').addEventListener('click', confirmManualLocation);
    document.getElementById('searchInput').addEventListener('input', handleSearch);

    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', function () { switchTab(this.dataset.tab); });
    });
});

// ── Favorites ─────────────────────────────────────────────
async function loadFavoritesFromServer() {
    try {
        const res  = await fetch('api/favorites.php');
        const data = await res.json();
        if (data.success) favorites = data.favorites.map(id => parseInt(id));
    } catch (err) { favorites = []; }
}

async function syncFavoriteWithServer(action, id) {
    try {
        const res  = await fetch('api/favorites.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, id })
        });
        const data = await res.json();
        if (data.success) favorites = data.favorites.map(id => parseInt(id));
    } catch (err) { console.error('Favorites sync failed:', err); }
}

// ── Hotlines API ──────────────────────────────────────────
async function loadHotlines(searchTerm = '') {
    showLoading();

    if (currentTab === 'favorites') {
        try {
            const res  = await fetch('api/hotlines.php');
            const data = await res.json();
            if (data.success) {
                allHotlines = data.hotlines;
                displayHotlines(allHotlines.filter(h => favorites.includes(parseInt(h.id))));
            } else { showError(); }
        } catch (err) { showError(); }
        return;
    }

    const params = new URLSearchParams();
    if (currentTab)              params.set('category', currentTab);
    if (currentLocation.city)    params.set('city',     currentLocation.city);
    if (currentLocation.barangay && currentLocation.barangay !== 'all')
                                 params.set('barangay', currentLocation.barangay);
    if (searchTerm)              params.set('search',   searchTerm);

    try {
        const res  = await fetch('api/hotlines.php?' + params.toString());
        const data = await res.json();
        if (data.success) { allHotlines = data.hotlines; displayHotlines(allHotlines); }
        else { showError(); }
    } catch (err) { showError(); }
}

// ── Location ──────────────────────────────────────────────
function populateCitySelect() {
    const sel = document.getElementById('citySelect');
    locationData.cities.forEach(city => {
        const opt = document.createElement('option');
        opt.value = city.id; opt.textContent = city.name;
        sel.appendChild(opt);
    });
}

function updateBarangaySelect() {
    const cityId = document.getElementById('citySelect').value;
    const sel    = document.getElementById('barangaySelect');
    sel.innerHTML = '<option value="">Select Barangay</option>';
    if (cityId && locationData.barangays[cityId]) {
        locationData.barangays[cityId].forEach(b => {
            const opt = document.createElement('option');
            opt.value = b; opt.textContent = b; sel.appendChild(opt);
        });
    }
}

function requestCurrentLocation() {
    if (!("geolocation" in navigator)) {
        alert('Geolocation not supported. Please select manually.');
        showManualLocationForm(); return;
    }
    navigator.geolocation.getCurrentPosition(
        pos => {
            currentLocation.latitude  = pos.coords.latitude;
            currentLocation.longitude = pos.coords.longitude;
            currentLocation.city      = 'san_fernando';
            currentLocation.barangay  = 'all';
            updateLocationDisplay('City of San Fernando (Current Location)');
            loadHotlines();
        },
        () => { alert('Unable to get location. Please select manually.'); showManualLocationForm(); }
    );
}

function showManualLocationForm() { document.getElementById('manualLocationForm').classList.remove('hidden'); }

function confirmManualLocation() {
    const cityId   = document.getElementById('citySelect').value;
    const barangay = document.getElementById('barangaySelect').value;
    if (!cityId) { alert('Please select a city'); return; }
    currentLocation.city     = cityId;
    currentLocation.barangay = barangay || 'all';
    const cityName = locationData.cities.find(c => c.id === cityId).name;
    updateLocationDisplay(barangay ? `${cityName}, ${barangay}` : cityName);
    document.getElementById('manualLocationForm').classList.add('hidden');
    loadHotlines();
}

function updateLocationDisplay(text) {
    document.getElementById('currentLocationDisplay').classList.remove('hidden');
    document.getElementById('locationText').textContent = text;
}

// ── Tabs ──────────────────────────────────────────────────
function switchTab(tabName) {
    currentTab = tabName;
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById('searchInput').value = '';
    loadHotlines();
}

// ── Display ───────────────────────────────────────────────
function showLoading() {
    document.getElementById('hotlinesContainer').innerHTML =
        `<div class="no-results"><i class="fas fa-spinner fa-spin"></i><h3>Loading...</h3></div>`;
}

function showError() {
    document.getElementById('hotlinesContainer').innerHTML =
        `<div class="no-results"><i class="fas fa-exclamation-triangle"></i>
        <h3>Could not load hotlines</h3>
        <p>Make sure MySQL is running and <strong>api/hotlines.php</strong> is reachable.<br>
        Test it directly: <code>localhost/fastline/api/hotlines.php?category=police</code></p></div>`;
}

function displayHotlines(hotlines) {
    const container = document.getElementById('hotlinesContainer');
    if (!hotlines || hotlines.length === 0) {
        container.innerHTML =
            `<div class="no-results"><i class="fas fa-search"></i>
            <h3>No hotlines found</h3>
            <p>${currentTab === 'favorites'
                ? "You haven't added any favorites yet — star a hotline to save it here."
                : 'Try selecting a different location or category.'}</p></div>`;
        return;
    }
    container.innerHTML = hotlines.map(h => createHotlineCard(h)).join('');
    container.querySelectorAll('.btn-call').forEach(btn =>
        btn.addEventListener('click', function () { callNumber(this.dataset.phone); })
    );
    container.querySelectorAll('.btn-favorite').forEach(btn =>
        btn.addEventListener('click', function () { toggleFavorite(parseInt(this.dataset.id)); })
    );
}

function createHotlineCard(hotline) {
    const isFav = favorites.includes(parseInt(hotline.id));
    const locText = hotline.barangay !== 'all'
        ? hotline.barangay
        : hotline.city === 'national'
            ? 'National'
            : locationData.cities.find(c => c.id === hotline.city)?.name || hotline.city;

    return `
        <div class="hotline-card">
            <div class="hotline-header">
                <div>
                    <h4>${hotline.name}</h4>
                    <div class="location-badge">
                        <i class="fas fa-map-marker-alt"></i> ${locText}
                    </div>
                </div>
            </div>
            <p class="description">${hotline.description ?? ''}</p>
            <div class="phone-number"><i class="fas fa-phone"></i> ${hotline.phone}</div>
            <div class="hotline-actions">
                <button class="btn-call" data-phone="${hotline.phone}">
                    <i class="fas fa-phone-alt"></i> Call Now
                </button>
                <button class="btn-favorite ${isFav ? 'active' : ''}" data-id="${hotline.id}"
                        title="${isFav ? 'Remove from favorites' : 'Add to favorites'}">
                    <i class="fas fa-star"></i>
                </button>
            </div>
        </div>`;
}

function callNumber(phone) {
    window.location.href = 'tel:' + phone.replace(/[^0-9+]/g, '');
}

async function toggleFavorite(id) {
    const action = favorites.includes(id) ? 'remove' : 'add';
    await syncFavoriteWithServer(action, id);
    loadHotlines();
}

function handleSearch(e) {
    loadHotlines(e.target.value.toLowerCase().trim());
}
