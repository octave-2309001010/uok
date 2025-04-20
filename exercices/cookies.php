<?php
// Prices
$samoas_price = 3.50;
$thinmints_price = 4.00;
$regular_shipping = 7.00;
$express_shipping = 9.00;
$donation_amount = 5.00;

// Get form values and sanitize
$samoas_qty = isset($_POST['samoas']) ? intval($_POST['samoas']) : 0;
$thinmints_qty = isset($_POST['thinmints']) ? intval($_POST['thinmints']) : 0;
$shipping_method = isset($_POST['shipping']) ? $_POST['shipping'] : 'regular';
$donate = isset($_POST['donation']);

// Calculate total
$samoas_total = $samoas_qty * $samoas_price;
$thinmints_total = $thinmints_qty * $thinmints_price;
$shipping_cost = ($shipping_method === 'express') ? $express_shipping : $regular_shipping;
$total = $samoas_total + $thinmints_total + $shipping_cost;
if ($donate) {
    $total += $donation_amount;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Order</title>
</head>
<body>
  <h1>Your order:</h1>

  <div>
    <?php for ($i = 0; $i < $samoas_qty; $i++): ?>
      <img src="samoas.jpg" alt="Samoas" width="100" />
    <?php endfor; ?>
    
    <?php for ($i = 0; $i < $thinmints_qty; $i++): ?>
      <img src="thinmints.jpg" alt="Thin Mints" width="100" />
    <?php endfor; ?>
  </div>

  <h2>Total: $<?php echo number_format($total, 2); ?></h2>

  <?php if ($donate): ?>
    <p><strong>Thank you for your donation!</strong></p>
  <?php endif; ?>
</body>
</html>
