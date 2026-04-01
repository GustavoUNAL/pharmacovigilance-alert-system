<script setup>
import axios from 'axios';
import { computed, ref } from 'vue';

const emit = defineEmits(['logout']);

const lot = ref('');
const startDate = ref('');
const endDate = ref('');
const searchedLot = ref('');
const orders = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const toastMessage = ref('');
const lotNotFound = ref(false);

const selectedOrder = ref(null);
const viewKind = ref(null);
const alertModalOpen = ref(false);
const alertOrderId = ref(null);

const formattedOrderId = (id) => String(id).padStart(7, '0');

const alertBodyText = computed(() => {
    const lotLabel = searchedLot.value || lot.value.trim() || '—';
    return `Warning: Important recall notice for medication Lot #${lotLabel}. Please contact the pharmacy immediately.`;
});

function openViewOrder(row) {
    selectedOrder.value = row;
    viewKind.value = 'order';
}

function openViewBuyer(row) {
    selectedOrder.value = row;
    viewKind.value = 'buyer';
}

function closeViewModal() {
    selectedOrder.value = null;
    viewKind.value = null;
}

function openAlertModal(row) {
    alertOrderId.value = row.order_id;
    alertModalOpen.value = true;
}

function closeAlertModal() {
    alertModalOpen.value = false;
    alertOrderId.value = null;
}

function closeLotNotFoundModal() {
    lotNotFound.value = false;
}

async function search() {
    errorMessage.value = '';
    toastMessage.value = '';
    lotNotFound.value = false;

    const trimmed = lot.value.trim();
    if (!trimmed) {
        errorMessage.value = 'Lot number is required.';
        orders.value = [];
        return;
    }

    loading.value = true;
    try {
        const params = { lot: trimmed };
        if (startDate.value) {
            params.start_date = startDate.value;
        }
        if (endDate.value) {
            params.end_date = endDate.value;
        }

        const { data } = await axios.get('/api/orders', { params });
        orders.value = Array.isArray(data) ? data : [];
        searchedLot.value = trimmed;
        lotNotFound.value = orders.value.length === 0;
    } catch (e) {
        orders.value = [];
        lotNotFound.value = false;
        const msg = e.response?.data?.message;
        errorMessage.value =
            typeof msg === 'string'
                ? msg
                : e.response?.data?.errors
                  ? 'Invalid request.'
                  : 'Search failed.';
    } finally {
        loading.value = false;
    }
}

async function sendAlertEmail() {
    if (alertOrderId.value == null) {
        return;
    }

    errorMessage.value = '';
    toastMessage.value = '';

    try {
        await axios.post('/api/alerts/send', { order_id: alertOrderId.value });
        toastMessage.value = 'Alert sent successfully';
        closeAlertModal();
    } catch (e) {
        const msg = e.response?.data?.message;
        errorMessage.value =
            typeof msg === 'string' ? msg : 'Failed to send alert.';
    }
}

async function logout() {
    try {
        await axios.post('/api/logout');
    } catch {
        /* still clear UI */
    }
    emit('logout');
}

const orderDetailLines = computed(() => {
    const row = selectedOrder.value;
    if (!row || viewKind.value !== 'order') {
        return [];
    }
    const meds = row.medications?.length
        ? row.medications.map((m) => `${m.name} (Lot ${m.lot_number})`).join('\n')
        : '—';
    return [
        `Order ID: ${formattedOrderId(row.order_id)}`,
        `Date: ${formatDisplayDate(row.purchase_date)}`,
        `Medications:\n${meds}`,
    ];
});

const buyerDetailLines = computed(() => {
    const row = selectedOrder.value;
    if (!row || viewKind.value !== 'buyer') {
        return [];
    }
    const c = row.customer || {};
    return [
        `Name: ${c.name ?? '—'}`,
        `Email: ${c.email ?? '—'}`,
        `Phone: ${c.phone ?? '—'}`,
    ];
});

