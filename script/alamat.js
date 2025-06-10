let map;
let currentMarker;
let searchTimeout;
let selectedCoordinates = null;

// Batasan koordinat Indonesia
const INDONESIA_BOUNDS = {
    north: 6.5,      // Pulau Weh, Aceh
    south: -11.5,    // Pulau Rote, NTT
    east: 141.5,     // Merauke, Papua
    west: 94.5       // Sabang, Aceh
};

// Initialize map dengan batasan Indonesia
function initMap() {
    // Center pada Indonesia (Jakarta)
    map = L.map('map', {
        minZoom: 5,
        maxZoom: 18
    }).setView([-2.5, 118], 5); // Center Indonesia
    
    // Set batasan maksimal untuk Indonesia
    const bounds = L.latLngBounds(
        [INDONESIA_BOUNDS.south, INDONESIA_BOUNDS.west], // Southwest
        [INDONESIA_BOUNDS.north, INDONESIA_BOUNDS.east]  // Northeast
    );
    
    map.setMaxBounds(bounds);
    map.fitBounds(bounds);
    
    // Add tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
        bounds: bounds
    }).addTo(map);
    
    // Add click handler dengan validasi bounds
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Validasi apakah koordinat dalam batas Indonesia
        if (isInIndonesia(lat, lng)) {
            setLocation(lat, lng, 'Lokasi Manual');
        } else {
            alert('⚠️ Lokasi yang dipilih berada di luar wilayah Indonesia. Silakan pilih lokasi dalam wilayah Indonesia.');
        }
    });
    
    // Prevent dragging outside bounds
    map.on('drag', function() {
        map.panInsideBounds(bounds, { animate: false });
    });
}

// Fungsi untuk mengecek apakah koordinat dalam wilayah Indonesia
function isInIndonesia(lat, lng) {
    return (lat >= INDONESIA_BOUNDS.south && 
            lat <= INDONESIA_BOUNDS.north && 
            lng >= INDONESIA_BOUNDS.west && 
            lng <= INDONESIA_BOUNDS.east);
}

// Fungsi untuk menentukan pulau berdasarkan koordinat
function getIslandFromCoordinates(lat, lng) {
    // Sumatera
    if (lat >= -6 && lat <= 6 && lng >= 94.5 && lng <= 107) {
        return "Sumatera";
    }
    // Jawa (termasuk Jakarta)
    else if (lat >= -9 && lat <= -5.5 && lng >= 105.5 && lng <= 115) {
        return "Jawa";
    }
    // Bali
    else if (lat >= -8.8 && lat <= -8.1 && lng >= 114.4 && lng <= 115.8) {
        return "Bali";
    }
    // Kalimantan
    else if (lat >= -4.5 && lat <= 4.5 && lng >= 107 && lng <= 119) {
        return "Kalimantan";
    }
    // Sulawesi
    else if (lat >= -6 && lat <= 2 && lng >= 118 && lng <= 126) {
        return "Sulawesi";
    }
    // Papua (termasuk Maluku)
    else if (lat >= -9 && lat <= 1 && lng >= 124 && lng <= 141.5) {
        return "Papua";
    }
    else {
        return "Indonesia";
    }
}

