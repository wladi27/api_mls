<?php  
// db.php  
$host = 'localhost'; // Cambia según tu configuración  
$db_name = 'test_db'; // Cambia según tu configuración  
$username = 'root'; // Cambia según tu configuración  
$password = ''; // Cambia según tu configuración  

try {  
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);  
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
} catch (PDOException $e) {  
    echo "Error de conexión: " . $e->getMessage();  
}  
?>