function formatDisplayDate(iso) {
    if (!iso) {
        return '—';
    }
    const d = new Date(iso + 'T12:00:00');
    if (Number.isNaN(d.getTime())) {
        return iso;
    }
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const yyyy = d.getFullYear();
    return `${mm}/${dd}/${yyyy}`;
}
</script>

<template>
    <div class="dash">
        <header class="dash-topbar">
            <button type="button" class="dash-icon-btn dash-icon-btn--ghost" aria-label="Menu">
                ☰
            </button>
            <h1 class="dash-topbar-title">Order Search</h1>
            <div class="dash-topbar-actions">
                <button type="button" class="dash-icon-btn" aria-label="Minimize">─</button>
                <button type="button" class="dash-icon-btn" aria-label="Settings">⚙</button>
                <button type="button" class="dash-icon-btn" @click="logout">✕</button>
            </div>
        </header>

        <div class="dash-bg">
            <div class="dash-card">
                <section class="dash-section">
                    <h2 class="dash-section-title">Medication Search</h2>
                    <div class="dash-search-grid">
                        <label class="dash-field dash-field--lot">
                            <span class="dash-label">Lot Number :</span>
                            <input
                                v-model="lot"
                                class="dash-input"
                                type="text"
                                autocomplete="off"
                            />
                        </label>
                        <div class="dash-dates">
                            <label class="dash-field">
                                <span class="dash-label">Start Date:</span>
                                <input v-model="startDate" class="dash-input" type="date" />
                            </label>
                            <label class="dash-field">
                                <span class="dash-label">End Date:</span>
                                <input v-model="endDate" class="dash-input" type="date" />
                            </label>
                        </div>
                        <div class="dash-search-actions">
                            <button
                                type="button"
                                class="btn btn--search"
                                :disabled="loading"
                                @click="search"
                            >
                                Search
                            </button>
                        </div>
                    </div>
                </section>

                <p v-if="errorMessage" class="dash-msg dash-msg--error">{{ errorMessage }}</p>
                <p v-if="toastMessage" class="dash-msg dash-msg--ok">{{ toastMessage }}</p>

                <section class="dash-section dash-section--table">
                    <div class="dash-results-bar">Order Results</div>
                    <div class="dash-table-wrap">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in orders" :key="row.order_id">
                                    <td>{{ formattedOrderId(row.order_id) }}</td>
                                    <td>{{ row.customer?.name }}</td>
                                    <td>{{ formatDisplayDate(row.purchase_date) }}</td>
                                    <td class="dash-actions">
                                        <button
                                            type="button"
                                            class="btn btn--view-order"
                                            @click="openViewOrder(row)"
                                        >
                                            <span class="btn-ic" aria-hidden="true">▣</span>
                                            View Order
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn--alert"
                                            @click="openAlertModal(row)"
                                        >
                                            <span class="btn-ic" aria-hidden="true">!</span>
                                            Alert Buyer
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn--buyer"
                                            @click="openViewBuyer(row)"
                                        >
                                            <span class="btn-ic" aria-hidden="true">⌂</span>
                                            View Buyer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p
                            v-if="!loading && orders.length === 0 && !lotNotFound"
                            class="dash-empty"
                        >
                            No orders to display. Run a search with a lot number.
                        </p>
                    </div>
                </section>
            </div>
        </div>

        <!-- View Order / View Buyer -->
        <div
            v-if="selectedOrder && viewKind"
            class="modal-backdrop"
            role="presentation"
            @click.self="closeViewModal"
        >
            <div class="modal modal--detail" role="dialog" aria-modal="true">
                <header class="modal-head modal-head--light">
                    <h3 class="modal-title modal-title--dark">
                        {{ viewKind === 'order' ? 'Order details' : 'Buyer details' }}
                    </h3>
                    <button
                        type="button"
                        class="modal-close"
                        aria-label="Close"
                        @click="closeViewModal"
                    >
                        ×
                    </button>
                </header>
                <div class="modal-body modal-body--detail">
                    <pre class="detail-pre">{{
                        viewKind === 'order' ? orderDetailLines.join('\n') : buyerDetailLines.join('\n')
                    }}</pre>
                </div>
                <footer class="modal-foot">
                    <button type="button" class="btn btn--navy" @click="closeViewModal">
                        Close
                    </button>
                </footer>
            </div>
        </div>

        <!-- Lot not found -->
        <div
            v-if="lotNotFound"
            class="modal-backdrop"
            role="presentation"
            @click.self="closeLotNotFoundModal"
        >
            <div
                class="modal"
                role="alertdialog"
                aria-labelledby="lot-not-found-title"
                aria-describedby="lot-not-found-desc"
            >
                <header class="modal-head modal-head--light">
                    <h3 id="lot-not-found-title" class="modal-title modal-title--dark">
                        Lot not found
                    </h3>
                    <button
                        type="button"
                        class="modal-close"
                        aria-label="Close"
                        @click="closeLotNotFoundModal"
                    >
                        ×
                    </button>
                </header>
                <div class="modal-body">
                    <p id="lot-not-found-desc" class="alert-copy">
                        There are no orders associated with this lot number in the database.
                    </p>
                </div>
                <footer class="modal-foot modal-foot--end">
                    <button type="button" class="btn btn--navy" @click="closeLotNotFoundModal">
                        OK
                    </button>
                </footer>
            </div>
        </div>

        <!-- Send Alert -->
        <div
            v-if="alertModalOpen"
            class="modal-backdrop"
            role="presentation"
            @click.self="closeAlertModal"
        >
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="alert-modal-title">
                <header class="modal-head modal-head--light">
                    <h3 id="alert-modal-title" class="modal-title modal-title--dark">
                        Send Alert to Customer
                    </h3>
                    <button
                        type="button"
                        class="modal-close"
                        aria-label="Close"
                        @click="closeAlertModal"
                    >
                        ×
                    </button>
                </header>
                <div class="modal-body">
                    <p class="alert-copy">{{ alertBodyText }}</p>
                </div>
                <footer class="modal-foot modal-foot--end">
                    <button type="button" class="btn btn--navy" @click="closeAlertModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn--search" @click="sendAlertEmail">
                        Send Email
                    </button>
                </footer>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dash {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    font-family:
        system-ui,
        -apple-system,
        'Segoe UI',
        Roboto,
        sans-serif;
    color: #2c3e50;
}

