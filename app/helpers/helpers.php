<?php 


function validateInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return "IMG_" . time() . "_" . uniqid() . "." . $extension;
}


?>
