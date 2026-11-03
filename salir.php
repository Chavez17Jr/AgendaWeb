<?php
require 'funciones.php';
cerrarSesion();
header('Location: acceso.php', true, 303);
exit;
