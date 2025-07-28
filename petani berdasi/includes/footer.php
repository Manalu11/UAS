<?php
// footer.php - Common footer for all pages
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($jsFile)): ?>
<script src="<?= $jsFile ?>"></script>
<?php endif; ?>
</body>

</html>