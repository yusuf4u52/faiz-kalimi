<?php
header("HTTP/1.0 404 Not Found");
// You can optionally include a custom 404 page content here
echo "<h1>404 Not Found</h1>";
echo "<p>The page you requested could not be found.</p>";
exit(); // It's good practice to exit after sending a 404 to prevent further script execution.
?>