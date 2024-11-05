<?php  
// Usuario.php  
class Usuario {  
    public $id;  
    public $nombre;  
    public $nivel;  

    public function __construct($id, $nombre, $nivel) {  
        $this->id = $id;  
        $this->nombre = $nombre;  
        $this->nivel = $nivel;  
    }  
}  
?>