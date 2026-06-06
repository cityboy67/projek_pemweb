let cart = [];

function toggleCart() {
  const sidebar = document.getElementById('cartSidebar');
  sidebar.classList.toggle('open');
}

function addToCart(id, name, price) {
  const existingItem = cart.find(item => item.id === id);
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({ id: id, name: name, price: price, quantity: 1 });
  }
  updateCartUI();
  document.getElementById('cartSidebar').classList.add('open');
}

function changeQty(id, delta) {
  const item = cart.find(item => item.id === id);
  if (item) {
    item.quantity += delta;
    if (item.quantity <= 0) {
      cart = cart.filter(i => i.id !== id);
    }
    updateCartUI();
  }
}

function updateCartUI() {
  const container = document.getElementById('cartItems');
  const countBadge = document.getElementById('cartCount');
  const totalLabel = document.getElementById('totalPrice');
  const modalTotalLabel = document.getElementById('modalTotalPrice'); // Harga di dalam modal rekening
  
  let totalItems = 0;
  let totalAmount = 0;
  
  if (cart.length === 0) {
    container.innerHTML = '<p style="text-align: center; color: #888; margin-top: 30px;">Keranjang Anda masih kosong.</p>';
    countBadge.innerText = '0';
    totalLabel.innerText = 'Rp 0';
    modalTotalLabel.innerText = 'Rp 0';
    return;
  }
  
  container.innerHTML = '';
  cart.forEach(item => {
    totalItems += item.quantity;
    totalAmount += (item.price * item.quantity);
    
    const div = document.createElement('div');
    div.className = 'cart-item';
    div.innerHTML = `
      <div class="cart-item-info">
        <p><strong>${item.name}</strong></p>
        <p style="color:#2e7d32; font-size:13px;">Rp ${item.price.toLocaleString('id-ID')}</p>
      </div>
      <div class="cart-item-qty">
        <button type="button" onclick="changeQty(${item.id}, -1)">-</button>
        <span>${item.quantity}</span>
        <button type="button" onclick="changeQty(${item.id}, 1)">+</button>
      </div>
    `;
    container.appendChild(div);
  });
  
  countBadge.innerText = totalItems;
  const formattedTotal = 'Rp ' + totalAmount.toLocaleString('id-ID');
  totalLabel.innerText = formattedTotal;
  modalTotalLabel.innerText = formattedTotal; // Menampilkan total di modal juga agar user tahu jumlah yang harus di transfer
}

// Membuka Modal Pembayaran
function openCheckoutModal() {
    let totalQty = cart.reduce((acc, current) => acc + current.quantity, 0);
    if (totalQty < 3) {
        alert("Mohon maaf, minimal total pembelian adalah 3 pcs tanaman hias sesuai dengan ketentuan.");
        return;
    }
    if (cart.length === 0) {
        alert("Keranjang Anda masih kosong!");
        return;
    }
    
    // Tutup sidebar keranjang dan buka modal rekening
    document.getElementById('cartSidebar').classList.remove('open');
    document.getElementById('checkout-modal').style.display = 'block';
}

// Menutup Modal Pembayaran
function closeCheckoutModal() {
    document.getElementById('checkout-modal').style.display = 'none';
}

// Penanganan Event Submit
const checkoutForm = document.getElementById('checkout-form');

checkoutForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const totalAmount = cart.reduce((acc, current) => acc + (current.price * current.quantity), 0);
    const formData = new FormData(checkoutForm);
    
    formData.append('cart_items', JSON.stringify(cart));
    formData.append('total_price', totalAmount);

    const btnSubmit = document.getElementById('btn-konfirmasi');
    btnSubmit.innerText = "Sedang Memproses...";
    btnSubmit.disabled = true;

    fetch('checkout.php', {
        method: 'POST',
        body: formData 
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // POP-UP PESAN BERHASIL SESUAI PERMINTAAN ANDA (Ganti nomor WA di bawah ini dengan nomor yang sebenarnya)
            alert("Pesanan telah dibuat mohon tunggu informasi selanjutnya dari admin kami (WhatsApp: 0812-3456-7890)");
            
            cart = []; 
            closeCheckoutModal();
            checkoutForm.reset();
            updateCartUI(); 
        } else {
            alert("Gagal memproses pesanan: " + data.message);
        }
        btnSubmit.innerText = "Konfirmasi & Kirim Pesanan";
        btnSubmit.disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Terjadi kesalahan sistem saat mengirimkan pesanan.");
        btnSubmit.innerText = "Konfirmasi & Kirim Pesanan";
        btnSubmit.disabled = false;
    });
});

// Menutup modal jika klik area luar modal
window.onclick = function(event) {
    const modal = document.getElementById('checkout-modal');
    if (event.target == modal) {
        closeCheckoutModal();
    }
}