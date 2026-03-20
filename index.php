<?php
session_start();
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}
?>
<?php include "includes/header.php"; ?>

    <div id="hotlinesContainer" class="hotlines-container">
        <!-- Hotlines loaded dynamically via JS -->
    </div>

    </div><!-- /.container -->
    </section><!-- /.categories-section -->

<?php include "includes/footer.php"; ?>
