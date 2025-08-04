<?php 
// components/footer.php
$date_now = getdate();
$year = $date_now['year'];
?>

    </main> <!-- Close the main tag opened in header.php -->
    
    <footer class="footer">
        <p>EAD | Esesp <?= $year ?></p>
    </footer>
    
</body>
</html>