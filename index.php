<?php  
// index.php  
require 'db.php';  
require 'Usuario.php';  

// Función para agregar un usuario  
function agregar_usuario($nombre) {  
    global $pdo;  
    
    // Obtener todos los usuarios  
    $stmt = $pdo->query("SELECT * FROM usuarios");  
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);  

    if (empty($usuarios)) {  
        // Crear usuario raíz  
        $nivel = 0;  
    } else {  
        $niveles = [];  
        foreach ($usuarios as $usuario) {  
            if (!isset($niveles[$usuario['nivel']])) {  
                $niveles[$usuario['nivel']] = 0;  
            }  
            $niveles[$usuario['nivel']]++;  
        }  

        // Determinar el siguiente nivel  
        $nivel = 0;  
        while (true) {  
            $max_usuarios_en_nivel = pow(3, $nivel); // 3^n usuarios en el nivel n  
            if (($niveles[$nivel] ?? 0) < $max_usuarios_en_nivel) {  
                break;  
            }  
            $nivel++;  
        }  
    }  

    // Insertar nuevo usuario  
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, nivel) VALUES (?, ?)");  
    $stmt->execute([$nombre, $nivel]);  
    $id = $pdo->lastInsertId();  

    return new Usuario($id, $nombre, $nivel);  
}  

// Recuperar usuarios  
function obtener_usuarios() {  
    global $pdo;  
    $stmt = $pdo->query("SELECT * FROM usuarios");  
    $usuarios = [];  
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {  
        $usuarios[] = new Usuario($row['id'], $row['nombre'], $row['nivel']);  
    }  
    return $usuarios;  
}  

// Eliminar usuario  
function eliminar_usuario($usuario_id) {  
    global $pdo;  
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");  
    return $stmt->execute([$usuario_id]);  
}  

// Manejo de peticiones  
header('Content-Type: application/json');  

$request_method = $_SERVER['REQUEST_METHOD'];  
switch ($request_method) {  
    case 'POST':  
        $data = json_decode(file_get_contents('php://input'), true);  
        if (isset($data['nombre'])) {  
            $usuario = agregar_usuario($data['nombre']);  
            echo json_encode($usuario);  
        } else {  
            echo json_encode(['error' => 'Nombre es requerido']);  
        }  
        break;  
    case 'GET':  
        if (isset($_GET['id'])) {  
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");  
            $stmt->execute([$_GET['id']]);  
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);  
            if ($usuario) {  
                echo json_encode(new Usuario($usuario['id'], $usuario['nombre'], $usuario['nivel']));  
            } else {  
                echo json_encode(['error' => 'Usuario no encontrado']);  
            }  
        } else {  
            $usuarios = obtener_usuarios();  
            echo json_encode($usuarios);  
        }  
        break;  
    case 'DELETE':  
        if (isset($_GET['id'])) {  
            if (eliminar_usuario($_GET['id'])) {  
                echo json_encode(['detail' => 'Usuario eliminado']);  
            } else {  
                echo json_encode(['error' => 'Usuario no encontrado']);  
            }  
        } else {  
            echo json_encode(['error' => 'ID de usuario requerido']);  
        }  
        break;  
    default:  
        echo json_encode(['error' => 'Método no soportado']);  
        break;  
}  
?>