.dash-topbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.65rem 1rem;
    background: #2c3e50;
    color: #fff;
    flex-shrink: 0;
}

.dash-topbar-title {
    flex: 1;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.dash-topbar-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.dash-icon-btn {
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    cursor: pointer;
    font-size: 0.85rem;
    line-height: 1;
}

.dash-icon-btn:hover {
    background: rgba(255, 255, 255, 0.22);
}

.dash-icon-btn--ghost {
    background: transparent;
}

.dash-bg {
    flex: 1;
    background: #e8e8e8;
    padding: 1.5rem 1rem 2rem;
    box-sizing: border-box;
}

.dash-card {
    max-width: 1100px;
    margin: 0 auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #d0d0d0;
    overflow: hidden;
}

.dash-section {
    padding: 0;
    border-bottom: 1px solid #ddd;
}

.dash-section:last-child {
    border-bottom: none;
}

.dash-section-title {
    margin: 0;
    padding: 0.75rem 1.25rem;
    font-size: 0.95rem;
    font-weight: 700;
    background: #ececec;
    border-bottom: 1px solid #ddd;
    color: #2c3e50;
}

.dash-search-grid {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem 1.25rem;
    padding: 1.25rem 1.25rem 1rem;
    align-items: end;
}

@media (max-width: 800px) {
    .dash-search-grid {
        grid-template-columns: 1fr;
    }
}

.dash-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.dash-field--lot {
    grid-column: 1 / -1;
}

.dash-dates {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    grid-column: 1 / 2;
}

.dash-dates .dash-field {
    flex: 1;
    min-width: 140px;
}

.dash-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #34495e;
}

