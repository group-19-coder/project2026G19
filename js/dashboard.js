
$(function () {

  // ── Avatar Dropdown ────────────────────────
  $('#avatarBtn').on('click', function (e) {
    e.stopPropagation();
    $('#avatarDropdown').toggleClass('open');
  });
  $(document).on('click', function () {
    $('#avatarDropdown').removeClass('open');
  });
  $('#avatarDropdown').on('click', function (e) { e.stopPropagation(); });

  // ── Sidebar Toggle (mobile) ────────────────
  $('#sidebarToggle').on('click', function () {
    $('#sidebar').toggleClass('open');
    $('#pageOverlay').toggleClass('show');
  });
  $('#pageOverlay').on('click', function () {
    $('#sidebar').removeClass('open');
    $('#pageOverlay').removeClass('show');
  });

  // ── DataTables ─────────────────────────────
  if ($('#menuTable').length) {
    $('#menuTable').DataTable({
      pageLength: 15,
      responsive: true,
      order: [[0, 'desc']],
      language: {
        search: '',
        searchPlaceholder: 'Search menu items…',
        lengthMenu: 'Show _MENU_ items',
        info: 'Showing _START_–_END_ of _TOTAL_ items',
        emptyTable: 'No menu items found.',
      },
      columnDefs: [
        { orderable: false, targets: [-1] } // disable sort on Actions column
      ]
    });
  }

  // ── Image Preview ──────────────────────────
  $('#foodImageInput').on('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
      $('#imgPreview').html(`<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">`);
    };
    reader.readAsDataURL(file);
  });
  $('#imgPreviewWrap').on('click', function () {
    $('#foodImageInput').trigger('click');
  });

  // ── Confirm Delete ─────────────────────────
  let deleteTarget = null;
  $(document).on('click', '.delete-btn', function () {
    deleteTarget = $(this).data('id');
    const name   = $(this).data('name') || 'this item';
    $('#confirmBody').text(`Are you sure you want to permanently delete "${name}"? This cannot be undone.`);
    $('#confirmOverlay').addClass('open');
  });
  $('#confirmCancel').on('click', function () {
    $('#confirmOverlay').removeClass('open');
  });
  $('#confirmOverlay').on('click', function (e) {
    if (e.target === this) $(this).removeClass('open');
  });
  $('#confirmDelete').on('click', function () {
    if (deleteTarget) {
      $('#deleteForm_' + deleteTarget).submit();
    }
  });

  // ── Toast auto-dismiss ─────────────────────
  setTimeout(() => { $('.toast-msg').fadeOut(400, function () { $(this).remove(); }); }, 3500);

  // ── Budget category auto-fill based on price
  $('#price').on('input', function () {
    const p = parseFloat($(this).val());
    if (!isNaN(p)) {
      let cat = 'Moderate';
      if (p < 15)  cat = 'Cheap';
      if (p >= 35) cat = 'Expensive';
      if ($('#budgetCategory').val() === '') {
        $('#budgetCategory').val(cat);
      }
    }
  });

  // ── Halal / Non-Halal mutual exclusion ─────
  $('#dietHalal').on('change', function () {
    if ($(this).is(':checked')) $('#dietNonHalal').prop('checked', false);
  });
  $('#dietNonHalal').on('change', function () {
    if ($(this).is(':checked')) $('#dietHalal').prop('checked', false);
  });
  $('#dietSpicy').on('change', function () {
    if ($(this).is(':checked')) $('#dietNonSpicy').prop('checked', false);
  });
  $('#dietNonSpicy').on('change', function () {
    if ($(this).is(':checked')) $('#dietSpicy').prop('checked', false);
  });
});
