const BACKEND_URL = 'admin.php';

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardStats();
    loadProducts();
    loadOrders();
});

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`tab-${tabId}`).classList.remove('hidden');
    
    document.querySelectorAll('.sidebar-menu-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(`nav-${tabId}`).classList.add('active');

    const titles = { 'dashboard': 'Dashboard Overview', 'products': 'Kelola Produk Toko', 'orders': 'Kelola Transaksi & Pesanan' };
    document.getElementById('page-title').innerText = titles[tabId] || 'Admin Panel';
}

function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function showAlert(message, isSuccess = true) {
    const alertBox = document.getElementById('alert-box');
    document.getElementById('alert-message').innerText = message;
    alertBox.className = `mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border transition-all ${isSuccess ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'}`;
    alertBox.classList.remove('hidden');
    setTimeout(closeAlert, 5000);
}
function closeAlert() { document.getElementById('alert-box').classList.add('hidden'); }

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
}

function loadDashboardStats() {
    fetch(`${BACKEND_URL}?action=get_dashboard_stats`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('stat-total-products').innerText = data.total_products;
            document.getElementById('stat-total-orders').innerText = data.total_orders;
            document.getElementById('stat-pending-orders').innerText = data.pending_orders;
            document.getElementById('stat-completed-orders').innerText = data.completed_orders;

            const tbody = document.getElementById('dashboard-orders-tbody');
            tbody.innerHTML = '';
            if(data.recent_orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-400 py-6">Belum ada aktivitas pesanan</td></tr>';
                return;
            }
            data.recent_orders.forEach(order => {
                const badgeClass = order.status === 'Pending' ? 'badge-pending' : 'badge-completed';
                tbody.innerHTML += `
                    <tr>
                        <td style="font-weight: 700; color: #64748b;">#${order.id}</td>
                        <td style="font-weight: 600;">${order.customer_name}</td>
                        <td style="color: #64748b;">${order.order_date}</td>
                        <td style="font-weight: 700;">${formatRupiah(order.total_price)}</td>
                        <td><span class="badge ${badgeClass}">${order.status}</span></td>
                    </tr>
                `;
            });
        });
}

