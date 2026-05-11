import React, { useState } from "react";

export default function ConsultPQR() {
  const [codigo, setCodigo] = useState("");
  const [estado, setEstado] = useState("");

  const consultarPQR = () => {
    // Simulación
    setEstado("En proceso");
  };

  return (
    <div>
      <h1>Consultar Estado PQR</h1>

      <input
        type="text"
        placeholder="Ingrese código PQR"
        value={codigo}
        onChange={(e) => setCodigo(e.target.value)}
      />

      <button onClick={consultarPQR}>
        Consultar
      </button>

      {estado && (
        <h3>Estado: {estado}</h3>
      )}
    </div>
  );
}