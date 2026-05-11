// App.jsx
import { useState } from "react";

export default function App() {
  const [cart] = useState([
    { id: 1, name: "Laptop", price: 2500, quantity: 1 },
    { id: 2, name: "Mouse", price: 80, quantity: 2 },
  ]);

  const [favorites] = useState([
    { id: 1, name: "Teclado Mecánico" },
    { id: 2, name: "Monitor Gamer" },
  ]);

  const [orders] = useState([
    { id: 1, product: "Auriculares", status: "En camino" },
    { id: 2, product: "Tablet", status: "Procesando" },
  ]);

  const [purchases] = useState([
    { id: 1, product: "Laptop", date: "2026-05-10" },
    { id: 2, product: "Mouse", date: "2026-05-09" },
  ]);

  const total = cart.reduce(
    (acc, item) => acc + item.price * item.quantity,
    0
  );

  return (
    <div className="container">
      <h1>Sistema de Compras</h1>

      {/* Productos Comprados */}
      <section className="card">
        <h2>Productos Comprados</h2>
        {purchases.map((purchase) => (
          <div key={purchase.id} className="item">
            <p><strong>Producto:</strong> {purchase.product}</p>
            <p><strong>Fecha:</strong> {purchase.date}</p>
          </div>
        ))}
      </section>

      {/* Favoritos */}
      <section className="card">
        <h2>Productos Favoritos</h2>
        {favorites.map((fav) => (
          <div key={fav.id} className="item">
            <p>{fav.name}</p>
          </div>
        ))}
      </section>

      {/* Pedidos */}
      <section className="card">
        <h2>Productos en Pedido</h2>
        {orders.map((order) => (
          <div key={order.id} className="item">
            <p><strong>Producto:</strong> {order.product}</p>
            <p><strong>Estado:</strong> {order.status}</p>
          </div>
        ))}
      </section>

      {/* Carrito */}
      <section className="card">
        <h2>Resumen de Compra</h2>
        {cart.map((item) => (
          <div key={item.id} className="item">
            <p>{item.name}</p>
            <p>
              {item.quantity} x ${item.price}
            </p>
          </div>
        ))}

        <h3>Total a pagar: ${total}</h3>
        <button>Finalizar Compra</button>
      </section>
    </div>
  );
}