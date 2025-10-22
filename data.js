// data.js
// Persistence model: localStorage as primary store; optional File System Access API for writing to data.json when user links a file.

let __dataFileHandle = null; // FileSystemFileHandle when linked by the user (non-serializable)

function loadData() {
  let data = localStorage.getItem("examData");
  if (!data) {
    data = { verifications: [], reports: [] };
    localStorage.setItem("examData", JSON.stringify(data));
    return data;
  }
  return JSON.parse(data);
}

function saveData(data) {
  localStorage.setItem("examData", JSON.stringify(data));
  // Fire-and-forget attempt to persist to linked file, if any
  try { maybePersistToLinkedFile(data); } catch (e) {}
  // Send to server (PHP) to save to data.json if available
  try { persistToServer(data); } catch (e) {}
}

// Add verification
function addVerification(reg_number, status, exam_name) {
  const data = loadData();
  const now = new Date();
  const session = getSession(now);
  data.verifications.push({
    reg_number,
    status,
    exam_name,
    timestamp: now.toISOString(),
    exam_session: session,
  });
  saveData(data);
}

// Add report
function addReport(issue_type, reg_number, exam_name, description) {
  const data = loadData();
  const now = new Date();
  data.reports.push({
    issue_type,
    reg_number,
    exam_name,
    description,
    reported_at: now.toISOString(),
  });
  saveData(data);
}

function getSession(date) {
  const h = date.getHours();
  if (h >= 8 && h < 12) return "morning";
  if (h >= 12 && h < 16) return "noon";
  if (h >= 16 && h < 19) return "evening";
  return "other";
}

// ---- Optional file persistence helpers ----

async function requestAndLinkDataJsonFile() {
  if (!window.showOpenFilePicker) {
    throw new Error("File System Access API not supported in this browser.");
  }
  const [handle] = await window.showOpenFilePicker({
    types: [{
      description: "JSON",
      accept: { "application/json": [".json"] },
    }],
    multiple: false,
  });
  __dataFileHandle = handle;
  return handle;
}

function isDataFileLinked() {
  return !!__dataFileHandle;
}

async function persistNowToLinkedFile() {
  const data = loadData();
  await maybePersistToLinkedFile(data, true);
}

async function maybePersistToLinkedFile(data, requirePermission) {
  if (!__dataFileHandle) return;
  try {
    const perm = await __dataFileHandle.queryPermission({ mode: "readwrite" });
    if (perm !== "granted") {
      if (requirePermission) {
        const req = await __dataFileHandle.requestPermission({ mode: "readwrite" });
        if (req !== "granted") return;
      } else {
        return; // silently skip if not granted
      }
    }
    const writable = await __dataFileHandle.createWritable();
    await writable.write(new Blob([JSON.stringify(data, null, 2)], { type: "application/json" }));
    await writable.close();
  } catch (e) {
    // Swallow errors; UI may notify user if needed
  }
}

function downloadDataJsonSnapshot() {
  const data = loadData();
  const blob = new Blob(["\uFEFF" + JSON.stringify(data, null, 2)], { type: "application/json;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "data.json";
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

// Expose minimal API to window for UI wiring
window.examPersistence = {
  requestAndLinkDataJsonFile,
  isDataFileLinked,
  persistNowToLinkedFile,
  downloadDataJsonSnapshot,
};

// ---- Server persistence (PHP) ----
async function persistToServer(data) {
  // If index.php exists and is serving this page, this will succeed.
  await fetch('index.php?action=save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
}
