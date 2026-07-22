</div>
</main>

<script src="../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script src="../assets/js/admin.js"></script>
<script>
  // Menu mobile du back-office
  var burger = document.getElementById('admin-burger');
  var sidebar = document.getElementById('admin-sidebar');
  var backdrop = document.getElementById('admin-sidebar-backdrop');

  function openSidebar() {
    sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('show');
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
  }

  if (burger && sidebar) {
    burger.addEventListener('click', function () {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
  }
  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });
  // Ferme automatiquement après avoir cliqué un lien (navigation mobile)
  if (sidebar) {
    sidebar.querySelectorAll('a, button.admin-sidebar-link').forEach(function (link) {
      link.addEventListener('click', closeSidebar);
    });
  }

  // Confirmation SweetAlert avant déconnexion
  document.querySelectorAll('.js-confirm-logout').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      Swal.fire({
        title: 'Se déconnecter ?',
        text: 'Vous devrez saisir de nouveau vos identifiants pour revenir sur le back-office.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#EA580C',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Oui, me déconnecter',
        cancelButtonText: 'Annuler',
      }).then(function (result) {
        if (result.isConfirmed) form.submit();
      });
    });
  });

  // Confirmation SweetAlert pour tous les boutons de suppression
  document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var nom = form.dataset.nom || 'cet élément';
      Swal.fire({
        title: 'Confirmer la suppression',
        html: 'Voulez-vous vraiment supprimer <strong>' + nom + '</strong> ? Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
      }).then(function (result) {
        if (result.isConfirmed) form.submit();
      });
    });
  });
</script>
</body>
</html>