// Set location function dengan validasi
function setLocation(lat, lng, source) {
    // Double check bounds
    if (!isInIndonesia(lat, lng)) {
        alert('⚠️ Lokasi berada di luar wilayah Indonesia!');
        return;
    }
    
    // Remove existing marker
    if (currentMarker) {
        map.removeLayer(currentMarker);
    }
    
    // Add new marker
    currentMarker = L.marker([lat, lng]).addTo(map);
    const island = getIslandFromCoordinates(lat, lng);
    currentMarker.bindPopup(`📍 Lokasi Terpilih<br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}<br>Pulau: ${island}`).openPopup();
    
    // Store coordinates
    selectedCoordinates = { lat: lat, lng: lng, island: island };
    
    // Update form inputs
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    
    // Update display
    updateCoordinatesDisplay(lat, lng, island);
    
    // Enable Google Maps button
    enableGoogleMapsButton();
    
    // Update search input if from search
    if (source !== 'search') {
        document.getElementById('locationSearch').value = `${source} (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
    }
}

// Fungsi untuk mengaktifkan tombol Google Maps
function enableGoogleMapsButton() {
    const btn = document.getElementById('googleMapsBtn');
    if (btn) {
        btn.disabled = false;
    }
}

// Fungsi untuk menonaktifkan tombol Google Maps  
function disableGoogleMapsButton() {
    const btn = document.getElementById('googleMapsBtn');
    if (btn) {
        btn.disabled = true;
    }
}

// Google Maps integration functions
function openGoogleMaps() {
    if (!selectedCoordinates) {
        alert('⚠️ Silakan pilih lokasi terlebih dahulu!');
        return;
    }
    
    const { lat, lng } = selectedCoordinates;
    const googleMapsUrl = `https://www.google.com/maps?q=${lat},${lng}&z=17`;
    
    // Open in new tab
    window.open(googleMapsUrl, '_blank');
}

function getDirections() {
    if (!selectedCoordinates) {
        alert('⚠️ Silakan pilih lokasi terlebih dahulu!');
        return;
    }
    
    const { lat, lng } = selectedCoordinates;
    const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
    
    // Open in new tab
    window.open(directionsUrl, '_blank');
}

function shareLocation() {
    if (!selectedCoordinates) {
        alert('⚠️ Silakan pilih lokasi terlebih dahulu!');
        return;
    }
    
    const { lat, lng, island } = selectedCoordinates;
    const shareText = `Lokasi di Pulau ${island}, Indonesia: ${lat.toFixed(6)}, ${lng.toFixed(6)}\nGoogle Maps: https://www.google.com/maps?q=${lat},${lng}`;
    
    // Try to use Web Share API if available
    if (navigator.share) {
        navigator.share({
            title: 'Bagikan Lokasi Indonesia',
            text: shareText,
            url: `https://www.google.com/maps?q=${lat},${lng}`
        }).catch(err => {
            console.log('Error sharing:', err);
            fallbackShare(shareText);
        });
    } else {
        fallbackShare(shareText);
    }
}

function fallbackShare(text) {
    // Copy to clipboard
    navigator.clipboard.writeText(text).then(() => {
        alert('📋 Lokasi berhasil disalin ke clipboard!\n\n' + text);
    }).catch(err => {
        // Fallback if clipboard API not available
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('📋 Lokasi berhasil disalin ke clipboard!\n\n' + text);
    });
}

// Search functionality - hanya Indonesia
document.getElementById('locationSearch').addEventListener('input', function() {
    const query = this.value.trim();
    
    if (query.length < 3) {
        hideSearchResults();
        return;
    }
    
    // Debounce search
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchLocation(query);
    }, 500);
});

// Hide search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        hideSearchResults();
    }
});

