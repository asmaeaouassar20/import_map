// ============================================================
//  ImportFlow — main.js
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  initFileDropZone();
  initAlertDismiss();
  animateEntrance();
});

// ---- Drag & Drop file zone ----
function initFileDropZone() {
  const zone  = document.getElementById('dropZone');
  const input = document.getElementById('fileInput');
  const label = document.getElementById('fileName');
  if (!zone) return;

  // Click passthrough already works via input overlay
  input.addEventListener('change', () => {
    if (input.files.length) showFile(input.files[0].name);
  });

  zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('dragging');
  });

  zone.addEventListener('dragleave', () => zone.classList.remove('dragging'));

  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragging');
    const files = e.dataTransfer.files;
    if (files.length) {
      // Transfer to input
      const dt = new DataTransfer();
      dt.items.add(files[0]);
      input.files = dt.files;
      showFile(files[0].name);
    }
  });

  function showFile(name) {
    if (!label) return;
    label.textContent = '📄 ' + name;
  }
}

// ---- Auto-dismiss alerts ----
function initAlertDismiss() {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(a => {
    setTimeout(() => {
      a.style.transition = 'opacity .5s, transform .5s';
      a.style.opacity = '0';
      a.style.transform = 'translateY(-8px)';
      setTimeout(() => a.remove(), 500);
    }, 5000);
  });
}

// ---- Staggered entrance animation ----
function animateEntrance() {
  const cards = document.querySelectorAll('.step-card, .hero-section, .info-bar, .result-stats, .import-confirm');
  cards.forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(16px)';
    card.style.transition = `opacity .4s ${i * 80}ms ease, transform .4s ${i * 80}ms ease`;
    requestAnimationFrame(() => {
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 30);
    });
  });
}