function loadProducts() {
    fetch(`${BACKEND_URL}?action=get_products`)
        .then(res => res.json())
        .then(products => {
            const tbody = document.getElementById('products-tbody');
            tbody.innerHTML = '';
            if(products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-400 py-6">Belum ada produk terdaftar</td></tr>';
                return;
            }
            products.forEach(p => {
                tbody.innerHTML += `
                    <tr class="group">
                        <td>
                            <img src="${p.image_path}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;" class="group-hover:scale-105 transition-transform">
                        </td>
                        <td style="font-weight: 700;">${p.name}</td>
                        <td><span class="badge badge-gray">${p.category}</span></td>
                        <td style="font-weight: 800; color: #15803d;">${formatRupiah(p.price)}</td>
                        <td class="text-center">
                            <button onclick="openEditModal(${p.id}, '${escapeHtml(p.name)}', '${p.category}', ${p.price})" class="btn-kaktus btn-icon-only" style="background: #eff6ff; color: #2563eb;" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button onclick="deleteProduct(${p.id})" class="btn-kaktus btn-icon-only" style="background: #fef2f2; color: #dc2626; margin-left: 4px;" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
}

function loadOrders() {
    fetch(`${BACKEND_URL}?action=get_orders`)
        .then(res => res.json())
        .then(orders => {
            const tbody = document.getElementById('orders-tbody');
            tbody.innerHTML = '';
            if(orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-400 py-6">Belum ada pesanan masuk</td></tr>';
                return;
            }
            orders.forEach(o => {
                const badgeClass = o.status === 'Pending' ? 'badge-pending' : 'badge-completed';
                const paymentProofLink = o.payment_proof 
                    ? `<a href="${o.payment_proof}" target="_blank" style="color: #2563eb; font-weight: 700; display: flex; align-items: center; gap: 6px;"><i class="fa-solid fa-file-image"></i> Lihat Bukti</a>`
                    : `<span style="color: #94a3b8; font-style: italic; font-size: 12px;">Belum Upload</span>`;
                
                const actionButton = o.status === 'Pending'
                    ? `<button onclick="completeOrder(${o.id})" class="btn-kaktus btn-primary" style="width: 100%; padding: 6px; font-size: 12px; margin-top: 8px;"><i class="fa-solid fa-check"></i> Selesaikan</button>`
                    : `<span style="font-size: 13px; color: #15803d; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 8px;"><i class="fa-solid fa-circle-check"></i> Selesai</span>`;

                tbody.innerHTML += `
                    <tr>
                        <td style="font-weight: 700; color: #64748b;">#${o.id}</td>
                        <td style="font-weight: 700;">${o.customer_name}</td>
                        <td>
                            <div style="font-size: 13px; font-weight: 600; background: #f1f5f9; display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;"><i class="fa-solid fa-phone" style="color: #94a3b8; margin-right: 6px;"></i>${o.customer_phone}</div>
                            <div style="font-size: 13px; color: #64748b; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${o.customer_address}"><i class="fa-solid fa-location-dot" style="color: #94a3b8; margin-right: 6px;"></i>${o.customer_address}</div>
                        </td>
                        <td style="font-weight: 800;">${formatRupiah(o.total_price)}</td>
                        <td>${paymentProofLink}</td>
                        <td><span class="badge ${badgeClass}">${o.status}</span></td>
                        <td class="text-center">
                            <button onclick="viewOrderItems(${o.id})" class="btn-kaktus btn-outline" style="width: 100%; padding: 6px; font-size: 12px;"><i class="fa-solid fa-list"></i> Rincian</button>
                            ${actionButton}
                        </td>
                    </tr>
                `;
            });
        });
}

function submitAddProduct(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('add-product-form'));
    fetch(`${BACKEND_URL}?action=add_product`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                showAlert(res.message, true); closeModal('add-product-modal');
                document.getElementById('add-product-form').reset();
                loadProducts(); loadDashboardStats();
            } else { showAlert(res.message, false); }
        });
}

function openEditModal(id, name, category, price) {
    document.getElementById('edit-p-id').value = id;
    document.getElementById('edit-p-name').value = name;
    document.getElementById('edit-p-category').value = category;
    document.getElementById('edit-p-price').value = price;
    openModal('edit-product-modal');
}

function submitEditProduct(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('edit-product-form'));
    fetch(`${BACKEND_URL}?action=edit_product`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if(res.success) { showAlert(res.message, true); closeModal('edit-product-modal'); loadProducts(); } 
            else { showAlert(res.message, false); }
        });
}

function deleteProduct(id) {
    if(confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
        fetch(`${BACKEND_URL}?action=delete_product&id=${id}`, { method: 'POST' })
            .then(res => res.json())
            .then(res => {
                if(res.success) { showAlert(res.message, true); loadProducts(); loadDashboardStats(); } 
                else { showAlert(res.message, false); }
            });
    }
}

function completeOrder(id) {
    if(confirm('Tandai pesanan ini sebagai Selesai?')) {
        fetch(`${BACKEND_URL}?action=update_order_status&id=${id}`, { method: 'POST' })
            .then(res => res.json())
            .then(res => {
                if(res.success) { showAlert(res.message, true); loadOrders(); loadDashboardStats(); } 
                else { showAlert(res.message, false); }
            });
    }
}

function viewOrderItems(orderId) {
    document.getElementById('detail-order-id').innerText = orderId;
    const tbody = document.getElementById('order-items-tbody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray-400 py-6">Memuat rincian...</td></tr>';
    openModal('order-items-modal');

    fetch(`${BACKEND_URL}?action=get_order_items&order_id=${orderId}`)
        .then(res => res.json())
        .then(items => {
            tbody.innerHTML = '';
            items.forEach(item => {
                const pName = item.product_name ? item.product_name : `<span style="color: #ef4444; font-style: italic;">Produk terhapus</span>`;
                tbody.innerHTML += `
                    <tr>
                        <td style="font-weight: 600;">${pName}</td>
                        <td class="text-center"><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 700;">${item.quantity}</span></td>
                        <td class="text-right" style="color: #64748b;">${formatRupiah(item.price)}</td>
                        <td class="text-right" style="font-weight: 800; color: #0f172a;">${formatRupiah(item.quantity * item.price)}</td>
                    </tr>
                `;
            });
        });
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}