function searchLocation(query) {
    const searchResults = document.getElementById('searchResults');
    searchResults.innerHTML = '<div class="search-result-item">🔍 Mencari di Indonesia...</div>';
    searchResults.style.display = 'block';
    
    // Using Nominatim API dengan batasan yang ketat untuk Indonesia
    const bounds = `${INDONESIA_BOUNDS.south},${INDONESIA_BOUNDS.west},${INDONESIA_BOUNDS.north},${INDONESIA_BOUNDS.east}`;
    
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ' Indonesia')}&limit=8&countrycodes=id&addressdetails=1&viewbox=${bounds}&bounded=1`)
        .then(response => response.json())
        .then(data => {
            // Filter hasil untuk memastikan dalam batas Indonesia
            const filteredData = data.filter(result => {
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                return isInIndonesia(lat, lng);
            });
            displaySearchResults(filteredData);
        })
        .catch(error => {
            console.error('Error searching location:', error);
            searchResults.innerHTML = '<div class="search-result-item">❌ Error dalam pencarian. Coba lagi.</div>';
        });
}

function displaySearchResults(results) {
    const searchResults = document.getElementById('searchResults');
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="search-result-item">❌ Lokasi tidak ditemukan di Indonesia</div>';
        return;
    }
    
    let html = '';
    results.forEach(result => {
        const displayName = result.display_name;
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);
        const island = getIslandFromCoordinates(lat, lng);
        
        html += `
            <div class="search-result-item" onclick="selectSearchResult(${lat}, ${lng}, '${displayName.replace(/'/g, "\\'")}')">
                <strong>📍 ${result.name || 'Lokasi'}</strong><br>
                <small>🏝️ Pulau ${island} - ${displayName}</small>
            </div>
        `;
    });
    
    searchResults.innerHTML = html;
}

function selectSearchResult(lat, lng, name) {
    // Pan map to location
    map.setView([lat, lng], 16);
    
    // Update search input
    document.getElementById('locationSearch').value = name.split(',')[0];
    hideSearchResults();
    
    // Don't set marker yet - let user click on map for precise location
    alert('Peta dipindahkan ke lokasi tersebut di Indonesia. Sekarang klik di map untuk menentukan lokasi yang tepat!');
}

function hideSearchResults() {
    document.getElementById('searchResults').style.display = 'none';
}

function updateCoordinatesDisplay(lat, lng, island = '') {
    document.getElementById('displayLat').textContent = lat.toFixed(6);
    document.getElementById('displayLng').textContent = lng.toFixed(6);
    document.getElementById('displayProvince').textContent = island || getIslandFromCoordinates(lat, lng);
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '🔄 Mencari lokasi...';
        button.classList.add('loading');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Restore button text
                button.textContent = originalText;
                button.classList.remove('loading');
                
                // Cek apakah lokasi GPS dalam wilayah Indonesia
                if (!isInIndonesia(lat, lng)) {
                    alert('⚠️ Lokasi GPS Anda berada di luar wilayah Indonesia. Silakan pilih lokasi secara manual.');
                    return;
                }
                
                // Pan map to current location
                map.setView([lat, lng], 17);
                
                // Set location
                setLocation(lat, lng, 'GPS');
                
                alert('📍 Lokasi GPS di Indonesia berhasil didapatkan!');
            },
            function(error) {
                // Restore button text on error
                button.textContent = originalText;
                button.classList.remove('loading');
                
                let errorMsg = 'Gagal mendapatkan lokasi: ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMsg += 'Akses lokasi ditolak. Silakan aktifkan lokasi di browser.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMsg += 'Informasi lokasi tidak tersedia.';
                        break;
                    case error.TIMEOUT:
                        errorMsg += 'Timeout. Coba lagi.';
                        break;
                    default:
                        errorMsg += 'Error tidak diketahui.';
                        break;
                }
                alert(errorMsg);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        alert('Browser Anda tidak mendukung geolocation.');
    }
}

function clearLocation() {
    // Clear form inputs
    document.getElementById('latitude').value = '';
    document.getElementById('longitude').value = '';
    document.getElementById('locationSearch').value = '';
    document.getElementById('displayLat').textContent = 'Belum dipilih';
    document.getElementById('displayLng').textContent = 'Belum dipilih';
    document.getElementById('displayProvince').textContent = '-';
    
    // Clear stored coordinates
    selectedCoordinates = null;
    
    // Disable Google Maps button
    disableGoogleMapsButton();
    
    // Remove marker
    if (currentMarker) {
        map.removeLayer(currentMarker);
        currentMarker = null;
    }
    
    // Reset map view to Indonesia
    map.setView([-2.5, 118], 5);
    
    hideSearchResults();
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const lat = document.getElementById('latitude').value;
    const lng = document.getElementById('longitude').value;
    
    if (!lat || !lng) {
        e.preventDefault();
        alert('⚠️ Silakan pilih lokasi dengan mengklik pada peta!');
        return false;
    }
    
    // Double check bounds sebelum submit
    if (!isInIndonesia(parseFloat(lat), parseFloat(lng))) {
        e.preventDefault();
        alert('⚠️ Lokasi yang dipilih berada di luar wilayah Indonesia!');
        return false;
    }
});

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
