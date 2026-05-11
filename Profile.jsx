import React from "react";

export default function Profile() {
  const usuario = {
    nombre: "Johan Giraldo",
    correo: "johan@gmail.com",
    telefono: "3001234567",
  };

  return (
    <div>
      <h1>Mi Perfil</h1>

      <div>
        <p><strong>Nombre:</strong> {usuario.nombre}</p>
        <p><strong>Correo:</strong> {usuario.correo}</p>
        <p><strong>Teléfono:</strong> {usuario.telefono}</p>
      </div>
    </div>
  );
}