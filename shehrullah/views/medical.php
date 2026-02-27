<?php

$action = $_GET['action'] ?? '';
if ($action === 'fetch_person') {
    header('Content-Type: application/json');
    $itsId = $_GET['its_id'] ?? '';
    if (!preg_match('/^\d{8}$/', $itsId)) {
        echo json_encode(['ok' => false, 'message' => 'Invalid ITS ID format.']);
        return;
    }

    $its_data = get_medical_data($itsId) ?? [];

    // Simulate fetching from its_data
    // $data = [
    //     '12345678' => ['full_name' => 'Alice Smith', 'age' => 30, 'height_cm' => 165],
    //     '87654321' => ['full_name' => 'Bob Johnson', 'age' => 45, 'height_cm' => 175],
    // ];

    if (isset($its_data->its_id)) {
        $its_data->height_cm = 175;;
        echo json_encode(['ok' => true, 'data' => $its_data]);
    } else {
        echo json_encode(['ok' => false, 'message' => 'No record found for the provided ITS ID.']);
    }
    die();
}


do_for_post('_handle_form_submission');

function _handle_form_submission() {
    // Here you would handle the form submission, validate inputs, and save to the database.
    // For this example, we'll just return a success message.

    // Validate CSRF token
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
        die('Invalid CSRF token.');
    }

    // You would also validate other inputs here...

    $result = add_medical_data($_POST['its_id'], $_POST['full_name'], $_POST['age'], $_POST['height_cm'], $_POST['weight_kg'], $_POST['bp_systolic'], $_POST['bp_diastolic'], $_POST['random_blood_sugar_mgdl'], $_POST['pledge_reduce_kg'], $_POST['pledge_target_date']);

    // Simulate saving to database...
    if( $result ) {
      $_SESSION['message'] = 'Medical checkup record saved successfully.';
      setAppData('success', true);
      header('Location: medical');
      die();
    } else {
        $_SESSION['message'] = 'Failed to save medical checkup record.';
        setAppData('success', false);
        header('Location: medical');
        die();
    }
    // Redirect back with success message
}

