import React, { useState } from "react";

export default function RegisterPQR() {
  const [formulario, setFormulario] = useState({
    nombre: "",
    tipo: "",
    descripcion: "",
  });

  const handleChange = (e) => {
    setFormulario({
      ...formulario,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    alert("PQR registrada correctamente");

    console.log(formulario);
  };

  return (
    <div>
      <h1>Registrar PQR</h1>

      <form onSubmit={handleSubmit}>
        <input
          type="text"
          name="nombre"
          placeholder="Nombre"
          onChange={handleChange}
        />

        <select name="tipo" onChange={handleChange}>
          <option>Seleccione</option>
          <option>Petición</option>
          <option>Queja</option>
          <option>Reclamo</option>
        </select>

        <textarea
          name="descripcion"
          placeholder="Describe tu solicitud"
          onChange={handleChange}
        />

        <button type="submit">Enviar PQR</button>
      </form>
    </div>
  );
}