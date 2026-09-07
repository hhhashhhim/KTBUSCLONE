<template>
  <section class="section inventory-reports-page">
    <div class="section-body">
      <div class="card card-primary report-directory">
        <div class="card-header report-header">
          <div>
            <h4 class="mb-1">Inventory Reports</h4>
            <p class="text-muted mb-0">Stock, purchasing and issuance reports in one place</p>
          </div>
          <span class="report-count">{{ reports.length }} reports</span>
        </div>
        <div class="card-body">
          <div class="row report-links">
            <div v-for="(item, index) in reports" :key="item.key" class="col-md-4 col-sm-6 mb-3">
              <a href="#" class="report-link" :class="{ active: activeReport === item.key }" @click.prevent="selectReport(item)">
                <span class="report-number">{{ index + 1 }}</span>
                <span class="report-icon"><i :class="item.icon"></i></span>
                <span><strong>{{ item.name }}</strong><small>{{ item.description }}</small></span>
                <i class="fas fa-chevron-right report-arrow"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

      <div v-if="selectedReport" class="card report-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div><h4 class="mb-1">{{ selectedReport.name }}</h4><p class="text-muted mb-0">{{ selectedReport.description }}</p></div>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeReport"><i class="fas fa-times mr-1"></i> Close</button>
        </div>
        <div class="card-body">
          <form @submit.prevent="runReport">
            <div class="row align-items-end">
              <div v-if="uses('product')" class="col-lg-3 col-md-4 form-group">
                <label>Product <span v-if="selectedReport.productRequired" class="text-danger">*</span></label>
                <select v-model="filters.product_id" class="form-control"><option value="">All Products</option><option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option></select>
              </div>
              <div v-if="uses('supplier')" class="col-lg-3 col-md-4 form-group">
                <label>Supplier <span v-if="selectedReport.supplierRequired" class="text-danger">*</span></label>
                <select v-model="filters.supplier_id" class="form-control"><option value="">All Suppliers</option><option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option></select>
              </div>
              <div v-if="uses('bus')" class="col-lg-3 col-md-4 form-group">
                <label>Bus</label><select v-model="filters.bus_id" class="form-control"><option value="">All Buses</option><option v-for="bus in buses" :key="bus.id" :value="bus.id">{{ bus.bus_number }}</option></select>
              </div>
              <div v-if="uses('grn')" class="col-lg-2 col-md-3 form-group"><label>GRN #</label><input v-model="filters.grn" type="number" min="1" class="form-control" placeholder="GRN number"></div>
              <div v-if="uses('po')" class="col-lg-2 col-md-3 form-group"><label>PO #</label><input v-model="filters.po" type="number" min="1" class="form-control" placeholder="PO number"></div>
              <div v-if="uses('mr')" class="col-lg-2 col-md-3 form-group"><label>MR #</label><input v-model="filters.mr" type="number" min="1" class="form-control" placeholder="MR number"></div>
              <div v-if="uses('sin')" class="col-lg-2 col-md-3 form-group"><label>SIN #</label><input v-model="filters.sin" type="number" min="1" class="form-control" placeholder="SIN number"></div>
              <div v-if="uses('dates')" class="col-lg-2 col-md-3 form-group"><label>From Date</label><input v-model="filters.from" type="date" class="form-control"></div>
              <div v-if="uses('dates')" class="col-lg-2 col-md-3 form-group"><label>To Date</label><input v-model="filters.to" type="date" class="form-control"></div>
              <div class="col-lg-3 col-md-6 form-group ml-auto">
                <div class="d-flex report-actions">
                  <button type="submit" class="btn btn-primary flex-fill" :disabled="loading"><span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span><i v-else class="fas fa-search mr-1"></i>{{ loading ? 'Loading...' : 'View Report' }}</button>
                  <button type="button" class="btn btn-light ml-2" :disabled="loading" @click="resetFilters">Reset</button>
                </div>
              </div>
            </div>
          </form>

          <div v-if="hasRun" class="report-results mt-3">
            <div class="results-toolbar">
              <div><h5 class="mb-0">{{ result.title || selectedReport.name }}</h5><small class="text-muted">{{ result.count || 0 }} record(s) found</small></div>
              <button type="button" class="btn btn-dark" :disabled="loading" @click="printReport"><i class="fas fa-print mr-1"></i> Print PDF</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover mb-0">
                <thead><tr><th v-for="column in result.columns" :key="column.key" :class="textClass(column)">{{ column.label }}</th></tr></thead>
                <tbody>
                  <tr v-for="(row, rowIndex) in result.rows" :key="rowIndex"><td v-for="column in result.columns" :key="column.key" :class="textClass(column)">{{ display(row[column.key]) }}</td></tr>
                  <tr v-if="!result.rows || !result.rows.length"><td :colspan="result.columns ? result.columns.length : 1" class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No records found for the selected filters.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <form ref="printForm" :action="`${$store.state.api_url}api/web/v1/inventory/reports/pdf`" method="post" target="_blank">
        <input type="hidden" name="token" :value="$store.state.token">
        <input v-for="(value, key) in printPayload" :key="key" type="hidden" :name="key" :value="value">
      </form>
    </div>
  </section>
