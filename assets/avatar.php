<?

include '../lib/auth.php';

header ("Content-type: image/jpeg");

$image_id = "img_" . $_GET['i'];
$image_query = mysqli_query("SELECT * FROM `store` WHERE `store_key` LIKE '$image_id'");
$image_data = mysql_fetch_assoc($image_query);

echo $image_data['store_data'];

?>