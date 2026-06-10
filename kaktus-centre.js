let cart = [];
    let allProducts = [];
    let itemsToShow = 8; // Mengatur batas produk awal yang muncul (contoh: 8 produk)

    // Memuat fungsi produk otomatis saat halaman dibuka
    document.addEventListener("DOMContentLoaded", function() {
        fetchProducts();
        
        // Logika klik tombol Load More
        document.getElementById('load-more-btn').addEventListener('click', function() {
            itemsToShow += 4; // Menambahkan 4 produk lagi setiap kali ditekan
            renderProducts();
        });
    });

    // Mengambil data dari database via get_products.php
    function fetchProducts() {
        fetch('get_products.php')
            .then(response => response.json())
            .then(data => {
                allProducts = data;
                renderProducts();
            })
            .catch(error => {
                console.error('Error memuat produk:', error);
                document.getElementById('product-container').innerHTML = '<p style="text-align:center; width:100; color:red;">Gagal mengambil data produk terbaru.</p>';
            });
    }

    // Menampilkan item ke HTML
    function renderProducts() {
        const container = document.getElementById('product-container');
        const loadMoreBtn = document.getElementById('load-more-btn');
        container.innerHTML = '';

        if (allProducts.length === 0) {
            container.innerHTML = '<p style="text-align: center; width:100%; color: #888;">Belum ada produk tersedia.</p>';
            loadMoreBtn.style.display = 'none';
            return;
        }

        // Memotong list array data produk sesuai jumlah batas tampil
        const productsToDisplay = allProducts.slice(0, itemsToShow);

        productsToDisplay.forEach(product => {
            const div = document.createElement('div');
            div.className = 'produk';
            
            // Berdasarkan folder terlampir, folder uploads ada di dalam admin_frontend
            const fixImagePath = product.image_path.startsWith('uploads') 
                                 ? product.image_path 
                                 : 'uploads/' + product.image_path;

            div.innerHTML = `
                <img class="img-produk" src="${fixImagePath}">
                <div class="detail-produk">
                    <div class="rincian-produk">
                        <p class="nama-produk">${product.name}</p>
                        <p class="harga">Harga: Rp.${product.price.toLocaleString('id-ID')}</p>
                    </div>
                    <button class="button-produk" onclick="addToCart(${product.id}, '${product.name}', ${product.price})">Beli Sekarang</button>
                </div>
            `;
            container.appendChild(div);
        });

        // Kontrol tampilan tombol Load More
        if (itemsToShow < allProducts.length) {
            loadMoreBtn.style.display = 'inline-block';
        } else {
            loadMoreBtn.style.display = 'none';
        }
    }

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
      const modalTotalLabel = document.getElementById('modalTotalPrice');
      
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
      modalTotalLabel.innerText = formattedTotal; 
    }

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
        
        document.getElementById('cartSidebar').classList.remove('open');
        document.getElementById('checkout-modal').style.display = 'block';
    }

    function closeCheckoutModal() {
        document.getElementById('checkout-modal').style.display = 'none';
    }

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

    window.onclick = function(event) {
        const modal = document.getElementById('checkout-modal');
        if (event.target == modal) {
            closeCheckoutModal();
        }
    }