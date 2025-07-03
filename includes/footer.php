<?php
// includes/footer.php
// This file is the closing part of the HTML structure started in header.php.
// It assumes it's included after the main content of the page.
?>
                            </div></div></div></div></div></div><script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            // Select all alert messages (success, danger, info, warning)
            $(".alert").each(function() {
                var $alert = $(this);
                // Set a timeout to fade out the alert after 5 seconds (5000 milliseconds)
                setTimeout(function() {
                    $alert.fadeOut("slow", function() {
                        $(this).remove(); // Remove the element from the DOM after fading
                    });
                }, 5000); // Adjust time as needed
            });
        });
    </script>
</body>
</html>