function content_display() {
    $message = $_SESSION['message'] ?? '';
    unset($_SESSION['message']);
    ?>
    

<style>
    body { font-family: system-ui, Arial; margin: 24px; max-width: 900px; }
    .row { display: flex; gap: 16px; flex-wrap: wrap; }
    .field { display: flex; flex-direction: column; margin-bottom: 12px; min-width: 220px; }
    input { padding: 10px; font-size: 14px; }
    .readonly { background: #f4f4f4; }
    .card { border: 1px solid #ddd; padding: 16px; border-radius: 10px; margin-top: 16px; }
    .ok { color: #0a7; }
    .err { color: #c00; }
    button { padding: 10px 14px; font-size: 14px; cursor: pointer; }
    .hint { font-size: 12px; color: #666; }
  </style>

<h2>Medical Checkup Form</h2>

<?php if ( isset($message) ): //$success): ?>
  <p class="ok"><strong><?= htmlspecialchars($message) ?></strong></p>
<?php endif; ?>

<div class="card">
  <div class="field" style="max-width:320px;">
    <label>ITS ID (8 digits)</label>
    <input id="its_id" type="text" inputmode="numeric" maxlength="8" placeholder="e.g., 12345678">
    <div class="hint">Type ITS ID and click "Fetch".</div>
    <button type="button" id="fetchBtn" style="margin-top:10px;">Fetch</button>
    <div id="fetchMsg" class="hint"></div>
  </div>
</div>

<form class="card" method="post" action="" id="checkupForm" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']??'', ENT_QUOTES) ?>">
  <input type="hidden" name="its_id" id="its_id_hidden">

  <h3>Person Details</h3>
  <div class="row">
    <div class="field">
      <label>Full Name</label>
      <input type="text" id="full_name" name="full_name" required>
    </div>
    <div class="field">
      <label>Age</label>
      <input type="text" id="age" name="age" required>
    </div>
  </div>

  <h3>Medical Inputs</h3>
  <div class="row">
    <div class="field">
      <label>Blood Pressure - Systolic</label>
      <input name="bp_systolic" id="bp_systolic" type="number" min="50" max="300" required>
    </div>
    <div class="field">
      <label>Blood Pressure - Diastolic</label>
      <input name="bp_diastolic" id="bp_diastolic" type="number" min="30" max="200" required>
    </div>
    <div class="field">
      <label>Random Blood Sugar (mg/dL)</label>
      <input name="random_blood_sugar_mgdl" id="random_blood_sugar_mgdl" type="number" min="10" max="1000" required>
    </div>
    <div class="field">
      <label>Height (cm)</label>
      <input type="text" id="height_cm" name="height_cm" type="number" step="0" min="0" max="1000" required>
    </div>
    <div class="field">
      <label>Weight (kg)</label>
      <input name="weight_kg" id="weight_kg" type="number" step="0.01" min="1" max="500" required>
    </div>
  </div>

  <h3>Calculated</h3>
  <div class="row">
    <div class="field">
      <label>BMI</label>
      <input type="text" id="bmi" class="readonly" readonly>
    </div>
    <div class="field">
      <label>Ideal Weight (kg) (BMI 24.9)</label>
      <input type="text" id="ideal_weight" class="readonly" readonly>
    </div>
    <div class="field">
      <label>Overweight (kg)</label>
      <input type="text" id="overweight" class="readonly" readonly>
    </div>
  </div>

  <h3>Pledge</h3>
  <div class="row">
    <div class="field">
      <label>How much to reduce (kg)</label>
      <input name="pledge_reduce_kg" id="pledge_reduce_kg" type="number" step="0.01" min="0" max="200">
    </div>
    <div class="field">
      <label>Date to complete target</label>
      <input name="pledge_target_date" id="pledge_target_date" type="date">
    </div>
  </div>

  <button type="submit" id="submitBtn" disabled>Submit</button>
  <span id="submitHint" class="hint">Fetch person details first.</span>
</form>

<script>
//$(document).ready(function () {
  function the_script() {          

  const itsIdInput = document.getElementById('its_id');
  const fetchBtn = document.getElementById('fetchBtn');
  const fetchMsg = document.getElementById('fetchMsg');

  const fullName = document.getElementById('full_name');
  const age = document.getElementById('age');
  const heightCm = document.getElementById('height_cm');
  const itsHidden = document.getElementById('its_id_hidden');

  const weightKg = document.getElementById('weight_kg');
  const bmiEl = document.getElementById('bmi');
  const idealEl = document.getElementById('ideal_weight');
  const overEl = document.getElementById('overweight');

  const bpSystolic = document.getElementById('bp_systolic');
  const bpDiastolic = document.getElementById('bp_diastolic');
  const randomBloodSugar = document.getElementById('random_blood_sugar_mgdl');
  const pledgeReduceKg = document.getElementById('pledge_reduce_kg');
  const pledgeTargetDate = document.getElementById('pledge_target_date');

  const submitBtn = document.getElementById('submitBtn');
  const submitHint = document.getElementById('submitHint');

  function calc() {
    const h = parseFloat(heightCm.value);
    const w = parseFloat(weightKg.value);

    if (!h || !w) {
      bmiEl.value = idealEl.value = overEl.value = '';
      return;
    }
    const hm = h / 100.0;
    const bmi = w / (hm * hm);
    const ideal = 24.9 * (hm * hm);
    const overweight = Math.max(0, w - ideal);

    bmiEl.value = bmi.toFixed(2);
    idealEl.value = ideal.toFixed(2);
    overEl.value = overweight.toFixed(2);
  }

  weightKg.addEventListener('input', calc);

  fetchBtn.addEventListener('click', async () => {
    const its = itsIdInput.value.trim();
    fetchMsg.textContent = '';
    fetchMsg.className = 'hint';

    if (!/^\d{8}$/.test(its)) {
      fetchMsg.textContent = 'ITS ID must be exactly 8 digits.';
      fetchMsg.className = 'err';
      return;
    }

    fetchMsg.textContent = 'Fetching...';

    try {
      const res = await fetch(`medical?action=fetch_person&its_id=${encodeURIComponent(its)}`);
      const json = await res.json();

      itsHidden.value = its;
      if (!json.ok) {
        fetchMsg.textContent = json.message || 'Not found.';
        fetchMsg.className = 'err';
        fullName.value = age.value = '';
        //submitBtn.disabled = true;
        //submitHint.textContent = 'Fetch person details first.';
        //return;
      } else {
        fullName.value = json.data.full_name;
        age.value = json.data.age;
        heightCm.value = json.data.height_cm;
        weightKg.value = json.data.weight_kg;
        itsHidden.value = json.data.its_id;
        bpSystolic.value = json.data.bp_systolic;
        bpDiastolic.value = json.data.bp_diastolic;
        randomBloodSugar.value = json.data.random_blood_sugar_mgdl;
        pledgeReduceKg.value = json.data.pledge_reduce_kg;
        pledgeTargetDate.value = json.data.pledge_target_date ? json.data.pledge_target_date.split(' ')[0] : '';
      }

      

      fetchMsg.textContent = 'Record loaded.';
      fetchMsg.className = 'ok';

      submitBtn.disabled = false;
      submitHint.textContent = '';
      calc();
    } catch (e) {
      fetchMsg.textContent = 'Error fetching details.';
      fetchMsg.className = 'err';
    }
  });


  //the_script();
        };
</script>

    <?php
}