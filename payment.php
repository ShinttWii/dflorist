<?php
$pageTitle = 'Pembayaran - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) { redirect('login.php'); }

$orderNumber = $_GET['order'] ?? '';
if (!$orderNumber) redirect('orders.php');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNumber, $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) redirect('orders.php');
if ($order['payment_status'] === 'paid') redirect('receipt.php?order=' . $orderNumber);

// Auto-cancel kalau sudah lebih 24 jam belum dibayar
if ($order['payment_status'] === 'pending' && $order['payment_method'] === 'midtrans') {
    $createdAt = strtotime($order['created_at']);
    if (time() - $createdAt >= 86400) {
        $pdo->prepare("UPDATE orders SET payment_status='failed', order_status='dibatalkan' WHERE id=?")
            ->execute([$order['id']]);
        redirect('orders.php?expired=1');
    }
}

$clientKey    = getSetting($pdo, 'midtrans_client_key') ?: ($_ENV['MIDTRANS_CLIENT_KEY'] ?? getenv('MIDTRANS_CLIENT_KEY') ?? '');
$isProduction = (getSetting($pdo, 'midtrans_is_production') ?: ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? getenv('MIDTRANS_IS_PRODUCTION') ?? 'false')) === 'true';
$snapUrl      = $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
?>
<style>
.pw{background:#C5E3F6;padding:32px 0 80px;min-height:100vh;}
.pc{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:12px;overflow:hidden;}
.ph{background:linear-gradient(135deg,#FF69B4,#ff8fab);padding:18px 22px;display:flex;align-items:center;gap:12px;}
.phi{background:rgba(255,255,255,.25);width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-size:16px;}
.pht{color:#fff;font-weight:700;font-size:16px;}
.phs{color:rgba(255,255,255,.85);font-size:12px;margin-top:2px;}
.pb{padding:18px 22px;}
.pr{display:flex;justify-content:space-between;font-size:14px;color:#555;margin-bottom:9px;}
.pd{border:none;border-top:1px dashed #e0e0e0;margin:12px 0;}
.pt{font-weight:700;font-size:15px;color:#222;margin-bottom:0;}
.pta{color:#FF69B4;font-size:19px;font-weight:700;}
.pst{font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.6px;padding:14px 22px 10px;}
.pm{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:0 22px 14px;}
.pmi{border-radius:10px;padding:11px 6px;text-align:center;}
.pmi i{font-size:17px;display:block;margin-bottom:5px;}
.pmi span{font-size:10px;font-weight:600;color:#444;line-height:1.3;display:block;}
.pn{margin:0 22px 16px;background:#fffbf0;border-radius:8px;padding:9px 12px;font-size:12px;color:#888;display:flex;align-items:center;gap:8px;}
.pbtn{width:100%;padding:15px;background:linear-gradient(135deg,#FF69B4,#ff8fab);border:none;border-radius:14px;color:#fff;font-size:16px;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(255,105,180,.4);display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:14px;}
.pbtn:disabled{opacity:.7;cursor:not-allowed;}
.psec{text-align:center;font-size:12px;color:#bbb;display:flex;align-items:center;justify-content:center;gap:6px;}
.pbk{display:inline-flex;align-items:center;gap:6px;color:#888;font-size:13px;text-decoration:none;margin-bottom:16px;}
.pbk:hover{color:#FF69B4;}
</style>
<div class="pw"><div class="container"><div class="row justify-content-center"><div class="col-12 col-sm-9 col-md-6 col-lg-5">
<a href="orders.php" class="pbk"><i class="fas fa-arrow-left"></i> Kembali ke Pesanan</a>
<div class="pc">
  <div class="ph">
    <div class="phi"><i class="fas fa-lock"></i></div>
    <div>
      <div class="pht">Selesaikan Pembayaran</div>
      <div class="phs">Pesanan #<?php echo htmlspecialchars($order['order_number']); ?></div>
    </div>
  </div>
  <div class="pb">
    <div class="pr"><span>Subtotal Produk</span><span><?php echo formatRupiah($order['subtotal']); ?></span></div>
    <?php if ($order['shipping_cost'] > 0): ?>
    <div class="pr"><span>Ongkos Kirim</span><span><?php echo formatRupiah($order['shipping_cost']); ?></span></div>
    <?php endif; ?>
    <?php if (($order['discount'] ?? 0) > 0): ?>
    <div class="pr"><span>Diskon</span><span style="color:#e53935;">-<?php echo formatRupiah($order['discount']); ?></span></div>
    <?php endif; ?>
    <hr class="pd">
    <div class="pr pt"><span>Total Pembayaran</span><span class="pta"><?php echo formatRupiah($order['total']); ?></span></div>
  </div>
</div>
<div class="pc">
  <div class="pst">Metode Pembayaran Tersedia</div>
  <div class="pm">
    <div class="pmi" style="background:#e8f8fc;"><i class="fas fa-qrcode" style="color:#00b4d8;"></i><span>QRIS</span></div>
    <div class="pmi" style="background:#e8eef8;"><i class="fas fa-university" style="color:#1565c0;"></i><span>Transfer Bank</span></div>
    <div class="pmi" style="background:#fdecea;"><i class="fas fa-wallet" style="color:#e53935;"></i><span>GoPay</span></div>
    <div class="pmi" style="background:#f3e5f5;"><i class="fas fa-wallet" style="color:#4a148c;"></i><span>OVO</span></div>
    <div class="pmi" style="background:#e1f5fe;"><i class="fas fa-wallet" style="color:#0277bd;"></i><span>DANA</span></div>
    <div class="pmi" style="background:#fff3e0;"><i class="fas fa-credit-card" style="color:#e65100;"></i><span>Kartu Kredit</span></div>
  </div>
  <div class="pn"><i class="fas fa-info-circle" style="color:#f59e0b;"></i> Pilih metode setelah klik tombol di bawah</div>
</div>
<button id="payBtn" onclick="startPayment()" class="pbtn"><i class="fas fa-lock"></i> Bayar Sekarang</button>
<div class="psec"><i class="fas fa-shield-alt" style="color:#4caf50;"></i> Transaksi aman &amp; terenkripsi oleh Midtrans</div>
</div></div></div></div>
<script src="<?php echo $snapUrl; ?>" data-client-key="<?php echo htmlspecialchars($clientKey); ?>"></script>
<script>
function startPayment(){
  var btn=document.getElementById('payBtn');
  btn.disabled=true;
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Memuat...';
  var fd=new FormData();
  fd.append('order_number','<?php echo $order['order_number']; ?>');
  fetch('api/midtrans_token.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(data){
      if(!data.success){alert('Gagal: '+data.message);resetBtn();return;}
      window.snap.pay(data.token,{
        onSuccess:function(){window.location.href='order_success.php?order=<?php echo $order['order_number']; ?>';},
        onPending:function(){window.location.href='orders.php';},
        onError:function(){alert('Pembayaran gagal.');resetBtn();},
        onClose:function(){resetBtn();}
      });
    })
    .catch(function(){alert('Terjadi kesalahan.');resetBtn();});
}
function resetBtn(){
  var btn=document.getElementById('payBtn');
  btn.disabled=false;
  btn.innerHTML='<i class="fas fa-lock"></i> Bayar Sekarang';
}
</script>
<?php include 'includes/footer.php'; ?>