.dash-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.5rem 0.65rem;
    border: 1px solid #bdc3c7;
    border-radius: 4px;
    font-size: 0.9rem;
}

.dash-input:focus {
    outline: none;
    border-color: #2c3e50;
    box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.15);
}

.dash-search-actions {
    display: flex;
    justify-content: flex-end;
    align-items: end;
    grid-column: 2 / 3;
    grid-row: 2 / 3;
}

@media (max-width: 800px) {
    .dash-search-actions {
        grid-column: 1 / -1;
        grid-row: auto;
        justify-content: stretch;
    }

    .dash-search-actions .btn {
        width: 100%;
    }
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.5rem 0.85rem;
    border: none;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.btn--search {
    background: #27ae60;
    color: #fff;
    padding: 0.55rem 1.5rem;
    font-size: 0.9rem;
}

.btn--search:hover:not(:disabled) {
    background: #219a52;
}

.btn--navy {
    background: #2c3e50;
    color: #fff;
}

.btn--navy:hover {
    background: #1f2d3a;
}

.btn--view-order {
    background: #2c3e50;
    color: #fff;
}

.btn--view-order:hover {
    background: #1f2d3a;
}

.btn--alert {
    background: #f39c12;
    color: #fff;
}

.btn--alert:hover {
    background: #e08e0b;
}

.btn--buyer {
    background: #16a085;
    color: #fff;
}

.btn--buyer:hover {
    background: #138d75;
}

.btn-ic {
    font-size: 0.75rem;
    opacity: 0.95;
}

.dash-msg {
    margin: 0 1.25rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
}

.dash-msg--error {
    color: #c0392b;
}

.dash-msg--ok {
    color: #27ae60;
}

.dash-section--table {
    padding-bottom: 0.5rem;
}

.dash-results-bar {
    padding: 0.6rem 1.25rem;
    font-size: 0.9rem;
    font-weight: 600;
    background: #d6eaf8;
    color: #2c3e50;
    border-bottom: 1px solid #aed6f1;
}

.dash-table-wrap {
    padding: 0 0 1rem;
    overflow-x: auto;
}

.dash-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.dash-table thead th {
    background: #2c3e50;
    color: #fff;
    text-align: left;
    padding: 0.65rem 1rem;
    font-weight: 600;
}

.dash-table tbody td {
    padding: 0.65rem 1rem;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: middle;
}

.dash-table tbody tr:nth-child(even) {
    background: #f7f7f7;
}

.dash-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.dash-empty {
    padding: 1.25rem;
    margin: 0;
    color: #7f8c8d;
    font-size: 0.875rem;
    text-align: center;
}

/* Modals */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
    box-sizing: border-box;
}

.modal {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    max-width: 520px;
    width: 100%;
    overflow: hidden;
    border: 1px solid #ccc;
}

.modal--detail {
    max-width: 440px;
}

.modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid #e0e0e0;
}

.modal-head--light {
    background: #fff;
}

.modal-title {
    margin: 0;
    font-size: 1.05rem;
}

.modal-title--dark {
    color: #2c3e50;
    font-weight: 700;
}

.modal-close {
    border: none;
    background: none;
    font-size: 1.5rem;
    line-height: 1;
    color: #7f8c8d;
    cursor: pointer;
    padding: 0 0.25rem;
}

.modal-close:hover {
    color: #2c3e50;
}

.modal-body {
    padding: 1.25rem 1.1rem;
}

.modal-body--detail {
    padding: 1rem 1.1rem;
}

.alert-copy {
    margin: 0;
    line-height: 1.55;
    color: #2c3e50;
    font-size: 0.95rem;
}

.detail-pre {
    margin: 0;
    font-family: inherit;
    white-space: pre-wrap;
    line-height: 1.5;
    font-size: 0.9rem;
    color: #34495e;
}

.modal-foot {
    display: flex;
    gap: 0.5rem;
    padding: 0.85rem 1.1rem;
    border-top: 1px solid #e0e0e0;
    background: #fafafa;
}

.modal-foot--end {
    justify-content: flex-end;
}
</style>
