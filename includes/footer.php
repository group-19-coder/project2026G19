  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Delete confirm dialog
document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id   = this.dataset.id;
    const name = this.dataset.name || 'this item';
    const overlay = document.getElementById('confirmOverlay');
    if (!overlay) return;
    document.getElementById('confirmBody').textContent = 'Are you sure you want to delete "' + name + '"?';
    overlay.classList.add('open');
    document.getElementById('confirmDelete').onclick = function() {
      document.getElementById('deleteForm_' + id)?.submit();
    };
  });
});
document.getElementById('confirmCancel')?.addEventListener('click', () => {
  document.getElementById('confirmOverlay')?.classList.remove('open');
});

// Image preview
const fileInput = document.getElementById('foodImageInput');
if (fileInput) {
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('imgPreview').innerHTML =
        '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">';
    };
    reader.readAsDataURL(file);
  });
}

// Price and budget auto-fill
const priceInput = document.getElementById('price');
const budgetSel  = document.getElementById('budgetCategory');
if (priceInput && budgetSel) {
  priceInput.addEventListener('input', function() {
    const p = parseFloat(this.value) || 0;
    if (p > 0 && p < 15)       budgetSel.value = 'Cheap';
    else if (p >= 15 && p <= 35) budgetSel.value = 'Moderate';
    else if (p > 35)             budgetSel.value = 'Expensive';
  });
}

// Halal / Non-Halal mutex
const halal    = document.getElementById('dietHalal');
const nonHalal = document.getElementById('dietNonHalal');
if (halal && nonHalal) {
  halal.addEventListener('change',    () => { if (halal.checked) nonHalal.checked = false; });
  nonHalal.addEventListener('change', () => { if (nonHalal.checked) halal.checked = false; });
}

// Spicy / Non-Spicy mutex
const spicy    = document.getElementById('dietSpicy');
const nonSpicy = document.getElementById('dietNonSpicy');
if (spicy && nonSpicy) {
  spicy.addEventListener('change',    () => { if (spicy.checked) nonSpicy.checked = false; });
  nonSpicy.addEventListener('change', () => { if (nonSpicy.checked) spicy.checked = false; });
}


document.querySelectorAll('.toast-msg').forEach(t => setTimeout(() => t.style.display='none', 4000));
</script>
</body>
</html>
