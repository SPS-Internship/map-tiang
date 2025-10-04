<template>
  <div class="boq-page">
    <!-- Header: Logo + Logout -->
    <div class="header">
      <div class="logo">
        <i class="fas fa-network-wired"></i>
        <span class="net">NETMAP</span>
      </div>
      <i class="fas fa-sign-out-alt logout-icon" @click="logout" title="Logout"></i>
    </div>

    
      <!-- Tabel BoQ Utama -->
    <section class="card">
        <div class="card-title">Bill of Quantity (BoQ)</div>

        <div class="table-wrapper">
          <table class="boq-table">
            <thead>
              <tr>
                <th style="width:50px;">No</th>
                <th>Uraian</th>
                <th style="width:90px;">Satuan</th>
                <th style="width:110px;">Volume</th>
                <th style="width:140px;">Status ODP</th>
                <th style="width:130px;">Harga Satuan</th>
                <th style="width:160px;">Total Harga</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in displayBoqRows" :key="idx">
                <td class="text-center">{{ idx + 1 }}</td>
                <td>{{ row.uraian }}</td>
                <td class="text-center">{{ row.satuan }}</td>
                <td class="text-right">{{ formatNumber(row.volume) }}</td>
                <td>
                  <span v-if="row.statusODP && row.statusODP !== '-'" class="badge badge-odp">{{ row.statusODP }}</span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td class="text-right">{{ formatCurrency(row.harga) }}</td>
                <td class="text-right">{{ formatCurrency(row.total) }}</td>
              </tr>
              <tr v-if="displayBoqRows.length === 0">
                <td colspan="7" class="empty">Tidak ada data</td>
              </tr>
            </tbody>
            <tfoot v-if="displayBoqRows.length > 0">
              <tr>
                <td colspan="6" class="text-right"><strong>Grand Total</strong></td>
                <td class="text-right"><strong>{{ formatCurrency(grandTotalDisplay) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="actions">
          <button class="btn-save" :disabled="saving || !project" @click="saveBoq">
            <i v-if="!saving" class="fas fa-save"></i>
            <i v-else class="fas fa-spinner fa-spin"></i>
            <span>{{ saving ? 'Menyimpan...' : 'Simpan' }}</span>
          </button>
          <p v-if="saveMessage" :class="['save-msg', saveMessageType]">{{ saveMessage }}</p>
        </div>
      </section>
  </div>
</template>

<script>
export default {
  name: 'Boq',
  data() {
    return {
      baseUrl: 'http://localhost:8000',
      project: null,
      placemarks: [],
      polygons: [],
      // Harga satuan contoh (bisa disesuaikan nanti)
      priceList: {
        kabelFo24: 12000,
        tiangBesi9m: 2000000,
      },
      avgDropLengthPerPole: 0, // fallback: 0 m jika belum ada data drop
      saving: false,
      saveMessage: '',
      saveMessageType: 'success', // 'success' | 'error'
    };
  },
  computed: {
    // Rows displayed on table computed from polygons and placemarks
    displayBoqRows() {
      const rows = [];
      const PRICE_CABLE = 12000;   // per meter
      const PRICE_POLE  = 2000000; // per unit

      // 1) Per polygon rows (kabel)
      for (const pg of this.polygons) {
        const nama = pg.nama_polygon || 'Polygon';
        const vol = Number(pg.panjang_meter || 0);
        if (vol > 0) {
          rows.push({
            uraian: nama,
            satuan: 'm',
            volume: vol,
            statusODP: '-',
            harga: PRICE_CABLE,
            total: PRICE_CABLE * vol,
          });
        }
      }

      // 2) Placemark grouped rows: Tiang Terhubung vs Tiang Lepas
      const totalTiang = this.placemarks.length;
      const terhubung = this.placemarks.filter(p => p.hasODP || p.id_odp).length;
      const lepas = Math.max(0, totalTiang - terhubung);

      if (terhubung > 0) {
        rows.push({
          uraian: 'Tiang terhubung',
          satuan: 'unit',
          volume: terhubung,
          statusODP: 'ODP',
          harga: PRICE_POLE,
          total: PRICE_POLE * terhubung,
        });
      }
      if (lepas > 0) {
        rows.push({
          uraian: 'Tiang lepas',
          satuan: 'unit',
          volume: lepas,
          statusODP: '-',
          harga: PRICE_POLE,
          total: PRICE_POLE * lepas,
        });
      }

      return rows;
    },
    // Total panjang kabel backbone dari semua polygon (meter)
    totalKabelFo24() {
      return this.polygons.reduce((sum, p) => sum + (parseFloat(p.panjang_meter) || 0), 0);
    },
    totalTiang() {
      return this.placemarks.length;
    },
    odpYesCount() {
      return this.placemarks.filter(p => p.hasODP).length;
    },
    odpNoCount() {
      return this.placemarks.filter(p => p.hasODP === false).length;
    },
    odpSummary() {
      const y = this.odpYesCount;
      const n = this.odpNoCount;
      return y + n > 0 ? `Ada: ${y} | Tidak: ${n}` : '-';
    },
    ringkasRows() {
      const rows = [];
      // 1. Kabel FO 24 core (total panjang semua polygon)
      const totalCable = Math.round(this.totalKabelFo24);
      rows.push({
        uraian: 'Kabel FO 24 core',
        satuan: 'm',
        volume: totalCable,
        harga: this.priceList.kabelFo24,
        total: totalCable * this.priceList.kabelFo24,
        statusODP: '-',
      });
      // 2. Tiang Besi 9 m (total jumlah tiang)
      const totalTiang = this.totalTiang;
      rows.push({
        uraian: 'Tiang Besi 9 m',
        satuan: 'unit',
        volume: totalTiang,
        harga: this.priceList.tiangBesi9m,
        total: totalTiang * this.priceList.tiangBesi9m,
        statusODP: this.odpYesCount > 0 ? 'ODP' : '-',
      });
      return rows;
    },
    grandTotal() {
      return this.ringkasRows.reduce((sum, r) => sum + (r.total || 0), 0);
    },
    grandTotalDisplay() {
      return this.displayBoqRows.reduce((sum, r) => sum + (r.total || 0), 0);
    },
  },
  async mounted() {
    const projectId = this.$route.params.id;
    if (projectId) {
      await this.loadProjectData(projectId);
    } else {
      // Fallback: coba ambil dari localStorage
      const saved = localStorage.getItem('currentProject');
      if (saved) {
        try {
          const p = JSON.parse(saved);
          if (p && p.id_project) await this.loadProjectData(p.id_project);
        } catch {}
      }
    }
  },
  methods: {
    async apiCall(endpoint, method = 'GET', data = null) {
      const headers = { 'Accept': 'application/json' };
      const config = { method, headers };
      if (data && method !== 'GET') {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(data);
      }
      const resp = await fetch(`${this.baseUrl}${endpoint}`, config);
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const ct = resp.headers.get('content-type') || '';
      const text = await resp.text();
      if (!ct.includes('application/json')) throw new Error('Non-JSON response');
      return JSON.parse(text);
    },
    goBack() {
      if (window.history.length > 1) this.$router.back();
      else this.$router.push({ name: 'Dashboard' });
    },
    async loadProjectData(projectId) {
      try {
        const pr = await this.apiCall(`/backend/api/project/readOne.php?id_project=${projectId}`, 'GET');
        if (pr && pr.success && pr.data) {
          this.project = pr.data;
          // Fetch polygons list explicitly (includes panjang_meter & nama)
          try {
            const pgRes = await this.apiCall(`/backend/api/polygon/read.php?id_project=${projectId}`, 'GET');
            if ((pgRes && pgRes.success) || (pgRes && pgRes.status === 'success')) {
              const arr = pgRes.data || [];
              this.polygons = arr.map(pg => ({
                id_polygon: pg.id_polygon,
                nama_polygon: pg.nama_polygon,
                panjang_meter: parseFloat(pg.panjang_meter) || 0,
              }));
            } else {
              this.polygons = [];
            }
          } catch { this.polygons = []; }

          // Fetch placemarks list explicitly (with names)
          try {
            const pmRes = await this.apiCall(`/backend/api/placemark/read.php?id_project=${projectId}`, 'GET');
            if ((pmRes && pmRes.success) || (pmRes && pmRes.status === 'success')) {
              const arr = pmRes.data || [];
              this.placemarks = arr.map(pm => ({
                id_placemark: pm.id_placemark,
                nama_placemark: pm.nama_placemark || 'Tiang',
                hasODP: false,
                id_odp: null,
              }));
            } else {
              this.placemarks = [];
            }
          } catch { this.placemarks = []; }

          // Pastikan status ODP terisi bila backend tidak embed
          await this.attachOdpFlagsToPlacemarks();
        }
      } catch (e) {
        console.error('Gagal memuat data project untuk BoQ:', e);
      }
    },
    async attachOdpFlagsToPlacemarks() {
      const tasks = this.placemarks.map(async (pm, idx) => {
        if (!pm.id_placemark) return;
        try {
          const res = await this.apiCall(`/backend/api/odp/read.php?id_placemark=${pm.id_placemark}`, 'GET');
          if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
            this.placemarks[idx].hasODP = true;
            this.placemarks[idx].id_odp = res.data[0]?.id_odp || null;
          } else {
            this.placemarks[idx].hasODP = false;
            this.placemarks[idx].id_odp = null;
          }
        } catch {}
      });
      await Promise.all(tasks);
    },
    async saveBoq() {
      if (!this.project || !this.project.id_project) {
        this.saveMessageType = 'error';
        this.saveMessage = 'Project tidak ditemukan.';
        return;
      }
      this.saving = true;
      this.saveMessage = '';
      try {
        const payload = {
          id_project: this.project.id_project,
          boq: {
            rows: this.ringkasRows.map(r => ({
              uraian: r.uraian,
              satuan: r.satuan,
              volume: r.volume,
              status_odp: r.statusODP || '-',
              harga_satuan: r.harga,
              total: r.total,
            })),
            grand_total: this.grandTotal,
            updated_at: new Date().toISOString(),
          }
        };
        // Simpan/generate BoQ baris detail di backend
        const res = await this.apiCall('/backend/api/boq/save.php', 'POST', { id_project: this.project.id_project, rows: payload.boq.rows });
        if (res && (res.success || res.status === 'ok' || res.status === 'success')) {
          this.saveMessageType = 'success';
          this.saveMessage = 'BoQ berhasil disimpan.';
          // Data tabel dihitung langsung dari polygons & placemarks; tidak perlu reload BoQ DB.
        } else {
          throw new Error(res?.message || 'Gagal menyimpan BoQ.');
        }
      } catch (err) {
        console.error('Simpan BoQ gagal:', err);
        this.saveMessageType = 'error';
        this.saveMessage = err?.message || 'Terjadi kesalahan saat menyimpan.';
      } finally {
        this.saving = false;
        setTimeout(() => { this.saveMessage = ''; }, 4000);
      }
    },
    logout() {
      localStorage.removeItem('authToken');
      this.$router.push({ name: 'Login' });
    },
    toFixed(v) {
      const n = parseFloat(v);
      return isNaN(n) ? '-' : n.toFixed(6);
    },
    formatNumber(n) {
      const v = Number(n || 0);
      return v.toLocaleString('id-ID');
    },
    formatCurrency(n) {
      const v = Number(n || 0);
      return v.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    },
  }
};
</script>

<style>
.boq-page {
  min-height: 100vh;
  background: #E5EEF1; /* Senada dengan Dashboard */
  color: #17677E;
}
.header {
  background: linear-gradient(135deg, #17677E 0%, #145a6b 100%);
  color: #E5EEF1;
  padding: 10px 18px;
  box-shadow: 0 4px 15px rgba(23, 103, 126, 0.2);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 10;
}
.logo {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #E5EEF1;
  font-size: 34px;
  font-weight: 700;
  letter-spacing: 1px;
}
.logo i { font-size: 34px; opacity: 0.95; }
.logo .net { color: #E5EEF1; }
.logo .map { color: #E5EEF1; }
.logout-icon {
  cursor: pointer;
  padding: 6px 10px;
  border-radius: 8px;
  transition: all 0.3s ease;
  color: #E5EEF1;
  font-size: 16px;
  background: rgba(229, 238, 241, 0.1);
}
.logout-icon:hover { background: rgba(229,229,229,0.2); transform: scale(1.05); }

h1 { font-size: 26px; margin: 14px 0 12px; margin-left: 20px; color: #17677E; }
.project-meta { display: flex; gap: 12px; margin-bottom: 10px; color: #145a6b; font-size: 13px; }

.card { 
    background: #ffffff; 
    border: 1px solid rgba(23, 103, 126, 0.15); 
    border-radius: 12px; 
  padding: 24px; 
    margin: 0 auto 50px;
    margin-top: 50px; 
    box-shadow: 0 6px 18px rgba(23, 103, 126, 0.08); 
  max-width: 1200px; 
}

.card-title { 
  font-weight: 700; 
  color: #17677E; 
  margin-bottom: 10px; 
  font-size: 20px; 
}

.card-subtitle { 
    color: #17677E; 
    opacity: 0.8; 
    margin-bottom: 10px; 
    font-size: 12px; 
}

.table-wrapper { 
  overflow-x: auto; 

}
.boq-table { 
  width: 100%; 
  border-collapse: separate; 
  border-spacing: 0; 
  background: white; 
  border-radius: 10px; 
  overflow: hidden; 
  font-size: 14px; 
}

.boq-table thead th { 
  color: #ffffff; 
  font-weight: 700; 
  text-align: left; 
  background: linear-gradient(135deg, #17677E 0%, #145a6b 100%); 
  position: sticky; 
  top: 0; 
  padding: 12px 12px; 
}

.boq-table th, .boq-table td { border-bottom: 1px solid rgba(23, 103, 126, 0.15); padding: 12px; }
.boq-table tbody tr:nth-child(even) { background: rgba(229, 238, 241, 0.25); }
.boq-table tfoot td { background: rgba(23, 103, 126, 0.05); font-weight: 700; }

.text-center { text-align: center; }
.text-right { text-align: right; }
.text-muted { color: #6b7280; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
.empty { text-align: center; padding: 14px; color: #17677E; opacity: 0.8; }

.badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
.badge-odp { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }

.actions { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.btn-save {
  padding: 12px 18px;
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 700;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-save:hover { 
  background: linear-gradient(135deg, #218838, #1ea085); 
  transform: translateY(-2px); 
}

.btn-save:disabled { 
  opacity: 0.7; 
  cursor: not-allowed; 
  transform: none; 
}

.save-msg { 
  margin: 0; 
  font-weight: 600; 
}

.save-msg.success { color: #218838; }

.save-msg.error { 
  color: #c82333; 
}
</style>
