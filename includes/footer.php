<?php
// ============================================================
//  includes/footer.php — Pied de page partagé
// ============================================================
$R = rtrim(BASE_URL, '/') . '/';
?>
<footer class="app-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Mboa237 &middot; Toutes les langues méritent d'être transmises</p>
    </div>
</footer>

<?php if (!empty($extraJs)): ?>
    <script src="<?= $R ?>assets/js/<?= e($extraJs) ?>"></script>
<?php endif; ?>
<script src="<?= $R ?>assets/js/app.js"></script>
</body>
</html>
