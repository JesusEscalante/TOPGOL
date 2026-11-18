<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - Layout Administrador (cierre)
 * ====================================================================
 */
?>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAdmSide(force) {
    var side = document.getElementById('admSide');
    var overlay = document.getElementById('admOverlay');
    var open = typeof force === 'boolean' ? force : !side.classList.contains('open');
    side.classList.toggle('open', open);
    overlay.classList.toggle('show', open);
}
</script>
</body>
</html>
