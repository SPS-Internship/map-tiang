<template>
<div class="project-container" id="ProjectPage">
  <div class="sidebar">
    <div class="sidebar-menu">
      <!-- Project Info -->
      <div class="project-info">
        <h3>{{ currentProject ? currentProject.nama_project : 'Current Project' }}</h3>
      </div>

      <!-- Placemarks Section -->
      <div class="sidebar-section">
        <div class="section-header" @click="toggleSection('placemarks')">
          <h4>
            <i class="fas fa-map-marker-alt"></i> 
            Placemarks ({{ placemarks.length }})
          </h4>
          <button 
            class="btn-toggle" 
            :class="{ active: showPlacemarks }">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
        
        <div v-if="showPlacemarks" class="section-content">
          <div 
            v-if="placemarks.length === 0" 
            class="empty-message">
            <i class="fas fa-map-marker-alt"></i>
            <p>No placemarks yet</p>
          </div>
          
          <div v-if="placemarks.length > 0" class="placemark-list">
            <div 
              v-for="(placemark, index) in placemarks" 
              :key="index" 
              class="sidebar-item placemark-item">
              <div class="item-info">
                <div class="item-icon">
                  <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="item-details">
                  <h5>
                    {{ placemark.nama_placemark || (placemark.type === 'placemark' ? `Tiang Lepas ${index + 1}` : `Tiang Terhubung ${index + 1}`) }}
                    <span v-if="placemark.hasODP" class="badge-odp">ODP</span>
                  </h5>
                  <p class="coordinates">{{ placemark.lat.toFixed(6) }}, {{ placemark.lng.toFixed(6) }}</p>
                  <p class="address" v-if="placemark.address">{{ placemark.address }}</p>
                  <p class="odp-extra" v-if="placemark.hasODP && placemark.odpInfo">
                    ODP: {{ placemark.odpInfo.nama_odp || '-' }} - Layanan: {{ placemark.odpInfo.kd_layanan || '-' }} - WO: {{ placemark.odpInfo.status_wo || '-' }}
                  </p>
                </div>
              </div>
              <div class="item-actions">
                <button class="btn-action btn-focus" @click="focusOnPlacemark(index)" title="Focus">
                  <i class="fas fa-crosshairs"></i>
                </button>
                <button 
                  class="btn-action btn-edit" 
                  @click="editPlacemark(index)" 
                  :class="{ disabled: isViewMode }"
                  :disabled="isViewMode"
                  :title="isViewMode ? 'Read-only mode' : 'Edit'">
                  <i class="fas fa-edit"></i>
                </button>
                <button 
                  class="btn-action btn-delete" 
                  @click="deletePlacemark(index)" 
                  :class="{ disabled: isViewMode }"
                  :disabled="isViewMode"
                  :title="isViewMode ? 'Read-only mode' : 'Delete'">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Polygons Section -->`
      <div class="sidebar-section">
        <div class="section-header" @click="toggleSection('polygons')">
          <h4><i class="fas fa-project-diagram"></i> Polygons ({{ polygons.length }})</h4>
          <button class="btn-toggle" :class="{ active: showPolygons }">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
        
        <div v-if="polygons.length === 0 && showPolygons" class="empty-message">
          <i class="fas fa-project-diagram"></i>
          <p>No polygons available</p>
        </div>

        <div v-if="showPolygons && polygons.length > 0" class="section-content">
          <div v-for="(polygonData, index) in polygons" :key="polygonData.id || index" class="sidebar-item polygon-item">
            <div class="item-info">
              <div class="item-icon">
                <i class="fas fa-project-diagram"></i>
              </div>
              <div class="item-details">
                <h5>{{ polygonData.nama_polygon || `Polygon ${index + 1}` }}</h5>
                <p class="coordinates">{{ polygonData.coordinates ? polygonData.coordinates.length : 0 }} Koordinat </p>
                <p class="distance" v-if="polygonData.panjang_meter">Panjang: {{ polygonData.panjang_meter.toFixed(2) }} meters</p>
                <p class="address" v-if="polygonData.deskripsi">{{ polygonData.deskripsi }}</p>
              </div>
            </div>
            <div class="item-actions">
              <button class="btn-action btn-focus" @click="focusOnPolygon(index)" title="Focus">
                <i class="fas fa-crosshairs"></i>
              </button>
              <button 
                class="btn-action btn-edit" 
                @click="editPolygon(index)" 
                :class="{ disabled: isViewMode }"
                :disabled="isViewMode"
                :title="isViewMode ? 'Read-only mode' : 'Edit'">
                <i class="fas fa-edit"></i>
              </button>
              <button 
                class="btn-action btn-delete" 
                @click="deletePolygon(index)" 
                :class="{ disabled: isViewMode }"
                :disabled="isViewMode"
                :title="isViewMode ? 'Read-only mode' : 'Delete'">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="main">
    <!-- Header -->
    <div class="header">
      <div class="logo">
          <i class="fas fa-network-wired"></i>
          <span class="net">NET</span><span class="map">MAP</span>
        </div>
      <i class="fas fa-sign-out-alt logout-icon" @click="logout"></i>
    </div>

    <!-- Toolbar -->
    <div v-if="isViewMode" class="mode-indicator read-only-mode">
      <i class="fas fa-eye"></i> 
      <span>READ-ONLY MODE</span>
      <small>- All editing functions are disabled</small>
    </div>
    
    <h1>Project {{ isViewMode ? '(Read Only)' : isCreateMode ? '(New Project)' : '' }}</h1>
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-tools">
          <input
            ref="searchInput"
            type="text"
            v-model="searchQuery"
            placeholder="Search Location"
            @keyup.enter="searchLocation"
          />
          <button 
            class="btn-icon" 
            @click="toggleAddPlacemark" 
            :class="{ active: addingPlacemark, disabled: isViewMode }"
            :disabled="isViewMode"
            :title="isViewMode ? 'Read-only mode' : 'Add Just Placemark'">
            <i class="fas fa-map-marker-alt"></i>
          </button>
          <button 
            class="btn-icon" 
            @click="toggleAddMarker" 
            :class="{ active: addingMarker, disabled: isViewMode }"
            :disabled="isViewMode"
            :title="isViewMode ? 'Read-only mode' : 'Add Polygon Area'">
            <i class="fas fa-project-diagram"></i>
          </button>
          <!-- Tombol Finish muncul ketika mode polygon aktif -->
          <button 
            v-if="addingMarker"
            class="btn-success" 
            @click="finishCurrentPolygon" 
            :class="{ disabled: isViewMode }"
            :disabled="isViewMode"
            :title="isViewMode ? 'Read-only mode' : 'Finish Current Polygon'">
            <i class="fas fa-check"></i> Finish
          </button>
          <button 
            class="btn-icon" 
            @click="clearPolygon" 
            v-if="drawingPolygon || polygon"
            :class="{ disabled: isViewMode }"
            :disabled="isViewMode"
            :title="isViewMode ? 'Read-only mode' : 'Clear Polygon'">
            <i class="fas fa-trash"></i> Clear
          </button>
          <button 
            class="btn-icon" 
            @click="finishPolygon" 
            v-if="drawingPolygon && polygonPath.length >= 3"
            :class="{ disabled: isViewMode }"
            :disabled="isViewMode"
            :title="isViewMode ? 'Read-only mode' : 'Finish Polygon'">
            <i class="fas fa-check"></i> Finish
          </button>
        </div>
      </div>
      <div class="toolbar-right">
        <button 
          class="btn-danger" 
          @click="showImportDialog = true"
          :class="{ disabled: isViewMode }"
          :disabled="isViewMode"
          :title="isViewMode ? 'Read-only mode' : 'Import File'">
          <i class="fas fa-file-import"></i> Import File
        </button>
        <button 
          class="btn-success" 
          @click="saveProject"
          :class="{ disabled: isViewMode }"
          :disabled="isViewMode"
          :title="isViewMode ? 'Read-only mode' : 'Save Project'">
          <i class="fas fa-save"></i> Save Project
        </button>
      </div>
    </div>

    <!-- Instructions -->
    <div v-if="drawingPolygon" class="instructions">
      <p>
        <strong>Drawing Polygon Mode:</strong> Klik di peta untuk menambah titik polygon
      </p>
    </div>

    <!-- Loading Indicator -->
    <div v-if="loading" class="loading">
      <i class="fas fa-spinner fa-spin"></i> {{ loadingMessage }}
    </div>

    <!-- Map -->
    <div class="map-container">
      <div id="map" style="height: 450px;"></div>
    </div>

    <!-- Edit Placemark Modal -->
    <div v-if="showEditPlacemarkModal" class="modern-modal-overlay" @click="closeEditPlacemarkModal">
      <div class="modern-modal-content" @click.stop>
        <div class="modern-modal-header">
          <div class="modal-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div class="modal-title-section">
            <h3>Edit Placemark</h3>
            <p>Modify placemark information</p>
          </div>
          <button class="modern-btn-close" @click="closeEditPlacemarkModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modern-modal-body">
          <div class="modern-form-group">
            <label class="modern-label">
              <i class="fas fa-tag"></i>
              Nama Placemark
            </label>
            <input 
              type="text" 
              v-model="editPlacemarkData.nama_placemark" 
              class="modern-form-input"
              placeholder="Enter placemark name..."
            />
          </div>
          <div class="modern-form-group">
            <label class="modern-label">
              <i class="fas fa-file-alt"></i>
              Deskripsi
            </label>
            <textarea 
              v-model="editPlacemarkData.deskripsi" 
              class="modern-form-textarea"
              placeholder="Enter description..."
              rows="3"
            ></textarea>
          </div>
          <div class="modern-form-group">
            <label class="modern-label">
              <i class="fas fa-map-marked-alt"></i>
              Alamat
            </label>
            <input 
              type="text" 
              v-model="editPlacemarkData.alamat" 
              class="modern-form-input"
              placeholder="Enter address..."
            />
          </div>
        </div>
        <div class="modern-modal-footer">
          <button class="modern-btn-primary" @click="updatePlacemark">
            Update
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Polygon Modal -->
    <div v-if="showEditPolygonModal" class="modern-modal-overlay" @click="closeEditPolygonModal">
      <div class="modern-modal-content" @click.stop>
        <div class="modern-modal-header">
          <div class="modal-icon">
            <i class="fas fa-project-diagram"></i>
          </div>
          <div class="modal-title-section">
            <h3>Edit Polygon</h3>
            <p>Modify polygon information</p>
          </div>
          <button class="modern-btn-close" @click="closeEditPolygonModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modern-modal-body">
          <div class="modern-form-group">
            <label class="modern-label">
              <i class="fas fa-tag"></i>
              Nama Polygon
            </label>
            <input 
              type="text" 
              v-model="editPolygonData.nama_polygon" 
              class="modern-form-input"
              placeholder="Enter polygon name..."
            />
          </div>
          <div class="modern-form-group">
            <label class="modern-label">
              <i class="fas fa-file-alt"></i>
              Deskripsi
            </label>
            <textarea 
              v-model="editPolygonData.deskripsi" 
              class="modern-form-textarea"
              placeholder="Enter description..."
              rows="3"
            ></textarea>
          </div>
        </div>
        <div class="modern-modal-footer">
          <button class="modern-btn-primary" @click="updatePolygon"> 
            Update Polygon
          </button>
        </div>
      </div>
    </div>

    <!-- ODP Modal (additive) -->
    <div v-if="showOdpModal" class="modern-modal-overlay" @click="closeOdpModal">
      <div class="modern-modal-content" @click.stop>
        <div class="modern-modal-header">
          <div class="modal-icon">
            <i class="fas fa-box"></i>
          </div>
          <div class="modal-title-section">
            <h3>Tambah ODP</h3>
            <p>Lengkapi informasi ODP</p>
          </div>
          <button class="modern-btn-close" @click="closeOdpModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modern-modal-body">
          <div class="modern-form-grid two-cols">
            <div class="modern-form-group">
              <label class="modern-label">ID Placemark</label>
              <input type="text" class="modern-form-input" :value="odpForm.id_placemark || '-'" disabled />
            </div>
            <div class="modern-form-group">
              <label class="modern-label">Nama ODP</label>
              <select v-model="odpSelectedName" class="modern-form-input">
                <option value="">-- Pilih dari daftar --</option>
                <option v-for="name in odpOptions" :key="name" :value="name">{{ name }}</option>
              </select>
              <div style="margin-top:6px;color:#1e7e34;font-size:12px;">
                Atau tambahkan baru di bawah ini
              </div>
              <input
                type="text"
                v-model="odpNewName"
                class="modern-form-input"
                placeholder="Tambah nama ODP baru (opsional)"
                style="margin-top:6px;"
              />
            </div>
            <div class="modern-form-group">
              <label class="modern-label">Kode Layanan</label>
              <select v-model="odpForm.kd_layanan" class="modern-form-input">
                <option disabled value="">Pilih Layanan</option>
                <option value="malang">Malang</option>
                <option value="pasuruan">Pasuruan</option>
                <option value="batu">Batu</option>
              </select>
            </div>
            <div class="modern-form-group">
              <label class="modern-label">Status WO</label>
              <select v-model="odpForm.status_wo" class="modern-form-input">
                <option disabled value="">Pilih Status</option>
                <option value="sudah terpasang">Sudah terpasang</option>
                <option value="belum terpasang">Belum terpasang</option>
              </select>
            </div>
            <div class="modern-form-group">
              <label class="modern-label">Status Tiang</label>
              <select v-model="odpForm.status_tiang" class="modern-form-input">
                <option disabled value="">Pilih Status Tiang</option>
                <option value="ada tiang">Ada tiang</option>
                <option value="tidak ada tiang">Tidak ada tiang</option>
              </select>
            </div>
            <div class="modern-form-group" style="grid-column: 1 / -1;">
              <label class="modern-label">Keterangan Lain</label>
              <textarea v-model="odpForm.lain_lain" class="modern-form-textarea" rows="3" placeholder="Keterangan tambahan..."></textarea>
            </div>
          </div>
        </div>
        <div class="modern-modal-footer">
          <button class="modern-btn-secondary" @click="closeOdpModal">Batal</button>
          <button class="modern-btn-primary" @click="saveOdp">Simpan</button>
        </div>
      </div>
    </div>

    <!-- Modern Toast Notifications -->
    <div class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" 
           :class="['toast', `toast-${toast.type}`, { 'toast-confirmation': toast.isConfirmation }]">
        
        <!-- Regular Toast -->
        <template v-if="!toast.isConfirmation">
          <div class="toast-icon">
            <i :class="getToastIcon(toast.type)"></i>
          </div>
          <div class="toast-content">
            <h4 class="toast-title">{{ toast.title }}</h4>
            <p class="toast-message">{{ toast.message }}</p>
          </div>
          <button class="toast-close" @click.stop="removeToast(toast.id)">
            <i class="fas fa-times"></i>
          </button>
        </template>

        <!-- Confirmation Toast -->
        <template v-if="toast.isConfirmation">
          <div class="confirmation-content">
            <div class="confirmation-header">
              <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div class="confirmation-text">
                <h4 class="confirmation-title">{{ toast.title }}</h4>
                <p class="confirmation-message">{{ toast.message }}</p>
                <p v-if="toast.subtitle" class="confirmation-subtitle">{{ toast.subtitle }}</p>
              </div>
            </div>
            <div class="confirmation-actions">
              <button class="confirmation-btn cancel" @click="handleConfirmation(toast.id, false)">
                <i class="fas fa-times"></i>
                {{ toast.cancelText }}
              </button>
              <button class="confirmation-btn confirm" @click="handleConfirmation(toast.id, true)">
                <i class="fas fa-check"></i>
                {{ toast.confirmText }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>
</template>


<script>
export default {
  name: "Project",
  props: {
    currentProject: Object
  },
  data() {
    return {
      // Map related
      map: null,
      addingMarker: false,
      addingPlacemark: false, // State untuk mode placemark saja
      drawingPolygon: false,
      placemarks: [],
      markers: [],
      polygons: [], // Changed to array to support multiple polygons
      polygon: null, // Keep for backward compatibility
      polygonPath: [],
      polygonMarkers: [],
      polyline: null,
      searchMarker: null,
      apiKey: "AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc",
      
      // Auto polygon properties
      autoPolygon: null,
      autoPolygonPath: [],
      directionsService: null,
      // In-progress polygon aggregation (satu kotak per FINISH)
      inProgressPolygon: {
        path: [],
        distance: 0
      },
      
      // Polygon grouping untuk multiple polygons terpisah
      currentPolygonGroup: 1, // ID grup polygon saat ini
      finishedPolygonGroups: [], // Array grup polygon yang sudah selesai
      
      // Backend integration
      baseUrl: "http://localhost:8000",
      savedProjects: [],
      currentProjectId: null,
      currentProject: null,
      showImportDialog: false,
      loading: false,
      loadingMessage: '',
      currentUser: null,

      // Toast notifications
      toasts: [],
      toastId: 0,

      // Sidebar states
      showPlacemarks: true,
      showPolygons: true,
      polygonArea: null,
      currentPolygonIndex: -1, // Track which polygon is being edited
      searchQuery: '',
      showEditModal: false,
      editIndex: null,
      editName: "",

      // Edit modal states
      showEditPlacemarkModal: false,
      showEditPolygonModal: false,
      editPlacemarkData: {
        id_placemark: null,
        nama_placemark: '',
        deskripsi: '',
        alamat: '',
        kelurahan: '',
        kecamatan: '',
        kota: '',
        provinsi: ''
      },
      editPolygonData: {
        id_polygon: null,
        nama_polygon: '',
        deskripsi: ''
      },
      editPlacemarkIndex: null,
      editPolygonIndex: null,
      
      // Mode management
      projectMode: 'edit', // 'edit' atau 'view'
      isReadOnly: false,

      // ODP state (additive)
      showOdpModal: false,
      odpForm: {
        id_placemark: null,
        nama_odp: '',
        kd_layanan: '',
        status_wo: '',
        status_tiang: '',
        lain_lain: ''
      },
      lastSavedPlacemark: null,
      // Dropdown opsi ODP yang pernah dibuat (reuse)
      odpOptions: [],
      // Keputusan pra-konfirmasi saat klik "Tambah Mark"
      odpIntentAfterPlacement: null,
      // UI state untuk pilihan ODP: pilih dari daftar atau ketik baru
      odpSelectedName: '',
      odpNewName: ''
    };
  },
  
  async mounted() {
    // Setup keyboard events
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (this.drawingPolygon) {
          this.clearPolygon();
        } else if (this.addingMarker) {
          this.addingMarker = false;
          this.updateMapCursor();
        } else if (this.addingPlacemark) {
          this.addingPlacemark = false;
          this.updateMapCursor();
        }
      }
    });

    // Initialize Google Maps
    window.initMap = this.initMap.bind(this);
    const existingScript = document.querySelector('script[src*="maps.googleapis.com"]');
    
    if (!existingScript) {
      const script = document.createElement("script");
      script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&libraries=places,geometry&callback=initMap`;
      script.async = true;
      script.defer = true;
      script.onerror = () => {
        console.error('Failed to load Google Maps API');
        alert('Failed to load Google Maps. Please check your API key and internet connection.');
      };
      document.head.appendChild(script);
    } else {
      this.initMap();
    }

    const saved = localStorage.getItem('currentProject');
    if (saved) {
      this.currentProject = JSON.parse(saved);
    }

    // Set mode berdasarkan query parameter
    this.setProjectMode();

    // Load project data from route parameter
    const projectId = this.$route.params.id;
    if (projectId) {
      this.currentProjectId = projectId;
      // Wait a bit for map to be initialized
      setTimeout(() => {
        this.loadProjectData(projectId);
      }, 1000);
    }

    // Preload opsi ODP untuk dropdown reuse
    this.loadOdpOptions();
  },

  computed: {
    // Computed property untuk mengecek apakah dalam mode read-only
    isViewMode() {
      return this.projectMode === 'view';
    },
    
    // Computed property untuk mengecek apakah sedang create project baru
    isCreateMode() {
      // Cek apakah tidak ada project ID di route atau currentProject kosong/baru
      const projectId = this.$route.params.id;
      return !projectId || !this.currentProject || 
             (this.currentProject && !this.currentProject.id_project);
    },
    
    // Computed property untuk button states
    buttonClass() {
      return this.isViewMode ? 'disabled' : '';
    }
  },

  watch: {
    '$route.params.id'(newId) {
      if (newId && this.map) {
        this.loadProjectData(newId);
      }
    },
    
    // Watch untuk perubahan query parameter
    '$route.query.mode'() {
      this.setProjectMode();
    }
  },

  methods: {
    // Set mode project berdasarkan query parameter
    setProjectMode() {
      const mode = this.$route.query.mode;
      if (mode === 'view') {
        this.projectMode = 'view';
        this.isReadOnly = true;
        console.log('🔒 Project set to READ-ONLY mode');
      } 
    },

    async apiCall(endpoint, method = 'GET', data = null) {
      this.loading = true;
      this.loadingMessage = 'Processing...';
      
      try {
        const config = {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        };
        
        if (data && method !== 'GET') {
          config.body = JSON.stringify(data);
        }
        
        const response = await fetch(`${this.baseUrl}${endpoint}`, config);
        
        // Log untuk debugging
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
          // Coba baca response sebagai text untuk error message yang lebih baik
          const errorText = await response.text();
          console.error('Server error response:', errorText);
          throw new Error(`HTTP ${response.status}: ${errorText || response.statusText}`);
        }
        
        // Cek content-type sebelum parse JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          const textResponse = await response.text();
          console.warn('Non-JSON response received:', textResponse);
          throw new Error(`Server returned non-JSON response: ${contentType}`);
        }
        
        // Dapatkan response text terlebih dahulu
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Cek apakah response kosong
        if (!responseText.trim()) {
          throw new Error('Server returned empty response');
        }
        
        // Parse JSON dengan error handling yang lebih baik
        let result;
        try {
          result = JSON.parse(responseText);
        } catch (parseError) {
          console.error('JSON parse error:', parseError);
          console.error('Response text that failed to parse:', responseText);
          throw new Error(`Invalid JSON response from server: ${parseError.message}`);
        }
        
        this.loading = false;
        return result;
        
      } catch (error) {
        this.loading = false;
        console.error('API Error:', error);
        
        // Tampilkan error message yang lebih informatif
        const errorMessage = error.message || 'Unknown error occurred';
        alert(`API Error: ${errorMessage}`);
        
        return { success: false, error: errorMessage };
      }
    },

    // Load all projects from backend
    async loadAllProjects() {
      const result = await this.apiCall('/backend/api/project/readOne.php');
      if (result.success) {
        this.savedProjects = result.data || [];
        console.log('Projects loaded:', this.savedProjects.length);
      }
    },

    // Load all data (projects, placemarks, polygons)
    async loadAllData() {
      this.loadingMessage = 'Loading all data...';
      await Promise.all([
        this.loadAllProjects(),
        this.loadAllPlacemarks(),
        this.loadAllPolygons()
      ]);
      alert('Data berhasil dimuat!');
    },

    // Load specific project and show on map
    async loadProject(projectId) {
      const result = await this.apiCall(`/project/readOne.php?id_project=${projectId}`);
      if (result.success) {
        const project = result.data;
        
        // Clear current map
        this.clearAllMapData();
        
        // Load placemarks
        if (project.placemarks && project.placemarks.length > 0) {
          project.placemarks.forEach(placemark => {
            this.addMarkerToMap(placemark.lat, placemark.lng, false);
          });
        }
        
        // Load polygon
        if (project.polygon && project.polygon.length >= 3) {
          this.loadPolygonToMap(project.polygon);
        }
        
        this.currentProjectId = projectId;
        alert(`Project "${project.name}" berhasil dimuat!`);
      }
    },

    // Save current map state as project
    async saveProject() {
      if (this.isViewMode) {
        console.log('🔒 Save project disabled in read-only mode');
        return;
      }
      // Get current user from localStorage
      const userData = localStorage.getItem('user');
      if (!userData) {
        alert('User tidak ditemukan. Silakan login ulang.');
        this.$router.push('/');
        return;
      }

      const currentUser = JSON.parse(userData);
      console.log('Current user for save:', currentUser);

      // Get project ID dari route parameter atau currentProject
      const projectId = this.$route.params.id || (this.currentProject ? this.currentProject.id_project : null);
      
      if (!projectId) {
        alert('Project ID tidak ditemukan. Silakan buat project baru dari Dashboard.');
        return;
      }

      console.log('Saving project ID:', projectId);

      // Siapkan data untuk disimpan
      let dataToSave = {
        id_project: projectId,
        name: this.currentProject ? this.currentProject.nama_project : 'Project ' + new Date().toLocaleString(),
        description: 'Project with markers and polygon data',
        timestamp: new Date().toISOString()
      };

      // Jika ada polygon legacy, simpan koordinatnya (backward compatibility)
      if (this.polygon) {
        const polygonCoords = this.polygon.getPath().getArray().map(coord => ({
          lat: coord.lat(),
          lng: coord.lng()
        }));
        dataToSave.polygon = polygonCoords;
        console.log('Saving legacy polygon with', polygonCoords.length, 'points');
      }

      console.log('Data to save:', dataToSave);
      console.log(`Project has ${this.polygons.length} polygons stored in database`);

      // Gunakan baseUrl yang benar dan endpoint save_project
      const result = await this.apiCall('/backend/api/project/save_project.php', 'POST', dataToSave);
      
      if (result && result.status === 'success') {
        // Show success toast
        this.showToast('success', 'Project Saved', 
          `Project berhasil disimpan!\nMarkers: ${this.placemarks.length}\nPolygons: ${this.polygons.length}`, 
          4000);
        
        // Optional: Update project info
        if (result.data) {
          console.log('Save result:', result.data);
        }

        // Redirect to dashboard after 2 seconds
        setTimeout(() => {
          this.$router.push('/dashboard');
        }, 2000);
        
      } else {
        this.showToast('error', 'Save Failed', 
          result.message || 'Gagal menyimpan project', 
          5000);
      }
    },

    // Update existing project
    async updateProject(projectId, updateData) {
      const result = await this.apiCall('/backend/api/project/update.php', 'PUT', {
        id_project: projectId,
        ...updateData
      });
      
      if (result.success) {
        alert('Project berhasil diupdate!');
        await this.loadAllProjects();
      }
    },

    // Delete project
    async deleteProject(projectId) {
      if (!confirm('Yakin ingin menghapus project ini?')) return;
      
      const result = await this.apiCall('/backend/api/project/delete.php', 'DELETE', { id_project: projectId });
      
      if (result.success) {
        alert('Project berhasil dihapus!');
        await this.loadAllProjects();
        
        // If current project is deleted, clear map
        if (this.currentProjectId === projectId) {
          this.clearAllMapData();
          this.currentProjectId = null;
        }
      }
    },

    // Toast notification methods
    showToast(type, title, message, duration = 5000) {
      const toast = {
        id: ++this.toastId,
        type,
        title,
        message,
        duration
      };
      
      this.toasts.push(toast);
      
      // Auto remove after duration
      setTimeout(() => {
        this.removeToast(toast.id);
      }, duration);
    },
    
    removeToast(id) {
      const index = this.toasts.findIndex(toast => toast.id === id);
      if (index > -1) {
        this.toasts.splice(index, 1);
      }
    },
    
    getToastIcon(type) {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      };
      return icons[type] || icons.info;
    },

    // Modern confirmation dialog
    showConfirmation(title, message, subtitle = '', confirmText = 'Confirm', cancelText = 'Cancel') {
      return new Promise((resolve) => {
        // Create a temporary confirmation toast
        const confirmToast = {
          id: ++this.toastId,
          type: 'warning',
          title: title,
          message: message,
          subtitle: subtitle,
          confirmText: confirmText,
          cancelText: cancelText,
          isConfirmation: true,
          resolve: resolve
        };
        
        this.toasts.push(confirmToast);
      });
    },

    handleConfirmation(toastId, confirmed) {
      const toast = this.toasts.find(t => t.id === toastId);
      if (toast && toast.isConfirmation) {
        toast.resolve(confirmed);
        this.removeToast(toastId);
      }
    },

    // Save individual placemark
    async savePlacemark(lat, lng, name = '', description = '', alamat = '', kelurahan = '', kecamatan = '', kota = '', provinsi = '') {
      const projectId = this.currentProjectId || this.$route.params.id;
      if (!projectId) {
        console.error("Project ID tidak ditemukan!");
        return;
      }

      const result = await this.apiCall('/backend/api/placemark/create.php', 'POST', {
        id_project: projectId,
        latitude: lat,
        longitude: lng,
        nama_placemark: name && name.trim() !== '' ? name : `Tiang Lepas ${Date.now()}`,
        deskripsi: description || 'Auto generated placemark',
        alamat,
        kelurahan,
        kecamatan,
        kota,
        provinsi
      });

      if (result.success) {
        console.log('Placemark saved:', result.data);
        this.showToast('success', 'Placemark Added', 'Marker berhasil ditambahkan ke peta!', 3000);

        // Pastikan array placemarks ada
        if (!this.currentProject.placemarks) {
          this.currentProject.placemarks = [];
        }

        // Tambah langsung ke array biar sidebar update otomatis
        const savedPm = {
          id_placemark: result.data.id_placemark, // ambil dari backend RETURNING
          nama_placemark: result.data.nama_placemark,
          deskripsi: result.data.deskripsi,
          latitude: result.data.latitude,
          longitude: result.data.longitude,
          alamat: result.data.alamat || '',
          rt: result.data.rt || '',
          rw: result.data.rw || '',
          kelurahan: result.data.kelurahan || '',
          kecamatan: result.data.kecamatan || '',
          kota: result.data.kota || '',
          provinsi: result.data.provinsi || ''
        };
        this.currentProject.placemarks.push(savedPm);
        this.lastSavedPlacemark = savedPm;

        // Buka ODP modal berdasar keputusan pra-konfirmasi; jika belum ada, fallback konfirmasi
        let openOdp = this.odpIntentAfterPlacement;
        if (openOdp === null) {
          openOdp = await this.showConfirmation(
            'Tambahkan ODP jika ada',
            'Ingin menambahkan data ODP untuk placemark ini?',
            '',
            'Tambah',
            'Tidak'
          );
        }
        if (openOdp) {
          this.odpForm = {
            id_placemark: savedPm.id_placemark,
            nama_odp: '',
            kd_layanan: '',
            status_wo: '',
            status_tiang: '',
            lain_lain: ''
          };
          await this.loadOdpOptions();
          this.odpSelectedName = '';
          this.odpNewName = '';
          this.showOdpModal = true;
        }
      } else {
        console.error('Save placemark failed:', result.message);
        this.showToast('error', 'Save Failed', 'Gagal menyimpan marker: ' + result.message, 4000);
      }
    },

    async saveOdp() {
      try {
        if (!this.odpForm.id_placemark) {
          this.showToast('error', 'ODP', 'ID Placemark tidak tersedia', 3000);
          return;
        }
        // Tentukan nama ODP dari pilihan atau input baru
        const chosenName = (this.odpNewName && this.odpNewName.trim().length > 0)
          ? this.odpNewName.trim()
          : (this.odpSelectedName || '').trim();
        if (!chosenName) {
          this.showToast('warning', 'Validasi', 'Pilih nama ODP dari daftar atau isi nama ODP baru.', 3500);
          return;
        }
        this.odpForm.nama_odp = chosenName;
        const res = await this.apiCall('/backend/api/odp/create.php', 'POST', { ...this.odpForm });
        if (res && res.success) {
          this.showToast('success', 'ODP', 'ODP berhasil disimpan', 3000);
          this.showOdpModal = false;
          // Refresh opsi reuse setelah menyimpan
          this.loadOdpOptions();
          // Reset UI input
          this.odpSelectedName = '';
          this.odpNewName = '';
          // Mark the related placemark in sidebar
          let pmIdx = this.placemarks.findIndex(pm => pm.id_placemark === this.odpForm.id_placemark);
          if (pmIdx === -1 && this.lastSavedPlacemark) {
            const tLat = parseFloat(this.lastSavedPlacemark.latitude);
            const tLng = parseFloat(this.lastSavedPlacemark.longitude);
            pmIdx = this.placemarks.findIndex(pm => Math.abs(pm.lat - tLat) < 1e-6 && Math.abs(pm.lng - tLng) < 1e-6);
          }
          if (pmIdx !== -1) {
            this.placemarks[pmIdx].hasODP = true;
            this.placemarks[pmIdx].odpInfo = res.data;
          }
        } else {
          this.showToast('error', 'ODP', res?.message || 'Gagal menyimpan ODP', 4000);
        }
      } catch (err) {
        console.error(err);
        this.showToast('error', 'ODP', err.message || 'Terjadi kesalahan', 4000);
      }
    },

    closeOdpModal() {
      this.showOdpModal = false;
    },

    // Muat daftar opsi ODP (nama/model) dari database untuk dropdown reuse
    async loadOdpOptions() {
      try {
        const res = await this.apiCall('/backend/api/odp/read.php', 'GET');
        if (res && res.success && Array.isArray(res.data)) {
          const names = res.data
            .map(r => (r.nama_odp || '').trim())
            .filter(n => n.length > 0);
          // Unique & sorted
          this.odpOptions = Array.from(new Set(names)).sort((a, b) => a.localeCompare(b));
        }
      } catch (e) {
        console.error('Gagal memuat opsi ODP:', e);
      }
    },

    // Load all placemarks
    async loadAllPlacemarks() {
      const result = await this.apiCall('/backend/api/placemark/read.php');
      if (result.success) {
        // mapping biar cocok dengan struktur di Vue
        this.placemarks = result.data.map(p => ({
          id_placemark: p.id_placemark,
          nama_placemark: p.nama_placemark,
          lat: parseFloat(p.latitude),
          lng: parseFloat(p.longitude),
          deskripsi: p.deskripsi,
          created_at: p.created_at,
          type: 'polygon',  // Default ke polygon untuk data existing
          polygonGroup: 1   // Default ke grup 1 untuk data existing
        }));

        console.log('Placemarks loaded:', this.placemarks);

        // pasang ke map tapi jangan overwrite array
        this.placemarks.forEach(pm => {
          // Gunakan warna sesuai type
          if (pm.type === 'polygon') {
            this.addMarkerToMap(pm.lat, pm.lng, false, pm.nama_placemark);
          } else {
            // Untuk placemark biru yang akan ditambahkan kemudian
            this.addPlacemarkOnly(pm.lat, pm.lng, false);
          }
        });
        
        // Update currentPolygonGroup untuk data yang sudah ada
        const maxGroup = Math.max(...this.placemarks.map(pm => pm.polygonGroup || 1));
        this.currentPolygonGroup = maxGroup + 1;
        
        // Trigger auto polygon creation if we have 2+ polygon placemarks in group 1
        const group1Placemarks = this.placemarks.filter(pm => pm.type === 'polygon' && pm.polygonGroup === 1);
        if (group1Placemarks.length >= 2) {
          setTimeout(() => {
            // Sementara set current group ke 1 untuk render existing data
            const originalGroup = this.currentPolygonGroup;
            this.currentPolygonGroup = 1;
            this.createAutoPolygon();
            this.currentPolygonGroup = originalGroup;
          }, 1000);
        }
      } else {
        console.error('Failed to load placemarks:', result.message);
      }
    },

    // Save individual polygon
    async savePolygon(coordinates, name = '', description = '') {
      const result = await this.apiCall('/backend/api/polygon/create.php', 'POST', {
        id_project: this.currentProjectId,
        coordinate: coordinates,
        nama_polygon: name || `Polygon ${Date.now()}`,
        deskripsi: description
      });
      
      if (result.success) {
        console.log('Polygon saved:', result.data);
      } else {
        console.error('Polygon save failed:', result.message);
      }
    },

    // Load all polygons
    async loadAllPolygons() {
      const result = await this.apiCall('/backend/api/polygon/read.php');
      if (result.success) {
        console.log('Polygons loaded:', result.data.length);
      }
    },

    // Load polygons for specific project
    async loadProjectPolygons(projectId) {
      if (!projectId) {
        console.error('Project ID is required for loading polygons');
        return;
      }

      const result = await this.apiCall(`/backend/api/polygon/read.php?id_project=${projectId}`);
      if (result.success && result.data) {
        this.polygons = result.data.map(polygonData => {
          // Parse coordinate JSON string if needed
          let coordinates = [];
          try {
            coordinates = typeof polygonData.coordinate === 'string' 
              ? JSON.parse(polygonData.coordinate) 
              : polygonData.coordinate || [];
          } catch (e) {
            console.error('Error parsing polygon coordinates:', e);
            coordinates = [];
          }

          return {
            id: polygonData.id_polygon,
            id_polygon: polygonData.id_polygon, // Keep both for compatibility
            nama_polygon: polygonData.nama_polygon,
            deskripsi: polygonData.deskripsi,
            coordinates: coordinates,
            area: null, // Remove area calculation for polylines
            panjang_meter: polygonData.panjang_meter || 0, // Add distance field
            googlePolygon: null // Will be set when rendered on map
          };
        });

        // Render all polygons on map
        this.renderAllPolygonsOnMap();
        
        console.log(`Loaded ${this.polygons.length} polygons for project ${projectId}`);
      } else {
        console.error('Failed to load project polygons:', result.message);
        this.polygons = [];
      }
    },

    // =============== AUTO POLYGON METHODS ===============
    
    // Create auto polygon when 2+ placemarks exist
    async createAutoPolygon() {
      // Filter hanya placemark yang untuk polygon (hijau) DAN dari grup saat ini
      const currentGroupPlacemarks = this.placemarks.filter(pm => 
        pm.type === 'polygon' && pm.polygonGroup === this.currentPolygonGroup
      );
      
      if (currentGroupPlacemarks.length < 2) {
        console.log('Not enough polygon placemarks in current group for auto polygon');
        return;
      }

      // Ensure DirectionsService is initialized
      if (!this.directionsService) {
        console.log('DirectionsService not initialized, creating new instance');
        this.directionsService = new google.maps.DirectionsService();
      }

      try {
        console.log(`Creating auto polygon with ${currentGroupPlacemarks.length} placemarks from group ${this.currentPolygonGroup}`);
        
        // Clear hanya polygon grup saat ini yang sedang dibuat (bukan yang sudah selesai)
        if (this.autoPolygon) {
          this.autoPolygon.setMap(null);
          this.autoPolygon = null;
        }
        // CATATAN: Polygon yang sudah selesai tetap di map karena disimpan di finishedPolygonGroups

        // Get coordinates from current group polygon placemarks only
        const waypoints = currentGroupPlacemarks.map(pm => ({
          lat: pm.lat,
          lng: pm.lng
        }));

        // Create route using Google Directions API
        if (waypoints.length === 2) {
          await this.createSimpleRoute(waypoints);
        } else {
          await this.createOptimizedRoute(waypoints);
        }

      } catch (error) {
        console.error('Error creating auto polygon:', error);
        this.showToast('error', 'Polygon Error', 'Failed to create automatic polygon', 3000);
      }
    },

    // Create simple route for 2 points
    async createSimpleRoute(waypoints) {
      return new Promise((resolve, reject) => {
        if (!this.directionsService) {
          console.error('DirectionsService not available');
          reject('DirectionsService not available');
          return;
        }

        const request = {
          origin: waypoints[0],
          destination: waypoints[1],
          travelMode: google.maps.TravelMode.DRIVING,
          unitSystem: google.maps.UnitSystem.METRIC
        };

        this.directionsService.route(request, (result, status) => {
          if (status === 'OK') {
            const route = result.routes[0];
            const path = route.overview_path;
            
            // Calculate distance from the route
            const distance = route.legs.reduce((total, leg) => total + leg.distance.value, 0);
            
            this.renderAutoPolygon(path, distance);
            resolve(result);
          } else {
            console.error('Directions request failed:', status);
            // Fallback: create straight line polygon with calculated distance
            this.createFallbackPolygon(waypoints);
            reject(status);
          }
        });
      });
    },

    // Create optimized route for 3+ points
    async createOptimizedRoute(waypoints) {
      return new Promise((resolve, reject) => {
        if (!this.directionsService) {
          console.error('DirectionsService not available');
          reject('DirectionsService not available');
          return;
        }

        const origin = waypoints[0];
        const destination = waypoints[waypoints.length - 1];
        const waypointsArray = waypoints.slice(1, -1).map(point => ({
          location: point,
          stopover: true
        }));

        const request = {
          origin: origin,
          destination: destination,
          waypoints: waypointsArray,
          optimizeWaypoints: true,
          travelMode: google.maps.TravelMode.DRIVING,
          unitSystem: google.maps.UnitSystem.METRIC
        };

        this.directionsService.route(request, (result, status) => {
          if (status === 'OK') {
            const route = result.routes[0];
            const path = route.overview_path;
            
            // Calculate total distance from all legs
            const distance = route.legs.reduce((total, leg) => total + leg.distance.value, 0);
            
            this.renderAutoPolygon(path, distance);
            resolve(result);
          } else {
            console.error('Directions request failed:', status);
            // Fallback: create straight line polygon with calculated distance
            this.createFallbackPolygon(waypoints);
            reject(status);
          }
        });
      });
    },

    // Fallback method when Directions API fails
    createFallbackPolygon(waypoints) {
      console.log('Using fallback polygon creation method');
      
      // Calculate distance using spherical geometry
      const distance = this.calculatePolygonDistance(waypoints);
      
      // Use the waypoints directly as polygon path
      this.renderAutoPolygon(waypoints, distance);
    },

    // Render auto polygon on map
    renderAutoPolygon(path, distanceInMeters) {
      // Clear existing auto polygon
      if (this.autoPolygon) {
        this.autoPolygon.setMap(null);
      }

      // Convert path to simple coordinate array
      this.autoPolygonPath = path.map(point => {
        // Handle both Google Maps LatLng objects and simple coordinate objects
        if (typeof point.lat === 'function') {
          // Google Maps LatLng object
          return {
            lat: point.lat(),
            lng: point.lng()
          };
        } else {
          // Simple coordinate object
          return {
            lat: point.lat,
            lng: point.lng
          };
        }
      });

      // Create polyline (not filled polygon) dengan warna berbeda per grup
      const polygonColor = this.getPolygonColorForGroup(this.currentPolygonGroup);
      this.autoPolygon = new google.maps.Polyline({
        path: this.autoPolygonPath,
        strokeColor: polygonColor,
        strokeOpacity: 0.8,
        strokeWeight: 4,
        editable: false,
        draggable: false
      });

      this.autoPolygon.setMap(this.map);
      
      console.log(`Auto polygon created with distance: ${distanceInMeters} meters`);
      
      // Simpan sementara; baru dipush ke sidebar saat finishCurrentPolygon()
      this.inProgressPolygon.path = [...this.autoPolygonPath];
      this.inProgressPolygon.distance = distanceInMeters;
    },

    // Calculate polygon distance using spherical geometry (fallback method)
    calculatePolygonDistance(coordinates) {
      if (!coordinates || coordinates.length < 2) {
        return 0;
      }

      let totalDistance = 0;
      for (let i = 0; i < coordinates.length - 1; i++) {
        const from = new google.maps.LatLng(coordinates[i].lat, coordinates[i].lng);
        const to = new google.maps.LatLng(coordinates[i + 1].lat, coordinates[i + 1].lng);
        totalDistance += google.maps.geometry.spherical.computeDistanceBetween(from, to);
      }

      return Math.round(totalDistance); // Return in meters
    },

    // Save auto polygon to database
  async saveAutoPolygon(distanceInMeters) {
      const projectId = this.$route.params.id || (this.currentProject ? this.currentProject.id_project : null);
      if (!projectId) {
        console.error('Project ID not found for saving auto polygon');
        return;
      }

      try {
        const result = await this.apiCall('/backend/api/polygon/create.php', 'POST', {
          id_project: projectId,
          nama_polygon: `Polygon ${this.polygons.length + 1}`,
          deskripsi: `Auto generated polygon - ${distanceInMeters}m`,
          coordinate: JSON.stringify(this.autoPolygonPath),
          panjang_meter: distanceInMeters
        });

        if (result.success) {
          console.log('Auto polygon saved (deferred push already added on FINISH).');
        } else {
          console.error('Failed to save auto polygon:', result.message);
        }
      } catch (error) {
        console.error('Error saving auto polygon:', error);
      }
    },

    // =============== MAP HELPER METHODS ===============
    
    addMarkerToMap(lat, lng, save = true) {
      if (!this.map) {
        console.error('Map not initialized');
        return;
      }

      console.log('Adding marker to map:', lat, lng);

      const greenIcon = {
        path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
        fillColor: "#3BB142",
        fillOpacity: 1,
        strokeColor: "rgba(93, 215, 103, 0.25)",
        strokeWeight: 2,
        scale: 2,
        anchor: { x: 12, y: 24 }
      };
      
      const marker = new google.maps.Marker({
        position: { lat, lng },
        map: this.map,
        icon: greenIcon,
        draggable: true
      });

      this.markers.push(marker);

      if (save) {
        // Add to placemarks array for sidebar display
        // Decide default name for green (polygon) marker
        const defaultName = `Tiang Terhubung ${this.placemarks.length + 1}`;

        this.placemarks.push({
          lat: lat,
          lng: lng,
          nama_placemark: defaultName,
          address: 'Loading address...',
          id_placemark: Date.now(),
          type: 'polygon',  // Marker untuk polygon
          polygonGroup: this.currentPolygonGroup 
        });

        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat, lng } }, (results, status) => {
          if (status === "OK" && results[0]) {
            const alamat = results[0].formatted_address;
            
            // Update address in placemarks array
            const placemarkIndex = this.placemarks.length - 1;
            if (this.placemarks[placemarkIndex]) {
              this.placemarks[placemarkIndex].address = alamat;
            }

            // 🔹 Pecah address_components
            const components = results[0].address_components;
            let kelurahan = '', kecamatan = '', kota = '', provinsi = '';

            components.forEach(c => {
              if (c.types.includes("administrative_area_level_4")) kelurahan = c.long_name;
              if (c.types.includes("administrative_area_level_3")) kecamatan = c.long_name;
              if (c.types.includes("administrative_area_level_2")) kota = c.long_name;
              if (c.types.includes("administrative_area_level_1")) provinsi = c.long_name;
            });

            this.savePlacemark(lat, lng, defaultName, '', alamat, kelurahan, kecamatan, kota, provinsi);
          } else {
            this.savePlacemark(lat, lng, defaultName);
          }

          console.log(JSON.stringify({
            nama_placemark: this.newPlacemark.nama_placemark,
            lat: this.newPlacemark.lat,
            lng: this.newPlacemark.lng,
          }))
        });
      }

      console.log('Marker added successfully. Total markers:', this.markers.length);
      
      // Trigger auto polygon creation if we have 2 or more markers in current group
      const currentGroupMarkers = this.placemarks.filter(pm => 
        pm.type === 'polygon' && pm.polygonGroup === this.currentPolygonGroup
      );
      if (currentGroupMarkers.length >= 2) {
        setTimeout(() => {
          this.createAutoPolygon();
        }, 1000); // Small delay to ensure marker is properly added
      }
    },

    // Method untuk menambahkan placemark saja (tanpa auto polygon)
    addPlacemarkOnly(lat, lng, save = true) {
      if (!this.map) {
        console.error('Map not initialized');
        return;
      }

      console.log('Adding placemark only to map:', lat, lng);

      const blueIcon = {
        path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
        fillColor: "#3BB142",
        fillOpacity: 1,
        strokeColor: "rgba(33, 150, 243, 0.25)",
        strokeWeight: 2,
        scale: 2,
        anchor: { x: 12, y: 24 }
      };
      
      const marker = new google.maps.Marker({
        position: { lat, lng },
        map: this.map,
        icon: blueIcon,
        draggable: true
      });

      this.markers.push(marker);

      if (save) {
        // Add to placemarks array for sidebar display
        // Default name for blue (placemark-only) marker
        const defaultName = `Tiang Lepas ${this.placemarks.length + 1}`;

        this.placemarks.push({
          lat: lat,
          lng: lng,
          nama_placemark: defaultName,
          address: 'Loading address...',
          id_placemark: Date.now(),
          type: 'placemark'  // Marker biru hanya untuk placemark
        });

        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat, lng } }, (results, status) => {
          if (status === "OK" && results[0]) {
            const alamat = results[0].formatted_address;
            
            // Update address in placemarks array
            const placemarkIndex = this.placemarks.length - 1;
            if (this.placemarks[placemarkIndex]) {
              this.placemarks[placemarkIndex].address = alamat;
            }

            // 🔹 Pecah address_components
            const components = results[0].address_components;
            let kelurahan = '', kecamatan = '', kota = '', provinsi = '';

            components.forEach(c => {
              if (c.types.includes("administrative_area_level_4")) kelurahan = c.long_name;
              if (c.types.includes("administrative_area_level_3")) kecamatan = c.long_name;
              if (c.types.includes("administrative_area_level_2")) kota = c.long_name;
              if (c.types.includes("administrative_area_level_1")) provinsi = c.long_name;
            });

            this.savePlacemark(lat, lng, defaultName, '', alamat, kelurahan, kecamatan, kota, provinsi);
          } else {
            this.savePlacemark(lat, lng, defaultName);
          }
        });
      }

      console.log('Placemark added successfully. Total markers:', this.markers.length);
      
      // TIDAK ada auto polygon creation untuk placemark mode
      this.showToast('success', 'Placemark Added', 
        'Placemark berhasil ditambahkan tanpa polygon!', 
        3000);
    },

    loadPolygonToMap(coordinates, mode = "view") {
      if (!this.map) {
        console.error("Map not initialized");
        return;
      }

      // Hapus polyline/polygon lama dari map
      if (this.polyline) {
        this.polyline.setMap(null);
        this.polyline = null;
      }
      if (this.polygon) {
        this.polygon.setMap(null);
        this.polygon = null;
      }

      if (mode === "view") {
        // Polyline untuk VIEW
        let openPath = [...coordinates];
        if (
          openPath.length > 2 &&
          openPath[0].lat === openPath[openPath.length - 1].lat &&
          openPath[0].lng === openPath[openPath.length - 1].lng
        ) {
          openPath.pop();
        }

        this.polyline = new google.maps.Polyline({
          path: openPath,
          strokeColor: "#FF0000",
          strokeOpacity: 1.0,
          strokeWeight: 4,
          editable: false,
          draggable: false,
        });
        this.polyline.setMap(this.map);
        this.polygonPath = openPath;

        console.log("Polyline loaded (VIEW mode, no closing line).");
      } else {
        // Polygon untuk EDIT
        this.polygon = new google.maps.Polygon({
          paths: coordinates,
          strokeColor: "#FF0000",
          strokeOpacity: 1.0,
          strokeWeight: 4,
          fillColor: "transparent",
          fillOpacity: 0,
          editable: true,
          draggable: false,
          clickable: true,
        });
        this.polygon.setMap(this.map);
        this.polygonPath = coordinates;

        console.log("Polygon loaded (EDIT mode, closed shape).");
      }
    },

    getPolygonColorForGroup(groupNumber) {
      const colors = ['#FF0000'];
      return colors[(groupNumber - 1) % colors.length];
    },
    
    clearAllMapData() {
      // Clear markers
      this.markers.forEach(marker => marker.setMap(null));
      this.markers = [];
      this.placemarks = [];
      
      // Clear polygon
      this.clearPolygon();
      
      // Clear auto polygon yang sedang dibuat
      if (this.autoPolygon) {
        this.autoPolygon.setMap(null);
        this.autoPolygon = null;
      }
      
      // Clear finished polygons
      this.finishedPolygonGroups.forEach(group => {
        if (group.googlePolygon) {
          group.googlePolygon.setMap(null);
        }
      });
      this.finishedPolygonGroups = [];
      this.currentPolygonGroup = 1; // Reset grup ke 1
      // Reset in-progress polygon aggregator
      this.inProgressPolygon.path = [];
      this.inProgressPolygon.distance = 0;
      
      // Clear all multiple polygons
      this.clearAllPolygonsFromMap();
      this.polygons = [];
      
      // Clear search marker
      if (this.searchMarker) {
        this.searchMarker.setMap(null);
        this.searchMarker = null;
      }
      
      console.log('All map data cleared');
    },

    // Render all polygons on map
    renderAllPolygonsOnMap() {
      if (!this.map) {
        console.error('Map not initialized');
        return;
      }

      // Clear existing polygons from map
      this.clearAllPolygonsFromMap();

      // Render each polygon 
      this.polygons.forEach((polygonData, index) => {
        if (polygonData.coordinates && polygonData.coordinates.length >= 1) {
          this.renderSinglePolygonOnMap(polygonData, index);
        }
      });

      this.fitMapToAllPolygons();
    },

    // Render single polygon on map (as open polyline without fill)
    renderSinglePolygonOnMap(polygonData, index) {
      if (!this.map || !polygonData.coordinates || polygonData.coordinates.length < 2) {
        return;
      }

      // Convert coordinates to Google Maps LatLng objects
      const path = polygonData.coordinates.map(coord => ({
        lat: parseFloat(coord.lat),
        lng: parseFloat(coord.lng)
      }));

      // Create polyline (open line without fill area) instead of polygon
      const polyline = new google.maps.Polyline({
        path: path,
        strokeColor: this.getPolygonColor(index),
        strokeOpacity: 0.8,
        strokeWeight: 3,
        editable: false,
        draggable: false
      });

      polyline.setMap(this.map);
      
      // Store reference to Google Maps polyline
      polygonData.googlePolygon = polyline; // Keep same property name for compatibility

      // Add click listener for polygon info
      polyline.addListener('click', (event) => {
        this.showPolygonInfo(polygonData, index, event.latLng);
      });

      console.log(`Rendered polyline ${index + 1} with ${path.length} points`);
    },

    // Get different colors for polygons
    getPolygonColor(index) {
      // Pastikan selalu merah konsisten; gunakan array untuk mencegah indexing string menjadi karakter tunggal
      const colors = ['#FF0000'];
      return colors[index % colors.length];
    },

    // Show polygon info window
    showPolygonInfo(polygonData, index, position) {
      const infoWindow = new google.maps.InfoWindow({
        content: `
          <div style="padding: 10px;">
            <h4>${polygonData.nama_polygon || `Polygon ${index + 1}`}</h4>
            <p><strong>Points:</strong> ${polygonData.coordinates.length}</p>
            ${polygonData.area ? `<p><strong>Area:</strong> ${polygonData.area}</p>` : ''}
            ${polygonData.deskripsi ? `<p><strong>Description:</strong> ${polygonData.deskripsi}</p>` : ''}
          </div>
        `,
        position: position
      });
      infoWindow.open(this.map);
    },

    // Clear all polygons from map
    clearAllPolygonsFromMap() {
      this.polygons.forEach(polygonData => {
        if (polygonData.googlePolygon) {
          polygonData.googlePolygon.setMap(null);
          polygonData.googlePolygon = null;
        }
      });
    },

    // Fit map to show all polygons
    fitMapToAllPolygons() {
      if (!this.map || this.polygons.length === 0) {
        return;
      }

      const bounds = new google.maps.LatLngBounds();
      let hasValidCoords = false;

      this.polygons.forEach(polygonData => {
        if (polygonData.coordinates && polygonData.coordinates.length > 0) {
          polygonData.coordinates.forEach(coord => {
            bounds.extend(new google.maps.LatLng(
              parseFloat(coord.lat), 
              parseFloat(coord.lng)
            ));
            hasValidCoords = true;
          });
        }
      });

      if (hasValidCoords) {
        this.map.fitBounds(bounds);
        // Prevent too much zoom for single point
        google.maps.event.addListenerOnce(this.map, 'bounds_changed', () => {
          if (this.map.getZoom() > 18) {
            this.map.setZoom(18);
          }
        });
      }
    },

    // Calculate area from coordinates
    calculatePolygonAreaFromCoords(coordinates) {
      if (!coordinates || coordinates.length < 3) {
        return null;
      }

      try {
        const path = coordinates.map(coord => 
          new google.maps.LatLng(parseFloat(coord.lat), parseFloat(coord.lng))
        );
        const area = google.maps.geometry.spherical.computeArea(path);
        return (area / 1000000).toFixed(2) + ' km²'; // Convert to km²
      } catch (error) {
        console.error('Error calculating polygon area:', error);
        return null;
      }
    },

    initMap() {
      this.map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: -7.983908, lng: 112.621391 },
        zoom: 13,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
      });

      // Initialize DirectionsService for auto polygon
      this.directionsService = new google.maps.DirectionsService();

      // Event listeners
      this.map.addListener("click", (e) => {
        // Prevent interaction in read-only mode
        if (this.isViewMode) {
          console.log('🔒 Map interaction disabled in read-only mode');
          return;
        }
        
        const lat = e.latLng.lat();
        const lng = e.latLng.lng();

        // Handle marker addition for different modes
        if (this.addingMarker === true) {
          this.addMarkerToMap(lat, lng);
          // JANGAN matikan mode - biarkan tetap aktif sampai user klik finish
          // this.addingMarker = false; // REMOVED: mode harus tetap aktif
          this.updateMapCursor();
          return;
        }
        
        // Handle placemark addition (without auto polygon)
        if (this.addingPlacemark === true) {
          this.addPlacemarkOnly(lat, lng);
          this.addingPlacemark = false;
          this.updateMapCursor();
          return;
        }
      });

      this.map.addListener("dblclick", (e) => {
        if (this.isViewMode) return;
        // Double click disabled for manual polygon - auto polygon feature is active
        console.log('ℹ️ Double click polygon creation disabled - auto polygon feature is active');
      });

      this.map.addListener("rightclick", (e) => {
        if (this.isViewMode) return;
        // Right click polygon clearing disabled - auto polygon feature is active
        console.log('ℹ️ Right click polygon clearing disabled - auto polygon feature is active');
      });

      console.log('Map initialized successfully');
      
      // Auto-load project data if we have a project ID
      const projectId = this.$route.params.id;
      if (projectId) {
        setTimeout(() => {
          this.loadProjectData(projectId);
        }, 500);
      }
    },

    // =============== UI METHODS ===============
    
    editProject(project) {
      const newName = prompt('Nama project baru:', project.name);
      const newDescription = prompt('Deskripsi project baru:', project.description);
      
      if (newName !== null) {
        this.updateProject(project.id, {
          name: newName,
          description: newDescription || project.description
        });
      }
    },

    importFile(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = JSON.parse(e.target.result);
          
          // Validate data structure
          if (data.placemarks || data.polygon) {
            // Clear current map
            this.clearAllMapData();
            
            // Load placemarks
            if (data.placemarks) {
              data.placemarks.forEach(placemark => {
                this.addMarkerToMap(placemark.lat, placemark.lng, false);
              });
            }
            
            // Load polygon
            if (data.polygon && data.polygon.length >= 3) {
              this.loadPolygonToMap(data.polygon);
            }
            
            alert('File berhasil diimport!');
          } else {
            alert('Format file tidak valid!');
          }
        } catch (error) {
          alert('Error membaca file: ' + error.message);
        }
      };
      reader.readAsText(file);
      this.showImportDialog = false;
    },

   logout() {
      if (confirm('Yakin ingin logout?')) {
        // Clear all data
        this.clearAllMapData();
        this.savedProjects = [];
        this.currentProjectId = null;
        this.currentProject = null;
        this.currentUser = null;
        
        // Clear storage
        localStorage.clear();
        
        alert("Logout berhasil!");
        
        // REDIRECT KE LOGIN
        this.$router.push('/login'); 
      }
    },

    // =============== ORIGINAL METHODS (Updated) ===============

    async toggleAddMarker() {
      if (this.isViewMode) {
        console.log('🔒 Add marker disabled in read-only mode');
        return;
      }
      this.addingMarker = !this.addingMarker;
      this.addingPlacemark = false; // Matikan mode placemark
      this.drawingPolygon = false;
      this.updateMapCursor();
      console.log('Toggle add marker:', this.addingMarker);

      // Saat mengaktifkan mode Tambah Mark, minta konfirmasi ingin tambah ODP
      if (this.addingMarker) {
        const confirmed = await this.showConfirmation(
          'Tambah ODP?',
          'Apakah ingin menambahkan ODP untuk mark yang akan dibuat?',
          '',
          'Ya',
          'Tidak'
        );
        this.odpIntentAfterPlacement = !!confirmed;
      } else {
        // Reset niat ketika mematikan mode
        this.odpIntentAfterPlacement = null;
      }
    },

    toggleAddPlacemark() {
      if (this.isViewMode) {
        console.log('🔒 Add placemark disabled in read-only mode');
        return;
      }
      this.addingPlacemark = !this.addingPlacemark;
      this.addingMarker = false; // Matikan mode marker
      this.drawingPolygon = false;
      this.updateMapCursor();
      console.log('Toggle add placemark:', this.addingPlacemark);
    },

    // Method untuk menyelesaikan polygon grup saat ini
    finishCurrentPolygon() {
      if (this.isViewMode) {
        console.log('🔒 Finish polygon disabled in read-only mode');
        return;
      }

      // Ambil placemark grup saat ini
      const currentGroupPlacemarks = this.placemarks.filter(pm => 
        pm.type === 'polygon' && pm.polygonGroup === this.currentPolygonGroup
      );

      if (currentGroupPlacemarks.length < 2) {
        this.showToast('warning', 'Insufficient Markers', 
          'Minimal 2 marker diperlukan untuk membuat polygon!', 
          3000);
        return;
      }

      // Ambil data in-progress (jarak & path)
      const totalDistance = this.inProgressPolygon.distance || 0;
      const pathCopy = [...this.inProgressPolygon.path];

      // Push SATU kotak summary ke polygons
      this.polygons.push({
        id: Date.now(),
        id_polygon: Date.now(),
        nama_polygon: `Polygon ${this.polygons.length + 1}`,
        deskripsi: `Total ${currentGroupPlacemarks.length} titik - ${totalDistance}m`,
        coordinates: pathCopy,
        panjang_meter: totalDistance,
        googlePolygon: this.autoPolygon
      });

      // Simpan grup selesai
      this.finishedPolygonGroups.push({
        groupId: this.currentPolygonGroup,
        placemarks: [...currentGroupPlacemarks],
        finishedAt: new Date(),
        distance: totalDistance
      });

      // Persist final polygon ke backend sekali
      this.saveAutoPolygon(totalDistance);

      // Reset temp
      this.inProgressPolygon.path = [];
      this.inProgressPolygon.distance = 0;

      // JANGAN hapus autoPolygon dari map - biarkan tetap terlihat
      // this.autoPolygon.setMap(null); // REMOVED: polygon harus tetap terlihat
      
      // Reset autoPolygon reference untuk grup baru
      this.autoPolygon = null;

      // MATIKAN mode polygon dan pindah ke grup baru
      this.addingMarker = false; // Mode polygon MATI
      this.currentPolygonGroup += 1; // Increment untuk grup baru
      this.updateMapCursor(); // Update cursor karena mode sudah mati

      console.log(`✅ Polygon group ${this.currentPolygonGroup - 1} finished! Mode disabled. Next group will be ${this.currentPolygonGroup}`);
      
      this.showToast('success', 'Polygon Finished', 
        `Polygon grup ${this.currentPolygonGroup - 1} berhasil diselesaikan!\n\nMode polygon dimatikan. Klik tombol polygon lagi untuk memulai grup baru.`, 
        5000);
    },

    togglePolygonMode() {
      // Manual polygon mode disabled - now using auto polygon feature
      this.showToast('info', 'Auto Polygon Active', 
        'Manual polygon creation has been replaced with automatic polygon generation when 2+ markers are added.', 
        4000);
      console.log('ℹ️ Manual polygon mode disabled - auto polygon feature is active');
    },

    updateMapCursor() {
      if (this.map) {
        if (this.addingMarker || this.addingPlacemark) {
          this.map.setOptions({ draggableCursor: 'crosshair' });
        } else {
          this.map.setOptions({ draggableCursor: 'grab' });
        }
      }
    },

    addPolygonPoint(lat, lng) {
      // Manual polygon point addition disabled - now using auto polygon feature
      console.log('ℹ️ Manual polygon point addition disabled - auto polygon feature is active');
      this.showToast('info', 'Auto Polygon Active', 
        'Manual polygon creation is disabled. Polygons are created automatically when you add 2+ markers.', 
        3000);
    },

    drawLine() {
      if (this.polyline) {
        this.polyline.setMap(null);
      }
      
      this.polyline = new google.maps.Polyline({
        path: this.polygonPath,
        strokeColor: '#FF0000',
        strokeOpacity: 1.0,
        strokeWeight: 4,
        geodesic: false
      });
      
      this.polyline.setMap(this.map);
    },

    async finishPolygon() {
      // Manual polygon finishing disabled - now using auto polygon feature
      this.showToast('info', 'Auto Polygon Active', 
        'Polygons are now created automatically when 2+ markers are added. No manual polygon creation needed.', 
        4000);
      console.log('ℹ️ Manual polygon finishing disabled - auto polygon feature handles polygon creation');
    },

    updatePolygonPath() {
      if (this.polygon) {
        this.polygonPath = this.polygon.getPath().getArray().map(coord => ({
          lat: coord.lat(),
          lng: coord.lng()
        }));
      }
    },

    clearPolygon() {
      if (this.isViewMode) {
        console.log('🔒 Clear polygon disabled in read-only mode');
        return;
      }
      this.polygonMarkers.forEach(item => {
        item.setMap(null);
      });
      this.polygonMarkers = [];
      
      if (this.polyline) {
        this.polyline.setMap(null);
        this.polyline = null;
      }
      
      if (this.polygon) {
        this.polygon.setMap(null);
        this.polygon = null;
      }
      
      this.polygonPath = [];
    },

    addMarker(lat, lng) {
      // Use the new addMarkerToMap method
      this.addMarkerToMap(lat, lng);
      this.addingMarker = false;
      this.updateMapCursor();
    },

    searchLocation() {
      const query = this.searchQuery.trim();
      if (!query) {
        alert('Masukkan lokasi untuk pencarian');
        return;
      }

      const geocoder = new google.maps.Geocoder();
      geocoder.geocode({ address: query }, (results, status) => {
        if (status === "OK" && results[0]) {
          const location = results[0].geometry.location;
          this.map.setCenter(location);
          this.map.setZoom(16);

          if (this.searchMarker) {
            this.searchMarker.setMap(null);
          }

          this.searchMarker = new google.maps.Marker({
            map: this.map,
            position: location,
            icon: {
              path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
              fillColor: "#FF0000",
              fillOpacity: 1,
              strokeColor: "#ff0000",
              strokeWeight: 2,
              scale: 2,
              anchor: { x: 12, y: 24 }
            },
            title: results[0].formatted_address
          });

          this.searchQuery = '';
        } else {
          alert("Lokasi tidak ditemukan: " + status);
        }
      });
    },

    // =============== SIDEBAR METHODS ===============
    
    toggleSection(section) {
      if (section === 'placemarks') {
        this.showPlacemarks = !this.showPlacemarks;
      } else if (section === 'polygons') {
        this.showPolygons = !this.showPolygons;
      }
    },

    // Focus map on specific placemark
    focusOnPlacemark(index) {
      if (this.placemarks[index] && this.map) {
        const placemark = this.placemarks[index];
        this.map.setCenter({ lat: placemark.lat, lng: placemark.lng });
        this.map.setZoom(18);
        
        // Highlight the marker temporarily if possible
        if (this.markers[index]) {
          // You could add bounce animation here
          this.markers[index].setAnimation(google.maps.Animation.BOUNCE);
          setTimeout(() => {
            if (this.markers[index]) {
              this.markers[index].setAnimation(null);
            }
          }, 2000);
        }
      }
    },

    // Load project data and populate sidebar
    async loadProjectData(projectId) {
      try {
        console.log("Loading project data for ID:", projectId);
        this.currentProjectId = projectId;

        // Clear existing data
        this.clearAllMapData();

        // === Load project detail ===
        const projectResult = await this.apiCall(`/backend/api/project/readOne.php?id_project=${projectId}`);
        if (projectResult.success && projectResult.data) {
          this.currentProject = projectResult.data;
          console.log('✅ Project info loaded:', this.currentProject);

          // === Load placemarks ===
          if (this.currentProject.placemarks && this.currentProject.placemarks.length > 0) {
            // Clear existing placemarks
            this.placemarks = [];
            
              this.currentProject.placemarks.forEach(pm => {
              // Add to placemarks array for sidebar display with consistent structure
              this.placemarks.push({
                lat: parseFloat(pm.latitude),
                lng: parseFloat(pm.longitude),
                nama_placemark: pm.nama_placemark || 'Marker',
                name_placemark: pm.nama_placemark || 'Marker', // Keep both for compatibility
                address: pm.alamat || '',
                alamat: pm.alamat || '',
                deskripsi: pm.deskripsi || '',
                kelurahan: pm.kelurahan || '',
                kecamatan: pm.kecamatan || '',
                kota: pm.kota || '',
                provinsi: pm.provinsi || '',
                  id: pm.id_placemark,
                  id_placemark: pm.id_placemark,
                  // tampilkan badge ODP di sidebar jika data ODP tersedia di payload project (opsional)
                  hasODP: !!pm.odp,
                  odpInfo: pm.odp || null
              });
              
              // Tampilkan marker sesuai mode untuk menghindari penumpukan:
              // Mode READ → marker biru (placemark)
              // Mode EDIT → marker hijau (untuk polygon)
              if (this.isViewMode) {
                // Mode READ: tampilkan marker biru (placemark)
                this.addPlacemarkOnly(parseFloat(pm.latitude), parseFloat(pm.longitude), false);
              } else {
                // Mode EDIT: tampilkan marker hijau (marker untuk polygon)
                this.addMarkerToMap(parseFloat(pm.latitude), parseFloat(pm.longitude), false);
              }
            });
            console.log(`✅ Loaded ${this.placemarks.length} placemarks`);

            // Tandai ODP pada setiap placemark (agar badge tampil di READ & EDIT)
            await this.attachOdpFlagsToPlacemarks();
          } else {
            this.placemarks = [];
            console.log('ℹ️ No placemarks found in this project');
          }

          // === Load multiple polygons from database ===
          await this.loadProjectPolygons(projectId);

        } else {
          console.error("❌ Failed to load project detail", projectResult.message);
        }
      } catch (err) {
        console.error("❌ Error loading project data:", err);
      }
    },

    // Ambil data ODP per placemark untuk menandai badge di sidebar (READ & EDIT)
    async attachOdpFlagsToPlacemarks() {
      try {
        const tasks = this.placemarks.map(async (pm, idx) => {
          if (!pm.id_placemark) return;
          const res = await this.apiCall(`/backend/api/odp/read.php?id_placemark=${pm.id_placemark}`, 'GET');
          if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
            // Vue 3: penambahan properti pada object reactive sudah terdeteksi otomatis
            this.placemarks[idx].hasODP = true;
            this.placemarks[idx].odpInfo = res.data[0];
          } else {
            this.placemarks[idx].hasODP = false;
            this.placemarks[idx].odpInfo = null;
          }
        });
        await Promise.all(tasks);
      } catch (e) {
        console.warn('Gagal melabeli ODP pada placemarks:', e);
      }
    },

    async getAddressForPlacemark(index, lat, lng) {
      const geocoder = new google.maps.Geocoder();
      geocoder.geocode({ location: { lat, lng } }, (results, status) => {
        if (status === "OK" && results[0]) {
          this.placemarks[index].address = results[0].formatted_address;
        }
      });
    },

    // Focus map on specific placemark
    focusOnPlacemark(index) {
      const placemark = this.placemarks[index];
      if (placemark && this.map) {
        this.map.setCenter({ lat: placemark.lat, lng: placemark.lng });
        this.map.setZoom(18);
        
        // Animate marker
        if (this.markers[index]) {
          this.markers[index].setAnimation(google.maps.Animation.BOUNCE);
          setTimeout(() => {
            this.markers[index].setAnimation(null);
          }, 1500);
        }
      }
    },

    // Focus map on polygon (supports both old single polygon and new multiple polygons)
    focusOnPolygon(index = null) {
      if (index !== null && this.polygons[index]) {
        // Focus on specific polygon from multiple polygons
        const polygonData = this.polygons[index];
        if (polygonData.coordinates && polygonData.coordinates.length > 0) {
          const bounds = new google.maps.LatLngBounds();
          polygonData.coordinates.forEach(coord => {
            bounds.extend(new google.maps.LatLng(
              parseFloat(coord.lat), 
              parseFloat(coord.lng)
            ));
          });
          this.map.fitBounds(bounds);
          
          // Highlight the polygon temporarily
          if (polygonData.googlePolygon) {
            const originalColor = polygonData.googlePolygon.get('fillColor');
            polygonData.googlePolygon.setOptions({ fillColor: '#FFD700', fillOpacity: 0.7 });
            setTimeout(() => {
              polygonData.googlePolygon.setOptions({ 
                fillColor: originalColor, 
                fillOpacity: 0.35 
              });
            }, 2000);
          }
        }
      } else if (this.polygon && this.map) {
        // Legacy: Focus on old single polygon
        const bounds = new google.maps.LatLngBounds();
        this.polygonPath.forEach(point => {
          bounds.extend(new google.maps.LatLng(point.lat, point.lng));
        });
        this.map.fitBounds(bounds);
      }
    },

    // Edit placemark
    editPlacemark(index) {
      if (this.isViewMode) {
        console.log('🔒 Edit placemark disabled in read-only mode');
        return;
      }
      if (!this.placemarks[index]) return;

      const placemark = this.placemarks[index];
      
      // Set data untuk modal edit
      this.editPlacemarkData = {
        id_placemark: placemark.id_placemark || placemark.id,
        nama_placemark: placemark.nama_placemark || placemark.name_placemark || (placemark.type === 'placemark' ? `Tiang Lepas ${index + 1}` : `Tiang Terhubung ${index + 1}`),
        deskripsi: placemark.deskripsi || '',
        alamat: placemark.alamat || placemark.address || '',
        kelurahan: placemark.kelurahan || '',
        kecamatan: placemark.kecamatan || '',
        kota: placemark.kota || '',
        provinsi: placemark.provinsi || ''
      };
      
      this.editPlacemarkIndex = index;
      this.showEditPlacemarkModal = true;
    },

    // Close edit placemark modal
    closeEditPlacemarkModal() {
      this.showEditPlacemarkModal = false;
      this.editPlacemarkIndex = null;
      this.editPlacemarkData = {
        id_placemark: null,
        nama_placemark: '',
        deskripsi: '',
        alamat: '',
        kelurahan: '',
        kecamatan: '',
        kota: '',
        provinsi: ''
      };
    },

    // Update placemark
    async updatePlacemark() {
      if (!this.editPlacemarkData.id_placemark) {
        alert('ID placemark tidak ditemukan');
        return;
      }

      try {
        const result = await this.apiCall('/backend/api/placemark/update.php', 'PUT', {
          id_placemark: this.editPlacemarkData.id_placemark,
          nama_placemark: this.editPlacemarkData.nama_placemark.trim(),
          deskripsi: this.editPlacemarkData.deskripsi,
          alamat: this.editPlacemarkData.alamat,
          kelurahan: this.editPlacemarkData.kelurahan,
          kecamatan: this.editPlacemarkData.kecamatan,
          kota: this.editPlacemarkData.kota,
          provinsi: this.editPlacemarkData.provinsi
        });

        if (result.success) {
          // Update data di array lokal
          if (this.editPlacemarkIndex !== null) {
            this.placemarks[this.editPlacemarkIndex].nama_placemark = this.editPlacemarkData.nama_placemark;
            this.placemarks[this.editPlacemarkIndex].deskripsi = this.editPlacemarkData.deskripsi;
            this.placemarks[this.editPlacemarkIndex].alamat = this.editPlacemarkData.alamat;
            this.placemarks[this.editPlacemarkIndex].kelurahan = this.editPlacemarkData.kelurahan;
            this.placemarks[this.editPlacemarkIndex].kecamatan = this.editPlacemarkData.kecamatan;
            this.placemarks[this.editPlacemarkIndex].kota = this.editPlacemarkData.kota;
            this.placemarks[this.editPlacemarkIndex].provinsi = this.editPlacemarkData.provinsi;
          }
          
          this.showToast('success', 'Placemark Updated', 'Placemark berhasil diupdate!', 3000);
          this.closeEditPlacemarkModal();
        } else {
          this.showToast('error', 'Update Failed', result.message || 'Gagal update placemark', 4000);
        }
      } catch (error) {
        console.error('Update placemark error:', error);
        this.showToast('error', 'Update Error', 'Terjadi kesalahan saat update placemark', 4000);
      }
    },

    // Delete placemark
    async deletePlacemark(index) {
      if (this.isViewMode) {
        console.log('🔒 Delete placemark disabled in read-only mode');
        return;
      }
      const placemark = this.placemarks[index];
      if (!placemark) return;

      // Show confirmation toast instead of confirm dialog
  const placemarkName = placemark.nama_placemark || placemark.name_placemark || (placemark.type === 'placemark' ? `Tiang Lepas ${index + 1}` : `Tiang Terhubung ${index + 1}`);
      
      // Create a more modern confirmation system
      const confirmed = await this.showConfirmation(
        'Delete Placemark',
        `Are you sure you want to delete "${placemarkName}"?`,
        'This action cannot be undone.',
        'Delete',
        'Cancel'
      );
      
      if (!confirmed) return;

      const placemarkId = placemark.id_placemark || placemark.id;
      
      if (placemarkId) {
        try {
          const result = await this.apiCall('/backend/api/placemark/delete.php', 'DELETE', {
            id_placemark: placemarkId
          });

          if (result.success) {
            console.log('Placemark deleted from DB:', placemarkId);

            // Hapus marker dari map
            if (this.markers[index]) {
              this.markers[index].setMap(null);
              this.markers.splice(index, 1);
            }
            
            // Hapus dari array placemarks
            this.placemarks.splice(index, 1);
            
            this.showToast('success', 'Success!', `Placemark "${placemarkName}" has been deleted successfully`, 3000);
          } else {
            this.showToast('error', 'Delete Failed', result.message || 'Failed to delete from database', 4000);
          }
        } catch (err) {
          console.error('Delete API error:', err);
          this.showToast('error', 'Network Error', 'Failed to connect to server while deleting placemark', 4000);
        }
      } else {
        this.showToast('warning', 'Invalid Data', 'This placemark has no database ID and cannot be deleted', 3000);
      }
    },

    // Edit polygon
    editPolygon(index) {
      if (this.isViewMode) {
        console.log('🔒 Edit polygon disabled in read-only mode');
        return;
      }
      if (index === null || !this.polygons[index]) return;

      const polygonData = this.polygons[index];
      
      // Set data untuk modal edit
      this.editPolygonData = {
        id_polygon: polygonData.id_polygon || polygonData.id,
        nama_polygon: polygonData.nama_polygon || `Polygon ${index + 1}`,
        deskripsi: polygonData.deskripsi || ''
      };
      
      this.editPolygonIndex = index;
      this.showEditPolygonModal = true;
    },

    // Close edit polygon modal
    closeEditPolygonModal() {
      this.showEditPolygonModal = false;
      this.editPolygonIndex = null;
      this.editPolygonData = {
        id_polygon: null,
        nama_polygon: '',
        deskripsi: ''
      };
    },

    // Update polygon
    async updatePolygon() {
      if (!this.editPolygonData.id_polygon) {
        alert('ID polygon tidak ditemukan');
        return;
      }

      try {
        const result = await this.apiCall('/backend/api/polygon/update.php', 'PUT', {
          id_polygon: this.editPolygonData.id_polygon,
          nama_polygon: this.editPolygonData.nama_polygon.trim(),
          deskripsi: this.editPolygonData.deskripsi
        });

        if (result.success) {
          // Update data di array lokal
          if (this.editPolygonIndex !== null) {
            this.polygons[this.editPolygonIndex].nama_polygon = this.editPolygonData.nama_polygon;
            this.polygons[this.editPolygonIndex].deskripsi = this.editPolygonData.deskripsi;
          }
          
          this.showToast('success', 'Polygon Updated', 'Polygon berhasil diupdate!', 3000);
          this.closeEditPolygonModal();
        } else {
          this.showToast('error', 'Update Failed', result.message || 'Gagal update polygon', 4000);
        }
      } catch (error) {
        console.error('Update polygon error:', error);
        this.showToast('error', 'Update Error', 'Terjadi kesalahan saat update polygon', 4000);
      }
    },

    // Delete polygon
    async deletePolygon(index) {
      if (this.isViewMode) {
        console.log('🔒 Delete polygon disabled in read-only mode');
        return;
      }
      if (index === null || !this.polygons[index]) return;

      const polygonData = this.polygons[index];
      const polygonName = polygonData.nama_polygon || `Polygon ${index + 1}`;
      
      // Show confirmation toast instead of confirm dialog
      const confirmed = await this.showConfirmation(
        'Delete Polygon',
        `Are you sure you want to delete "${polygonName}"?`,
        'This action cannot be undone.',
        'Delete',
        'Cancel'
      );
      
      if (!confirmed) return;
      
      const polygonId = polygonData.id_polygon || polygonData.id;
      
      if (polygonId) {
        try {
          const result = await this.apiCall('/backend/api/polygon/delete.php', 'DELETE', { 
            id: polygonId 
          });
          
          if (result.success) {
            // Remove from map
            if (polygonData.googlePolygon) {
              polygonData.googlePolygon.setMap(null);
            }
            
            // Remove from local array
            this.polygons.splice(index, 1);
            
            this.showToast('success', 'Success!', `Polygon "${polygonName}" has been deleted successfully`, 3000);
          } else {
            this.showToast('error', 'Delete Failed', result.message || 'Failed to delete polygon from database', 4000);
          }
        } catch (error) {
          console.error('Error deleting polygon:', error);
          this.showToast('error', 'Network Error', 'Failed to connect to server while deleting polygon', 4000);
        }
      } else {
        this.showToast('warning', 'Invalid Data', 'This polygon has no database ID and cannot be deleted', 3000);
      }
    },

    // Update polygon in backend
    async updatePolygonInBackend(polygonId, updateData) {
      try {
        const result = await this.apiCall('/backend/api/polygon/update.php', 'PUT', {
          id_polygon: polygonId,
          ...updateData
        });
        
        if (result.success) {
          console.log('Polygon updated in backend successfully');
        } else {
          console.error('Failed to update polygon in backend:', result.message);
        }
      } catch (error) {
        console.error('Error updating polygon:', error);
      }
    },

    calculatePolygonArea() {
      if (!this.polygon || this.polygon.length === 0) {
        console.warn("Tidak ada polygon untuk dihitung.");
        return;
      }

      let totalArea = 0;

      this.polygon.forEach(poly => {
        if (poly && typeof poly.getPath === "function") {
          const path = poly.getPath();
          const area = google.maps.geometry.spherical.computeArea(path);
          totalArea += area;
        }
      });

      console.log("Total area semua polygon:", totalArea, "m²");
      return totalArea;
    },
  }
};
</script>

