<?php
// model/Usuario.php
require_once __DIR__ . '/../config/conexion.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conn;
    }

    // Método para verificar el login
    public function login($username, $password) {
        $query = "SELECT * FROM usuarios WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica si el usuario existe y la contraseña es válida
        if ($user && password_verify($password, $user['password'])) {
            return $user; // Usuario autenticado
        }
        return false; // Usuario o contraseña incorrectos
    }

    // Método para registrar un usuario con contraseña encriptada y control de duplicados
    public function registrar($nombre, $apellido, $documento_id, $fecha_nacimiento, $correo, $username, $password, $rol = 'cliente') {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT); // Encripta la contraseña
            $query = "INSERT INTO usuarios (nombre, apellido, documento_id, fecha_nacimiento, correo, username, password, rol) VALUES (:nombre, :apellido, :documento_id, :fecha_nacimiento, :correo, :username, :password, :rol)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":apellido", $apellido);
            $stmt->bindParam(":documento_id", $documento_id);
            $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);   
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hash);
            $stmt->bindParam(":rol", $rol);
            return $stmt->execute(); // Inserta en la base de datos
        } catch (PDOException $e) {
            // Si el código de error es 23000 (violación de clave única por usuario duplicado)
            if ($e->getCode() == 23000) {
                return false; 
            }
            throw $e; // Lanza la excepción si es otro error distinto
        }
    }
}
?>