</template>

<script>
export default {
  data() {
    return {
      activeReport: '', loading: false, hasRun: false, products: [], suppliers: [], buses: [],
      result: { title: '', columns: [], rows: [], count: 0 },
      filters: { product_id: '', supplier_id: '', bus_id: '', grn: '', po: '', mr: '', sin: '', from: '', to: '' },
      reports: [
        { key: 'control-register', name: 'Inventory Control Register', description: 'Opening, received, issued and balance quantities', icon: 'fas fa-clipboard-list', fields: ['product', 'dates'] },
        { key: 'goods-received', name: 'Goods Received Register', description: 'Detailed GRN and supplier purchase receipts', icon: 'fas fa-shipping-fast', fields: ['product', 'supplier', 'grn', 'po', 'dates'] },
        { key: 'goods-issued', name: 'Goods Issued Register', description: 'Detailed stock issuance by bus and document', icon: 'fas fa-dolly-flatbed', fields: ['product', 'bus', 'mr', 'sin', 'dates'] },
        { key: 'pending-grn', name: 'Pending GRN Details', description: 'Ordered stock still awaiting receipt', icon: 'fas fa-hourglass-half', fields: ['product', 'supplier', 'po', 'dates'] },
        { key: 'daily-issuance', name: 'Daily Issuance Detail', description: 'Daily item issuance transaction history', icon: 'fas fa-calendar-day', fields: ['product', 'bus', 'mr', 'sin', 'dates'] },
        { key: 'item-issue-summary', name: 'Item Issue Summary', description: 'Quantity and value issued by item and bus', icon: 'fas fa-chart-pie', fields: ['product', 'bus', 'dates'] },
        { key: 'item-ledger', name: 'Item Ledger', description: 'Chronological receipts, issues and running balance', icon: 'fas fa-book', fields: ['product', 'dates'], productRequired: true },
        { key: 'item-wise-purchase', name: 'Item-wise Purchase History', description: 'Purchase price and quantity history for an item', icon: 'fas fa-history', fields: ['product', 'supplier', 'dates'], productRequired: true },
        { key: 'party-wise-purchase', name: 'Party-wise Purchase History', description: 'Purchases made from a selected supplier', icon: 'fas fa-handshake', fields: ['supplier', 'product', 'dates'], supplierRequired: true },
        { key: 'balance-stock', name: 'Balance Stock Report', description: 'Current quantity, average rate and stock value', icon: 'fas fa-boxes', fields: ['product'] }
      ]
    }
  },
  computed: {
    selectedReport() { return this.reports.find(report => report.key === this.activeReport) || null },
    printPayload() { return Object.assign({ type: this.activeReport }, this.filters) }
  },
  created() { this.fetchReportData() },
  methods: {
    emptyFilters() { return { product_id: '', supplier_id: '', bus_id: '', grn: '', po: '', mr: '', sin: '', from: '', to: '' } },
    async fetchReportData() { const response = await this.callApi('get', 'inventory/reports/data'); if (response.status === 200) { this.products = response.data.products || []; this.suppliers = response.data.suppliers || []; this.buses = response.data.buses || [] } },
    selectReport(report) { this.activeReport = report.key; this.resetFilters() },
    closeReport() { this.activeReport = ''; this.hasRun = false; this.result = { title: '', columns: [], rows: [], count: 0 } },
    uses(field) { return this.selectedReport && this.selectedReport.fields.includes(field) },
    resetFilters() { this.filters = this.emptyFilters(); this.hasRun = false; this.result = { title: '', columns: [], rows: [], count: 0 } },
    async runReport() {
      if (this.selectedReport.productRequired && !this.filters.product_id) return this.alertRequired('Please select a product.')
      if (this.selectedReport.supplierRequired && !this.filters.supplier_id) return this.alertRequired('Please select a supplier.')
      if (this.filters.from && this.filters.to && this.filters.from > this.filters.to) return this.alertRequired('From Date cannot be after To Date.')
      this.loading = true
      try { const response = await this.callApi('post', 'inventory/reports/run', this.printPayload); if (response.status === 200) { this.result = response.data; this.hasRun = true } else this.showApiError(response) } finally { this.loading = false }
    },
    printReport() { this.$nextTick(() => this.$refs.printForm.submit()) },
    alertRequired(text) { swal({ title: 'Required', text, icon: 'warning', timer: 2500 }) },
    showApiError(response) { const errors = response.data && response.data.errors ? Object.values(response.data.errors).reduce((all, messages) => all.concat(messages), []).join('\n') : 'Unable to load this report.'; swal({ title: 'Report Error', text: errors, icon: 'error' }) },
    textClass(column) { return column.align === 'right' ? 'text-right' : (column.align === 'center' ? 'text-center' : '') },
    display(value) { return value === null || value === undefined || value === '' ? '-' : value }
  }
}
</script>