<style>

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #E5EEF1 0%, #CCD2DE 100%);
  min-height: 100vh;
  color: #17677E;
}

.project-container {
  display: flex;
  min-height: 100vh;
}

.logo {
  color: #E5EEF1;
  font-size: 28px;
  font-weight: bold;
  text-align: center;
  letter-spacing: 2px;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.logo .net {
  color: #E5EEF1;
}

.logo .map {
  color: #E5EEF1;
}

.logo i {
  margin-right: 8px;  
}

.project-container .main {
  flex: 1 !important;
  margin-left: 260px !important;
  display: flex !important;
  flex-direction: column !important;
  min-height: 100vh !important;
  padding-top: 70px !important;
}

.header {
  background: linear-gradient(180deg, #17677E 0%, #145a6b 100%);
  color: #E5EEF1;
  padding: 15px 30px;
  box-shadow: 0 4px 15px rgba(23, 103, 126, 0.2);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: fixed;
  top: 0;
  left: 260px; 
  right: 0;
  z-index: 100;
  height: 70px;
}

.project-container .sidebar {
  width: 260px !important;
  background: linear-gradient(180deg, #17677E 0%, #145a6b 100%) !important;
  box-shadow: 4px 0 15px rgba(23, 103, 126, 0.2) !important;
  position: fixed !important;
  top: 70px !important; /* ganti 9vh dengan tinggi header */
  left: 0 !important;
  height: calc(100vh - 70px) !important; /* supaya sidebar tidak menabrak header */
  z-index: 1000 !important;
  overflow-y: auto !important;
  transition: all 0.3s ease !important;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  transform: translateX(0) !important;
}

.logout-icon {
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
  color: #E5EEF1;
  font-size: 18px;
  background: rgba(229, 238, 241, 0.1);
}

.logout-icon:hover {
  background: rgba(229, 238, 241, 0.2);
  transform: scale(1.1);
}

.content {
  flex: 1;
  padding: 80px;
  background: #E5EEF1;
  margin: 20px;
  border-radius: 15px;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(23, 103, 126, 0.1);
}

h1 {
  color: #17677E;
  font-size: 28px;
  font-weight: 700;
  margin: 20px;
  text-align: left;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

.project-container .toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 20px;
  margin-top: -5px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 15px;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(23, 103, 126, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 15px;
}

.toolbar-right {
  display: flex;
  gap: 15px;
}

.search-tools {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.search-tools input[type="text"] {
  padding: 10px 14px;
  border: 2px solid #CCD2DE;
  border-radius: 10px;
  width: 280px;
  font-size: 14px;
  background: rgba(255, 255, 255, 0.9);
  color: #17677E;
  transition: all 0.3s ease;
}

.search-tools input[type="text"]:focus {
  outline: none;
  border-color: #17677E;
  box-shadow: 0 0 0 3px rgba(23, 103, 126, 0.2);
  background: white;
}

.search-tools input[type="text"]::placeholder {
  color: #999;
  font-style: italic;
}

.btn-icon {
  padding: 10px;
  border: none;
  background: #FFFFFF;
  color: #17677E;
  border-radius: 10px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 10px rgba(23, 103, 126, 0.1);
  min-width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #CCD2DE;
}

.btn-icon:hover {
  background: #17677E;
  color: #E5EEF1;
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(23, 103, 126, 0.3);
}

.btn-icon.active {
  background: linear-gradient(135deg, #17677E 0%, #2980b9 100%);
  color: #E5EEF1;
  box-shadow: 0 4px 10px rgba(23, 103, 126, 0.3);
  border-color: #17677E;
}

/* Disabled state untuk btn-icon */
.btn-icon.disabled,
.btn-icon:disabled {
  background: #f5f5f5 !important;
  color: #999 !important;
  border-color: #ddd !important;
  cursor: not-allowed !important;
  opacity: 0.6;
  transform: none !important;
  box-shadow: none !important;
}

.btn-icon.disabled:hover,
.btn-icon:disabled:hover {
  background: #f5f5f5 !important;
  color: #999 !important;
  transform: none !important;
  box-shadow: none !important;
}

.btn-success {
  padding: 8px 6px;
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-success:hover {
  background: linear-gradient(135deg, #218838, #1ea085);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
}

.btn-danger {
  padding: 10px 8px;
  background: linear-gradient(135deg, #dc3545, #e74c3c);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-danger:hover {
  background: linear-gradient(135deg, #c82333, #dc2626);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(220, 53, 69, 0.4);
}

/* Disabled state untuk btn-success dan btn-danger */
.btn-success.disabled,
.btn-success:disabled,
.btn-danger.disabled,
.btn-danger:disabled {
  background: #ddd !important;
  color: #999 !important;
  cursor: not-allowed !important;
  opacity: 0.6;
  transform: none !important;
  box-shadow: none !important;
}

.btn-success.disabled:hover,
.btn-success:disabled:hover,
.btn-danger.disabled:hover,
.btn-danger:disabled:hover {
  background: #ddd !important;
  color: #999 !important;
  transform: none !important;
  box-shadow: none !important;
}

.btn-secondary {
  padding: 10px 20px;
  background: #CCD2DE;
  color: #17677E;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  background: #b8c1cc;
  transform: translateY(-1px);
}

.instructions {
  background: linear-gradient(135deg, #17677E, #145a6b);
  color: #E5EEF1;
  padding: 15px 25px;
  border-radius: 10px;
  margin-bottom: 20px;
  box-shadow: 0 4px 15px rgba(23, 103, 126, 0.2);
  border-left: 5px solid #CCD2DE;
}

.instructions p {
  margin: 0;
  font-size: 16px;
  font-weight: 500;
}

.instructions strong {
  color: #CCD2DE;
}

.loading {
  background: rgba(23, 103, 126, 0.95);
  color: #E5EEF1;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(23, 103, 126, 0.3);
  backdrop-filter: blur(10px);
}

.loading i {
  margin-right: 10px;
  font-size: 18px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.map-container {
  background: white;
  border-radius: 15px;
  padding: 15px;
  margin: 20px;
  margin-top: 2px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(23, 103, 126, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

#map {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  width: 100%;
  height: 450px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(23, 103, 126, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(5px);
}

.modal-content {
  background: white;
  padding: 40px;
  border-radius: 20px;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 20px 50px rgba(23, 103, 126, 0.3);
  border: 3px solid #CCD2DE;
}

.modal-content h3 {
  color: #17677E;
  margin-bottom: 25px;
  font-size: 24px;
  font-weight: 700;
  text-align: center;
}

.modal-content input[type="file"] {
  width: 100%;
  padding: 15px;
  margin: 20px 0;
  border: 2px dashed #CCD2DE;
  border-radius: 10px;
  background: rgba(229, 238, 241, 0.3);
  color: #17677E;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.modal-content input[type="file"]:hover {
  border-color: #17677E;
  background: rgba(229, 238, 241, 0.5);
}

.modal-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 25px;
}

.sidebar-menu {
  padding: 20px 0;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.placemark-list {
  max-height: 200px; /* cukup untuk sekitar 3 item */
  overflow-y: auto;
  padding-right: 5px; /* biar isi tidak ketiban scrollbar */
}

.placemark-list::-webkit-scrollbar {
  width: 6px;
}
.placemark-list::-webkit-scrollbar-thumb {
  background: #aaa;
  border-radius: 3px;
}
.placemark-list::-webkit-scrollbar-thumb:hover {
  background: #777;
}

.project-info {
  padding: 0 20px 20px 20px;
  border-bottom: 1px solid rgba(229, 238, 241, 0.2);
  margin-bottom: 20px;
}

.project-info h3 {
  color: #E5EEF1;
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 5px;
}

.project-id {
  color: #CCD2DE;
  font-size: 12px;
  opacity: 0.8;
  margin: 0;
}

.sidebar-section {
  margin-bottom: 15px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: rgba(229, 238, 241, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
}

.section-header:hover {
  background: rgba(229, 238, 241, 0.15);
}

.section-header h4 {
  color: #E5EEF1;
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-toggle {
  background: none;
  border: none;
  color: #CCD2DE;
  font-size: 12px;
  cursor: pointer;
  padding: 4px;
  border-radius: 3px;
  transition: all 0.3s ease;
}

.btn-toggle.active {
  transform: rotate(180deg);
}

.section-content {
  max-height: 300px;
  overflow-y: auto;
}

.sidebar-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  border-bottom: 1px solid rgba(229, 238, 241, 0.1);
  transition: all 0.3s ease;
}

.sidebar-item:hover {
  background: rgba(229, 238, 241, 0.05);
}

.badge-odp {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 6px;
  font-size: 11px;
  font-weight: 600;
  color: #0a5e2a;
  background: #d4f6df;
  border: 1px solid #9fe2b2;
  border-radius: 10px;
  vertical-align: middle;
}

.odp-extra {
  margin-top: 4px;
  font-size: 12px;
  color: #cfe9ff;
}

.item-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.item-icon {
  width: 32px;
  height: 32px;
  background: rgba(229, 238, 241, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #E5EEF1;
  font-size: 14px;
}

.placemark-item .item-icon {
  background: rgba(76, 175, 80, 0.3);
}

.polygon-item .item-icon {
  background: rgba(255, 215, 0, 0.3);
}

.item-details h5 {
  color: #E5EEF1;
  font-size: 13px;
  font-weight: 600;
  margin: 0 0 4px 0;
}

.coordinates {
  color: #CCD2DE;
  font-size: 11px;
  margin: 0;
  font-family: monospace;
}

.address {
  color: #CCD2DE;
  font-size: 10px;
  margin: 2px 0 0 0;
  opacity: 0.8;
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.area {
  color: #CCD2DE;
  font-size: 11px;
  margin: 2px 0 0 0;
}

.distance {
  color: #4CAF50;
  font-size: 11px;
  margin: 2px 0 0 0;
  font-weight: 600;
}

.item-actions {
  display: flex;
  gap: 4px;
}

.btn-action {
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  transition: all 0.3s ease;
}

.btn-focus {
  background: #2196F3;
  color: white;
}

.btn-focus:hover {
  background: #1976D2;
}

.btn-edit {
  background: #FF9800;
  color: white;
}

.btn-edit:hover {
  background: #F57C00;
}

.btn-delete {
  background: #F44336;
  color: white;
}

.btn-delete:hover {
  background: #D32F2F;
}

/* Disabled state untuk btn-action */
.btn-action.disabled,
.btn-action:disabled {
  background: #ddd !important;
  color: #999 !important;
  cursor: not-allowed !important;
  opacity: 0.5;
  transform: none !important;
}

.btn-action.disabled:hover,
.btn-action:disabled:hover {
  background: #ddd !important;
  color: #999 !important;
  transform: none !important;
}

.empty-message {
  text-align: center;
  padding: 30px 20px;
  color: #CCD2DE;
  opacity: 0.7;
}

.empty-message i {
  font-size: 32px;
  margin-bottom: 10px;
  opacity: 0.5;
}

.empty-message p {
  font-size: 12px;
  margin: 0;
}

.sidebar-actions {
  margin-top: auto;
  padding: 20px;
  border-top: 1px solid rgba(229, 238, 241, 0.2);
}

/* Scrollbar for sidebar sections */
.section-content::-webkit-scrollbar {
  width: 4px;
}

.section-content::-webkit-scrollbar-track {
  background: rgba(229, 238, 241, 0.1);
}

.section-content::-webkit-scrollbar-thumb {
  background: rgba(229, 238, 241, 0.3);
  border-radius: 2px;
}

.section-content::-webkit-scrollbar-thumb:hover {
  background: rgba(229, 238, 241, 0.5);
}

.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(229, 238, 241, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(229, 238, 241, 0.3);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(229, 238, 241, 0.5);
}

button:focus,
input:focus {
  outline: 2px solid #17677E;
  outline-offset: 2px;
}

.text-center {
  text-align: center;
}

.mt-20 {
  margin-top: 20px;
}

.mb-20 {
  margin-bottom: 20px;
}

.hidden {
  display: none;
}

.visible {
  display: block;
}

.project-container {
  animation: fadeIn 0.6s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.map-container {
  animation: slideUp 0.8s ease-out 0.3s both;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.toolbar {
  animation: slideDown 0.8s ease-out 0.1s both;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  color: #333;
  flex: 1;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  color: #333;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-input, .form-textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  box-sizing: border-box;
}

.form-textarea {
  min-height: 80px;
  resize: vertical;
}

.form-input:focus, .form-textarea:focus {
  outline: none;
  border-color: #4CAF50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #eee;
}

.btn-secondary {
  padding: 8px 16px;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-primary {
  padding: 8px 16px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary:hover {
  background: #45a049;
}

/* Mode Indicator Styles */
.mode-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-radius: 10px;
  margin: 10px 20px;
  font-weight: 600;
  border: 2px solid;
  animation: fadeIn 0.5s ease-in-out;
}

.mode-indicator i {
  font-size: 16px;
}

.mode-indicator span {
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.mode-indicator small {
  font-size: 12px;
  opacity: 0.8;
  font-weight: 400;
}

.read-only-mode {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  color: #6c757d;
  border-color: #6c757d;
}

.edit-mode {
  background: linear-gradient(135deg, #e3f2fd, #f1f8e9);
  color: #2e7d32;
  border-color: #4caf50;
}

.create-mode {
  background: linear-gradient(135deg, #fff3e0, #e8f5e8);
  color: #f57c00;
  border-color: #ff9800;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* =============== TOAST NOTIFICATIONS =============== */
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 400px;
}

.toast {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
  animation: toastSlideIn 0.4s ease-out;
  position: relative;
  overflow: hidden;
}

.toast::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: currentColor;
  opacity: 0.8;
}

.toast:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
}

.toast-success {
  background: linear-gradient(135deg, rgba(40, 167, 69, 0.95), rgba(32, 201, 151, 0.95));
  color: white;
  border-color: rgba(40, 167, 69, 0.3);
}

.toast-error {
  background: linear-gradient(135deg, rgba(220, 53, 69, 0.95), rgba(231, 76, 60, 0.95));
  color: white;
  border-color: rgba(220, 53, 69, 0.3);
}

.toast-warning {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.95), rgba(255, 159, 64, 0.95));
  color: #212529;
  border-color: rgba(255, 193, 7, 0.3);
}

.toast-info {
  background: linear-gradient(135deg, rgba(23, 103, 126, 0.95), rgba(32, 201, 151, 0.95));
  color: white;
  border-color: rgba(23, 103, 126, 0.3);
}

.toast-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  font-size: 16px;
  flex-shrink: 0;
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 4px 0;
  line-height: 1.2;
}

.toast-message {
  font-size: 13px;
  margin: 0;
  opacity: 0.9;
  line-height: 1.3;
  word-wrap: break-word;
  white-space: pre-line;
}

.toast-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  background: none;
  border: none;
  color: currentColor;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s ease;
  opacity: 0.7;
  flex-shrink: 0;
}

.toast-close:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

/* =============== CONFIRMATION TOAST =============== */
.toast-confirmation {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.95), rgba(255, 159, 64, 0.95));
  border-color: rgba(255, 193, 7, 0.3);
  color: #212529;
  cursor: default;
  min-width: 350px;
  padding: 20px;
}

.confirmation-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.confirmation-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.confirmation-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  font-size: 18px;
  flex-shrink: 0;
  color: #ff6b35;
}

.confirmation-text {
  flex: 1;
}

.confirmation-title {
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 6px 0;
  line-height: 1.2;
  color: #212529;
}

.confirmation-message {
  font-size: 14px;
  margin: 0 0 4px 0;
  opacity: 0.9;
  line-height: 1.3;
  color: #495057;
}

.confirmation-subtitle {
  font-size: 12px;
  margin: 0;
  opacity: 0.7;
  font-style: italic;
  color: #6c757d;
}

.confirmation-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.confirmation-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.confirmation-btn.cancel {
  background: rgba(108, 117, 125, 0.1);
  color: #6c757d;
  border: 1px solid rgba(108, 117, 125, 0.3);
}

.confirmation-btn.cancel:hover {
  background: rgba(108, 117, 125, 0.2);
  transform: translateY(-1px);
}

.confirmation-btn.confirm {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  border: 1px solid rgba(220, 53, 69, 0.3);
}

.confirmation-btn.confirm:hover {
  background: linear-gradient(135deg, #c82333, #bd2130);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

@keyframes toastSlideIn {
  from {
    transform: translateX(100%) scale(0.8);
    opacity: 0;
  }
  to {
    transform: translateX(0) scale(1);
    opacity: 1;
  }
}

/* Responsive Toast */
@media (max-width: 768px) {
  .toast-container {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
  }
  
  .toast {
    padding: 14px 16px;
  }
  
  .toast-confirmation {
    min-width: auto;
    padding: 16px;
  }
  
  .confirmation-actions {
    flex-direction: column;
  }
  
  .confirmation-btn {
    width: 100%;
    justify-content: center;
  }
  
  .toast-title {
    font-size: 13px;
  }
  
  .toast-message {
    font-size: 12px;
  }
}

/* =============== MODERN MODAL STYLES =============== */
.modern-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: modalOverlayFadeIn 0.3s ease-out;
}

.modern-modal-content {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.8);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  animation: modalContentSlideIn 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.modern-modal-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px 28px;
  background: linear-gradient(135deg, #17677E 0%, #145a6b 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.modern-modal-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.3;
}

.modal-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  font-size: 20px;
  color: white;
  position: relative;
  z-index: 1;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.modal-title-section {
  flex: 1;
  position: relative;
  z-index: 1;
}

.modal-title-section h3 {
  margin: 0 0 4px 0;
  font-size: 20px;
  font-weight: 700;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.modal-title-section p {
  margin: 0;
  font-size: 14px;
  opacity: 0.9;
  color: rgba(255, 255, 255, 0.9);
}

.modern-btn-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
  position: relative;
  z-index: 1;
  backdrop-filter: blur(10px);
}

.modern-btn-close:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.1);
}

.modern-modal-body {
  padding: 28px;
  max-height: 60vh;
  overflow-y: auto;
}

.modern-form-group {
  margin-bottom: 24px;
}

.modern-form-group:last-child {
  margin-bottom: 0;
}

.modern-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 8px;
  font-size: 14px;
}

.modern-label i {
  color: #4154f1;
  font-size: 14px;
}

.modern-form-input,
.modern-form-textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  font-size: 14px;
  transition: all 0.3s ease;
  background: #ffffff;
  color: #2c3e50;
  font-family: inherit;
  box-sizing: border-box;
}

.modern-form-input:focus,
.modern-form-textarea:focus {
  outline: none;
  border-color: #4154f1;
  box-shadow: 0 0 0 3px rgba(65, 84, 241, 0.1);
  background: #ffffff;
}

.modern-form-textarea {
  resize: vertical;
  min-height: 80px;
  line-height: 1.5;
}

.modern-modal-footer {
  display: flex;
  gap: 12px;
  padding: 20px 28px 28px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.modern-btn-primary,
.modern-btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  justify-content: center;
  min-width: 120px;
}

.modern-btn-primary {
  background: green;
  color: white;
  box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
  position: center;
}

.modern-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(65, 84, 241, 0.4);
  background: green;
}

.modern-btn-secondary {
  background: #ffffff;
  color: #6c757d;
  border: 2px solid #e9ecef;
}

.modern-btn-secondary:hover {
  background: #f8f9fa;
  border-color: #dee2e6;
  transform: translateY(-1px);
}

@keyframes modalOverlayFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes modalContentSlideIn {
  from {
    transform: scale(0.8) translateY(20px);
    opacity: 0;
  }
  to {
    transform: scale(1) translateY(0);
    opacity: 1;
  }
}

/* Responsive Modal */
@media (max-width: 768px) {
  .modern-modal-content {
    width: 95%;
    margin: 20px;
    max-height: calc(100vh - 40px);
  }
  
  .modern-modal-header {
    padding: 20px 24px;
  }
  
  .modal-icon {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
  
  .modal-title-section h3 {
    font-size: 18px;
  }
  
  .modern-modal-body {
    padding: 24px;
  }
  
  .modern-modal-footer {
    flex-direction: column;
    padding: 20px 24px 24px;
  }
  
  .modern-btn-primary,
  .modern-btn-secondary {
    width: 100%;
    justify-content: center;
  }
}
/* ===== SINGLE OVERLAP TOAST MODE (Additive, no JS changes) ===== */
/* Menyembunyikan semua kecuali toast terakhir; tampil bergantian di pojok kanan atas */
.toast-container {
  position: fixed;
  top: 16px;
  right: 16px;
  width: 380px;
  max-width: 90vw;
  min-height: 100px; /* stabil agar tidak loncat */
}
.toast-container > .toast {
  position: absolute;
  top: 0; left: 0; right: 0;
  width: 100%;
  margin: 0 !important;
  opacity: 0;
  transform: translateY(-6px) scale(.97);
  transition: opacity .35s ease, transform .35s ease;
}
.toast-container > .toast:last-child {
  opacity: 1;
  transform: translateY(0) scale(1);
  position: relative; /* biarkan paling atas secara normal */
}
.toast-container > .toast:not(:last-child) {
  pointer-events: none;
  filter: blur(2px);
}
/* ============================================================= */
</style>