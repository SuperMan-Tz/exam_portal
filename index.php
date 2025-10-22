<?php
// Simple API endpoint to persist the current dataset to data.json
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save') {
  header('Content-Type: application/json');
  $raw = file_get_contents('php://input');
  $payload = json_decode($raw, true);
  if (!is_array($payload) || !isset($payload['verifications']) || !isset($payload['reports'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
    exit;
  }
  $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to encode JSON']);
    exit;
  }
  $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data.json';
  $result = @file_put_contents($filePath, $json, LOCK_EX);
  if ($result === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to write data.json']);
    exit;
  }
  echo json_encode(['ok' => true]);
  exit;
}

// Note: URL parameter handling is done in JavaScript, not PHP redirects
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Exam ID Verification Portal</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      rel="stylesheet"
    />
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI",
          Roboto, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
      }

      body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23ffffff" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="%23ffffff" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="%23ffffff" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        pointer-events: none;
      }

      .container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        padding: 40px;
        width: 100%;
        max-width: 480px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
      }

      .container:hover {
        transform: translateY(-2px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
      }

      .header {
        margin-bottom: 30px;
      }

      .header h1 {
        color: #2d3748;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }

      .header p {
        color: #718096;
        font-size: 16px;
        line-height: 1.5;
      }

      .input-group {
        position: relative;
        margin-bottom: 25px;
      }

      .input-group i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
        font-size: 18px;
      }

      input[type="text"] {
        width: 100%;
        padding: 18px 18px 18px 50px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #f7fafc;
      }

      input[type="text"]:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      .button-group {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
      }

      button {
        flex: 1;
        padding: 16px 24px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
      }

      .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
      }

      .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
      }

      .btn-secondary {
        background: #f56565;
        color: white;
      }

      .btn-secondary:hover {
        background: #e53e3e;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(245, 101, 101, 0.3);
      }

      .btn-tertiary {
        background: #e2e8f0;
        color: #2d3748;
      }

      .btn-tertiary:hover {
        background: #cbd5e0;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(160, 174, 192, 0.3);
      }

      .student-photo {
        margin-top: 30px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;
      }

      .student-photo.show {
        opacity: 1;
        transform: translateY(0);
      }

      .photo-container {
        background: #f7fafc;
        border-radius: 16px;
        padding: 20px;
        border: 2px dashed #cbd5e0;
        margin-bottom: 15px;
      }

      .student-photo img {
        max-width: 100%;
        height: auto;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
      }

      .student-photo img:hover {
        transform: scale(1.02);
      }

      .photo-header {
        background: linear-gradient(135deg, #4299e1, #3182ce);
        color: white;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-weight: 600;
      }

      .footer-info {
        background: #edf2f7;
        padding: 20px;
        border-radius: 12px;
        margin: 30px 0;
      }

      .footer-info p {
        color: #4a5568;
        font-size: 14px;
        line-height: 1.6;
        margin: 0;
      }

      .credit {
        padding: 20px;
        background: linear-gradient(135deg, #f7fafc, #edf2f7);
        border-radius: 12px;
        border-left: 4px solid #667eea;
      }

      .credit p {
        color: #2d3748;
        font-size: 14px;
        margin-bottom: 8px;
      }

      .credit strong {
        color: #667eea;
      }

      .credit a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
      }

      .credit a:hover {
        color: #764ba2;
        text-decoration: underline;
      }

      /* Loading Animation */
      .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s linear infinite;
      }

      @keyframes spin {
        to {
          transform: rotate(360deg);
        }
      }

      /* Error/Success Messages */
      .message {
        padding: 12px 16px;
        border-radius: 8px;
        margin: 15px 0;
        font-weight: 500;
        display: none;
      }

      .message.error {
        background: #fed7d7;
        color: #c53030;
        border: 1px solid #feb2b2;
      }

      .message.success {
        background: #c6f6d5;
        color: #276749;
        border: 1px solid #9ae6b4;
      }

      /* Report Modal */
      .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
      }

      .modal-content {
        background: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        position: relative;
        animation: slideIn 0.3s ease;
        max-height: 85vh;
        overflow: auto;
      }

      @keyframes slideIn {
        from {
          opacity: 0;
          transform: translateY(-30px);
        }

        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #edf2f7;
      }

      .modal-header h2 {
        color: #2d3748;
        font-size: 1.5rem;
      }

      .close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #a0aec0;
        padding: 0;
        width: auto;
        height: auto;
      }

      .close:hover {
        color: #2d3748;
      }

      .form-group {
        margin-bottom: 20px;
        text-align: left;
      }

      .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #4a5568;
        font-weight: 500;
      }

      .form-group select,
      .form-group textarea,
      .form-group input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s ease;
      }

      .form-group select:focus,
      .form-group textarea:focus,
      .form-group input:focus {
        outline: none;
        border-color: #667eea;
      }

      .form-group textarea {
        resize: vertical;
        min-height: 120px;
      }

      @media (max-width: 768px) {
        .container {
          padding: 30px 20px;
          margin: 10px;
        }

        .header h1 {
          font-size: 1.75rem;
        }

        .button-group {
          flex-direction: column;
        }


        .modal-content {
          margin: 10% auto;
          padding: 20px;
        }
      }

      @media (max-width: 480px) {
        .container {
          padding: 20px 15px;
        }

        .header h1 {
          font-size: 1.5rem;
        }

        input[type="text"] {
          font-size: 14px;
          padding: 16px 16px 16px 45px;
        }

        button {
          padding: 14px 20px;
          font-size: 14px;
        }
      }
      /* Tracking Modal extras */
      .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
      }

      .stat-box {
        background: linear-gradient(135deg, #f7fafc, #edf2f7);
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
      }

      .stat-box h3 {
        margin-bottom: 4px;
        color: #2d3748;
      }

      .modal-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0 20px;
      }

      .modal-content th,
      .modal-content td {
        border: 1px solid #e2e8f0;
        padding: 8px;
        font-size: 14px;
        color: #2d3748;
      }

      .modal-content thead th {
        position: sticky;
        top: 0;
        background: #edf2f7;
        font-weight: 600;
        z-index: 1;
      }

      .modal-content tbody tr:nth-child(odd) {
        background: #f9fafb;
      }

      .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
      }

      .badge-success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
      .badge-failed { background: #fed7d7; color: #822727; border: 1px solid #feb2b2; }

      .tracking-toolbar {
        display: flex;
        gap: 12px;
        align-items: center;
        margin: 8px 0 12px;
        flex-wrap: wrap;
      }

      .tracking-toolbar input[type="text"] {
        flex: 1;
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        background: #f7fafc;
      }
    </style>
  </head>

  <body>
    <div class="container">
      <div class="header">
        <h1><i class="fas fa-shield-alt"></i> Exam ID Verification</h1>
        <p>
          Enter the student's registration number to verify photo identity
          during examinations
        </p>
      </div>

      <div class="input-group">
        <i class="fas fa-id-card"></i>
        <input
          type="text"
          id="regNumber"
          placeholder="e.g. 03.3632.01.01.2021"
        />
      </div>

      <div class="message" id="message"></div>

      <div class="button-group">
        <button class="btn-primary" onclick="loadImage()">
          <i class="fas fa-search"></i>
          <span>Verify ID</span>
        </button>
        <button class="btn-secondary" onclick="openReportModal()">
          <i class="fas fa-exclamation-triangle"></i>
          <span>Report Issue</span>
        </button>
        <button class="btn-tertiary" onclick="openTrackingModal()">
          <i class="fas fa-database"></i>
          <span>View Tracking</span>
        </button>
      </div>

      <div class="student-photo" id="imageContainer"></div>

      <div class="footer-info">
        <p>
          <i class="fas fa-info-circle"></i> This system helps invigilators
          verify students' exam identity during examinations. Ensure the ID
          matches the system profile photo.
        </p>
      </div>

      <div class="credit">
        <p>Made with ❤️ by <strong>Samwel Peter Mmari 😉</strong></p>
        <p>
          <i class="fas fa-envelope"></i> Email:
          <a href="mailto:spom.mmari@gmail.com">spom.mmari@gmail.com</a><br />
          <i class="fab fa-whatsapp"></i> WhatsApp:
          <a href="https://wa.me/255784629597" target="_blank"
            >+255 784 629 597</a
          >
        </p>
      </div>
    </div>


    <!-- Report Modal -->
    <div id="reportModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2><i class="fas fa-bug"></i> Report Issue</h2>
          <button class="close" onclick="closeReportModal()">&times;</button>
        </div>
        <form id="reportForm">
          <div class="form-group">
            <label for="issueType">Issue Type</label>
            <select id="issueType" required onchange="updateFormFields()">
              <option value="">Select issue type</option>
              <option value="cheating_outsider">
                Cheating - Fake ID (Outsider)
              </option>
              <option value="cheating_student">
                Cheating - Fake ID (Our Student)
              </option>
              <option value="cheating_other">Cheating - Other Methods</option>
              <option value="wrong_image">Wrong Student Image</option>
              <option value="image_not_found">Image Not Found</option>
              <option value="technical_issue">Technical Issue</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div id="dynamicFields"></div>

          <div class="form-group">
            <label for="description">Generated Report</label>
            <textarea
              id="description"
              placeholder="Report will appear here..."
              required
            ></textarea>
            <small style="color: #718096"
              >You can adjust this text before submitting.</small
            >
          </div>
          <div class="form-group">
            <label for="invigilatorName">Invigilator Name (optional)</label>
            <input type="text" id="invigilatorName" placeholder="e.g. J. Doe">
          </div>

          <button type="submit" class="btn-primary" style="width: 100%">
            <i class="fas fa-paper-plane"></i> Submit Report
          </button>
        </form>
      </div>
    </div>

    <!-- Tracking Modal -->
    <div id="trackingModal" class="modal" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <h2><i class="fas fa-database"></i> Verification & Reports</h2>
          <button class="close" onclick="closeTrackingModal()">&times;</button>
        </div>
        <div class="stats">
          <div class="stat-box">
            <h3 id="countTotal">0</h3>
            <p>Total Verifications</p>
          </div>
          <div class="stat-box">
            <h3 id="countSuccess">0</h3>
            <p>Successful</p>
          </div>
          <div class="stat-box">
            <h3 id="countFailed">0</h3>
            <p>Failed</p>
          </div>
        </div>
        <div class="tracking-toolbar">
          <input type="text" id="searchInput" placeholder="Search by Reg or Description" oninput="renderTrackingData()">
          <button class="btn-tertiary" onclick="clearTrackingFilters()"><i class="fas fa-eraser"></i> Clear</button>
          <button class="btn-primary" onclick="exportTrackingCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
          <button class="btn-tertiary" onclick="filterToday()"><i class="fas fa-calendar-day"></i> Today</button>
          <button class="btn-secondary" onclick="printCurrentView()"><i class="fas fa-print"></i> Print</button>
        </div>
        <div class="form-group" style="display:flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <label style="flex:1; min-width: 160px;">From
            <input type="date" id="filterFrom" onchange="renderTrackingData()">
          </label>
          <label style="flex:1; min-width: 160px;">To
            <input type="date" id="filterTo" onchange="renderTrackingData()">
          </label>
          <label style="flex:1; min-width: 160px;">Session
            <select id="filterSession" onchange="renderTrackingData()">
              <option value="">All</option>
              <option value="morning">Morning</option>
              <option value="noon">Noon</option>
              <option value="evening">Evening</option>
              <option value="other">Other</option>
            </select>
          </label>
        </div>
        <h3 style="margin-top:8px;">Verifications</h3>
        <table>
          <thead>
            <tr>
              <th>Reg</th>
              <th>Status</th>
              <th>Session</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody id="verificationTable"></tbody>
        </table>
        <h3>Reports</h3>
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Reg</th>
              <th>Description</th>
              <th>Reported</th>
            </tr>
          </thead>
          <tbody id="reportTable"></tbody>
        </table>
      </div>
    </div>

    <!-- WhatsApp Float Button -->
     <?php include 'whatsapp-float.html'; ?>

    <script src="data.js"></script>
    <script>
      // Seed local storage from data.json if empty
      (function seedFromJson() {
        try {
          const existing = localStorage.getItem("examData");
          if (!existing) {
            fetch("data.json")
              .then((r) => (r.ok ? r.json() : null))
              .then((json) => {
                if (json && json.verifications && json.reports) {
                  localStorage.setItem("examData", JSON.stringify(json));
                }
              })
              .catch(() => {});
          }
        } catch (e) {}
      })();

      function loadImage() {
        const regNum = document.getElementById("regNumber").value.trim();
        const button = document.querySelector(".btn-primary span");
        const icon = document.querySelector(".btn-primary i");

        if (!regNum) {
          showMessage("Please enter a valid registration number.", "error");
          return;
        }

        button.textContent = "Verifying...";
        icon.className = "loading";

        const imageName = regNum.replace(/\./g, "_") + ".jpg";
        const imageUrl =
          "https://cosis.cbe.ac.tz/media/profiles/student/" + imageName;

        const img = new Image();

        img.onload = function () {
          document.getElementById("imageContainer").innerHTML = `
          <div class="photo-header">
            <i class="fas fa-user-check"></i> Student Profile Image
          </div>
          <div class="photo-container">
            <img src="${imageUrl}" alt="Student Image for ${regNum}">
          </div>
          <p style="color: #4a5568; font-size: 14px; margin-top: 10px;">
            <i class="fas fa-check-circle" style="color: #48bb78;"></i> 
            Registration: <strong>${regNum}</strong>
          </p>
        `;
          document.getElementById("imageContainer").classList.add("show");
          showMessage("Student image loaded successfully!", "success");
          try { addVerification(regNum, "success", ""); } catch (e) {}
          setTimeout(() => {
            button.textContent = "Verify ID";
            icon.className = "fas fa-search";
          }, 1000);
        };

        img.onerror = function () {
          document.getElementById("imageContainer").innerHTML = `
          <div class="photo-container" style="border-color: #f56565; background: #fed7d7;">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #c53030; margin: 20px 0;"></i>
            <h3 style="color: #c53030; margin-bottom: 10px;">Image Not Found</h3>
            <p style="color: #742a2a;">No profile image found for registration number: <strong>${regNum}</strong></p>
          </div>
        `;
          document.getElementById("imageContainer").classList.add("show");
          showMessage("Image not found for this registration number.", "error");
          try { addVerification(regNum, "failed", ""); } catch (e) {}
          button.textContent = "Verify ID";
          icon.className = "fas fa-search";
        };

        img.src = imageUrl;
      }

      function showMessage(text, type) {
        const messageEl = document.getElementById("message");
        messageEl.textContent = text;
        messageEl.className = `message ${type}`;
        messageEl.style.display = "block";
        setTimeout(() => {
          messageEl.style.display = "none";
        }, 5000);
      }

      function openReportModal() {
        const modal = document.getElementById("reportModal");
        modal.style.display = "block";
        document.body.style.overflow = "hidden";

        // Get reg number from the main input
        const mainRegNum = document.getElementById("regNumber").value.trim();

        // Wait for dynamic fields to load, then pre-fill if available
        setTimeout(() => {
          const regInput = document.getElementById("regNumberReport");
          if (regInput) {
            regInput.value = mainRegNum || ""; // auto-fill if available, else blank
            generateReport(); // regenerate report with this reg
          }
        }, 200);
      }

      function closeReportModal() {
        const modal = document.getElementById("reportModal");
        modal.style.display = "none";
        document.body.style.overflow = "auto";
        document.getElementById("reportForm").reset();
        document.getElementById("dynamicFields").innerHTML = "";
        document.getElementById("description").value = "";
      }

      function updateFormFields() {
        const issueType = document.getElementById("issueType").value;
        const dynamicFields = document.getElementById("dynamicFields");
        dynamicFields.innerHTML = "";
        let extraFields = "";

        if (issueType.startsWith("cheating")) {
          extraFields += `
          <div class="form-group">
            <label for="examName">Exam Name</label>
            <input type="text" id="examName" placeholder="e.g. Database Management Exam">
          </div>
          <div class="form-group">
            <label for="regNumberReport">Student Registration Number</label>
            <input type="text" id="regNumberReport" placeholder="e.g. 03.3632.01.01.2021">
          </div>
          <div class="form-group">
            <label for="incidentTime">Time of Incident</label>
            <input type="time" id="incidentTime">
          </div>
        `;
          if (issueType === "cheating_student") {
            extraFields += `
            <div class="form-group">
              <label for="secondStudentId">Second Student Registration Number</label>
              <input type="text" id="secondStudentId" placeholder="Reg. No. of impersonated student">
            </div>`;
          }
          if (issueType === "cheating_other") {
            extraFields += `
            <div class="form-group">
              <label for="cheatingMethod">Cheating Method</label>
              <input type="text" id="cheatingMethod" placeholder="e.g. Unauthorized materials">
            </div>`;
          }
        } else {
          extraFields += `
          <div class="form-group">
            <label for="examName">Exam Name</label>
            <input type="text" id="examName" placeholder="e.g. Database Management Exam">
          </div>
          <div class="form-group">
            <label for="regNumberReport">Student Registration Number</label>
            <input type="text" id="regNumberReport" placeholder="e.g. 03.3632.01.01.2021">
          </div>
        `;
          if (issueType === "technical_issue") {
            extraFields += `
            <div class="form-group">
              <label for="errorDetails">Error Details</label>
              <input type="text" id="errorDetails" placeholder="Describe the error message or behavior">
            </div>`;
          }
          if (issueType === "other") {
            extraFields += `
            <div class="form-group">
              <label for="customIssue">Custom Issue</label>
              <input type="text" id="customIssue" placeholder="Describe the issue">
            </div>`;
          }
        }
        dynamicFields.innerHTML = extraFields;
        generateReport();
      }

      function generateReport() {
        const type = document.getElementById("issueType").value;
        const date = new Date().toLocaleDateString();
        const time =
          document.getElementById("incidentTime")?.value ||
          new Date().toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
          });
        const exam =
          document.getElementById("examName")?.value || "[EXAM NAME]";
        const reg =
          document.getElementById("regNumberReport")?.value || "[REG_NUMBER]";
        const second =
          document.getElementById("secondStudentId")?.value ||
          "[SECOND_STUDENT_ID]";
        const method =
          document.getElementById("cheatingMethod")?.value || "[METHOD]";
        const errorDetails =
          document.getElementById("errorDetails")?.value || "[ERROR_DETAILS]";
        const customIssue =
          document.getElementById("customIssue")?.value ||
          "[CUSTOM_ISSUE_DESCRIPTION]";
        const invigilator = (document.getElementById("invigilatorName")?.value || "").trim();

        let report = "";
        switch (type) {
          case "cheating_outsider":
            report = `🚨 Cheating Incident Report 🚨\nOn ${date}, at ${time}, during the ${exam}, an outsider candidate attempted to impersonate student with Registration Number ${reg} using a fake ID card.\nThe invigilator confirmed that the displayed profile photo did not match the individual present.\nThis incident is suspected academic fraud and requires immediate disciplinary action.`;
            break;
          case "cheating_student":
            report = `🚨 Cheating Incident Report 🚨\nOn ${date}, at ${time}, during the ${exam}, student with Registration Number ${reg} was found impersonating another student with Registration Number ${second} using a fake ID card.\nThe invigilator confirmed that the displayed profile photo did not match the individual present.\nThis constitutes a case of academic dishonesty and collusion requiring immediate disciplinary measures.`;
            break;
          case "cheating_other":
            report = `🚨 Cheating Incident Report 🚨\nOn ${date}, at ${time}, during the ${exam}, student with Registration Number ${reg} was caught engaging in ${method}.\nThe invigilator documented the evidence and confirmed violation of examination regulations.\nThis incident is classified as academic misconduct and must be addressed according to institutional policies.`;
            break;
          case "wrong_image":
            report = `⚠️ Image Verification Issue Report ⚠️\nOn ${date}, during the ${exam}, the system displayed an image for student with Registration Number ${reg} that did not match the official student identity.\nThis may indicate a data mismatch or incorrect linking of student records.\nVerification could not be completed accurately and requires administrative review.`;
            break;
          case "image_not_found":
            report = `⚠️ Image Verification Issue Report ⚠️\nOn ${date}, during the ${exam}, no profile photo was available in the system for student with Registration Number ${reg}.\nThis prevented proper identity verification and requires corrective action from the administration.`;
            break;
          case "technical_issue":
            report = `⚙️ System Technical Report ⚙️\nOn ${date}, during the ${exam}, a technical issue occurred while verifying student with Registration Number ${reg}.\nError details: ${errorDetails}.\nThis issue disrupted the verification process and requires urgent technical support.`;
            break;
          case "other":
            report = `ℹ️ General Report ℹ️\nOn ${date}, during the ${exam}, an issue was observed regarding student with Registration Number ${reg}.\nDetails: ${customIssue}.\nFurther clarification and action may be required by the relevant authorities.`;
            break;
        }
        const withInvigilator = invigilator ? `${report}\n\nReported by: ${invigilator}` : report;
        document.getElementById("description").value = withInvigilator;
      }

      document.addEventListener("input", function (e) {
        if (e.target.closest("#dynamicFields")) {
          generateReport();
        }
      });

      document
        .getElementById("reportForm")
        .addEventListener("submit", function (e) {
          e.preventDefault();
          const issueType = document.getElementById("issueType").value;
          const regNum =
            document.getElementById("regNumberReport")?.value || "[UNKNOWN]";
          const description = document.getElementById("description").value;
          const invigilator = (document.getElementById("invigilatorName")?.value || "").trim();
          const examName = document.getElementById("examName")?.value || "";

          try { addReport(issueType, regNum, examName, description, invigilator); } catch (e) {}

          const message =
            `🚨 EXAM VERIFICATION ISSUE REPORT 🚨%0A` +
            `📋 Issue Type: ${issueType.replace(/_/g, " ").toUpperCase()}%0A` +
            `🆔 Student ID: ${regNum}%0A` +
            `📝 Description: ${encodeURIComponent(description)}%0A` +
            (invigilator ? `👤 Invigilator: ${encodeURIComponent(invigilator)}%0A` : "") +
            `⏰ Reported at: ${new Date().toLocaleString()}`;

          const whatsappUrl = `https://wa.me/255784629597?text=${message}`;
          window.open(whatsappUrl, "_blank");
          closeReportModal();
          showMessage(
            "Report submitted! You'll be redirected to WhatsApp.",
            "success"
          );
        });

      // Close modal when clicking outside
      window.addEventListener("click", function (e) {
        const reportModal = document.getElementById("reportModal");
        if (e.target === reportModal) closeReportModal();
        const trackingModal = document.getElementById("trackingModal");
        if (e.target === trackingModal) closeTrackingModal();
      });

      // Enter key triggers verify
      document
        .getElementById("regNumber")
        .addEventListener("keypress", function (e) {
          if (e.key === "Enter") {
            loadImage();
          }
        });

      // Page load animation
      document.addEventListener("DOMContentLoaded", function () {
        const container = document.querySelector(".container");
        container.style.opacity = "0";
        container.style.transform = "translateY(20px)";
        setTimeout(() => {
          container.style.transition = "all 0.6s ease";
          container.style.opacity = "1";
          container.style.transform = "translateY(0)";
        }, 100);
        
        // Check for URL parameters and auto-load student details
        checkUrlParameters();
      });

      // Function to check URL parameters and auto-load student details
      function checkUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const studentId = urlParams.get('student_id');
        
        if (studentId) {
          // Set the registration number in the input field
          document.getElementById('regNumber').value = studentId;
          
          // Automatically load the student image
          setTimeout(() => {
            loadImage();
          }, 500); // Small delay to ensure the input is set
        }
      }

      // Tracking modal control
      function openTrackingModal() {
        const modal = document.getElementById("trackingModal");
        modal.style.display = "block";
        document.body.style.overflow = "hidden";
        renderTrackingData();
        updateLinkedStatus();
      }

      function closeTrackingModal() {
        const modal = document.getElementById("trackingModal");
        modal.style.display = "none";
        document.body.style.overflow = "auto";
      }

      function renderTrackingData() {
        let data;
        try { data = loadData(); } catch (e) { data = { verifications: [], reports: [] }; }
        const fromDate = document.getElementById("filterFrom").value;
        const toDate = document.getElementById("filterTo").value;
        const sessionFilter = document.getElementById("filterSession").value;
        const q = (document.getElementById("searchInput")?.value || "").toLowerCase();

        const inRange = (iso) => {
          const d = new Date(iso);
          const day = new Date(d.getFullYear(), d.getMonth(), d.getDate());
          let ok = true;
          if (fromDate) {
            const f = new Date(fromDate + 'T00:00:00');
            ok = ok && day >= new Date(f.getFullYear(), f.getMonth(), f.getDate());
          }
          if (toDate) {
            const t = new Date(toDate + 'T00:00:00');
            ok = ok && day <= new Date(t.getFullYear(), t.getMonth(), t.getDate());
          }
          return ok;
        };

        const verifs = data.verifications.filter((v) => {
          const matchesDate = inRange(v.timestamp);
          const matchesSession = !sessionFilter || v.exam_session === sessionFilter;
          const matchesQuery = !q ||
            (v.reg_number || "").toLowerCase().includes(q) ||
            (v.status || "").toLowerCase().includes(q);
          return matchesDate && matchesSession && matchesQuery;
        });

        const total = verifs.length;
        const success = verifs.filter((v) => v.status === "success").length;
        const failed = total - success;

        document.getElementById("countTotal").textContent = String(total);
        document.getElementById("countSuccess").textContent = String(success);
        document.getElementById("countFailed").textContent = String(failed);

        const vTbody = document.getElementById("verificationTable");
        vTbody.innerHTML = verifs
          .map((v) => {
            const badgeClass = v.status === "success" ? "badge-success" : "badge-failed";
            return `<tr><td>${v.reg_number}</td><td><span class="badge ${badgeClass}">${v.status}</span></td><td>${v.exam_session}</td><td>${new Date(v.timestamp).toLocaleString()}</td></tr>`;
          })
          .join("");

        const reports = data.reports.filter((r) => {
          const matchesDate = inRange(r.reported_at);
          const matchesQuery = !q ||
            (r.reg_number || "").toLowerCase().includes(q) ||
            (r.description || "").toLowerCase().includes(q) ||
            (r.issue_type || "").toLowerCase().includes(q);
          return matchesDate && matchesQuery;
        });
        const rTbody = document.getElementById("reportTable");
        rTbody.innerHTML = reports
          .map(
            (r) =>
              `<tr><td>${r.issue_type}</td><td>${r.reg_number}</td><td>${escapeHtml(r.description)}</td><td>${new Date(r.reported_at).toLocaleString()}</td></tr>`
          )
          .join("");
      }

      function filterToday() {
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        const d = String(today.getDate()).padStart(2, '0');
        const iso = `${y}-${m}-${d}`;
        const from = document.getElementById('filterFrom');
        const to = document.getElementById('filterTo');
        if (from) from.value = iso;
        if (to) to.value = iso;
        renderTrackingData();
      }

      function printCurrentView() {
        let data;
        try { data = loadData(); } catch (e) { data = { verifications: [], reports: [] }; }
        const fromDate = document.getElementById("filterFrom").value;
        const toDate = document.getElementById("filterTo").value;
        const sessionFilter = document.getElementById("filterSession").value;
        const q = (document.getElementById("searchInput")?.value || "").toLowerCase();

        const inRange = (iso) => {
          const d = new Date(iso);
          const day = new Date(d.getFullYear(), d.getMonth(), d.getDate());
          let ok = true;
          if (fromDate) {
            const f = new Date(fromDate + 'T00:00:00');
            ok = ok && day >= new Date(f.getFullYear(), f.getMonth(), f.getDate());
          }
          if (toDate) {
            const t = new Date(toDate + 'T00:00:00');
            ok = ok && day <= new Date(t.getFullYear(), t.getMonth(), t.getDate());
          }
          return ok;
        };

        const verifs = data.verifications.filter((v) => {
          const matchesDate = inRange(v.timestamp);
          const matchesSession = !sessionFilter || v.exam_session === sessionFilter;
          const matchesQuery = !q ||
            (v.reg_number || "").toLowerCase().includes(q) ||
            (v.status || "").toLowerCase().includes(q);
          return matchesDate && matchesSession && matchesQuery;
        });
        const reports = data.reports.filter((r) => {
          const matchesDate = inRange(r.reported_at);
          const matchesQuery = !q ||
            (r.reg_number || "").toLowerCase().includes(q) ||
            (r.description || "").toLowerCase().includes(q) ||
            (r.issue_type || "").toLowerCase().includes(q);
          return matchesDate && matchesQuery;
        });

        const w = window.open('', '_blank');
        const dateRangeText = (fromDate || toDate) ? `Date: ${fromDate || '...'} to ${toDate || '...'}` : 'Date: All';
        const sessionText = sessionFilter ? ` | Session: ${sessionFilter}` : '';
        const heading = `Verification & Reports ${dateRangeText}${sessionText}`;
        const style = `
          <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .banner { display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 2px solid #ddd; }
            .banner img { height: 80px; }
            .center-text { text-align: center; flex: 1; color: #001f66; }
            .center-text h1 { font-size: 20px; margin: 4px 0; font-weight: bold; }
            .center-text h2 { font-size: 18px; margin: 4px 0; font-weight: bold; }
            .center-text p { font-size: 14px; margin: 2px 0; font-weight: bold; }
            h3 { margin: 18px 0 8px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 8px; font-size: 12px; }
            thead th { background: #f0f0f0; }
            .meta { margin-top: 8px; color: #333; font-size: 12px; }
            @media print { .no-print { display: none; } }
          </style>`;
        const verifRows = verifs.map(v => `<tr><td>${v.reg_number}</td><td>${v.status}</td><td>${v.exam_session}</td><td>${new Date(v.timestamp).toLocaleString()}</td></tr>`).join('');
        const reportRows = reports.map(r => `<tr><td>${r.issue_type}</td><td>${r.reg_number}</td><td>${escapeHtml(r.description)}</td><td>${new Date(r.reported_at).toLocaleString()}</td></tr>`).join('');
        w.document.write(`<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>${heading}</title>${style}</head><body>
          <div class=\"banner\">\n            <img src=\"https://examportal.ct.ws/emblem2.png\" alt=\"College Logo\">\n            <div class=\"center-text\">\n              <h1>COLLEGE OF BUSINESS EDUCATION (CBE)</h1>\n              <h2>Examination Verification Report</h2>\n              <p>DAR ES SALAAM | DODOMA | MWANZA | MBEYA</p>\n            </div>\n            <img src=\"https://examportal.ct.ws/cbe_logo.png\" alt=\"National Symbol\">\n          </div>
          <div class=\"meta\">${dateRangeText}${sessionText}</div>
          <h3>Verifications (${verifs.length})</h3>
          <table><thead><tr><th>Reg</th><th>Status</th><th>Session</th><th>Timestamp</th></tr></thead><tbody>${verifRows}</tbody></table>
          <h3>Reports (${reports.length})</h3>
          <table><thead><tr><th>Type</th><th>Reg</th><th>Description</th><th>Reported</th></tr></thead><tbody>${reportRows}</tbody></table>
          <div class=\"no-print\" style=\"margin-top:16px;\"><button onclick=\"window.print()\">Print</button></div>
        </body></html>`);
        w.document.close();
        w.focus();
      }

      function clearTrackingFilters() {
        const from = document.getElementById("filterFrom");
        const to = document.getElementById("filterTo");
        const s = document.getElementById("filterSession");
        const q = document.getElementById("searchInput");
        if (from) from.value = "";
        if (to) to.value = "";
        if (s) s.value = "";
        if (q) q.value = "";
        renderTrackingData();
      }

      function exportTrackingCSV() {
        let data;
        try { data = loadData(); } catch (e) { data = { verifications: [], reports: [] }; }
        const lines = [];
        lines.push(["Section","Reg","Status/Type","Session","Timestamp","Exam","Description"].join(","));
        data.verifications.forEach(v => {
          lines.push(["verification", safeCsv(v.reg_number), safeCsv(v.status), safeCsv(v.exam_session), safeCsv(v.timestamp), safeCsv(v.exam_name || ""), ""].join(","));
        });
        data.reports.forEach(r => {
          lines.push(["report", safeCsv(r.reg_number), safeCsv(r.issue_type), "", safeCsv(r.reported_at), safeCsv(r.exam_name || ""), safeCsv(r.description)].join(","));
        });
        const blob = new Blob(["\uFEFF" + lines.join("\n")], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `exam-tracking-${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }

      function safeCsv(value) {
        const s = (value == null ? "" : String(value));
        if (/[",\n]/.test(s)) {
          return '"' + s.replace(/"/g, '""') + '"';
        }
        return s;
      }

      function escapeHtml(str) {
        try {
          return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        } catch (e) {
          return "";
        }
      }

      // Removed data.json linking and manual download controls per requirements
    </script>
  </body>
  </html>