<style scoped>
.inventory-reports-page { --report-primary: #6777ef; --report-navy: #263b70; }
.report-directory, .report-panel { border: 0; border-radius: 12px; box-shadow: 0 5px 22px rgba(31,45,61,.08); overflow: hidden; }
.report-header { min-height: 86px; padding: 20px 24px; }
.report-header h4, .report-panel h4 { color: var(--report-navy); }
.report-count { background: #eef0ff; color: var(--report-primary); border-radius: 20px; padding: 7px 13px; font-size: 12px; font-weight: 700; }
.report-link { display: flex; align-items: center; min-height: 82px; padding: 14px; color: #3d4655; border: 1px solid #edf0f5; border-radius: 10px; background: #fff; transition: .2s ease; text-decoration: none; }
.report-link:hover, .report-link.active { color: var(--report-primary); border-color: #cbd1ff; background: #f8f9ff; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(103,119,239,.13); }
.report-number { align-self: flex-start; color: #a3a9b5; font-size: 11px; margin-right: 8px; }
.report-icon { display: grid; place-items: center; flex: 0 0 42px; height: 42px; margin-right: 12px; border-radius: 10px; color: var(--report-primary); background: #eef0ff; font-size: 17px; }
.report-link strong, .report-link small { display: block; }
.report-link strong { font-size: 14px; margin-bottom: 3px; }
.report-link small { color: #8b93a1; line-height: 1.35; }
.report-arrow { margin-left: auto; color: #c3c8d1; font-size: 11px; }
.report-panel .card-header { min-height: 82px; padding: 18px 24px; border-bottom: 1px solid #f0f1f5; }
.report-actions .btn { min-height: 42px; }
.report-results { border: 1px solid #e9ebf1; border-radius: 10px; overflow: hidden; }
.results-toolbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 18px; background: #f8f9fc; border-bottom: 1px solid #e9ebf1; }
.report-results thead th { white-space: nowrap; background: var(--report-navy); color: #fff; border-color: #405482; padding: 10px 8px; font-size: 12px; }
.report-results tbody td { padding: 9px 8px; vertical-align: middle; }
@media (max-width: 767px) { .report-header, .results-toolbar { align-items: flex-start; gap: 12px; } .results-toolbar { flex-direction: column; } }
</style>
