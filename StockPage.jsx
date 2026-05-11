import React from "react";

const productos = [
  { id: 1, nombre: "Mouse Gamer", stock: 15 },
  { id: 2, nombre: "Teclado Mecánico", stock: 8 },
  { id: 3, nombre: "Monitor 24", stock: 20 },
];

export default function StockPage() {
  return (
    <div className="container">
      <h1>Inventario</h1>

      <table border="1" cellPadding="10">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Stock Disponible</th>
          </tr>
        </thead>

        <tbody>
          {productos.map((producto) => (
            <tr key={producto.id}>
              <td>{producto.nombre}</td>
              <td>{producto.stock}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}