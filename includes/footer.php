<footer>
  <div class="footer-category">
    <div class="container">
      <h2 class="footer-category-title">Brand directory</h2>
      <div class="footer-flex">
        <?php
        // Get all categories from database
        $categories = $conn->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName");
        
        if ($categories && $categories->num_rows > 0) {
            // Split categories into two columns
            $half = ceil($categories->num_rows / 2);
            $count = 0;
            
            echo '<div>'; // First column
            
            while ($category = $categories->fetch_assoc()) {
                if ($count == $half) {
                    echo '</div><div>'; // Start second column
                }
                
                echo '<div class="footer-category-box">';
                echo '<h3 class="category-box-title">'.htmlspecialchars($category['CategoryName']).' :</h3>';
                
                // Get brands for this category
                $brands = $conn->query("
                    SELECT b.BrandID, b.BrandName 
                    FROM brand b
                    JOIN product p ON b.BrandID = p.BrandID
                    WHERE p.CategoryID = ".$category['CategoryID']."
                    GROUP BY b.BrandID
                    ORDER BY b.BrandName
                    LIMIT 6
                ");
                
                if ($brands && $brands->num_rows > 0) {
                    while ($brand = $brands->fetch_assoc()) {
                        echo '<a href="search.php?category='.$category['CategoryID'].'&brand='.$brand['BrandID'].'" class="footer-category-link">'.htmlspecialchars($brand['BrandName']).'</a>';
                    }
                } else {
                    echo '<span class="footer-category-link">No brands found</span>';
                }
                
                echo '</div>';
                $count++;
            }
            
            echo '</div>'; // Close last column
        } else {
            echo '<p>No categories found</p>';
        }
        ?>
      </div>         
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <p class="copyright">
        Copyright &copy; <a href="index.php">PHONE MART</a> all rights reserved.
      </p>
    </div>
  </div>
</footer>